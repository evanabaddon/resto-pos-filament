$headers = @{
    "Accept" = "application/json"
    "X-POS-Key" = "123456"
    "Content-Type" = "application/json"
}

Write-Host "--- TEST: Get Current Session ---"
try {
    $current = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/session/current -Method Get -Headers $headers
    $current | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "`n--- TEST: Close Session ---"
try {
    $close = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/session/close -Method Post -Headers $headers -Body '{"cash_out": 10000}'
    $close | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "`n--- TEST: Open Session ---"
try {
    $open = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/session/open -Method Post -Headers $headers -Body '{"cash_in_hand": 50000}'
    $open | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "`n--- TEST: Create Draft Order ---"
$orderBody = @{
    order_type = "Dine In"
    table_number = "T1"
    customer_name = "Test User"
    subtotal = 50000
    tax = 5000
    final_total = 55000
    items = @(
        @{
            product_id = 1
            quantity = 2
            unit_price = 25000
            subtotal = 50000
            notes = "Test Note"
        }
    )
} | ConvertTo-Json -Depth 5

try {
    $order = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/orders -Method Post -Headers $headers -Body $orderBody
    $order | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $reader.ReadToEnd()
    }
}

Write-Host "`n--- TEST: Get Expense Categories ---"
try {
    $expenseCategories = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/expense-categories -Method Get -Headers $headers
    $expenseCategories | ConvertTo-Json -Depth 5
    $firstCategoryId = $expenseCategories.categories[0].id
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "`n--- TEST: Create Expense ---"
$expenseBody = @{
    expense_category_id = $firstCategoryId
    amount = 15000
    description = "Beli sabun cuci piring"
    recipient = "Warung Sebelah"
    notes = "Untuk keperluan dapur"
} | ConvertTo-Json -Depth 5

try {
    $expense = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/expenses -Method Post -Headers $headers -Body $expenseBody
    $expense | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $reader.ReadToEnd()
    }
}

Write-Host "`n--- TEST: Get Expenses List ---"
try {
    $expensesList = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v2/pos/expenses -Method Get -Headers $headers
    $expensesList | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}
