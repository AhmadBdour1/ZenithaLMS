<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Models\Language;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get language from different sources in order of priority
        $locale = $this->getLocale($request);

        // Check if language is supported
        if ($this->isSupported($locale)) {
            App::setLocale($locale);
            
            // Set locale for Carbon
            if (class_exists('Carbon\Carbon')) {
                \Carbon\Carbon::setLocale($locale);
            }
            
            // Store in session for future requests
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Get locale from request
     */
    private function getLocale(Request $request)
    {
        // 1. From URL parameter (highest priority)
        if ($request->has('lang')) {
            return $request->get('lang');
        }

        // 2. From session
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 3. From user preference (if authenticated)
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->preferences && isset($user->preferences['language'])) {
                return $user->preferences['language'];
            }
        }

        // 4. From browser Accept-Language header
        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale && $this->isSupported($browserLocale)) {
            return $browserLocale;
        }

        // 5. From default language
        $defaultLanguage = Language::getDefault();
        if ($defaultLanguage) {
            return $defaultLanguage->code;
        }

        // 6. Fallback to app config
        return config('app.locale', 'en');
    }

    /**
     * Get locale from browser Accept-Language header
     */
    private function getBrowserLocale(Request $request)
    {
        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $languages = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            $values = explode(';q=', $part);
            $lang = $values[0];
            $quality = isset($values[1]) ? (float) $values[1] : 1.0;
            $languages[$lang] = $quality;
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Return first supported language
        foreach ($languages as $lang => $quality) {
            // Extract primary language code (e.g., 'en' from 'en-US')
            $primaryLang = substr($lang, 0, 2);
            
            if ($this->isSupported($lang)) {
                return $lang;
            }
            
            if ($this->isSupported($primaryLang)) {
                return $primaryLang;
            }
        }

        return null;
    }

    /**
     * Check if locale is supported
     */
    private function isSupported($locale)
    {
        // Check if language exists in database
        return Language::where('code', $locale)->where('is_active', true)->exists();
    }
}
