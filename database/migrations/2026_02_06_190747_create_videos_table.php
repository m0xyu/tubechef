<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('video_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('published_at');
            $table->unsignedBigInteger('view_count')->nullable();
            $table->unsignedBigInteger('like_count')->nullable();
            $table->unsignedBigInteger('comment_count')->nullable();
            $table->json('topic_categories')->nullable();
            $table->timestamp('fetched_at')->useCurrent();
            $table->string('category_id')->nullable()->comment('YouTubeカテゴリID');
            $table->unsignedInteger('duration')->nullable()->comment('動画の長さ（秒）');
            $table->string('recipe_generation_status')->default('pending');
            $table->text('recipe_generation_error_message')->nullable();
            $table->timestamps();

            $table->index('view_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
