<?php
/**
 * API para Registro e Atualização de Viagens / Trajetos
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$action = $data['action'] ?? '';
$userId = isLoggedIn() ? $_SESSION['user_id'] : null;

switch ($action) {
    case 'iniciar':
        $destinoNome = trim($data['destino_nome'] ?? 'Destino Marcado');
        $destinoLat  = filter_var($data['destino_lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $destinoLng  = filter_var($data['destino_lng'] ?? null, FILTER_VALIDATE_FLOAT);
        $raioMetros  = (int)($data['raio_metros'] ?? 300);
        $tipoAlarme  = $data['tipo_alarme'] ?? 'ambos';
        $somAlarme   = $data['som_alarme'] ?? 'sirene';

        if ($destinoLat === false || $destinoLng === false) {
            jsonResponse(['success' => false, 'message' => 'Coordenadas do destino inválidas.'], 400);
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO viagens (usuario_id, destino_nome, destino_lat, destino_lng, raio_metros, tipo_alarme, som_alarme, status, acionado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', 0)
            ");
            $stmt->execute([$userId, $destinoNome, $destinoLat, $destinoLng, $raioMetros, $tipoAlarme, $somAlarme]);
            $tripId = (int)$pdo->lastInsertId();

            jsonResponse(['success' => true, 'trip_id' => $tripId]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao registrar trajeto: ' . $e->getMessage()], 500);
        }
        break;

    case 'atualizar':
        $tripId = (int)($data['trip_id'] ?? 0);
        $status = $data['status'] ?? 'ativo';
        $acionado = !empty($data['acionado']) ? 1 : 0;

        if ($tripId <= 0) {
            jsonResponse(['success' => true, 'message' => 'Trajeto sem persistência']);
        }

        try {
            $stmt = $pdo->prepare("UPDATE viagens SET status = ?, acionado = ? WHERE id = ?");
            $stmt->execute([$status, $acionado, $tripId]);
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao atualizar viagem'], 500);
        }
        break;

    case 'concluir':
        $tripId = (int)($data['trip_id'] ?? 0);
        if ($tripId <= 0) {
            jsonResponse(['success' => true]);
        }

        try {
            $stmt = $pdo->prepare("UPDATE viagens SET status = 'concluido', acionado = 1, concluido_em = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$tripId]);
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao concluir viagem'], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Ação não reconhecida.'], 400);
}

