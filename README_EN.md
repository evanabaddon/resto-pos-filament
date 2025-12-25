<div align="center">

# 🍽️ Intelligent Restaurant Ecosystem 🚀
### Next-Gen Restaurant OS — Powered by Adaptive AI & Native WhatsApp Gateway

**Intelligent Restaurant Ecosystem** is an **Enterprise-Grade F&B Ecosystem** designed to redefine modern restaurant operational standards. Integrating **Laravel 11**, **Filament 4**, and **Hybrid AI Intelligence** technologies, this system delivers a perfect blend of **High-Speed Point of Sale**, **Native WhatsApp Gateway**, and **Autonomous Business Intelligence**.

This is not just a transaction recording tool; it's a digital command center that empowers your outlet with **AI-Driven CRM**, **Real-time Kitchen Orchestration (KDS)**, and **Automated Fiscal & P&L Analysis**. The system ensures every operational second is optimized, every customer feels personally valued through **Hyper-Personalized WhatsApp**, and every business decision is backed by accurate artificial intelligence.

</div>

---

## 📘 System Fundamentals

### Accounting System: Accrual Method
This system uses **Accrual Accounting** for COGS (Cost of Goods Sold) calculation, not Cash Basis.

**Core Principles:**
- **Raw Material Purchase ≠ Expense** - Purchases are recorded as **Assets (Stock Value)**, not direct costs.
- **COGS recorded at sale** - Material costs are only deducted from profit when menu items are sold.
- **Matching Principle** - Costs are matched with the revenue they generate.

**Example:**
```
Day 1: Buy Rice 10kg @ Rp 15,000/kg = Rp 150,000
├─ Cash: -Rp 150,000
├─ Stock Value (Asset): +Rp 150,000
├─ COGS: Rp 0 (no sales yet)
└─ Profit: Rp 0 (unchanged)

Day 2: Sell Fried Rice 5 portions @ Rp 25,000
├─ Revenue: +Rp 125,000
├─ COGS: -Rp 40,000 (materials used)
├─ Stock Value: -Rp 40,000 (rice reduced by 1kg)
└─ Gross Profit: Rp 85,000 (Revenue - COGS)
```

### Inventory System: Real-time Stock Tracking
Every transaction automatically creates `StockMovement` and updates stock in real-time:
- **Purchase** → Stock increases, Stock Value rises
- **POS Sale** → Stock decreases (via recipe), COGS recorded
- **Stock Opname** → Corrects physical vs system variance
- **Wastage** → Stock decreases, enters Expense

### Recipe System: Automatic Ingredient Deduction
Menu items with recipes automatically deduct raw material stock when sold:
- **Unit Conversion** - System automatically converts units (e.g., recipe uses grams, stock in kg)
- **Multi-Channel** - Applies to POS, Waiter App, and Self-Order
- **HPP Calculation** - COGS calculated from total raw material costs in recipe

> **⚠️ Important:** Ensure every `produced` menu item has a complete recipe for accurate COGS and stock deduction.

### Recipe Stock Validation: Prevent Negative Stock
Raw material stock validation system that prevents overselling and negative stock:
- **Real-time Availability Check** - Checks ingredient availability before adding items to cart
- **Draft Sales Consideration** - Accounts for quantities in draft sales (unpaid)
- **Cross-Channel Sync** - Auto-refresh every 5 seconds to sync across POS, Waiter, and Self-Order
- **Visual Indicators** - "Available: X portions" badge in POS/Waiter, "OUT OF STOCK" overlay when depleted
- **Cart Increment Protection** - Validates when user increments quantity in cart
- **Toast Notifications** - Real-time notifications via Livewire events (no page reload)

**Features per Channel:**
| Feature | POS | Waiter App | Self-Order |
|---------|-----|------------|------------|
| Availability Badge | ✅ "Available: X portions" | ✅ "X portions" | ❌ (validation only) |
| Stock Validation | ✅ | ✅ | ✅ |
| Cart Increment Check | ✅ | ✅ | ✅ |
| Auto-disable when out | ✅ | ✅ | ✅ |
| "OUT OF STOCK" Overlay | ✅ | ✅ | ✅ |
| Toast Notifications | ✅ (Filament) | ✅ (Alpine.js) | ✅ (Alpine.js) |
| Auto-refresh (Polling) | ✅ 5s | ✅ 5s | ✅ 5s |

