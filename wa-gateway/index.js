import { makeWASocket, DisconnectReason, useMultiFileAuthState, Browsers, fetchLatestBaileysVersion, downloadMediaMessage } from '@whiskeysockets/baileys'
import express from 'express'
import cors from 'cors'
import pino from 'pino'
import QRCode from 'qrcode'
import dotenv from 'dotenv'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

dotenv.config()

const app = express()
app.use(cors())
app.use(express.json({ limit: '50mb' }))
app.use(express.urlencoded({ limit: '50mb', extended: true }))

const PORT = process.env.PORT || 3000
const WEBHOOK_URL = process.env.WEBHOOK_URL || 'http://127.0.0.1:8000/api/webhook/wa'

let sock;
let status = 'disconnected';
let qrCodeData = null;

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState('auth_info_baileys')
    const { version, isLatest } = await fetchLatestBaileysVersion()
    console.log(`using WA v${version.join('.')}, isLatest: ${isLatest}`)

    sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        auth: state,
        browser: Browsers.ubuntu('Chrome'),
        syncFullHistory: false,
        connectTimeoutMs: 60000,
        keepAliveIntervalMs: 10000,
        retryRequestDelayMs: 250
    })

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update

        if (qr) {
            status = 'scanning';
            qrCodeData = await QRCode.toDataURL(qr);
            console.log('QR Generated');
        }

        if (connection === 'close') {
            const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut
            console.log('connection closed due to ', lastDisconnect.error, ', reconnecting ', shouldReconnect)
            status = 'disconnected';
            qrCodeData = null;
            if (shouldReconnect) {
                connectToWhatsApp()
            } else {
                console.log('Connection closed due to LOGOUT (401). Clearing credentials and restarting...');
                const authPath = path.join(__dirname, 'auth_info_baileys');
                if (fs.existsSync(authPath)) {
                    fs.rmSync(authPath, { recursive: true, force: true });
                }
                connectToWhatsApp();
            }
        } else if (connection === 'open') {
            console.log('opened connection')
            status = 'connected';
            qrCodeData = null;
        }
    })

    sock.ev.on('creds.update', saveCreds)
    // Handle incoming messages
    // Listen for Message Updates (Read Receipts, Delivery Status)
    sock.ev.on('messages.update', async (updates) => {
        for (const update of updates) {
            console.log('Message Update:', JSON.stringify(update));

            // update.update.status: 3 (sent/delivered), 4 (read), 5 (played)
            if (update.update.status) {
                try {
                    const payload = {
                        status_update: true,
                        remote_jid: update.key.remoteJid,
                        message_id: update.key.id,
                        status: update.update.status,
                        from_me: update.key.fromMe
                    };

                    if (WEBHOOK_URL) {
                        await fetch(WEBHOOK_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                    }
                } catch (err) {
                    console.error('Failed to send status update webhook:', err);
                }
            }
        }
    });

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        console.log(`Received messages.upsert type: ${type}`);

        // Only process real-time notifications, ignore history syncs (append)
        if (type !== 'notify') return;

        try {
            for (const msg of messages) {
                if (!msg.key.remoteJid) continue;

                // Process incoming message
                console.log('Incoming Message:', msg);

                // Send to Laravel Webhook
                try {
                    let remoteJid = msg.key.remoteJid;

                    // Fix for LID (Linked Device) JIDs -> Use the Phone Number JID
                    if (remoteJid.includes('@lid')) {
                        console.log('Detected LID JID:', remoteJid, 'FromMe:', msg.key.fromMe);

                        // 1. Try senderPn (Best source for Phone Number)
                        if (msg.key.senderPn) {
                            remoteJid = msg.key.senderPn;
                            console.log('-> Resolved LID to senderPn:', remoteJid);
                        }
                        // 2. Try participant (Group context or explicit participant)
                        else if (msg.key.participant && !msg.key.participant.includes('@lid')) {
                            remoteJid = msg.key.participant;
                            console.log('-> Resolved LID to participant:', remoteJid);
                        }
                        // 3. FromMe case: If I sent it from a linked device, we really want the recipient.
                        // But msg.key.remoteJid is usually the recipient in fromMe cases (unless it's a self-chat).
                        // If remoteJid IS the recipient and it is a LID, we try to fix it.
                        else if (msg.key.fromMe) {
                            // If I sent TO a LID, we can't easily guess the phone number unless we have it mapped.
                            // But usually, remoteJid for outgoing is the target.
                        }
                    }

                    // Format JID to ensure it is @s.whatsapp.net if it is a phone number
                    if (!remoteJid.includes('@') && !remoteJid.includes('-')) {
                        remoteJid = remoteJid + '@s.whatsapp.net';
                    }

                    // Ensure we prefer s.whatsapp.net for 1-on-1 chats
                    if (remoteJid.includes('@lid') && !remoteJid.includes('g.us')) {
                        // Try to extract number if possible? 
                        // Unfortunately without mapping, we might be stuck. 
                        // But usually senderPn is present in the log shown by user.
                    }

                    const isFromMe = msg.key.fromMe;

                    if (!msg.message) {
                        console.log('Skipping message with no content (e.g. Protocol/Stub)');
                        continue;
                    }

                    // Reliable way to find the message type
                    const supportedTypes = ['imageMessage', 'videoMessage', 'audioMessage', 'documentMessage', 'stickerMessage'];
                    const messageKeys = Object.keys(msg.message);
                    const messageType = messageKeys.find(key => supportedTypes.includes(key));

                    // Simple Text extraction
                    const text = msg.message.conversation || msg.message.extendedTextMessage?.text || msg.message.imageMessage?.caption || msg.message[messageType]?.caption || '';

                    // Skip only if no text AND no supported media
                    if (!text && !messageType) {
                        console.log('Skipping message with no text and no supported media');
                        continue;
                    }

                    console.log('Message Type:', messageType || 'text');

                    // Ignore Status Broadcasts
                    if (remoteJid === 'status@broadcast') return;

                    // Ignore Protocol Messages (History Sync, etc) unless important
                    if (msg.message?.protocolMessage) return;

                    // Conversation Name (Group Subject or Contact Name)
                    let conversationName = msg.pushName;
                    if (remoteJid.endsWith('@g.us')) {
                        try {
                            const groupMetadata = await sock.groupMetadata(remoteJid);
                            conversationName = groupMetadata.subject;
                        } catch (err) {
                            console.error('Failed to fetch group subject:', err);
                            // Fallback to pushName if group metadata fetch fails
                        }
                    }

                    console.log('Processed Metadata. checking Media...');

                    // Media Handling
                    let attachmentData = null;
                    let attachmentType = null;
                    let mimeType = null;
                    let caption = null;

                    if (messageType) {
                        try {
                            console.log(`Attempting to download media: ${messageType}`);
                            const buffer = await downloadMediaMessage(
                                msg,
                                'buffer',
                                {},
                                { logger: pino({ level: 'silent' }), reuploadRequest: sock.updateMediaMessage }
                            );
                            attachmentData = buffer.toString('base64');

                            if (messageType === 'imageMessage') attachmentType = 'image';
                            else if (messageType === 'videoMessage') attachmentType = 'video';
                            else if (messageType === 'audioMessage') attachmentType = 'audio';
                            else if (messageType === 'documentMessage') attachmentType = 'document';
                            else if (messageType === 'stickerMessage') attachmentType = 'image'; // Treat sticker as image for now

                            mimeType = msg.message[messageType]?.mimetype || '';
                            caption = msg.message[messageType]?.caption || '';
                            console.log(`Media downloaded: ${attachmentType} (${attachmentData.length} bytes)`);
                        } catch (err) {
                            console.error('Failed to download media:', err);
                        }
                    } else {
                        console.log('No supported media type found.');
                    }

                    // Use fetch to post to Laravel
                    try {
                        console.log('Posting to webhook:', WEBHOOK_URL);
                        const response = await fetch(WEBHOOK_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                wa_id: msg.key.id, // Unique WA message ID
                                remote_jid: remoteJid,
                                from_me: isFromMe,
                                push_name: msg.pushName, // Sender Name
                                conversation_name: conversationName, // Group Name or Sender Name
                                message: text || caption, // Use caption if text is empty
                                attachment_data: attachmentData,
                                attachment_type: attachmentType,
                                attachment_mimetype: mimeType,
                                caption: caption,
                                timestamp: msg.messageTimestamp,
                                full_message: msg,
                                is_ptt: msg.message[messageType]?.ptt || false
                            })
                        });
                        console.log('Webhook response:', response.status, response.statusText);

                        const respText = await response.text();
                        console.log(`Webhook sent to Laravel: ${response.status} ${response.statusText} - ${respText}`);
                    } catch (err) {
                        console.error('Webhook Fetch Error:', err);
                    }

                } catch (e) {
                    console.error('Webhook failed:', e.message);
                }
            }
        } catch (e) {
            console.error('Message processing error:', e);
        }
    })
}

