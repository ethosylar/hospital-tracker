<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dt_files', function (Blueprint $table) {
            $table->id();

            $table->string('disk', 50)->default('local'); // local, s3, etc
            $table->string('path', 500);                 // uploads/files/2026/02/...
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();  // sha256

            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['checksum']);
            $table->index(['uploaded_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dt_files');
    }
};