<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('dishes')->onDelete('set null');
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('dish_id')->nullable()->constrained()->onDelete('set null');
            $table->string('slug')->unique()->comment('URL用スラッグ');
            $table->string('title')->comment('レシピのタイトル');
            $table->text('summary')->nullable()->comment('レシピの概要・紹介文');
            $table->string('serving_size')->nullable()->comment('分量（例: 2人前）');
            $table->string('cooking_time')->nullable()->comment('調理時間（例: 15分）');
            $table->timestamps();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');

            $table->string('group')->nullable()->comment('グループ名（例: 調味料A, 具材）');
            $table->string('name')->comment('材料名');
            $table->string('quantity')->nullable()->comment('分量');
            $table->unsignedSmallInteger('order')->default(0)->comment('表示順');

            $table->timestamps();
        });

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->unsignedSmallInteger('step_number');
            $table->text('description');
            $table->unsignedInteger('start_time_in_seconds')->nullable()->comment('開始時間(秒)');
            $table->unsignedInteger('end_time_in_seconds')->nullable()->comment('終了時間(秒)');
            $table->timestamps();
            $table->index(['recipe_id', 'step_number']);
        });

        Schema::create('recipe_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->foreignId('recipe_step_id')->nullable()->constrained('recipe_steps')->onDelete('cascade');

            $table->text('description')->comment('コツの内容');
            $table->unsignedInteger('start_time_in_seconds')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // 作成と逆順で削除
        Schema::dropIfExists('recipe_tips');
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('dishes');
    }
};
