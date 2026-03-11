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
        // ZenithaLMS Themes Table
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('preview_image')->nullable();
            $table->string('version')->default('1.0.0');
            $table->string('author')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('theme_settings')->nullable(); // ZenithaLMS theme configuration
            $table->json('color_scheme')->nullable(); // ZenithaLMS color palette
            $table->json('custom_css')->nullable(); // ZenithaLMS custom CSS
            $table->timestamps();
        });

        // ZenithaLMS Theme Settings Table
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id');
            $table->string('setting_key');
            $table->text('setting_value');
            $table->string('setting_type')->default('text'); // text, color, image, boolean
            $table->timestamps();
        });

        // ZenithaLMS Pages Table
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('template')->default('default'); // default, about, contact, etc.
            $table->string('status')->default('published'); // published, draft, archived
            $table->boolean('is_homepage')->default(false);
            $table->boolean('show_in_menu')->default(true);
            $table->integer('menu_order')->default(0);
            $table->json('seo_data')->nullable(); // ZenithaLMS SEO metadata
            $table->json('page_settings')->nullable(); // ZenithaLMS page settings
            $table->timestamps();
        });

        // ZenithaLMS Page Sections Table
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id');
            $table->string('section_type'); // hero, features, testimonials, etc.
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->json('section_data')->nullable(); // ZenithaLMS section configuration
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ZenithaLMS Popups Table
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('popup_type'); // modal, slide_up, notification
            $table->string('trigger_type'); // page_load, scroll, exit_intent, time_delay
            $table->integer('trigger_value')->nullable(); // scroll percentage, time in seconds
            $table->string('display_rules')->nullable(); // page-specific rules
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('popup_settings')->nullable(); // ZenithaLMS popup configuration
            $table->json('analytics_data')->nullable(); // ZenithaLMS popup analytics
            $table->timestamps();
        });

        // ZenithaLMS Footer Settings Table
        Schema::create('footer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section_name'); // about, links, social, contact
            $table->string('section_title');
            $table->text('section_content')->nullable();
            $table->json('section_links')->nullable(); // ZenithaLMS footer links
            $table->json('section_settings')->nullable(); // ZenithaLMS footer settings
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ZenithaLMS Sidebar Settings Table
        Schema::create('sidebar_settings', function (Blueprint $table) {
            $table->id();
            $table->string('menu_name'); // main, user, admin, etc.
            $table->string('menu_title');
            $table->json('menu_items')->nullable(); // ZenithaLMS sidebar menu items
            $table->json('menu_settings')->nullable(); // ZenithaLMS sidebar settings
            $table->string('user_role')->nullable(); // admin, instructor, student, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ZenithaLMS Menu Items Table
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->nullable(); // For dynamic menus
            $table->string('menu_type')->default('main'); // main, footer, sidebar
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->string('target')->default('_self'); // _self, _blank
            $table->boolean('is_active')->default(true);
            $table->json('menu_settings')->nullable(); // ZenithaLMS menu item settings
            $table->timestamps();
        });

        // ZenithaLMS Banners Table
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image');
            $table->string('link_url')->nullable();
            $table->string('button_text')->nullable();
            $table->string('banner_type')->default('image'); // image, video, slider
            $table->string('position'); // homepage, courses, etc.
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('banner_settings')->nullable(); // ZenithaLMS banner settings
            $table->json('analytics_data')->nullable(); // ZenithaLMS banner analytics
            $table->timestamps();
        });

        // ZenithaLMS Testimonials Table
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->string('company')->nullable();
            $table->string('avatar')->nullable();
            $table->text('testimonial');
            $table->integer('rating')->default(5);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->json('testimonial_settings')->nullable(); // ZenithaLMS testimonial settings
            $table->timestamps();
        });

        // ZenithaLMS Partners Table
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('website')->nullable();
            $table->string('logo');
            $table->text('description')->nullable();
            $table->string('partner_type'); // university, company, affiliate
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->json('partner_settings')->nullable(); // ZenithaLMS partner settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('sidebar_settings');
        Schema::dropIfExists('footer_settings');
        Schema::dropIfExists('popups');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('theme_settings');
        Schema::dropIfExists('themes');
    }
};
