<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneratedQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('generated_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('source_type'); // 'pdf' or 'notes'
            $table->string('source_id');   // PDF path or JSON array of note IDs
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('generated_questions');
    }
}