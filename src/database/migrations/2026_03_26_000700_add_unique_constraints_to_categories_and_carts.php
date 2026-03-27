<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->unique('name');
        });

        Schema::table('carts', function (Blueprint $table): void {
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique(['name']);
        });

        Schema::table('carts', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'product_id']);
        });
    }
};
