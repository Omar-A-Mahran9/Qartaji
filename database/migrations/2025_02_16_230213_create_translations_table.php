<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale'); // Language code (e.g., en, fr, ar)
            $table->string('field');  // Field name (e.g., title, description, name)
            $table->text('value');    // Translated content

            // Polymorphic relationship
            $table->morphs('translatable');
            $table->timestamps();

            // Prevent duplicate translations for the same field and locale
            $table->unique(['locale', 'field', 'translatable_type', 'translatable_id'], 'unique_translation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('translations');
    }
};
