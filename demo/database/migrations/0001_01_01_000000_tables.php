<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('single_image_examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('variations');
            $table->json('image');
            $table->timestamps();
        });

        Schema::create('image_collection_examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('images');
            $table->timestamps();
        });
    }
};
