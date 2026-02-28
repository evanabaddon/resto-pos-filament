$headers = @{"Accept"="application/json";"X-POS-Key"="123456";"Content-Type"="application/json"}
$body = '{"order_type":"Dine In","table_number":"T1","customer_name":"Test","subtotal":50000,"tax":5000,"final_total":55000,"items":[{"product_id":1,"quantity":2,"unit_price":25000,"subtotal":50000,"notes":null}]}'
try {
    $res = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/orders -Method Post -Headers $headers -Body $body
    $res | ConvertTo-Json -Depth 5
} catch {
    $stream = $_.Exception.Response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    $responseBody = $reader.ReadToEnd()
    Write-Host $responseBody
}
