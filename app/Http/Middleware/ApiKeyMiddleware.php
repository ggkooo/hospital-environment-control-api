<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'API Key obrigatória',
                'message' => 'Informe a API Key no header X-API-Key ou como parâmetro api_key'
            ], 401);
        }

        $validApiKeys = array_filter(config('app.api_keys', []), fn($key) => !empty($key));

        if (empty($validApiKeys) || !in_array($apiKey, $validApiKeys)) {
            return response()->json([
                'error' => 'API Key inválida',
                'message' => 'A API Key fornecida não é válida'
            ], 401);
        }

        return $next($request);
    }
}