---

## ⚡️ Key Features Highlights

### 💎 Smart POS (Point of Sale)
Cashier interface designed for speed and premium user experience.
- **Space-Saving Layout (Responsive)**: Responsive layout that automatically adapts to Tablet (iPad) or Desktop screens.
- **⚡️ Quick Add Member**: Register new members **directly from transaction screen** without switching menus (using Custom Native Modal).
- **🔔 Live POS Notifications**: Non-blocking internal notification system for payment status, printing, and errors.
- **Seamless Modal Experience**: Custom native modals (Livewire) for discount and member input, providing smooth mobile app feel.
- **Smart Cart Logic**: Split payment, merged tables, and draft orders (pending transactions).
- **🖨️ Multi-Printer Infrastructure**: Supports multiple printers simultaneously (Cashier, Kitchen, & Bar) via **Electron Agent** (USB/LAN/Bluetooth support).
- **Direct Printing**: Automatic receipt and order printing without browser print dialog for maximum transaction speed.
- **🔄 Dynamic Cash Session Orchestration**: `Expected Cash` calculation performed dynamically and in real-time by summing Sales (Cash) and subtracting Expenses and Purchases using cashier funds.

### 🤝 Advanced CRM & Loyalty (Smart SOP) 🆕
Not just recording customer data, but building long-term relationships.
- **🎯 Smart WhatsApp SOP Integration**:
  - **Context-Aware SOP**: System automatically suggests personalized WhatsApp messages based on "Customer Phase" (New / Repeat / High Value).
  - **Dynamic Templates**: Message templates that automatically adapt to customer data (Name, Points Balance, Tier).
  - **Click-to-Followup**: Quick action buttons in member list for routine greetings or FAQs.
  - **🚀 Productivity Boost**: All WhatsApp commands (SOP/FAQ/AI) automatically open in **New Tab**, maintaining admin workflow consistency without closing main dashboard.
  - **Activity Tracking**: Tracks when member was last contacted (`last_contacted_at`), with **Reset Status** feature if send is cancelled.
- **💰 Smart Point System**:
  - **Dynamic Exchange Rate**: Point exchange rate freely configurable in settings (e.g., 1 Point = Rp 10).
  - **Realtime Redemption Validation**: POS automatically calculates member remaining balance during redemption and prevents "Over-Redemption".
  - **Instant Balance Preview**: Cashier can instantly see rupiah value of redeemed points.
- **Dynamic Loyalty Tiers**: Member levels that automatically rise/fall based on visit frequency or total spending.

### 📅 Intelligent Reservation & DP System 🆕
Reservation system directly integrated with customer communication and financial management.
- **One-Click WhatsApp Confirmation**: Send formal reservation confirmation via WhatsApp directly from calendar. Dynamic template format with perfect Emoji support (📅 ⏰ 😊).
- **💰 Robust Down Payment (DP) System**:
  - **Pay DP Anywhere**: Manage down payments through Reservation Table or Calendar.
  - **Automated DP Ledger**: Each DP payment automatically creates POS transaction with `DP-` prefix for accurate financial tracking.
  - **Self-Cleaning Catalog**: "Down Payment (DP)" product managed as *System Item*—automatically hidden from master data and POS catalog to avoid disrupting daily operations.
- **🔄 Smart Pre-Order to Sales (Flexible)**:
  - **Flexible Item Management**: Add pre-order menu items with flexible pricing (manually editable) and automatic calculations.
  - **Instant Conversion upon Arrival**: When customer arrives, convert entire reservation data including pre-order items into sales transaction in POS with one click.
  - **Automatic DP Deduction**: System automatically detects down payment and adds it as deduction item (minus) in cashier transaction.
  - **Double-Transaction Protection**: Conversion button automatically disappears after use (when status changes to `Seated`), preventing data duplication.
