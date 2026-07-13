<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_moderations', function (Blueprint $table) {
            $table->id();
            $table->string('path');                    // /storage/... public path
            $table->string('context_type', 40);        // user.avatar | tindahan.photo | post.images | ...
            $table->unsignedBigInteger('context_id');
            $table->string('verdict', 20);             // safe | flagged | unknown
            $table->string('provider', 20);            // claude | nsfwjs | none
            $table->json('scores')->nullable();        // raw classifier output for auditing
            $table->boolean('action_taken')->default(false); // image removed from the record
            $table->timestamps();

            $table->index(['verdict', 'created_at']);
            $table->index(['context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_moderations');
    }
};
