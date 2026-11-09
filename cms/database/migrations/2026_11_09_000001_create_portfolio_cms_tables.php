<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_owner')) {
                $table->boolean('is_owner')->default(true)->after('password');
            }
        });

        Schema::create('content_entries', function (Blueprint $table) {
            $table->id();
            $table->string('group', 64)->index();
            $table->string('key', 128);
            $table->text('value_en')->nullable();
            $table->text('value_fa')->nullable();
            $table->boolean('is_dirty')->default(false);
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('public_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime', 128)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('alt_en')->nullable();
            $table->string('alt_fa')->nullable();
            $table->boolean('is_published')->default(false);
            $table->json('used_by')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category', 64)->nullable();
            $table->string('year', 16)->nullable();
            $table->string('live_url')->nullable();
            $table->string('github_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_dirty')->default(false);
            $table->timestamp('archived_at')->nullable()->index();
            $table->foreignId('cover_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('short_description')->nullable();
            $table->text('full_description')->nullable();
            $table->string('role')->nullable();
            $table->string('category_label')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'locale']);
        });

        Schema::create('project_technologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('project_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('role', 32)->default('gallery');
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->string('source', 32)->default('upload');
            $table->foreignId('pdf_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->json('fields_en')->nullable();
            $table->json('fields_fa')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('seo_entries', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            $table->string('scope', 32);
            $table->string('scope_key', 128)->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('robots', 64)->default('index,follow');
            $table->boolean('is_dirty')->default(false);
            $table->timestamps();
            $table->unique(['locale', 'scope', 'scope_key']);
        });

        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->json('payload');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('site_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('current_publication_id')->nullable()->constrained('publications')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action', 32);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('site_state');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('seo_entries');
        Schema::dropIfExists('resume_versions');
        Schema::dropIfExists('project_media');
        Schema::dropIfExists('project_technologies');
        Schema::dropIfExists('project_translations');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('content_entries');
    }
};
