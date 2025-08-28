<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('account_receivable_id');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'debit_card', 'check', 'other'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->string('voucher_path')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Índices
            $table->index(['school_id', 'payment_date']);
            $table->index(['school_id', 'status']);
            $table->index('account_receivable_id');
            $table->index('reference_number');
            $table->index('created_by');

            // Claves foráneas
            $table->foreign('account_receivable_id')->references('id')->on('accounts_receivable')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};