// REST API
// Helper to normalize JID (Fix 08 -> 628)
function normalizeJid(jid) {
    if (!jid) return null;

    // Don't mess with Group JIDs too much
    if (jid.includes('@g.us') || jid.includes('@lid')) return jid;

    let [user, domain] = jid.split('@');

    // Remove non-numeric from user part
    user = user.replace(/[^0-9]/g, '');

    // Indonesia 08 -> 628
    if (user.startsWith('08')) {
        user = '62' + user.substring(1);
    }

    // Keep original domain or default to s.whatsapp.net
    if (!domain) {
        domain = 's.whatsapp.net';
    }

    return `${user}@${domain}`;
}

app.get('/avatar/:jid', async (req, res) => {
    try {
        if (!sock) return res.status(503).send('Not connected');

        let jid = normalizeJid(req.params.jid);

        console.log(`[Avatar] Requesting for cleaned: ${jid} (Original: ${req.params.jid})`);

        // Try High Res first
        let ppUrl = await sock.profilePictureUrl(jid, 'image').catch(() => null);

        // Fallback to Low Res (Preview) if High Res fails
        if (!ppUrl) {
            ppUrl = await sock.profilePictureUrl(jid, 'preview').catch(() => null);
        }

        if (ppUrl) {
            return res.redirect(ppUrl);
        }
        return res.status(404).send('No profile picture');
    } catch (e) {
        console.error(`[Avatar] Error for ${req.params.jid}:`, e.message);
        res.status(404).send('No profile picture');
    }
});

