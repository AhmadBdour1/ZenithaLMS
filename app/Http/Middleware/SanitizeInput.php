<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Get all input data
        $input = $request->all();
        
        // Sanitize string inputs
        $sanitized = $this->sanitizeArray($input);
        
        // Replace the request input with sanitized data
        $request->replace($sanitized);
        
        return $next($request);
    }
    
    /**
     * Recursively sanitize array values
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitize a string to prevent XSS
     */
    private function sanitizeString(string $value): string
    {
        // Remove potentially dangerous characters
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        // Remove script tags and related content
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        
        // Remove other potentially dangerous HTML tags
        $dangerousTags = [
            '<iframe', '</iframe>', '<object', '</object>', 
            '<embed', '</embed>', '<form', '</form>',
            '<input', '<textarea', '</textarea>', '<select', '</select>',
            '<option', '</option>', '<button', '</button>',
            '<link', '<meta', '<style', '</style>'
        ];
        
        foreach ($dangerousTags as $tag) {
            $value = str_ireplace($tag, '', $value);
        }
        
        // Remove JavaScript event handlers
        $value = preg_replace('/on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $value);
        
        // Remove javascript: protocol
        $value = preg_replace('/javascript\s*:/i', '', $value);
        
        return trim($value);
    }
}
