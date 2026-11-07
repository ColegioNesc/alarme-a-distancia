<?php
/**
 * API para Gerenciamento de Destinos Favoritos
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Faça login para gerenciar favoritos.'], 401);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$action = $data['action'] ?? '';
$userId = $_SESSION['user_id'];

switch ($action) {
    case 'adicionar':
        $titulo = trim($data['titulo'] ?? '');
        $endereco = trim($data['endereco'] ?? '');
        $lat = filter_var($data['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($data['lng'] ?? null, FILTER_VALIDATE_FLOAT);
        $raio = (int)($data['raio_preferido'] ?? 300);

        if (!$titulo || !$endereco || $lat === false || $lng === false) {
            jsonResponse(['success' => false, 'message' => 'Preencha título, endereço e coordenadas válidas.'], 400);
        }

        // Previne duplicação acidental (se requisições duplicadas chegarem quase juntas)
        $stmtCheck = $pdo->prepare("
            SELECT id FROM destinos_favoritos 
            WHERE usuario_id = ? AND titulo = ? AND lat = ? AND lng = ?
            AND criado_em >= (NOW() - INTERVAL 5 SECOND)
            LIMIT 1
        ");
        $stmtCheck->execute([$userId, $titulo, $lat, $lng]);
        if ($stmtCheck->fetch()) {
            jsonResponse(['success' => true, 'message' => 'Destino favorito salvo com sucesso!']);
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO destinos_favoritos (usuario_id, titulo, endereco, lat, lng, raio_preferido)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $titulo, $endereco, $lat, $lng, $raio]);
            jsonResponse(['success' => true, 'message' => 'Destino favorito salvo com sucesso!']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao salvar favorito.'], 500);
        }
        break;

    case 'excluir':
        $favId = (int)($data['id'] ?? 0);
        if ($favId <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID de favorito inválido.'], 400);
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM destinos_favoritos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$favId, $userId]);
            jsonResponse(['success' => true, 'message' => 'Favorito removido com sucesso.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao remover favorito.'], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Ação inválida.'], 400);
}

