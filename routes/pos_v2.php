<?php

use App\Models\CashSession;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Table;
use App\Models\Member;
use App\Models\LoyaltyTier;
use App\Services\RecipeStockChecker;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/*
|--------------------------------------------------------------------------
| API Routes for React POS v2
|--------------------------------------------------------------------------
|
| Prefix: /api/v2/pos
| Middleware: api -> then PosAuthMiddleware
*/

Route::middleware(\App\Http\Middleware\PosAuthMiddleware::class)->group(function () {

    // 1. Bootstrap Endpoint
    Route::get('/bootstrap', function () {
        $settings = app(GeneralSettings::class);

        $settingsData = [
            'store_name' => $settings->app_name ?? 'Resto POS',
            'tax_rate' => $settings->tax_percentage ?? 0,
            'enable_tax' => $settings->enable_tax ?? false,
            'loyalty_point_exchange_rate' => $settings->loyalty_point_exchange_rate ?? 10000,
            'loyalty_point_value' => $settings->loyalty_point_value ?? 1,
            'loyalty_program_name' => $settings->loyalty_program_name ?? 'Loyalty',
        ];

        // Products
        $query = Product::where('is_sellable', true)
            ->where('name', 'not like', '%Down Payment%');

        $products = $query->with(['recipes.ingredient', 'recipes.unit', 'unit'])
            ->get()
            ->map(function ($product) {
                $realStock = 0;
                $prepared = 0;
                $potential = 0;

                if ($product->type === 'service') {
                    $realStock = 9999;
                } elseif (in_array($product->type, ['produced', 'bar'])) {
                    $prepared = $product->prepared_stock ?? 0;
                    if ($product->recipes->isNotEmpty()) {
                        $potential = app(RecipeStockChecker::class)->getMaxPortions($product);
                    }
                    $realStock = $prepared + $potential;
                } else {
                    $realStock = $product->stock ?? 0;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->sell_price,
                    'stock' => (float) $realStock,
                    'stock_porsi' => (float) $prepared,
                    'stock_bahan' => (float) $potential,
                    'category_id' => $product->category_id,
                    'image' => $product->image ? asset('storage/' . $product->image) : null,
                    'type' => $product->type,
                    'barcode' => $product->barcode, // Send barcode for barcode scanner
                ];
            });

        return response()->json([
            'settings' => $settingsData,
            'products' => $products,
            'categories' => Category::select('id', 'name')->get(),
            'product_types' => Product::select('type')->distinct()->whereNotNull('type')->get()->pluck('type'),
            'payment_methods' => PaymentMethod::where('is_active', true)->select('id', 'name', 'code')->get(),
            'tables' => Table::select('id', 'name', 'slug', 'status', 'x', 'y', 'width', 'height', 'shape')->get(),
            'loyalty_tiers' => \App\Models\LoyaltyTier::select('id', 'name', 'min_points as minimum_points', 'benefit_description')->orderBy('min_points', 'asc')->get() ?? [], // Assume exists
        ]);
    });

    // 2. Auth & Cash Session (Shift)
    Route::post('/login', function (Request $request) {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = \App\Models\User::where('email', $validated['email'])->first();

        // Check if user exists and password is correct using Laravel's Hash
        if (!$user || !\Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah'
            ], 401);
        }

        // Optional: Check if user has permission (valid role)
        if ($user->role === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki peran (Role)'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value ?? $user->role, // handle enum backing value
                // Optional avatar field mapping if you have one
                'avatar' => '👨‍💼',
                'outlet' => 'RestoOS Pusat' // Static for now
            ]
        ]);
    });

    Route::get('/session/current', function () {
        $openSession = CashSession::with('user:id,name')->where('status', 'open')->latest()->first();
        if (!$openSession)
            return response()->json(['session' => null]);

        return response()->json([
            'session' => [
                'id' => $openSession->id,
                'user_name' => $openSession->user->name ?? 'Unknown',
                'user_id' => $openSession->user_id,
                'cash_in_hand' => $openSession->cash_in_hand,
                'opened_at' => $openSession->opened_at,
            ]
        ]);
    });

    Route::post('/session/open', function (Request $request) {
        $request->validate([
            'cash_in_hand' => 'required|numeric|min:0',
            'user_id' => 'sometimes|nullable|integer'
        ]);

        $activeSession = CashSession::where('status', 'open')->latest()->first();
        if ($activeSession) {
            return response()->json(['message' => 'A shift is already open.', 'session' => $activeSession], 400);
        }

        $session = CashSession::create([
            'user_id' => $request->user_id ?? 2, // Admin User as fallback
            'cash_in_hand' => $request->cash_in_hand,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return response()->json(['session' => $session]);
    });

    Route::post('/session/close', function (Request $request) {
        $request->validate(['cash_out' => 'required|numeric|min:0']);

        $activeSession = CashSession::where('status', 'open')->latest()->first();
        if (!$activeSession)
            return response()->json(['message' => 'No active shift.'], 404);

        $activeSession->update([
            'status' => 'closed',
            'cash_out' => $request->cash_out,
            'closed_at' => now(),
        ]);

        return response()->json(['session' => $activeSession]);
    });

    Route::get('/session/summary', function () {
        $session = CashSession::where('status', 'open')->latest()->first();
        if (!$session)
            return response()->json(['summary' => null]);

        // Calculate sales for this session
        $sales = Sale::where('cash_session_id', $session->id)->where('status', 'completed');
        $totalSales = clone $sales;

        $cashSales = clone $sales;
        $totalCashSales = $cashSales->whereHas('paymentMethod', function ($q) {
            $q->where('code', 'cash')->orWhere('name', 'like', '%cash%');
        })->sum('final_total');

        $expectedCash = $session->cash_in_hand + $totalCashSales; // Excluding payouts for now

        $hpp = \App\Models\SaleItem::whereHas('sale', function ($q) use ($session) {
            $q->where('cash_session_id', $session->id)->where('status', 'completed');
        })->join('products', 'sale_items.product_id', '=', 'products.id')
            ->sum(DB::raw('sale_items.quantity * IFNULL(products.base_price, 0)'));

        // Get payment details breakdown
        $paymentDetailsRaw = DB::table('sales')
            ->where('cash_session_id', $session->id)
            ->where('status', 'completed')
            ->select('payment_method_id', 'payment_method', DB::raw('SUM(final_total) as amount'))
            ->groupBy('payment_method_id', 'payment_method')
            ->get();

        $paymentDetails = $paymentDetailsRaw->map(function ($pd) {
            $pm = \App\Models\PaymentMethod::find($pd->payment_method_id);
            return [
                'method' => $pm ? $pm->name : ($pd->payment_method ?: 'Unknown'),
                'amount' => (float) $pd->amount
            ];
        });

        return response()->json([
            'summary' => [
                'cash_in_hand' => $session->cash_in_hand,
                'total_sales' => $totalSales->sum('final_total'),
                'cash_sales' => $totalCashSales,
                'expected_cash' => $expectedCash,
                'total_orders' => $totalSales->count(),
                'hpp' => (float) $hpp,
                'opened_at' => $session->opened_at,
                'current_time' => now(),
                'payment_details' => $paymentDetails
            ]
        ]);
    });

    // 3. Orders (Draft / Pending Bill)
    Route::get('/orders', function () {
        $drafts = Sale::with(['items.product'])->where('status', 'draft')->latest()->get()->map(function ($sale) {
            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'table_number' => $sale->table_number,
                'order_type' => $sale->order_type,
                'customer_name' => $sale->customer_name,
                'subtotal' => $sale->subtotal,
                'tax' => $sale->tax,
                'final_total' => $sale->final_total,
                'created_at' => $sale->created_at,
                'items' => $sale->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'Unknown',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'notes' => $item->notes
                    ];
                })
            ];
        });
        return response()->json(['orders' => $drafts]);
    });

    Route::get('/orders/{id}', function ($id) {
        $sale = Sale::with(['items.product'])->where('status', 'draft')->findOrFail($id);
        return response()->json(['order' => $sale]);
    });

    Route::post('/orders', function (Request $request) {
        $data = $request->validate([
            'table_number' => 'nullable|string',
            'order_type' => 'required|string',
            'customer_name' => 'nullable|string',
            'note' => 'nullable|string',
            'member_id' => 'nullable|integer|exists:members,id',
            'user_id' => 'nullable|integer',
            'subtotal' => 'required|numeric',
            'tax' => 'required|numeric',
            'final_total' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.subtotal' => 'required|numeric',
            'items.*.notes' => 'nullable|string',
        ]);

        $session = CashSession::where('status', 'open')->latest()->first();

        // Transaction to ensure atomicity
        $sale = DB::transaction(function () use ($data, $session) {
            $sale = Sale::create([
                'invoice_number' => 'APP-' . time() . '-' . rand(100, 999),
                'customer_name' => $data['customer_name'] ?? 'Walk-in',
                'order_type' => $data['order_type'],
                'table_number' => $data['table_number'],
                'table_name' => $data['table_number'], // Store table name
                'user_id' => $data['user_id'] ?? 2,
                'member_id' => $data['member_id'] ?? null,
                'cash_session_id' => $session ? $session->id : null,
                'subtotal' => $data['subtotal'],
                'tax' => $data['tax'],
                'total' => $data['final_total'],
                'final_total' => $data['final_total'],
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'notes' => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes'] ?? null,
                ]);

                // Deduct stock
                $product = Product::find($item['product_id']);
                if ($product && !in_array($product->type, ['service'])) {
                    // if ($product->stock !== null) {
                    //   $product->decrement('stock', $item['quantity']);
                    // }
                    // Not decrementing base stock for produced items here? Let's just decrement stock.
                    if ($product->stock !== null) {
                        $product->decrement('stock', $item['quantity']);
                    }
                }
            }

            // Note: If table linked, update table status
            if ($data['table_number']) {
                $table = Table::where('name', $data['table_number'])->first();
                if ($table) {
                    $table->update(['status' => 'occupied']);
                }
            }

            return $sale->load('items.product');
        });

        // Trigger KITCHEN Print Job automatically for drafts?
        // Only if not already printed locally by the desktop POS
        if (!$request->input('printed_locally', false)) {
            // Create Kitchen Print Job
            PrintJob::create([
                'job_id' => 'KTC_' . uniqid(),
                'content' => 'KITCHEN ORDER',
                'payload' => [
                    'sale' => $sale->toArray(),
                    'items' => $sale->items->map(function ($i) {
                        return [
                            'product_name' => $i->product->name ?? 'Unknown',
                            'quantity' => $i->quantity,
                            'notes' => $i->notes
                        ];
                    })->toArray(),
                    'table' => $sale->table_name ?? $sale->table_number,
                    'order_type' => $sale->order_type,
                    'customer_name' => $sale->customer_name
                ],
                'printer' => 'KASIR', // Fallback, would be mapped
                'division' => 'Kitchen',
                'sale_id' => $sale->id,
                'type' => 'order',
                'status' => 'pending'
            ]);
        }

        return response()->json(['success' => true, 'order' => $sale]);
    });

    Route::put('/orders/{id}', function (Request $request, $id) {
        // Update Draft Implementation Placeholder
        return response()->json(['message' => 'Not implemented yet']);
    });

    Route::delete('/orders/{id}', function ($id) {
        $sale = Sale::where('status', 'draft')->findOrFail($id);

        DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product && $product->stock !== null) {
                        $product->increment('stock', $item->quantity);
                    }
                }
            }
            $sale->items()->delete();
            $sale->delete();

            // If table has no active orders, set available
            if ($sale->table_number) {
                $activeOrders = Sale::where('status', 'draft')->where('table_number', $sale->table_number)->count();
                if ($activeOrders === 0) {
                    Table::where('name', $sale->table_number)->update(['status' => 'available']);
                }
            }
        });

        return response()->json(['success' => true]);
    });

    // 4. Checkout
    Route::post('/orders/{id}/checkout', function (Request $request, $id) {
        $request->validate([
            'payment_method_id' => 'required|integer',
            'amount_paid' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'points_redeemed' => 'nullable|integer',
            'customer_name' => 'nullable|string',
            'member_id' => 'nullable|integer',
        ]);

        $sale = Sale::with('items.product')->where('status', 'draft')->findOrFail($id);

        DB::transaction(function () use ($sale, $request) {
            $paymentMethod = PaymentMethod::find($request->payment_method_id);

            $updateData = [
                'status' => 'completed',
                'payment_status' => 'paid',
                'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT),
                'payment_method_id' => $paymentMethod ? $paymentMethod->id : null,
                'payment_method' => $paymentMethod ? $paymentMethod->name : 'Unknown',
                'amount_paid' => $request->amount_paid,
                'change_amount' => max(0, $request->amount_paid - $sale->final_total),
                'discount' => $request->discount_amount ?? 0,
                'paid_at' => now(),
            ];

            // Override customer name and member if provided at checkout time
            if ($request->filled('customer_name')) {
                $updateData['customer_name'] = $request->customer_name;
            }
            if ($request->filled('member_id')) {
                $updateData['member_id'] = $request->member_id;
            }

            $sale->update($updateData);

            // If table used, set to available
            if ($sale->table_number) {
                $activeOrders = Sale::where('status', 'draft')->where('id', '!=', $sale->id)->where('table_number', $sale->table_number)->count();
                if ($activeOrders === 0) {
                    Table::where('name', $sale->table_number)->update(['status' => 'available']);
                }
            }

            // Loyalty Points Logic
            if ($sale->member_id) {
                $member = \App\Models\Member::find($sale->member_id);
                if ($member) {
                    // Deduct points if redeemed
                    if ($request->points_redeemed) {
                        $member->decrement('points_balance', $request->points_redeemed);
                    }

                    // Earn points
                    $settings = app(GeneralSettings::class);
                    $exchangeRate = $settings->loyalty_point_exchange_rate ?? 10000;
                    if ($exchangeRate > 0) {
                        $pointsEarned = floor($sale->final_total / $exchangeRate);
                        if ($pointsEarned > 0) {
                            $member->increment('points_balance', $pointsEarned);
                            $sale->update(['points_earned' => $pointsEarned]);
                        }
                    }

                    // Record visit for this member
                    $member->recordVisit($sale->final_total, $sale->paid_at ?? now());
                }
            }

            // Create Receipt Print Job if not already printed locally
            if (!$request->input('printed_locally', false)) {
                PrintJob::create([
                    'job_id' => 'RCP_' . uniqid(),
                    'content' => 'RECEIPT',
                    'payload' => [
                        'sale' => $sale->toArray(),
                        'items' => $sale->items->map(function ($i) {
                            return [
                                'product_name' => $i->product->name ?? 'Unknown',
                                'quantity' => $i->quantity,
                                'unit_price' => $i->unit_price,
                                'subtotal' => $i->subtotal,
                                'notes' => $i->notes
                            ];
                        })->toArray(),
                    ],
                    'printer' => 'KASIR',
                    'division' => 'Receipt',
                    'sale_id' => $sale->id,
                    'type' => 'receipt',
                    'status' => 'pending'
                ]);
            }
        });

        return response()->json(['success' => true, 'order' => $sale->refresh()]);
    });

    // 4.5. Transactions History
    Route::get('/transactions', function (Request $request) {
        $limit = $request->query('limit', 50);
        $transactions = Sale::with(['items.product', 'paymentMethod'])
            ->where('status', 'completed')
            ->latest()
            ->limit($limit)
            ->get()->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'table_number' => $sale->table_number,
                    'order_type' => $sale->order_type,
                    'customer_name' => $sale->customer_name,
                    'subtotal' => $sale->subtotal,
                    'discount' => $sale->discount,
                    'tax' => $sale->tax,
                    'final_total' => $sale->final_total,
                    'amount_paid' => $sale->amount_paid,
                    'change_amount' => $sale->change_amount,
                    'payment_method_id' => $sale->payment_method_id,
                    'payment_method' => $sale->payment_method ?? ($sale->paymentMethod->name ?? 'Unknown'),
                    'created_at' => $sale->created_at,
                    'items' => $sale->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name ?? 'Unknown',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                            'notes' => $item->notes
                        ];
                    })
                ];
            });
        return response()->json(['transactions' => $transactions]);
    });

    // 5. Members
    Route::get('/members/search', function (Request $request) {
        $q = $request->query('q');
        if (!$q)
            return response()->json(['members' => []]);

        $members = \App\Models\Member::where('name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->limit(10)
            ->get();

        return response()->json(['members' => $members]);
    });

    Route::get('/members/{id}', function ($id) {
        return response()->json(['member' => \App\Models\Member::findOrFail($id)]);
    });

    Route::get('/members/{id}/history', function ($id) {
        $member = \App\Models\Member::findOrFail($id);

        // 1. Get Top 5 Frequently Ordered Products
        $topProducts = SaleItem::whereHas('sale', function ($q) use ($id) {
            $q->where('member_id', $id)->where('status', 'completed');
        })
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('product:id,name,sell_price,image,type')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->product->name ?? 'Unknown',
                    'price' => (float) ($item->product->sell_price ?? 0),
                    'total_ordered' => (float) $item->total_qty,
                    'emoji' => '🍽️', // Fallback or mapping
                ];
            });

        // 2. Last 5 Transactions
        $lastTransactions = Sale::where('member_id', $id)
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->with('items.product')
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total' => (float) $sale->final_total,
                    'date' => $sale->paid_at,
                    'items' => $sale->items->map(function ($i) {
                        return [
                            'name' => $i->product->name ?? 'Unknown',
                            'qty' => $i->quantity
                        ];
                    })
                ];
            });

        return response()->json([
            'member_id' => $id,
            'top_products' => $topProducts,
            'recent_transactions' => $lastTransactions
        ]);
    });

    Route::post('/members', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:members,phone',
            'email' => 'nullable|email',
        ]);

        $member = \App\Models\Member::create($data);
        return response()->json(['member' => $member]);
    });

    // 5.5. Tables Layout
    Route::patch('/tables/{id}/layout', function (Request $request, $id) {
        $table = Table::findOrFail($id);
        $data = $request->validate([
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'sometimes|numeric',
            'height' => 'sometimes|numeric',
            'shape' => 'sometimes|string|in:square,round',
        ]);

        // Schema auto-migration check
        if (!\Illuminate\Support\Facades\Schema::hasColumn('tables', 'x')) {
            \Illuminate\Support\Facades\Schema::table('tables', function ($table) {
                $table->float('x')->default(0)->nullable();
                $table->float('y')->default(0)->nullable();
                $table->float('width')->default(100)->nullable();
                $table->float('height')->default(100)->nullable();
                $table->string('shape')->default('square')->nullable();
            });
        }

        $table->update($data);
        return response()->json(['success' => true, 'table' => $table]);
    });

    // 6. Discounts Validate
    Route::post('/discounts/validate', function (Request $request) {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric'
        ]);
        // Dummy implementation for now
        if (strtoupper($request->code) === 'PROMO10') {
            return response()->json([
                'valid' => true,
                'discount_amount' => $request->subtotal * 0.1,
                'type' => 'percentage',
                'value' => 10
            ]);
        }
        return response()->json(['valid' => false, 'message' => 'Invalid promo code'], 400);
    });

    // 7. Products Stock Realtime
    Route::get('/products/stock', function () {
        $products = Product::where('is_sellable', true)
            ->with(['recipes.ingredient', 'recipes.unit'])
            ->get()
            ->map(function ($product) {
                $realStock = 0;
                $prepared = 0;
                $potential = 0;

                if ($product->type === 'service') {
                    $realStock = 9999;
                } elseif (in_array($product->type, ['produced', 'bar'])) {
                    $prepared = $product->prepared_stock ?? 0;
                    if ($product->recipes->isNotEmpty()) {
                        $potential = app(RecipeStockChecker::class)->getMaxPortions($product);
                    }
                    $realStock = $prepared + $potential;
                } else {
                    $realStock = $product->stock ?? 0;
                }

                return [
                    'id' => $product->id,
                    'stock' => (float) $realStock,
                    'stock_porsi' => (float) $prepared,
                    'stock_bahan' => (float) $potential,
                ];
            });

        return response()->json(['stocks' => $products]);
    });

    // 8. Tables
    Route::get('/tables', function () {
        return response()->json(['tables' => Table::select('id', 'name', 'slug', 'status')->get()]);
    });

    Route::patch('/tables/{id}/status', function (Request $request, $id) {
        $table = Table::findOrFail($id);
        $request->validate(['status' => 'required|in:available,occupied']);
        $table->update(['status' => $request->status]);
        return response()->json(['table' => $table]);
    });

    // 8.5 Reservations
    Route::get('/reservations', function (Request $request) {
        $date = $request->query('date', now()->toDateString());
        $status = $request->query('status');

        $query = \App\Models\Reservation::with('items.product')
            ->whereDate('reservation_date', $date);

        if ($status) {
            $query->where('status', $status);
        }

        $reservations = $query->orderBy('reservation_date', 'asc')->get();

        return response()->json(['reservations' => $reservations]);
    });

    Route::post('/reservations', function (Request $request) {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'party_size' => 'required|integer|min:1',
            'reservation_date' => 'required|date',
            'special_requests' => 'nullable|string',
            'table_id' => 'nullable|integer|exists:tables,id',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.notes' => 'nullable|string',
        ]);

        $reservation = DB::transaction(function () use ($data) {
            $res = \App\Models\Reservation::create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'party_size' => $data['party_size'],
                'reservation_date' => $data['reservation_date'],
                'special_requests' => $data['special_requests'],
                'table_id' => $data['table_id'] ?? null,
                'status' => 'pending',
            ]);

            if (isset($data['items']) && !empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    $res->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->sell_price ?? 0,
                        'total_price' => ($product->sell_price ?? 0) * $item['quantity'],
                        'note' => $item['notes'] ?? null,
                    ]);
                }
            }

            return $res->load('items.product');
        });

        return response()->json(['success' => true, 'reservation' => $reservation]);
    });

    Route::post('/reservations/{id}/check-in', function ($id) {
        $reservation = \App\Models\Reservation::with('items.product')->findOrFail($id);

        if ($reservation->status === 'seated' || $reservation->status === 'completed' || $reservation->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Status reservasi tidak valid untuk Check-In'], 400);
        }

        $session = CashSession::where('status', 'open')->latest()->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Sesi kasir belum dibuka'], 400);
        }

        $sale = DB::transaction(function () use ($reservation, $session) {
            // 1. Create Sale Header (Draft)
            $sale = Sale::create([
                'invoice_number' => 'RSVP-' . time() . '-' . rand(100, 999),
                'customer_name' => $reservation->customer_name,
                'order_type' => 'Dine In',
                'user_id' => auth()->id() ?? 2,
                'cash_session_id' => $session->id,
                'reservation_id' => $reservation->id,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'subtotal' => 0,
                'tax' => 0,
                'final_total' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;

            // 2. Copy Items
            foreach ($reservation->items as $item) {
                $itemSubtotal = $item->unit_price * $item->quantity;
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item->note,
                ]);
                $subtotal += $itemSubtotal;

                // Deduct stock
                if ($item->product && !in_array($item->product->type, ['service'])) {
                    if ($item->product->stock !== null) {
                        $item->product->decrement('stock', $item->quantity);
                    }
                }
            }

            // 3. Handle Deposit (Negative Item)
            if ($reservation->deposit_amount > 0) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => null, // Just a label for DP
                    'product_name' => 'Down Payment (DP)',
                    'quantity' => 1,
                    'unit_price' => -$reservation->deposit_amount,
                    'subtotal' => -$reservation->deposit_amount,
                    'notes' => 'Potongan DP Reservasi',
                ]);
                $subtotal -= $reservation->deposit_amount;
            }

            // 4. Update Totals
            $settings = app(GeneralSettings::class);
            $enableTax = $settings->enable_tax ?? false;
            $taxRate = $settings->tax_percentage ?? 0;
            $taxAmount = $enableTax ? round($subtotal * ($taxRate / 100)) : 0;
            $finalTotal = max(0, $subtotal + $taxAmount);

            $sale->update([
                'subtotal' => $subtotal,
                'tax' => $taxAmount,
                'total' => $finalTotal,
                'final_total' => $finalTotal,
            ]);

            // 5. Update Reservation Status
            $reservation->update(['status' => 'seated']);

            return $sale->load('items.product');
        });

        return response()->json(['success' => true, 'order' => $sale]);
    });

    Route::patch('/reservations/{id}', function (Request $request, $id) {
        $reservation = \App\Models\Reservation::findOrFail($id);
        $data = $request->validate([
            'status' => 'nullable|in:pending,confirmed,seated,completed,cancelled',
            'special_requests' => 'nullable|string',
            'party_size' => 'nullable|integer|min:1',
            'reservation_date' => 'nullable|date',
        ]);

        $reservation->update($data);
        return response()->json(['success' => true, 'reservation' => $reservation]);
    });

    Route::delete('/reservations/{id}', function ($id) {
        $reservation = \App\Models\Reservation::findOrFail($id);
        $reservation->delete();
        return response()->json(['success' => true]);
    });

    // 9. Reports
    Route::get('/reports/dashboard', function (Request $request) {
        $now = \Carbon\Carbon::now();
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth();
        $sevenDaysAgo = $now->copy()->subDays(6)->startOfDay();

        // 6-Months Trend
        $monthlyRevenue = DB::table('sales')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month_idx, SUM(final_total) as revenue')
            ->where('status', 'completed')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('month_idx')
            ->get()->keyBy('month_idx');

        // HPP Approximation (SaleItem qty * Product base_price)
        $monthlyHpp = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('DATE_FORMAT(sales.created_at, "%Y-%m") as month_idx, SUM(sale_items.quantity * IFNULL(products.base_price, 0)) as hpp')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $sixMonthsAgo)
            ->groupBy('month_idx')
            ->get()->keyBy('month_idx');

        $monthlyExpenses = DB::table('expenses')
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month_idx, SUM(amount) as expenses')
            ->where('status', 'approved')
            ->where('date', '>=', $sixMonthsAgo)
            ->groupBy('month_idx')
            ->get()->keyBy('month_idx');

        $monthsData = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $idx = $m->format('Y-m');
            $monthsData[] = [
                'month' => $m->translatedFormat('M'), // e.g. "Ags"
                'revenue' => (float) ($monthlyRevenue[$idx]->revenue ?? 0),
                'hpp' => (float) ($monthlyHpp[$idx]->hpp ?? 0),
                'expenses' => (float) ($monthlyExpenses[$idx]->expenses ?? 0),
            ];
        }

        // 7-Days Trend
        $dailyRevenue = DB::table('sales')
            ->selectRaw('DATE(created_at) as day_idx, SUM(final_total) as revenue, COUNT(id) as transactions')
            ->where('status', 'completed')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->groupBy('day_idx')
            ->get()->keyBy('day_idx');

        $dailyExpenses = DB::table('expenses')
            ->selectRaw('DATE(date) as day_idx, SUM(amount) as expenses')
            ->where('status', 'approved')
            ->where('date', '>=', $sevenDaysAgo)
            ->groupBy('day_idx')
            ->get()->keyBy('day_idx');

        $dailyHpp = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('DATE(sales.created_at) as day_idx, SUM(sale_items.quantity * IFNULL(products.base_price, 0)) as hpp')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $sevenDaysAgo)
            ->groupBy('day_idx')
            ->get()->keyBy('day_idx');

        $daysData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $idx = $d->format('Y-m-d');
            $daysData[] = [
                'day' => $d->translatedFormat('D'), // e.g. "Sen"
                'revenue' => (float) ($dailyRevenue[$idx]->revenue ?? 0),
                'expenses' => (float) ($dailyExpenses[$idx]->expenses ?? 0),
                'hpp' => (float) ($dailyHpp[$idx]->hpp ?? 0),
                'transactions' => (int) ($dailyRevenue[$idx]->transactions ?? 0),
            ];
        }

        // Current Month Expense Breakdown
        $currentMonthStart = $now->copy()->startOfMonth();
        $expensesBreakdown = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name, SUM(expenses.amount) as amount')
            ->where('expenses.status', 'approved')
            ->where('expenses.date', '>=', $currentMonthStart)
            ->groupBy('expense_categories.name')
            ->orderByDesc('amount')
            ->get();

        $totalMonthExpenses = $expensesBreakdown->sum('amount');
        $expenseIcons = [
            'Listrik & Air' => '⚡',
            'Sewa Tempat' => '🏠',
            'Gaji & Upah' => '👤',
            'Lain-lain' => '📋',
            'Bahan Baku' => '📦'
        ];

        $breakdownData = $expensesBreakdown->map(function ($item) use ($totalMonthExpenses, $expenseIcons) {
            $icon = '💸'; // default
            foreach ($expenseIcons as $key => $i) {
                if (stripos($item->name, $key) !== false) {
                    $icon = $i;
                    break;
                }
            }
            return [
                'name' => $item->name,
                'amount' => (float) $item->amount,
                'pct' => $totalMonthExpenses > 0 ? round(($item->amount / $totalMonthExpenses) * 100) : 0,
                'icon' => $icon
            ];
        });

        return response()->json([
            'months' => $monthsData,
            'days' => $daysData,
            'expenses_breakdown' => $breakdownData,
        ]);
    });
});
