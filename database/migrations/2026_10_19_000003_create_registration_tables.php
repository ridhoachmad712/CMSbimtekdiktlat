<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('institution');
            $table->string('position')->nullable();
            $table->string('region')->nullable();
            $table->unsignedInteger('participants_count')->default(1);
            $table->text('notes')->nullable();
            $table->string('status')->default('new');
            $table->string('source')->default('web');
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('nip')->nullable();
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
            $table->string('certificate_number')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
        Schema::dropIfExists('registrations');
    }
};