- **Snapshot Integrity**: Product names are snapshotted during conversion to ensure historical data remains accurate even if original product is deleted or modified.

### 📱 QR Self-Order Menu (Table Ordering) [PRO] 🆕
Elegant self-ordering system for customers directly from their table.
- **Scan to Order**: Customers scan QR at table → Select Menu → Order automatically sent to KDS/POS linked with active cashier session.
- **✨ Premium QR Generator**: Smart system to generate aesthetic table QR cards, automatically including Restaurant Name & Social Media.
- **🖨️ One-Click Printing**: Print QR feature directly from dashboard to printer or save as PDF with optimized card layout.
- **Glassmorphism UI**: Modern mobile menu interface with smooth category navigation and transition animations.
- **🚀 PWA Enabled**: Can be installed as smartphone app for easy access by regular customers.
- **🤖 Automated AI WhatsApp Broadcast**: If customer enters WhatsApp number at checkout, system will send **automatic** confirmation message with draft intelligently generated by AI (DeepSeek).
- **AI-Powered Notifications**: Automatic *personalized* WhatsApp notifications using AI after order is received.
- **Pro Module**: This is a paid module protected by license (`EnsureSelfOrderEnabled` Middleware).

### 🤵 Waiter Digital Order Panel 🆕
Mobile command center for waiters to speed up service and increase revenue.
- **High-Speed Ordering**: Input customer orders instantly via smartphone/tablet with realtime sync to cashier.
- **🌟 Featured Upselling Section**: AI intelligently displays favorite menu items or high-margin items at top to help waiters suggest best menu (Upselling).
- **🚀 Mobile PWA App**: Can be installed on waiter's phone as native app (Progressive Web App), ensuring high performance and one-click access from home screen.
- **🔄 Flexible Table Mapping**: Supports flexible table number input according to field conditions.
- **🤖 Automated AI WhatsApp Broadcast**: Same as *Self-Order*, if WhatsApp number is entered by Waiter, system will automatically send confirmation message to customer number with natural language style from AI.
- **🖨️ Automated Division Printing**: Each order automatically includes crucial details (**Table Number**, **Order Type**, **Notes**) and sent to relevant division printer (Kitchen/Bar).

### 🍳 Intelligent Kitchen Display System (KDS) & Printing
Paperless digital kitchen orchestration with smart filtering system.
- **Department Routing & Auto-Print**: Automatically separates Bar (Drinks) and Kitchen (Food) orders. Orders automatically materialize as **Print-Outs in respective divisions** as soon as saved by cashier.
- **🔍 Detailed Print-Outs**: Each order receipt (Kitchen/Bar/General) now includes complete information: **Table Number**, **Order Type**, and **Special Notes** per item.
- **🚫 System Item Filtering**: Financial items like "Down Payment (DP)" are intelligently filtered to not appear in KDS or kitchen/bar order receipts.
- **Status Workflow**: *Pending* ➝ *Cooking* ➝ *Ready* ➝ *Served*.

### 👥 Modern HRM (Payroll & Attendance)
Full-suite human resource management fully integrated with finance.
- **Smart Attendance System**:
  - **Face Recognition Ready**: Attendance validation using facial biometrics.
  - **GPS Geofencing**: Ensures employees clock-in/out only in outlet area.
  - **Late Penalty Logic**: Automatic deduction for tardiness.
- **Automated Payroll Engine**:
  - **Dynamic & Flexible Formula**: Fully customizable payroll formula (Base Salary, Allowances, Overtime, Penalties).
  - **Employee Loan System**: Loan management with **Auto-Deduction** feature on monthly payslip.

