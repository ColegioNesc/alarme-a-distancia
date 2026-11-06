<?php
/**
 * API de Geocodificação de Endereços (Proxy CORS-safe)
 * Consulta Photon (OpenStreetMap) e Nominatim de forma segura no backend
 */

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');

if (empty($query) || mb_strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// 1. Tenta Photon (Baseado em OSM, super rápido e estruturado)
try {
    $photonUrl = 'https://photon.komoot.io/api/?q=' . urlencode($query) . '&limit=6&lang=pt';
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: AlarmeADistancia/2.0 (contato@alarme.local)\r\n",
            'timeout' => 3
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($photonUrl, false, $context);

    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['features'])) {
            foreach ($data['features'] as $feat) {
                $coords = $feat['geometry']['coordinates'] ?? [];
                $props = $feat['properties'] ?? [];
                if (!empty($coords) && count($coords) >= 2) {
                    $parts = array_filter([
                        $props['name'] ?? '',
                        $props['street'] ?? '',
                        $props['housenumber'] ?? '',
                        $props['district'] ?? ($props['suburb'] ?? ''),
                        $props['city'] ?? '',
                        $props['state'] ?? '',
                        $props['country'] ?? ''
                    ]);
                    $label = implode(', ', array_unique($parts));
                    if (empty($label)) {
                        $label = $props['name'] ?? 'Local encontrado';
                    }

                    $results[] = [
                        'display_name' => $label,
                        'lat' => (float)$coords[1],
                        'lng' => (float)$coords[0],
                        'source' => 'photon'
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    // Fallback silencioso
}

// 2. Se Photon não retornar nada, tenta Nominatim com User-Agent adequado
if (empty($results)) {
    try {
        $nomUrl = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($query) . '&countrycodes=br&limit=5&addressdetails=1';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: AlarmeADistanciaApp/2.0 (app@alarme.local)\r\nAccept-Language: pt-BR,pt;q=0.9\r\n",
                'timeout' => 3
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($nomUrl, false, $context);

        if ($response) {
            $data = json_decode($response, true);
            if (is_array($data)) {
                foreach ($data as $item) {
                    $results[] = [
                        'display_name' => $item['display_name'] ?? 'Endereço',
                        'lat' => (float)$item['lat'],
                        'lng' => (float)$item['lon'],
                        'source' => 'nominatim'
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // Fallback
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
exit;

