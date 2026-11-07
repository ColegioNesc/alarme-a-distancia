<?php
/**
 * API de Geocodificação Ultra-Rápida com cURL e fallback
 * Suporta busca por texto (?q=...) e busca reversa (?lat=...&lng=...)
 */
header('Content-Type: application/json; charset=utf-8');

function curlFetch($url, $timeout = 2) {
    if (!function_exists('curl_init')) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AlarmeApp/2.0\r\n",
                'timeout' => $timeout
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        return @file_get_contents($url, false, stream_context_create($opts));
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AlarmeApp/2.0'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// -------------------------------------------------------------
// 1. Busca Reversa por Coordenadas (lat / lng)
// -------------------------------------------------------------
$lat = filter_var($_GET['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($_GET['lng'] ?? ($_GET['lon'] ?? null), FILTER_VALIDATE_FLOAT);

if ($lat !== false && $lng !== false && $lat !== null && $lng !== null) {
    $reverseResult = null;

    // 1.1 Tenta Photon Reverse
    $photonRevUrl = "https://photon.komoot.io/reverse?lat={$lat}&lon={$lng}&lang=pt";
    $rawRev = curlFetch($photonRevUrl, 2);
    if ($rawRev) {
        $pData = json_decode($rawRev, true);
        if (!empty($pData['features'][0]['properties'])) {
            $props = $pData['features'][0]['properties'];
            $main = $props['name'] ?? ($props['street'] ?? '');
            $subParts = array_filter([
                $props['street'] ?? '',
                $props['housenumber'] ?? '',
                $props['district'] ?? ($props['suburb'] ?? ''),
                $props['city'] ?? '',
                $props['state'] ?? ''
            ]);
            $full = implode(', ', array_unique(array_filter(array_merge([$main], $subParts))));
            if ($full) {
                $reverseResult = [
                    'success' => true,
                    'title' => $main ?: $full,
                    'display_name' => $full,
                    'lat' => $lat,
                    'lng' => $lng
                ];
            }
        }
    }

    // 1.2 Fallback Nominatim Reverse
    if (!$reverseResult) {
        $nomRevUrl = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&addressdetails=1";
        $rawNom = curlFetch($nomRevUrl, 2);
        if ($rawNom) {
            $nData = json_decode($rawNom, true);
            if (!empty($nData['display_name'])) {
                $reverseResult = [
                    'success' => true,
                    'title' => explode(',', $nData['display_name'])[0] ?? 'Local Selecionado',
                    'display_name' => $nData['display_name'],
                    'lat' => $lat,
                    'lng' => $lng
                ];
            }
        }
    }

    if ($reverseResult) {
        echo json_encode($reverseResult, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'title' => 'Ponto Marcado',
            'display_name' => "Ponto ({$lat}, {$lng})",
            'lat' => $lat,
            'lng' => $lng
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// -------------------------------------------------------------
// 2. Busca Textual por Termo (?q=...)
// -------------------------------------------------------------
$query = trim($_GET['q'] ?? '');

if (empty($query) || mb_strlen($query) < 2) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$results = [];

// 2.1 Tenta Photon Komoot (Baseado no OpenStreetMap)
$photonUrl = 'https://photon.komoot.io/api/?q=' . urlencode($query) . '&limit=6&lang=pt';
$raw = curlFetch($photonUrl, 2);

if ($raw) {
    $data = json_decode($raw, true);
    if (!empty($data['features'])) {
        foreach ($data['features'] as $feat) {
            $coords = $feat['geometry']['coordinates'] ?? [];
            $props = $feat['properties'] ?? [];
            if (!empty($coords) && count($coords) >= 2) {
                $main = $props['name'] ?? ($props['street'] ?? 'Local');
                $subParts = array_filter([
                    $props['street'] ?? '',
                    $props['housenumber'] ?? '',
                    $props['district'] ?? ($props['suburb'] ?? ''),
                    $props['city'] ?? '',
                    $props['state'] ?? '',
                    $props['country'] ?? ''
                ]);
                $full = implode(', ', array_unique(array_filter(array_merge([$main], $subParts))));

                $results[] = [
                    'title' => $main,
                    'display_name' => $full ?: $main,
                    'lat' => (float)$coords[1],
                    'lng' => (float)$coords[0],
                    'source' => 'photon'
                ];
            }
        }
    }
}

// 2.2 Se Photon não encontrou nada, consulta Nominatim OpenStreetMap
if (empty($results)) {
    $nomUrl = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($query) . '&countrycodes=br&limit=6&addressdetails=1';
    $rawNom = curlFetch($nomUrl, 3);
    if ($rawNom) {
        $nomData = json_decode($rawNom, true);
        if (is_array($nomData)) {
            foreach ($nomData as $item) {
                $results[] = [
                    'title' => explode(',', $item['display_name'])[0] ?? 'Endereço',
                    'display_name' => $item['display_name'] ?? 'Endereço',
                    'lat' => (float)$item['lat'],
                    'lng' => (float)$item['lon'],
                    'source' => 'nominatim'
                ];
            }
        }
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
exit;
