<?php

namespace App\Http\Controllers;

use App\Support\CurrentBusiness;
use Illuminate\Http\JsonResponse;

/**
 * manifest de la PWA. Cada subdominio es una app distinta en el celular,
 * así que el nombre y el color salen del negocio.
 */
class ManifestController extends Controller
{
    public function __invoke(CurrentBusiness $current): JsonResponse
    {
        $business = $current->get();
        $name = $business?->name ?? config('app.name');
        $color = $business?->primary_color ?? '#312E81';

        return response()->json([
            'name' => $name,
            'short_name' => mb_strimwidth($name, 0, 12, ''),
            'description' => 'Puntos, sellos y cupones.',
            'id' => '/',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => $color,
            'theme_color' => $color,
            'lang' => 'es-PE',
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
