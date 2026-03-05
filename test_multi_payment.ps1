$headers = @{
    "Accept" = "application/json"
    "X-POS-Key" = "123456"
    "Content-Type" = "application/json"
}

# 1. Create a draft order
$orderBody = @{
    order_type = "Dine In"
    table_number = "T2"
    customer_name = "MultiPay User"
    subtotal = 100000
    tax = 10000
    final_total = 110000
    items = @(
        @{
            product_id = 1
            quantity = 1
            unit_price = 100000
            subtotal = 100000
        }
    )
} | ConvertTo-Json -Depth 5

Write-Host "--- Creating Draft Order ---"
$order = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/orders -Method Post -Headers $headers -Body $orderBody
$id = $order.order.id
Write-Host "Order ID: $id"

# 2. Get Payment Methods to find IDs
Write-Host "`n--- Getting Payment Methods ---"
$bootstrap = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/bootstrap -Method Get -Headers $headers
$cashMethod = $bootstrap.payment_methods | Where-Object { $_.code -eq "cash" }
$qrisMethod = $bootstrap.payment_methods | Where-Object { $_.code -ne "cash" } | Select-Object -First 1

$cashId = $cashMethod.id
$qrisId = $qrisMethod.id

Write-Host "Cash Method ID: $cashId"
Write-Host "QRIS/Other Method ID: $qrisId"

# 3. Perform Multi-Payment Checkout
$checkoutBody = @{
    payments = @(
        @{ payment_method_id = $cashId; amount = 50000 },
        @{ payment_method_id = $qrisId; amount = 60000; reference_number = "QRIS-123456" }
    )
    customer_name = "MultiPay User Paid"
} | ConvertTo-Json -Depth 5

Write-Host "`n--- Performing Multi-Payment Checkout ---"
try {
    $result = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/orders/$id/checkout -Method Post -Headers $headers -Body $checkoutBody
    $result | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $reader.ReadToEnd()
    }
}
