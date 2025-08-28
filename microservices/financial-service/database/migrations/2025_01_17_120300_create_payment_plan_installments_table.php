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
        Schema::create('payment_plan_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_plan_id');
            $table->integer('installment_number');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamps();

            // Índices
            $table->index('payment_plan_id');
            $table->index(['payment_plan_id', 'installment_number'], 'pmnt_plan_installment_pmnt_plan_id_installment_number_index');
            $table->index(['payment_plan_id', 'status']);
            $table->index(['payment_plan_id', 'due_date']);
            $table->index('payment_id');

            // Claves foráneas
            $table->foreign('payment_plan_id')->references('id')->on('payment_plans')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');

            // Restricción única para evitar duplicados
            $table->unique(['payment_plan_id', 'installment_number'], 'pmnt_plan_installment_pmnt_plan_id_installment_number_unq');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_plan_installments');
    }
};