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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone',18)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city',25);
            $table->string('region',15);
            $table->string('country',2);
            $table->string('tax_office',64)->nullable();
            $table->string('tax_number',20)->nullable();
            $table->string('subscriber_id')->nullable();
            $table->boolean('superadmin')->default(false);
            $table->rememberToken();
            $table->timestamps();

            //indexes
            $table->index('email');
            $table->index('phone');
            $table->index('subscriber_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