### 🧠 AI-Powered Intelligence (Powered by Nirmala AI) 🚀
Bringing restaurant operations to autonomous level with cutting-edge LLM integration for sharper business decisions.
- **🤖 AI Daily Suggestion [READY]**: Smart dashboard widget that automatically provides business strategy suggestions and critical stock alerts daily. Deeply analyzes last 30 days sales data and inventory status.
- **💬 AI Business Assistant ("Ask Nirmala") [READY]**: Natural language business performance consultation with **Conversation Memory**.
    - **Premium UI/UX**: Modern interface with *Glassmorphism* effects, dynamic input, and full responsiveness.
    - **Live Context**: AI has access to Sales data, Top Selling Menu, and Critical Stock (Retail & Raw Materials).
    - **Persona Config**: Change AI Name (e.g., "Sarah", "Jarvis") in Settings for more personal experience.
- **💌 AI Smart Message & Reply (Grounded AI) [READY]**: Highly personalized message draft generation with **Zero-Hallucination Logic**.
    - **Product Grounding**: AI will only mention **Real Menu** (Top 5 Best Sellers) from database, not fictional menu.
    - **Promo Awareness**: Automatically includes active promo codes available in cashier system.
    - **Signature System**: Automatic message closing according to configured Assistant name.
- **🔄 AI Provider Agnostic & Dynamic Models [NEW] [READY]**:
    - **Multi-Provider Support**: Free choice between **DeepSeek (Native)**, **OpenRouter (Free/Paid)**, or **Custom OpenAI API**.
    - **One-Click Presets**: Instant setup for OpenRouter & DeepSeek with auto-fill URL and model configuration.
    - **Dynamic Model Fetching**: Model list pulled in real-time from OpenRouter API with **(FREE)** model indicators.
    - **Zero-Code Configuration**: Set API Keys and Models directly through Admin Dashboard without touching `.env` file.
- **📅 AI Reservation Awareness & Weather Intelligence [NEW]**:
    - **Smart Availability Check**: AI intelligently checks reservation time availability by reading schedule 7 days ahead.
    - **🌤️ Hyper-Local Weather Forecaster**: Directly integrated with BMKG. Weather data used to provide precision advice during reservation confirmation:
        - **"Rain"**: AI reminds customer to bring umbrella or take car.
        - **"Hot"**: AI suggests refreshing cold drink menu.
        - **"Neutral"**: AI provides friendly touch about pleasant weather.
    - **Visual Weather Widget**: 12-hour weather forecast widget (every 3 hours) on dashboard pulling real-time data from local sub-district area code.
    - **🗺️ BMKG Location Sync**: Precision weather location configuration down to Sub-district level using BMKG Area Code for data accuracy.
- **📉 Stock Forecasting (AI) [PRO] [READY]**:
    - **Predictive Restocking**: AI predicts raw material needs for next 7 days based on historical trends and current remaining stock.
    - **Recipe-Aware Analytics**: Automatically calculates *raw material* needs (e.g., coffee beans) based on menu sales (e.g., Latte) using recipe data with **Unit Conversion Logic** (Gram/Kg/Pcs).
    - **Persistence & Speed**: Analysis results stored in cache for 24 hours for instant access without regenerating every page load.
    - **Urgency Insights**: Provides urgency labels (High/Medium/Low) and logical reasoning behind each restock suggestion.
    - **📄 Professional PDF Export**: Generate official restock report in neat PDF format, complete with recommendation table, urgency levels, and AI logical reasoning.
- **🍳 Menu Engineering (AI) (Profit & Popularity Matrix) [READY]**:
    - **Strategic Classification**: AI classifies menu into 4 strategic categories: **Stars**, **Plowhorses**, **Puzzles**, and **Dogs**.
    - **Ultra-Accurate COGS**: Super accurate COGS calculation supporting **Unit Conversion Rate** (e.g., sack/bag purchase price automatically converted to grams in recipe).
    - **🧙‍♂️ One-Click HPP Calibration (Magic Button)**: "Recalculate All COGS" feature that automatically fixes raw material base prices based on last purchase and mass recalculates all menu recipe COGS. Powerful solution for raw material price spikes.
    - **AI Strategic Advice**: Get tactical advice directly from AI (e.g., 10% price increase suggestion, portion reduction, or special promotion recommendations).
    - **Premium Matrix UI**: Visual dashboard with popularity bars, indigo category badges, and elegant gradient insight boxes.
    - **📄 Integrated PDF Report**: Export analysis results to PDF with professional layout ready for management presentations.

