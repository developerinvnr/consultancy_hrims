<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id');
            $table->string('document_type', 50);
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->foreignId('uploaded_by_user_id');
            $table->timestamps();
            
            $table->index(['requisition_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_documents');
    }
};