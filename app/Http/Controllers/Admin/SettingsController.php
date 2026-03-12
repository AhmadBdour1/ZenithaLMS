<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct(
        private SettingService $settingService
    ) {}

    /**
     * Display the settings page.
     */
    public function index()
    {
        Gate::authorize('view_settings');
        
        $settings = $this->settingService->allGrouped();
        $groupedSettings = collect($settings)->groupBy('group');
        
        return view('admin.settings.index', compact('groupedSettings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        Gate::authorize('update_settings');
        
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.type' => ['required', Rule::in(['string', 'boolean', 'integer', 'float', 'json'])],
        ]);

        foreach ($validated['settings'] as $settingData) {
            $key = $settingData['key'];
            $value = $settingData['value'];
            $type = $settingData['type'];
            
            // Convert JSON string to array if needed
            if ($type === 'json' && is_string($value)) {
                $value = json_decode($value, true);
            }
            
            // Validate based on type
            $validator = Validator::make(['value' => $value], [
                'value' => $this->getValidationRule($type),
            ]);
            
            if ($validator->fails()) {
                return back()
                    ->with('error', "Invalid value for setting {$key}: " . $validator->errors()->first())
                    ->withInput();
            }
            
            $this->settingService->set($key, $value, $type);
        }

        return back()
            ->with('success', 'Settings updated successfully!');
    }

    /**
     * Get validation rule for setting type.
     */
    private function getValidationRule(string $type): string
    {
        return match ($type) {
            'string' => 'string|max:1000',
            'boolean' => 'boolean',
            'integer' => 'integer',
            'float' => 'numeric',
            'json' => 'array',
            default => 'string',
        };
    }
}
