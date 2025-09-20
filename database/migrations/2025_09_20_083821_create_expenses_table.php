<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_id')
                  ->constrained()
                  ->cascadeOnDelete();       // when a group goes, its expenses go too

            $table->foreignId('payer_id')    // who paid
                  ->constrained('members')   // references members.id
                  ->cascadeOnDelete();

            $table->string('description');   // "Groceries", "Taxi"
            $table->integer('amount_cents'); // store money as integer cents
            $table->date('spent_at')->nullable(); // optional: date of the expense

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
