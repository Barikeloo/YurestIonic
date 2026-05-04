<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. La tabla charge_session_payments deja de existir: los pagos viven
        // ahora exclusivamente en sale_payments con charge_session_id como
        // etiqueta opcional.
        Schema::dropIfExists('charge_session_payments');

        // 2. Las columnas congeladas (amount_per_diner, paid_diners_count,
        // prepaid_cents) ya no existen en el dominio: la cuota y la deuda se
        // calculan al vuelo a partir de los SalePayments.
        Schema::table('charge_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('charge_sessions', 'amount_per_diner')) {
                $table->dropColumn('amount_per_diner');
            }
            if (Schema::hasColumn('charge_sessions', 'paid_diners_count')) {
                $table->dropColumn('paid_diners_count');
            }
            if (Schema::hasColumn('charge_sessions', 'prepaid_cents')) {
                $table->dropColumn('prepaid_cents');
            }
        });

        // 3. SalePayment recibe etiquetas opcionales para trazar a qué sesión
        // de cobro pertenece y, dentro de ella, a qué comensal. Sin FK estricta
        // porque sale_payments usa bigInt + composites mientras charge_sessions
        // usa UUID; la integridad se garantiza en el caso de uso.
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->uuid('charge_session_id')->nullable()->after('cash_session_id');
            $table->unsignedSmallInteger('diner_number')->nullable()->after('charge_session_id');
            $table->index(['restaurant_id', 'charge_session_id'], 'sale_payments_restaurant_charge_session_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropIndex('sale_payments_restaurant_charge_session_idx');
            $table->dropColumn(['charge_session_id', 'diner_number']);
        });

        Schema::table('charge_sessions', function (Blueprint $table) {
            $table->integer('amount_per_diner')->default(0);
            $table->integer('paid_diners_count')->default(0);
            $table->integer('prepaid_cents')->default(0);
        });

        Schema::create('charge_session_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('charge_session_id');
            $table->integer('diner_number');
            $table->integer('amount_cents');
            $table->string('payment_method', 20);
            $table->string('status', 20)->default('completed');
            $table->timestamps();

            $table->index('charge_session_id');
            $table->unique(['charge_session_id', 'diner_number', 'status'], 'unique_diner_payment');
            $table->foreign('charge_session_id')
                ->references('id')
                ->on('charge_sessions')
                ->onDelete('cascade');
        });
    }
};