### 💬 Integrated WhatsApp Center (Native Chat) 🚀
Bringing complete WhatsApp Web experience directly into admin dashboard.
- **✨ Full-Featured Interface**: Familiar, responsive, and elegant chat display with **Dark Mode** support.
- **🤖 Grounded AI Reply (Anti-Hallucination)**: Generate smart automatic replies backed by Boss's business **Knowledge Base**:
  - **Menu Awareness**: AI knows Boss's top selling menu in realtime.
  - **Promo Awareness**: AI knows what promos are currently running in cashier.
  - **Reservation Availability**: AI can check table availability by automatically reading reservation data 7 days ahead.
- **↩️ Reply with Quote**: Reply message feature with original quote, exactly like native app.
- **⚡️ Realtime Architecture**:
  - **Live Notifications**: Instant sound and visual notifications when new message arrives without refresh.
  - **Direct Avatar Proxy**: Automatic avatar handling system for high performance (Fixing JID cleaning & device sync).
- **📁 Advanced Media Handling**:
  - **Ratio-Perfect Video**: Smart video player that respects original ratio (Portrait/Landscape).
  - **Drag & Drop**: Send images/documents just drag & drop with quick preview.
  - **Voice & Documents**: Full support for voice notes and PDF documents.
- **🚧 Group Mention Autocomplete [WIP]**: Group member tagging feature (`@user`) in development stage (Beta).
- **🔄 Smart Conversion Actions**:
  - **Quick Member**: Convert new customer chat to CRM Member directly from chat header. Auto-detect if already registered.
  - **Create Reservation**: Create reservation schedule directly while chatting without switching menus.
- **⚙️ Storage Management**:
  - **Auto Download Control**: *"Auto Download WhatsApp Media"* option in Settings menu to save server storage. If disabled, media only downloaded when "Download" button clicked.

### 📊 Reports & Analysis (Financial & Analytical Intelligence) 🚀
Data control center combining artificial intelligence and accurate fiscal calculations.
- **🏛️ Tax Report (Fiscal)**: Flexible tax planning with Revenue Target feature.
- **📈 Financial Report 2.0**: Net profit dashboard that accurately calculates gross margin by separating **Operating Expenses** (Electricity, Rent) and **Cost of Goods Sold/COGS** (Stock Purchases & Recipe Estimates).
    - **Period Comparison**: Performance comparison feature (Revenue, COGS, Expenses, Net Profit) with previous period (Month-to-Month) including growth indicators (Growth %) displayed in real-time on each metric.
    - **Stock Valuation Analysis**: Inventory stock valuation analysis with breakdown per category (Retail & Raw Materials), showing total asset value stored in warehouse.
    - **Interactive Trend Chart**: Financial trend chart visualization (Revenue vs Expenses) with responsive period filter.
    - **Enterprise PDF Export**: Export professional financial report in PDF format including all key metrics, operating cost breakdown, stock valuation, and executive summary.
- **📦 Stock Forecasting (AI)**: Raw material needs analysis for next 7 days based on historical trends.
- **🍽️ Menu Analysis (AI)**: Menu profitability classification (Stars, Plowhorses, etc.) with AI strategic suggestions.
- **🔍 Granular Cost Analysis**: Stock purchase breakdown **per product** and operating cost details **per category** with progress bar visualization for tight cost control.
- **📝 Smart Stock Movement (Adjustment) 🆕**: Smart stock adjustment form with **Dynamic Unit Suffix** to prevent operator input errors.
    - **Auto Unit Display**: Unit suffix automatically appears in quantity input when product is selected (e.g., `[1500] g`, `[2] Kg`).
    - **Base Unit Enforcement**: System ensures stock opname always uses base unit for data consistency.
    - **Reactive UX**: Unit suffix updates automatically when product changes without page reload.
