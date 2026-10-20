<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Jejak impor WordPress: wp_id untuk impor ulang tanpa duplikat,
            // wp_slug untuk redirect 301 dari alamat lama (/{slug}/).
            $table->unsignedBigInteger('wp_id')->nullable()->unique()->after('id');
            $table->string('wp_slug')->nullable()->index()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['wp_id']);
            $table->dropIndex(['wp_slug']);
            $table->dropColumn(['wp_id', 'wp_slug']);
        });
    }
};
