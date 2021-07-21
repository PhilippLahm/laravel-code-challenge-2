<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\ScheduledRepayment;

class CreateScheduledRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->integer('amount');
            $table->integer('outstanding_amount')->default(0);
            $table->string('currency_code');
            $table->date('due_date');
            $table->string('status')->default(ScheduledRepayment::STATUS_DUE);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('loan_id')
                ->references('id')
                ->on('loans')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('scheduled_repayments');
        Schema::enableForeignKeyConstraints();
    }
}
