<?php

$common = require __DIR__ . '/partials/common.php';
$pos = require __DIR__ . '/partials/pos.php';
$crm = require __DIR__ . '/partials/crm.php';
$hrm = require __DIR__ . '/partials/hrm.php';
$report = require __DIR__ . '/partials/report.php';
$settings = require __DIR__ . '/partials/settings.php';
$products = require __DIR__ . '/partials/products.php';
$transaction = require __DIR__ . '/partials/transaction.php';
$cash_session = require __DIR__ . '/partials/cash_session.php';
$expense = require __DIR__ . '/partials/expense.php';
$purchase = require __DIR__ . '/partials/purchase.php';
$stock_opname = require __DIR__ . '/partials/stock_opname.php';
$stock_movement = require __DIR__ . '/partials/stock_movement.php';
$expense_category = require __DIR__ . '/partials/expense_category.php';
$inventory_forecasting = require __DIR__ . '/partials/inventory_forecasting.php';
$menu_engineering = require __DIR__ . '/partials/menu_engineering.php';
$fiscal_report = require __DIR__ . '/partials/fiscal_report.php';
$financial_report = require __DIR__ . '/partials/financial_report.php';
$dashboard = require __DIR__ . '/partials/dashboard.php';
$reservation = require __DIR__ . '/partials/reservation.php';
$ai_assistant = require __DIR__ . '/partials/ai_assistant.php';
$discount = require __DIR__ . '/partials/discount.php';
$payment = require __DIR__ . '/partials/payment.php';

return array_merge(
    $common,
    $pos,
    $crm,
    $hrm,
    $report,
    $settings,
    $products,
    $transaction,
    $cash_session,
    $expense,
    $purchase,
    $stock_opname,
    $stock_movement,
    $expense_category,
    $inventory_forecasting,
    $menu_engineering,
    $fiscal_report,
    $financial_report,
    $dashboard,
    $reservation,
    $ai_assistant,
    $discount,
    $payment
);
