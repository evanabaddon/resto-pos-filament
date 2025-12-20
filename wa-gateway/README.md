# Resto POS WhatsApp Gateway

This is a self-hosted WhatsApp Gateway using [Baileys](https://github.com/WhiskeySockets/Baileys).

## 🚀 How to Run

1.  **Install Dependencies** (First time only):
    ```bash
    npm install
    ```

2.  **Start the Service**:
    ```bash
    npm start
    ```
    or for production (background):
    ```bash
    npm install -g pm2
    pm2 start index.js --name "wa-gateway"
    ```

3.  **Connect WhatsApp**:
    - Open your Laravel Admin Panel.
    - Go to **Whatsapp Center** (under Communication).
    - Scan the QR Code that appears.


## 🌟 New Features
-   **Avatar Proxy**: Proxies WhatsApp profile pictures via `/avatar/:jid` (auto-fallback to low-res/preview if high-res fails).
-   **Status Sync**: Listens for 'read' and 'delivered' updates and forwards them to Laravel.
-   **Media Handling**: Enhanced support for Images, Videos, Audio (Voice Notes), and Documents.
-   **Robust Auth**: Auto-logout cleanup and seamless re-pairing flow.

## 🔗 Architecture

## 🔗 Architecture

- **Port**: Default `3000` (Configurable via `PORT` in `.env`).
- **Webhook**: Sends updates to `http://127.0.0.1:8000/api/webhook/wa` (Configurable via `WEBHOOK_URL` in `.env`).

## 📂 Project Structure

- `index.js`: Main entry point (Baileys + Express).
- `auth_info_baileys/`: Folder where WhatsApp session credentials are stored (Auto-created).

## ⚠️ Troubleshooting

- **QR Code not showing?** Check if `npm start` is running without errors.
- **Messages not appearing in Chat?** Check Laravel logs (`storage/logs/laravel.log`) for Webhook errors.
- **Avatar 404 Error?** This is **NORMAL**. It means the contact has no profile picture or their privacy settings block it. The frontend will automatically show initials instead.

