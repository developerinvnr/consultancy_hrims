<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgreementDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('agreement_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id');
            $table->string('candidate_code')->index();
            
            // Simple: unsigned or signed
            $table->enum('document_type', ['unsigned', 'signed']);
            
            $table->string('agreement_number');
            $table->string('agreement_path'); // S3 path
            
            $table->integer('uploaded_by_user_id');
            $table->string('uploaded_by_role'); // 'hr_admin', 'submitter'
            
            $table->timestamps();
            
            $table->index(['candidate_id', 'document_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('agreement_documents');
    }
}