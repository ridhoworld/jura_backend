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
        // 1. Journals (Buku Jurnal)
        Schema::create('journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('color_hex')->default('#4F46E5');
            $table->string('icon')->nullable()->default('book');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });

        // 2. Moods
        Schema::create('moods', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('emoji', 32);
            $table->tinyInteger('score')->default(3); // 1 - 5
            $table->timestamps();
        });

        // 3. Entries
        Schema::create('entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('journal_id')->constrained('journals')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->timestamp('entry_date')->useCurrent();
            $table->string('location_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('weather_info')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'entry_date']);
            $table->index(['user_id', 'journal_id', 'entry_date']);
            $table->index(['user_id', 'is_pinned', 'is_favorite']);
            $table->index(['user_id', 'deleted_at']);
        });

        // 4. Entry Moods (Many-to-Many)
        Schema::create('entry_moods', function (Blueprint $table) {
            $table->foreignUuid('entry_id')->constrained('entries')->onDelete('cascade');
            $table->foreignId('mood_id')->constrained('moods')->onDelete('cascade');
            $table->primary(['entry_id', 'mood_id']);
        });

        // 5. Tags
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        // 6. Entry Tags (Many-to-Many)
        Schema::create('entry_tags', function (Blueprint $table) {
            $table->foreignUuid('entry_id')->constrained('entries')->onDelete('cascade');
            $table->foreignUuid('tag_id')->constrained('tags')->onDelete('cascade');
            $table->primary(['entry_id', 'tag_id']);
        });

        // 7. Attachments
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entry_id')->constrained('entries')->onDelete('cascade');
            $table->enum('file_type', ['image', 'audio', 'video', 'document'])->default('image');
            $table->text('file_url');
            $table->text('thumbnail_url')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('caption')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();

            $table->index(['entry_id', 'order_index']);
        });

        // 8. Prompts
        Schema::create('prompts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category')->default('Refleksi Diri');
            $table->text('question_text');
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        // 9. Templates
        Schema::create('templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->longText('content_structure');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // 10. Reminders
        Schema::create('reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('reminder_time'); // "21:00"
            $table->json('days_of_week'); // [1,2,3,4,5,6,7]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('prompts');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('entry_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('entry_moods');
        Schema::dropIfExists('entries');
        Schema::dropIfExists('moods');
        Schema::dropIfExists('journals');
    }
};