- **📋 Stock Opname (Bulk Input) 🆕**: Efficient stock opname interface for monthly inventory.
    - **Bulk Input Table**: Input physical count for all products (Raw Material & Retail) on one page.
    - **Real-time Summary**: Summary dashboard displaying Items Checked, Total Variance, and Value Loss in real-time.
    - **Auto Variance Calculation**: System automatically calculates difference between system stock and physical count.
    - **Monetary Loss Tracking**: Track financial loss value from negative variance (loss/damaged goods).
    - **One-Click Submission**: Submit all variances at once with professional Filament modal confirmation.
- **🤝 Loyalty Automation (Re-engagement) 🆕**: AI-powered automatic system to re-approach customers who haven't visited in a long time.
    - **Soft-Greeting Strategy**: AI trained to greet emotionally (asking about well-being & health), avoiding *hard-selling* impression that disturbs.
    - **Auto-detect Inactive Members**: Automatic filter for members with last visit >30 days.
    - **AI Assistant Persona**: Messages set according to digital assistant profile (Assistant Name, Language Style) in Settings.
    - **🚀 One-Click Manual Re-engage**: Direct action button in CRM dashboard to manually trigger AI outreach per customer.
    - **Smart Scheduling**: Automatic mass execution every Monday morning via scheduler.
    - **Anti-spam Logic**: 7-day followup cooldown to maintain customer comfort.

### 🛡️ Role-Based Access Control (RBAC) [NEW]
Strict tiered security system. **Delete Button** globally **HIDDEN** from display for all roles except **Super Admin** & **Admin**.

| Role | Description & Main Access |
| :--- | :--- |
| **Super Admin** | **Full Access** & Root Privileges. Only one who can Manage System Users, Backup, & Audit Log. |
| **Operational Admin** | Manage Products, Stock, Employees. **Can Delete Data**. Cannot access System Users & Core Settings. |
| **Accountant** | Full Financial Access (P&L, Payroll). **Read-only** to Stock & Transactions. **NO DELETE** (Button Hidden). |
| **Inventory** | Full Stock & Purchase Access. **Sale Price & Profit Hidden** (Blind Access). **NO DELETE** (Button Hidden). |
| **Kitchen** | KDS & Stock Report Access Only. **Cannot see** Price/Customer. **NO DELETE**. |
| **Cashier** | POS & CRM Member Access Only. Limited Edit. **Cannot see** Reports/Profit. **NO DELETE** (Void Transaction Hidden). |
| **Waiter** | Input Order Only. View KDS Status. **No Access** to Member/Finance Data. **NO DELETE**. |

### 🧩 Modular Architecture (On-Demand Features)
Advanced features that can be customized to outlet needs.
- **Toggleable Modules**: Freely enable/disable features according to your business scale, from **CRM (Loyalty)**, **HRM (Payroll)**, **KDS (Kitchen)**, **WhatsApp Center**, **Fiscal Planning**, to smart modules **AI Forecasting** and **AI Menu Engineering**.
- **Centralized Settings**: Centralized settings panel for all module configurations.

---

## 🗺️ 2026 Strategic Roadmap: Strategic Expansion

Future feature development plans to maximize ROI and operational efficiency.

### 🗺️ Visual Table Management (Interactive Floor Plan) [MEDIUM PRIORITY]
- **Restaurant Floor Plan Display**: "Drag & Drop" editor to arrange table positions according to actual floor plan.
- **Live Status Indicator**: Real-time visual indicators (Empty Table = Green, Occupied = Red, Dirty = Yellow).
- **Impact**: Enhances application *Look & Feel* to premium and helps waiters monitor tables.

### 📊 Real-time Dashboard 2.0 (Live Analytics) [HIGH PRIORITY]
- **Live Widgets**: Adding "Live Sales Tick", "Top Items Today", and "Hourly Heatmap" (Busy hours) widgets.
- **Owner Mode**: Compact display mode specifically for owners to monitor revenue from phone in real-time.

### 💳 Intelligent Payment Gateway (Auto-Settlement)
- **Dynamic QRIS**: Midtrans/Xendit integration for automatic payment in Self-Order.
- **Auto-Fulfillment**: Orders automatically change to "Paid" and KDS sounds immediately after funds received.

