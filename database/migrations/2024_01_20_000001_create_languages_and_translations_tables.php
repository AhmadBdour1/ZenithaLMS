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
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique(); // ISO 639-1 language code
            $table->string('name'); // English name
            $table->string('native_name'); // Name in native language
            $table->string('flag', 50)->nullable(); // Flag emoji or icon
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_rtl')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_default');
            $table->index('is_active');
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('language_code', 2); // Foreign key to languages.code
            $table->string('group', 50); // Translation group (auth, validation, etc.)
            $table->string('key', 255); // Translation key
            $table->text('value'); // Translated value
            $table->timestamps();

            // Unique constraint
            $table->unique(['language_code', 'group', 'key']);

            // Indexes
            $table->index('language_code');
            $table->index('group');
            $table->index(['language_code', 'group']);
        });

        // Insert default languages
        DB::table('languages')->insert([
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇺🇸',
                'is_default' => true,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'flag' => '🇸🇦',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'es',
                'name' => 'Spanish',
                'native_name' => 'Español',
                'flag' => '🇪🇸',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'flag' => '🇫🇷',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'de',
                'name' => 'German',
                'native_name' => 'Deutsch',
                'flag' => '🇩🇪',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'zh',
                'name' => 'Chinese',
                'native_name' => '中文',
                'flag' => '🇨🇳',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ja',
                'name' => 'Japanese',
                'native_name' => '日本語',
                'flag' => '🇯🇵',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ko',
                'name' => 'Korean',
                'native_name' => '한국어',
                'flag' => '🇰🇷',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'pt',
                'name' => 'Portuguese',
                'native_name' => 'Português',
                'flag' => '🇵🇹',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ru',
                'name' => 'Russian',
                'native_name' => 'Русский',
                'flag' => '🇷🇺',
                'is_default' => false,
                'is_active' => true,
                'is_rtl' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Insert basic English translations
        $basicTranslations = [
            // Auth group
            ['group' => 'auth', 'key' => 'failed', 'value' => 'These credentials do not match our records.'],
            ['group' => 'auth', 'key' => 'throttle', 'value' => 'Too many login attempts. Please try again in :seconds seconds.'],
            ['group' => 'auth', 'key' => 'password', 'value' => 'The provided password is incorrect.'],
            
            // Validation group
            ['group' => 'validation', 'key' => 'required', 'value' => 'This field is required.'],
            ['group' => 'validation', 'key' => 'email', 'value' => 'Please enter a valid email address.'],
            ['group' => 'validation', 'key' => 'min', 'value' => 'This field must be at least :min characters.'],
            ['group' => 'validation', 'key' => 'max', 'value' => 'This field may not be greater than :max characters.'],
            ['group' => 'validation', 'key' => 'unique', 'value' => 'This value is already taken.'],
            
            // Pagination group
            ['group' => 'pagination', 'key' => 'previous', 'value' => 'Previous'],
            ['group' => 'pagination', 'key' => 'next', 'value' => 'Next'],
            ['group' => 'pagination', 'key' => 'showing', 'value' => 'Showing :first to :last of :total results'],
            
            // App group
            ['group' => 'app', 'key' => 'welcome', 'value' => 'Welcome to ZenithaLMS'],
            ['group' => 'app', 'key' => 'dashboard', 'value' => 'Dashboard'],
            ['group' => 'app', 'key' => 'profile', 'value' => 'Profile'],
            ['group' => 'app', 'key' => 'settings', 'value' => 'Settings'],
            ['group' => 'app', 'key' => 'logout', 'value' => 'Logout'],
            ['group' => 'app', 'key' => 'login', 'value' => 'Login'],
            ['group' => 'app', 'key' => 'register', 'value' => 'Register'],
            ['group' => 'app', 'key' => 'save', 'value' => 'Save'],
            ['group' => 'app', 'key' => 'cancel', 'value' => 'Cancel'],
            ['group' => 'app', 'key' => 'edit', 'value' => 'Edit'],
            ['group' => 'app', 'key' => 'delete', 'value' => 'Delete'],
            ['group' => 'app', 'key' => 'create', 'value' => 'Create'],
            ['group' => 'app', 'key' => 'update', 'value' => 'Update'],
            ['group' => 'app', 'key' => 'search', 'value' => 'Search'],
            ['group' => 'app', 'key' => 'filter', 'value' => 'Filter'],
            ['group' => 'app', 'key' => 'sort', 'value' => 'Sort'],
            ['group' => 'app', 'key' => 'loading', 'value' => 'Loading...'],
            ['group' => 'app', 'key' => 'error', 'value' => 'Error'],
            ['group' => 'app', 'key' => 'success', 'value' => 'Success'],
            ['group' => 'app', 'key' => 'warning', 'value' => 'Warning'],
            ['group' => 'app', 'key' => 'info', 'value' => 'Information'],
        ];

        foreach ($basicTranslations as $translation) {
            DB::table('translations')->insert([
                'language_code' => 'en',
                'group' => $translation['group'],
                'key' => $translation['key'],
                'value' => $translation['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
    }
};
