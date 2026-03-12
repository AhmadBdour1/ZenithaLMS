<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use App\Models\Course;
use App\Models\Stuff;
use App\Models\AuraProduct;
use App\Models\AuraPage;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminLanguageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages()
    {
        $languages = Language::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($lang) {
                return [
                    'id' => $lang->id,
                    'code' => $lang->code,
                    'name' => $lang->name,
                    'native_name' => $lang->native_name,
                    'flag' => $lang->flag,
                    'is_default' => $lang->is_default,
                    'is_rtl' => $lang->is_rtl,
                    'progress' => $this->getLanguageProgress($lang->code),
                    'translations_count' => Translation::where('language_code', $lang->code)->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $languages,
        ]);
    }

    /**
     * Get all languages (including inactive)
     */
    public function getAllLanguages()
    {
        $languages = Language::withCount('translations')
            ->orderBy('name')
            ->get()
            ->map(function ($lang) {
                return [
                    'id' => $lang->id,
                    'code' => $lang->code,
                    'name' => $lang->name,
                    'native_name' => $lang->native_name,
                    'flag' => $lang->flag,
                    'is_default' => $lang->is_default,
                    'is_active' => $lang->is_active,
                    'is_rtl' => $lang->is_rtl,
                    'translations_count' => $lang->translations_count,
                    'progress' => $this->getLanguageProgress($lang->code),
                    'created_at' => $lang->created_at,
                    'updated_at' => $lang->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $languages,
        ]);
    }

    /**
     * Create new language
     */
    public function createLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:2|unique:languages,code',
            'name' => 'required|string|max:100',
            'native_name' => 'required|string|max:100',
            'flag' => 'nullable|string|max:50',
            'is_default' => 'boolean',
            'is_rtl' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if language code is in supported list
        if (!$this->isSupportedLanguage($request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Language code not in supported languages list',
            ], 400);
        }

        // If setting as default, remove default from other languages
        if ($request->is_default) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        $language = Language::create([
            'code' => $request->code,
            'name' => $request->name,
            'native_name' => $request->native_name,
            'flag' => $request->flag ?? $this->getDefaultFlag($request->code),
            'is_default' => $request->is_default ?? false,
            'is_rtl' => $request->is_rtl ?? false,
            'is_active' => $request->is_active ?? true,
        ]);

        // Create language directory
        $this->createLanguageDirectory($request->code);

        // Copy default translations if exists
        $this->copyDefaultTranslations($request->code);

        return response()->json([
            'success' => true,
            'message' => 'Language created successfully',
            'data' => $language,
        ], 201);
    }

    /**
     * Update language
     */
    public function updateLanguage(Request $request, $id)
    {
        $language = Language::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'native_name' => 'sometimes|required|string|max:100',
            'flag' => 'nullable|string|max:50',
            'is_default' => 'boolean',
            'is_rtl' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // If setting as default, remove default from other languages
        if ($request->has('is_default') && $request->is_default && !$language->is_default) {
            Language::where('id', '!=', $id)->update(['is_default' => false]);
        }

        $language->update($request->only([
            'name', 'native_name', 'flag', 'is_default', 'is_rtl', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Language updated successfully',
            'data' => $language,
        ]);
    }

    /**
     * Delete language
     */
    public function deleteLanguage($id)
    {
        $language = Language::findOrFail($id);

        // Cannot delete default language
        if ($language->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default language',
            ], 400);
        }

        // Delete translations
        Translation::where('language_code', $language->code)->delete();

        // Delete language directory
        $this->deleteLanguageDirectory($language->code);

        $language->delete();

        return response()->json([
            'success' => true,
            'message' => 'Language deleted successfully',
        ]);
    }

    /**
     * Get translations for a language
     */
    public function getTranslations(Request $request, $languageCode)
    {
        $validator = Validator::make($request->all(), [
            'group' => 'nullable|string',
            'search' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Translation::where('language_code', $languageCode);

        if ($request->group) {
            $query->where('group', $request->group);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('key', 'like', '%' . $request->search . '%')
                  ->orWhere('value', 'like', '%' . $request->search . '%');
            });
        }

        $translations = $query->orderBy('group')
            ->orderBy('key')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $translations,
        ]);
    }

    /**
     * Create or update translation
     */
    public function upsertTranslation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'required|string|exists:languages,code',
            'group' => 'required|string|max:50',
            'key' => 'required|string|max:255',
            'value' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $translation = Translation::updateOrCreate(
            [
                'language_code' => $request->language_code,
                'group' => $request->group,
                'key' => $request->key,
            ],
            [
                'value' => $request->value,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Translation saved successfully',
            'data' => $translation,
        ]);
    }

    /**
     * Bulk upsert translations
     */
    public function bulkUpsertTranslations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'required|string|exists:languages,code',
            'translations' => 'required|array',
            'translations.*.group' => 'required|string|max:50',
            'translations.*.key' => 'required|string|max:255',
            'translations.*.value' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $languageCode = $request->language_code;
        $translations = $request->translations;
        $count = 0;

        DB::transaction(function () use ($languageCode, $translations, &$count) {
            foreach ($translations as $translationData) {
                Translation::updateOrCreate(
                    [
                        'language_code' => $languageCode,
                        'group' => $translationData['group'],
                        'key' => $translationData['key'],
                    ],
                    [
                        'value' => $translationData['value'],
                    ]
                );
                $count++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully saved {$count} translations",
            'count' => $count,
        ]);
    }

    /**
     * Delete translation
     */
    public function deleteTranslation($id)
    {
        $translation = Translation::findOrFail($id);
        $translation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Translation deleted successfully',
        ]);
    }

    /**
     * Get translation groups
     */
    public function getTranslationGroups()
    {
        $groups = Translation::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    /**
     * Get language progress
     */
    public function getLanguageProgress($languageCode)
    {
        $totalKeys = Translation::where('language_code', 'en')->distinct('key')->count('key');
        $translatedKeys = Translation::where('language_code', $languageCode)
            ->where('value', '!=', '')
            ->distinct('key')
            ->count('key');

        $progress = $totalKeys > 0 ? round(($translatedKeys / $totalKeys) * 100, 2) : 0;

        return [
            'total_keys' => $totalKeys,
            'translated_keys' => $translatedKeys,
            'missing_keys' => $totalKeys - $translatedKeys,
            'progress_percentage' => $progress,
        ];
    }

    /**
     * Get missing translations
     */
    public function getMissingTranslations($languageCode)
    {
        $englishKeys = Translation::where('language_code', 'en')
            ->distinct('key')
            ->pluck('key');

        $translatedKeys = Translation::where('language_code', $languageCode)
            ->distinct('key')
            ->pluck('key');

        $missingKeys = $englishKeys->diff($translatedKeys);

        $missingTranslations = Translation::where('language_code', 'en')
            ->whereIn('key', $missingKeys)
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->map(function ($translation) use ($languageCode) {
                return [
                    'group' => $translation->group,
                    'key' => $translation->key,
                    'english_value' => $translation->value,
                    'translated_value' => '',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $missingTranslations,
        ]);
    }

    /**
     * Import translations from file
     */
    public function importTranslations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'required|string|exists:languages,code',
            'file' => 'required|file|mimes:json,csv,xlsx',
            'format' => 'required|in:json,csv,xlsx',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $languageCode = $request->language_code;
        $format = $request->format;

        try {
            $translations = $this->parseTranslationFile($file, $format);
            $count = 0;

            DB::transaction(function () use ($languageCode, $translations, &$count) {
                foreach ($translations as $group => $keys) {
                    foreach ($keys as $key => $value) {
                        Translation::updateOrCreate(
                            [
                                'language_code' => $languageCode,
                                'group' => $group,
                                'key' => $key,
                            ],
                            [
                                'value' => $value,
                            ]
                        );
                        $count++;
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$count} translations",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import translations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export translations to file
     */
    public function exportTranslations(Request $request, $languageCode)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:json,csv,xlsx',
            'group' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Translation::where('language_code', $languageCode);

        if ($request->group) {
            $query->where('group', $request->group);
        }

        $translations = $query->orderBy('group')->orderBy('key')->get();

        try {
            $filename = $this->exportTranslationFile($translations, $languageCode, $request->format);

            return response()->json([
                'success' => true,
                'message' => 'Translations exported successfully',
                'filename' => $filename,
                'download_url' => route('admin.languages.download-export', $filename),
                'count' => $translations->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export translations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download exported file
     */
    public function downloadExport($filename)
    {
        $filepath = storage_path('app/exports/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->download($filepath);
    }

    /**
     * Get language statistics
     */
    public function getLanguageStatistics()
    {
        $languages = Language::withCount('translations')->get();

        $statistics = [
            'total_languages' => $languages->count(),
            'active_languages' => $languages->where('is_active', true)->count(),
            'default_language' => $languages->where('is_default', true)->first(),
            'rtl_languages' => $languages->where('is_rtl', true)->count(),
            'total_translations' => $languages->sum('translations_count'),
            'language_progress' => $languages->mapWithKeys(function ($lang) {
                return [$lang->code => $this->getLanguageProgress($lang->code)];
            }),
            'most_translated' => $languages->sortByDesc('translations_count')->take(5),
            'least_translated' => $languages->sortBy('translations_count')->take(5),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Set default language
     */
    public function setDefaultLanguage($id)
    {
        $language = Language::findOrFail($id);

        // Remove default from all languages
        Language::where('is_default', true)->update(['is_default' => false]);

        // Set new default
        $language->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Default language updated successfully',
            'data' => $language,
        ]);
    }

    /**
     * Sync translations from source
     */
    public function syncTranslations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_language' => 'required|string|exists:languages,code',
            'target_languages' => 'required|array',
            'target_languages.*' => 'required|string|exists:languages,code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sourceLanguage = $request->source_language;
        $targetLanguages = $request->target_languages;
        $syncedCount = 0;

        foreach ($targetLanguages as $targetLanguage) {
            $sourceTranslations = Translation::where('language_code', $sourceLanguage)->get();
            
            foreach ($sourceTranslations as $sourceTranslation) {
                Translation::updateOrCreate(
                    [
                        'language_code' => $targetLanguage,
                        'group' => $sourceTranslation->group,
                        'key' => $sourceTranslation->key,
                    ],
                    [
                        'value' => '', // Keep empty for manual translation
                    ]
                );
                $syncedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synced {$syncedCount} translation keys",
            'synced_count' => $syncedCount,
        ]);
    }

    // Helper methods
    private function isSupportedLanguage($code)
    {
        $supportedLanguages = [
            'en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'zh', 'ko', 'ar',
            'hi', 'tr', 'pl', 'nl', 'sv', 'da', 'no', 'fi', 'el', 'he', 'th',
            'vi', 'id', 'ms', 'tl', 'ur', 'bn', 'ta', 'te', 'mr', 'gu', 'kn',
            'ml', 'pa', 'si', 'my', 'km', 'lo', 'ka', 'hy', 'az', 'kk', 'ky',
            'uz', 'tg', 'mn', 'bo', 'dz', 'ne', 'as', 'or', 'sa', 'sd', 'ks',
            'ps', 'ku', 'am', 'so', 'sw', 'zu', 'xh', 'af', 'is', 'mt', 'cy',
            'ga', 'gd', 'eu', 'ca', 'gl', 'ast', 'lb', 'fy', 'nl', 'de', 'pl',
            'cs', 'sk', 'hu', 'sl', 'hr', 'bs', 'sr', 'bg', 'mk', 'ro', 'uk',
            'be', 'ru', 'et', 'lv', 'lt', 'fi', 'sv', 'da', 'no', 'is', 'fo',
            'ga', 'gd', 'cy', 'kw', 'br', 'oc', 'ca', 'eu', 'gl', 'ast', 'lb',
            'fy', 'nl', 'de', 'pl', 'cs', 'sk', 'hu', 'sl', 'hr', 'bs', 'sr',
            'bg', 'mk', 'ro', 'uk', 'be', 'ru', 'et', 'lv', 'lt', 'fi', 'sv',
            'da', 'no', 'is', 'fo', 'ga', 'gd', 'cy', 'kw', 'br', 'oc', 'ca',
            'eu', 'gl', 'ast', 'lb', 'fy'
        ];

        return in_array($code, $supportedLanguages);
    }

    private function getDefaultFlag($code)
    {
        $flags = [
            'en' => '🇺🇸', 'es' => '🇪🇸', 'fr' => '🇫🇷', 'de' => '🇩🇪', 'it' => '🇮🇹',
            'pt' => '🇵🇹', 'ru' => '🇷🇺', 'ja' => '🇯🇵', 'zh' => '🇨🇳', 'ko' => '🇰🇷',
            'ar' => '🇸🇦', 'hi' => '🇮🇳', 'tr' => '🇹🇷', 'pl' => '🇵🇱', 'nl' => '🇳🇱',
            'sv' => '🇸🇪', 'da' => '🇩🇰', 'no' => '🇳🇴', 'fi' => '🇫🇮', 'el' => '🇬🇷',
            'he' => '🇮🇱', 'th' => '🇹🇭', 'vi' => '🇻🇳', 'id' => '🇮🇩', 'ms' => '🇲🇾',
            'tl' => '🇵🇭', 'ur' => '🇵🇰', 'bn' => '🇧🇩', 'ta' => '🇮🇳', 'te' => '🇮🇳',
            'mr' => '🇮🇳', 'gu' => '🇮🇳', 'kn' => '🇮🇳', 'ml' => '🇮🇳', 'pa' => '🇮🇳',
            'si' => '🇱🇰', 'my' => '🇲🇲', 'km' => '🇰🇭', 'lo' => '🇱🇦', 'ka' => '🇬🇪',
            'hy' => '🇦🇲', 'az' => '🇦🇿', 'kk' => '🇰🇿', 'ky' => '🇰🇬', 'uz' => '🇺🇿',
            'tg' => '🇹🇯', 'mn' => '🇲🇳', 'bo' => '🇧🇹', 'dz' => '🇧🇹', 'ne' => '🇳🇵',
            'as' => '🇮🇳', 'or' => '🇮🇳', 'sa' => '🇮🇳', 'sd' => '🇵🇰', 'ks' => '🇮🇳',
            'ps' => '🇦🇫', 'ku' => '🇹🇷', 'am' => '🇪🇹', 'so' => '🇸🇴', 'sw' => '🇰🇪',
            'zu' => '🇿🇦', 'xh' => '🇿🇦', 'af' => '🇿🇦', 'is' => '🇮🇸', 'mt' => '🇲🇹',
            'cy' => '🏴󠁧󠁢󠁷󠁬󠁳󠁿', 'ga' => '🇮🇪', 'gd' => '🏴󠁧󠁢󠁳󠁣󠁴󠁿', 'eu' => '🏴󠁥󠁳󠁰󠁶󠁿',
            'ca' => '🏴󠁥󠁳󠁣󠁴󠁿', 'gl' => '🏴󠁥󠁳󠁣󠁴󠁿', 'ast' => '🏴󠁥󠁳󠁣󠁴󠁿', 'lb' => '🇱🇺',
            'fy' => '🇳🇱', 'cs' => '🇨🇿', 'sk' => '🇸🇰', 'hu' => '🇭🇺', 'sl' => '🇸🇮',
            'hr' => '🇭🇷', 'bs' => '🇧🇦', 'sr' => '🇷🇸', 'bg' => '🇧🇬', 'mk' => '🇲🇰',
            'ro' => '🇷🇴', 'uk' => '🇺🇦', 'be' => '🇧🇾', 'et' => '🇪🇪', 'lv' => '🇱🇻',
            'lt' => '🇱🇹', 'fo' => '🇫🇴', 'kw' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'br' => '🇧🇷',
            'oc' => '🏴󠁥󠁳󠁣󠁴󠁿'
        ];

        return $flags[$code] ?? '🌐';
    }

    private function createLanguageDirectory($code)
    {
        $path = resource_path("lang/{$code}");
        
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        // Create common group files
        $commonGroups = ['auth', 'validation', 'pagination', 'passwords', 'app'];
        
        foreach ($commonGroups as $group) {
            $filePath = $path . "/{$group}.php";
            
            if (!File::exists($filePath)) {
                File::put($filePath, "<?php\n\nreturn [\n    // {$group} translations\n];\n");
            }
        }
    }

    private function deleteLanguageDirectory($code)
    {
        $path = resource_path("lang/{$code}");
        
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }

    private function copyDefaultTranslations($code)
    {
        $sourcePath = resource_path('lang/en');
        $targetPath = resource_path("lang/{$code}");

        if (File::exists($sourcePath)) {
            $files = File::allFiles($sourcePath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $targetFile = $targetPath . '/' . $file->getFilename();
                    
                    if (!File::exists($targetFile)) {
                        File::copy($file->getPathname(), $targetFile);
                    }
                }
            }
        }
    }

    private function parseTranslationFile($file, $format)
    {
        $content = file_get_contents($file->getPathname());

        switch ($format) {
            case 'json':
                $data = json_decode($content, true);
                return $this->flattenTranslations($data);
                
            case 'csv':
                $translations = [];
                $lines = explode("\n", $content);
                
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $parts = str_getcsv($line);
                        if (count($parts) >= 3) {
                            $group = $parts[0];
                            $key = $parts[1];
                            $value = $parts[2];
                            $translations[$group][$key] = $value;
                        }
                    }
                }
                return $translations;
                
            case 'xlsx':
                // Would use PhpSpreadsheet here
                // For now, return empty array
                return [];
                
            default:
                throw new \Exception("Unsupported format: {$format}");
        }
    }

    private function flattenTranslations($array, $prefix = '')
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenTranslations($value, $prefix . $key . '.'));
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        
        return $result;
    }

    private function exportTranslationFile($translations, $languageCode, $format)
    {
        $filename = "translations_{$languageCode}_" . date('Y-m-d_H-i-s') . ".{$format}";
        $filepath = storage_path("app/exports/{$filename}");

        // Create exports directory if not exists
        if (!File::exists(storage_path('app/exports'))) {
            File::makeDirectory(storage_path('app/exports'), 0755, true);
        }

        switch ($format) {
            case 'json':
                $data = [];
                
                foreach ($translations as $translation) {
                    $keys = explode('.', $translation->key);
                    $current = &$data;
                    
                    foreach ($keys as $key) {
                        if (!isset($current[$key])) {
                            $current[$key] = [];
                        }
                        $current = &$current[$key];
                    }
                    
                    $current = $translation->value;
                }
                
                file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
                
            case 'csv':
                $csvContent = "group,key,value\n";
                
                foreach ($translations as $translation) {
                    $csvContent .= "{$translation->group},{$translation->key},\"{$translation->value}\"\n";
                }
                
                file_put_contents($filepath, $csvContent);
                break;
                
            case 'xlsx':
                // Would use PhpSpreadsheet here
                // For now, create a simple CSV
                $this->exportTranslationFile($translations, $languageCode, 'csv');
                $filename = str_replace('.xlsx', '.csv', $filename);
                break;
        }

        return $filename;
    }
}
