# Resto POS Filament - Implementation Roadmap

This document outlines the strategic plan to maximize the system's potential, addressing performance, integrity, and advanced features.

## 🚀 Phase 1: Core Integrity & Stability (Foundation)
*Focus: Ensuring data is 100% accurate and system is robust against errors.*

- [ ] **Transaction Safety**: Audit `OrderService` and `StockMovement` to ensure all database writes use `DB::transaction()` to prevent data corruption during failures.
- [ ] **Race Condition Handling**: Implement Atomic Locks (`Cache::lock`) for stock deduction to prevent overselling when two waiters order simultaneously.
- [ ] **Snapshotting**: Ensure historical data accuracy by snapshotting Product Price & COGS into `order_items` at the time of purchase (don't rely on live product table relationships).
- [ ] **Delete Protection**: Ensure critical Master Data (Categories, Payment Methods) cannot be deleted if used in transactions (Soft Deletes or Restricted Actions).

## ⚡ Phase 2: Performance & Real-Time Interaction
*Focus: Making the app feel instant and "alive".*

- [ ] **N+1 Query Audit**: Review all Filament Tables (`ProductResource`, `OrderResource`, `StockMovementResource`) and implement Eager Loading (`with(['relation'])`).
- [ ] **WebSockets Integration**: Install **Laravel Reverb** (or Pusher) to push updates instantly.
    - [ ] Real-time Kitchen Display System (KDS) updates (no polling).
    - [ ] Real-time Waiter notifications (Kitchen Finished).
- [ ] **Queue Workers**: Offload heavy tasks (Monthly Report Generation, AI Analysis, Bulk WA Messages) to background queues (Redis/Database).

## 🧠 Phase 3: AI Intelligence Maximization
*Focus: Moving from "Passive Data" to "Active Recommendations".*

- [ ] **Predictive Stock Forecasting**:
    - Analyze consumption trends to predict "Out of Stock" dates.
    - Auto-generate Purchase Order drafts when stock is predicted to run low.
- [ ] **Context-Aware Recommendations**:
    - Suggest upsells based on Weather (OpenWeather API) + Time of Day in the POS.
    - Example: "It's raining; suggest 'Hot Ginger Tea'."
- [ ] **Menu Engineering Analysis**:
    - Auto-classify items (Stars, Puzzles, Plowhorses, Dogs) based on Profit vs Popularity.
    - Suggest pricing adjustments AI-driven.

## 🤝 Phase 4: Customer Loyalty (CRM) & Experience
*Focus: Retaining customers and modernizing the ordering process.*

- [ ] **Tiered Membership System**:
    - Implement Bronze/Silver/Gold tiers based on total spend.
    - Auto-update tiers via Queues.
- [ ] **Automated WhatsApp CRM**:
    - Schedule "We Miss You" messages for customers absent > 30 days.
    - Birthday blast automations.
- [ ] **QR Self-Order (Table Ordering)**:
    - Create a public-facing mobile view for customers to scan QR, order, and pay.

## 🛠️ Phase 5: Hardware & Operations
*Focus: Bridging the digital and physical world.*

- [ ] **Local Print Server**:
    - Create a simple agent (or use JS print service) to allow browser-based POS to print directly to USB Thermal Printers (ESCPOS) without dialogs.
- [ ] **Offline Resilience (PWA)**:
    - Configure PWA manifest and Service Workers to allow basic app loading even when offline (cached assets).

---
*Created: 2026-01-13*
