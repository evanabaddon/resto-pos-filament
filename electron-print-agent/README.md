# Resto POS Print Agent (Windows)

Aplikasi kecil untuk menghubungkan Web Hosting POS dengan Printer Thermal (USB/Network) di Windows.

## Fitur
*   Menerima pekerjaan print dari Webhook.
*   Support Multiple Printer (Kasir, Dapur, Bar).
*   **Template Struk**: Support Logo & Layout Rapi.
*   **Template Order**: Huruf Besar untuk Dapur/Bar.
*   Auto-start di Background.

## Cara Install (Untuk Developer/User)

### Prerequisite
Pastikan komputer Windows sudah terinstall **Node.js** (Versi 16 keatas).
Download di: https://nodejs.org/

### Langkah 1: Setup
1.  Copy folder ini (`electron-print-agent`) ke komputer Windows (misal ke `C:\PosPrintAgent`).
2.  Buka Command Prompt (CMD) atau PowerShell di folder tersebut.
3.  Jalankan perintah:
    ```bash
    npm install
    ```

### Langkah 2: Jalankan Aplikasi
1.  Jalankan perintah:
    ```bash
    npm start
    ```
2.  Aplikasi akan muncul (UI Configuration).

### Langkah 3: Konfigurasi
1.  **Webhook URL**: Masukkan URL website Anda + `/api/webhook`
    *   Contoh: `https://pos.suralaya.id/api/webhook`
2.  **Secret Key**: Masukkan key yang sama dengan di `.env` file website (`APP_PRINT_SECRET`).
3.  **Printer Mapping**:
    *   Masukkan **Nama Printer** persis seperti di "Control Panel > Devices and Printers".
    *   Contoh: `POS-58`, `EPSON TM-T82`, `OneNote for Windows 10` (untuk test).
4.  **Logo Path**: (Optional) Path ke file gambar logo di komputer lokal.
    *   Contoh: `C:\PosPrintAgent\assets\logo.png`
5.  Klik **Save & Restart Service**.

### Langkah 4: Build .EXE (Optional - Agar tinggal klik)
Jika ingin membuat file `.exe` agar mudah didistribusikan:
```bash
npm run dist
```
File `.exe` akan muncul di folder `dist`.

## Troubleshooting
*   **Printer Error**: Pastikan driver printer sudah terinstall dan bisa print Test Page dari Windows.
*   **Logs**: Cek kotak log di aplikasi untuk melihat status error atau sukses.