app.get('/status', async (req, res) => {
    let user = null;
    if (status === 'connected' && sock?.user) {
        user = {
            id: sock.user.id,
            name: sock.user.name || sock.user.notify || 'Me',
            avatar: `http://localhost:${PORT}/avatar/${sock.user.id.split(':')[0]}`
        };
    }

    res.json({
        status,
        qr: qrCodeData,
        user: user
    });
})

app.post('/chat/send', async (req, res) => {
    const { number, message, media_data, media_type, caption, quoted, mentions } = req.body;

    if (!status === 'connected' || !sock) {
        return res.status(400).json({ error: 'WhatsApp not connected' });
    }

    try {
        // Format number: ensure it ends with @s.whatsapp.net AND fix 08 -> 628
        let jid = normalizeJid(number);

        let sentMsg;
        const options = {};
        if (quoted) {
            options.quoted = quoted;
        }
        if (mentions) {
            options.mentions = mentions;
        }

        if (media_data && media_type) {
            // Convert base64 to buffer
            const buffer = Buffer.from(media_data, 'base64');

            let messagePayload = {};
            if (media_type === 'image') {
                messagePayload = { image: buffer, caption: caption || message };
            } else if (media_type === 'video') {
                messagePayload = { video: buffer, caption: caption || message };
            } else if (media_type === 'audio') {
                messagePayload = { audio: buffer, mimetype: 'audio/mp4', ptt: true }; // Send as voice note if audio
            } else if (media_type === 'document') {
                messagePayload = { document: buffer, mimetype: 'application/pdf', fileName: caption || 'file.pdf' };
            }

            sentMsg = await sock.sendMessage(jid, messagePayload, options);
        } else {
            // Simple text message
            sentMsg = await sock.sendMessage(jid, { text: message }, options);
        }

        res.json({ success: true, data: sentMsg });
    } catch (e) {
        console.error(e);
        res.status(500).json({ error: e.message });
    }
})

app.post('/chat/download-media', async (req, res) => {
    const { message } = req.body;

    if (!status === 'connected' || !sock) {
        return res.status(400).json({ error: 'WhatsApp not connected' });
    }

    try {
        console.log(`Manual download requested for message ID: ${message.key.id}`);
        const buffer = await downloadMediaMessage(
            message,
            'buffer',
            {},
            { logger: pino({ level: 'silent' }), reuploadRequest: sock.updateMediaMessage }
        );

        const supportedTypes = ['imageMessage', 'videoMessage', 'audioMessage', 'documentMessage', 'stickerMessage'];
        const messageKeys = Object.keys(message.message);
        const messageType = messageKeys.find(key => supportedTypes.includes(key));
        const mimeType = message.message[messageType]?.mimetype || '';

        res.json({
            success: true,
            media_data: buffer.toString('base64'),
            mimetype: mimeType
        });
    } catch (e) {
        console.error('Manual download failed:', e);
        res.status(500).json({ error: e.message });
    }
})

app.post('/logout', async (req, res) => {
    try {
        if (sock) {
            try {
                await sock.logout();
            } catch (err) {
                console.warn('Socket logout failed, forcing local cleanup:', err.message);
            }
        }

        // Delete Auth Folder to force new QR
        const authPath = path.join(__dirname, 'auth_info_baileys');

        if (fs.existsSync(authPath)) {
            fs.rmSync(authPath, { recursive: true, force: true });
        }

        // Reset state
        status = 'disconnected';
        qrCodeData = null;
        sock = null;

        res.json({ success: true });

        // Re-initialize to generate new QR
        setTimeout(() => connectToWhatsApp(), 1000);

    } catch (e) {
        console.error('Logout error:', e);
        res.status(500).json({ error: e.message });
    }
})

app.listen(PORT, () => {
    console.log(`\n🚀 WA Gateway running on port ${PORT}`)
    console.log(`🔗 Webhook URL: ${WEBHOOK_URL}\n`)
    connectToWhatsApp()
})
