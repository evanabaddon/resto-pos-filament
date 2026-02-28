# React POS API v2 Documentation

**Base URL**: `http://localhost:8000/api/v2/pos` (or your production domain)
**Authentication**: All endpoints require an `X-POS-Key` header.
*Example: `X-POS-Key: 123456`*
**Accept**: `application/json`

---

## 1. Bootstrap & Initialization

### Get Bootstrap Data
Fetches all necessary initial data for the POS to operate, including settings, product menus, categories, tables, and payment methods.
- **Method**: `GET`
- **Endpoint**: `/bootstrap`
- **Response**:
```json
{
  "settings": {
    "store_name": "Resto POS",
    "tax_rate": 10,
    "enable_tax": true,
    "loyalty_point_exchange_rate": 10000,
    "loyalty_point_value": 1,
    "loyalty_program_name": "Loyalty"
  },
  "products": [
    {
      "id": 1,
      "name": "Nasi Goreng",
      "price": 25000,
      "stock": 50,
      "stock_porsi": 10,
      "stock_bahan": 40,
      "category_id": 2,
      "image": "http://localhost:8000/storage/...",
      "type": "produced",
      "barcode": "123456789"
    }
  ],
  "categories": [...],
  "payment_methods": [...],
  "tables": [...],
  "loyalty_tiers": [...]
}
```

---

## 2. Cash Session (Shift)

### Get Current Session
- **Method**: `GET`
- **Endpoint**: `/session/current`
- **Response**:
```json
{
  "session": {
    "id": 1,
    "user_name": "Admin",
    "cash_in_hand": 500000,
    "opened_at": "2026-02-24T10:00:00Z"
  }
}
```
*(Returns `{"session": null}` if no shift is open)*

### Open Session (Shift)
- **Method**: `POST`
- **Endpoint**: `/session/open`
- **Body**:
```json
{
  "cash_in_hand": 500000
}
```

### Close Session
- **Method**: `POST`
- **Endpoint**: `/session/close`
- **Body**:
```json
{
  "cash_out": 1500000
}
```

### Session Summary
Calculates projected cash based on sales and initial float.
- **Method**: `GET`
- **Endpoint**: `/session/summary`

---

## 3. Orders (Drafts / Pending Bills)

### List All Active Drafts
- **Method**: `GET`
- **Endpoint**: `/orders`

### Get Draft Detail
- **Method**: `GET`
- **Endpoint**: `/orders/{id}`

### Create Draft Order
Creates a pending bill, instantly deducts stock, sets table to 'occupied', and triggers Kitchen Print Job.
- **Method**: `POST`
- **Endpoint**: `/orders`
- **Body**:
```json
{
  "table_number": "Meja 1", 
  "order_type": "Dine In",
  "customer_name": "John Doe",
  "note": "Jangan pedas",
  "member_id": 1, // Optional nullable
  "subtotal": 50000,
  "tax": 5000,
  "final_total": 55000,
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "unit_price": 25000,
      "subtotal": 50000,
      "notes": "Sedang"
    }
  ]
}
```

### Void/Delete Draft Order
Restores stock levels and frees the table.
- **Method**: `DELETE`
- **Endpoint**: `/orders/{id}`

---

## 4. Checkout (Payment)

### Complete Order Payment
Marks draft as 'completed', clears the table status, manages loyalty points natively, and issues Receipt Print Job.
- **Method**: `POST`
- **Endpoint**: `/orders/{id}/checkout`
- **Body**:
```json
{
  "payment_method_id": 6, // ID for Tunai/Cash etc.
  "amount_paid": 60000,
  "discount_amount": 0,
  "points_redeemed": null
}
```

---

## 5. Members

### Search Members
- **Method**: `GET`
- **Endpoint**: `/members/search?q={query}`
- **Query** `q`: Can be either member Name or Phone.

### Get Member by ID
- **Method**: `GET`
- **Endpoint**: `/members/{id}`

### Register New Member
- **Method**: `POST`
- **Endpoint**: `/members`
- **Body**:
```json
{
  "name": "Jane Doe",
  "phone": "08123456789",
  "email": "jane@example.com" // optional
}
```

---

## 6. Discounts & Promotions

### Validate Promo Code
- **Method**: `POST`
- **Endpoint**: `/discounts/validate`
- **Body**:
```json
{
  "code": "PROMO10",
  "subtotal": 50000
}
```

---

## 7. Realtime Syncs

### Fast Product Stock Sync
A lightweight endpoint to poll exactly how much stock each product currently has without full product details.
- **Method**: `GET`
- **Endpoint**: `/products/stock`
- **Response**:
```json
{
  "stocks": [
    {"id": 1, "stock": 48, "stock_porsi": 10, "stock_bahan": 38},
    {"id": 2, "stock": 10, "stock_porsi": 10, "stock_bahan": 0}
  ]
}
```

**Penjelasan Sistem Stock**:
- `stock`: Total akumulasi porsi yang bisa dijual saat ini (`stock_porsi` + `stock_bahan`).
- `stock_porsi`: Jumlah produk yang sudah disiapkan / sudah jadi (Prepared Stock).
- `stock_bahan`: Potensi jumlah porsi yang bisa diproduksi berdasarkan ketersediaan resep bahan baku (Potential Stock).
- Jika tipe produk adalah `service`, nilai stock diset menjadi `9999`.

### Get Tables Status
- **Method**: `GET`
- **Endpoint**: `/tables`

### Manually Override Table Status
- **Method**: `PATCH`
- **Endpoint**: `/tables/{id}/status`
- **Body**:
```json
{
  "status": "available" // Or "occupied"
}
```

---

## 8. Transactions History

### Get Latest Completed Transactions
Fetches the latest `completed` transactions / sales history along with their items and payment method name.
- **Method**: `GET`
- **Endpoint**: `/transactions?limit=50`
- **Query** `limit`: (Optional) Default is 50. Controls how many items returned.
- **Response**:
```json
{
  "transactions": [
    {
      "id": 264,
      "invoice_number": "INV-1771946618",
      "table_number": "T3",
      "order_type": "Dine In",
      "customer_name": "Test",
      "subtotal": "50000.00",
      "discount": "0.00",
      "tax": "5000.00",
      "final_total": "55000.00",
      "amount_paid": "55000.00",
      "change_amount": "0.00",
      "payment_method": "Tunai",
      "created_at": "2026-02-24T15:23:30.000000Z",
      "items": [
        {
          "id": 455,
          "product_id": 1,
          "product_name": "Nasi Goreng",
          "quantity": "2.00",
          "unit_price": "25000.00",
          "subtotal": "50000.00",
          "notes": null
        }
      ]
    }
  ]
}
```