---

## 📱 Mobile App Access (PWA)

This system is designed with *Mobile-First* approach. You can access the following pages through Google Chrome on smartphone and select **"Add to Home Screen"** to install it as native app (PWA).

| Component | Access URL | Description |
| :--- | :--- | :--- |
| **QR Self-Order** | `/scan/{table-slug}` | Scan QR at table for self-service menu access (Customer PWA). |
| **Waiter App** | `/waiter/order` | Instant orders in Waiter's hand (Waiter PWA). |
| **Attendance Kiosk** | `/kiosk` | Face/biometric attendance panel at entrance (Kiosk PWA). |

> [!TIP]
> **PWA Installation**: After opening above URL via Mobile Chrome/Safari, click browser menu then select **"Install App"** so app appears in phone menu with faster and more stable performance.

---

## 🛠️ Technology Stack (Enterprise Grade)

Built on the most modern and stable technology foundation in 2025.

- **Framework**: [Laravel 11](https://laravel.com)
- **Admin & UI**: [FilamentPHP v4](https://filamentphp.com)
- **Engine**: [Livewire 3](https://livewire.laravel.com)
- **Styling**: [TailwindCSS v4](https://tailwindcss.com) & Vanilla CSS
- **Database**: MySQL 8 / MariaDB
- **State Management**: Alpine.js v3
- **Local Agent**: [Electron Bridge](https://electronjs.org) (Hardware Bridge)

---

## 🚀 Deployment & Background Services

For WhatsApp and AI orchestration to run *real-time*, services below **MUST** run in background.

### 1. Queue & Scheduler Setup

#### **A. Using PM2 (Windows/Desktop/VPS)**
Most stable and easy method for service management in one panel.
```bash
# 1. Run WhatsApp Gateway
cd wa-gateway && pm2 start index.js --name "wa-gateway"

# 2. Run Laravel Scheduler (AI & Report Automation)
pm2 start "php artisan schedule:work" --name "resto-scheduler"

# 3. Run Laravel Worker (Notification Delivery)
pm2 start "php artisan queue:work" --name "resto-worker"
```

#### **B. PM2 Windows Autostart**
To make all processes start automatically when Windows boots/restarts:
1. Run CMD/PowerShell as **Administrator**.
2. Install startup utility: `npm install pm2-windows-startup -g`.
3. Install utility: `pm2-startup install`.
4. Save active configuration: `pm2 save`.

#### **C. VPS / Shared Hosting (Standard Cron)**
Add to Cron Jobs (`crontab -e`):
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

> [!TIP]
> **Windows Dev Optimization:** Use PM2 to reduce memory load when not in use with command `pm2 stop all`.

---

### 🚨 Important: Security & Performance
> [!IMPORTANT]
> **Double-Check `routes/console.php`**: Ensure queue command uses `--stop-when-empty` flag if run via Scheduler on Shared Hosting. Without this, your server risks **OVERLOAD** due to accumulating processes.

```php
// Example in routes/console.php (Laravel 11)
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty')->everyMinute();
```

---

### 🛠️ System Requirements
- **PHP 8.2+** (Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD)
- **MySQL 8.0+** / MariaDB 10.4+
- **AI API Integrated**: Supports DeepSeek, OpenRouter, or OpenAI-compatible API.
    - **Quick Setup**: Select provider (DeepSeek/OpenRouter) in Settings menu for automatic configuration.
    - **Dynamic Models**: Model list pulled in real-time from API (OpenRouter only).
    - **Local Dev**: If running locally (Windows/MacOS) and encountering SSL error, add `DEEPSEEK_VERIFY_SSL=false` in `.env`.
- **Node.js 18+**: Required specifically for WhatsApp Gateway module.
- **Microphone & Camera**: Browser permission required for Voice Note & Face Recognition features.

---

<p align="center">
    📍 <b>Developed by Evan Helga</b> — <i>Crafting Digital Excellence for F&B Business.</i>
</p>
