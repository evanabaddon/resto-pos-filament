<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PaymentMethod;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('fund_source')->default('cashier')->comment('cashier, petty_cash, transfer, other');
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->onDelete('set null');
            
            // Set payment_method_id jadi nullable, karena tidak selalu diisi
            $table->foreignId('payment_method_id')->nullable()->change();
        });
        
        // Set payment_method_id = CASH untuk expense cashier yang sudah ada
        $cashPaymentMethod = PaymentMethod::where('code', 'cash')->first();
        if ($cashPaymentMethod) {
            DB::table('expenses')
                ->whereNull('payment_method_id')
                ->update(['payment_method_id' => $cashPaymentMethod->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['cash_session_id']);
            $table->dropColumn(['fund_source', 'cash_session_id']);
            $table->foreignId('payment_method_id')->nullable(false)->change();
        });
    }
};
