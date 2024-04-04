<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->enum('event',['started','cancelled','reactivated','renewed','failed','extended','shrinked']);
            $table->string('ip')->nullable();
            $table->timestamps();

            //indexes
            $table->index('user_id');
            $table->index('event');
            $table->index(['created_at']);
            $table->index(['created_at','event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
