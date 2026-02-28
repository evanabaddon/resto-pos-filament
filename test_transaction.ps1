$headers = @{"Accept"="application/json";"X-POS-Key"="123456";"Content-Type"="application/json"}
$body = '{"order_type":"Dine In","table_number":"T3","customer_name":"Test riwayat","subtotal":50000,"tax":5000,"final_total":55000,"items":[{"product_id":1,"quantity":2,"unit_price":25000,"subtotal":50000,"notes":null}]}'

try {
    $res = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/orders -Method Post -Headers $headers -Body $body
    $id = $res.order.id
    Write-Host "Created Draft ID: $id"
    
    $checkoutBody = '{"payment_method_id":6,"amount_paid":55000,"discount_amount":0,"points_redeemed":null}'
    Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v2/pos/orders/$id/checkout" -Method Post -Headers $headers -Body $checkoutBody | Out-Null
    Write-Host "Checkout Success"
    
    $res = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/transactions -Method Get -Headers $headers
    $res | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}
