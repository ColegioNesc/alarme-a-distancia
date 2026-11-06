<?php
/**
 * API de Autenticação (Login, Registro, Verificação)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$action = $data['action'] ?? '';

switch ($action) {
    case 'login':
        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            jsonResponse(['success' => false, 'message' => 'E-mail e senha são obrigatórios.'], 400);
        }

        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, raio_padrao, tipo_alarme_padrao, som_alarme FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($senha, $user['senha'])) {
                jsonResponse(['success' => false, 'message' => 'E-mail ou senha incorretos.'], 401);
            }

            loginUser($user);
            jsonResponse(['success' => true, 'message' => 'Login realizado com sucesso!']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno ao autenticar.'], 500);
        }
        break;

    case 'register':
        $nome = trim($data['nome'] ?? '');
        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';
        $raio = (int)($data['raio_padrao'] ?? 300);
        $tipo = in_array($data['tipo_alarme_padrao'] ?? '', ['som', 'vibracao', 'ambos']) ? $data['tipo_alarme_padrao'] : 'ambos';
        $som = in_array($data['som_alarme'] ?? '', ['sirene', 'beep', 'despertador', 'radar', 'buzina', 'melodia', 'sino', 'urgente', 'custom']) ? $data['som_alarme'] : 'sirene';

        if (empty($nome) || empty($email) || empty($senha)) {
            jsonResponse(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Endereço de e-mail inválido.'], 400);
        }

        if (strlen($senha) < 6) {
            jsonResponse(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'], 400);
        }

        try {
            // Verifica duplicidade
            $checkStmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Este e-mail já está cadastrado.'], 409);
            }

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, raio_padrao, tipo_alarme_padrao, som_alarme) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nome, $email, $hash, $raio, $tipo, $som]);
            $userId = (int)$pdo->lastInsertId();

            $newUser = [
                'id' => $userId,
                'nome' => $nome,
                'email' => $email,
                'raio_padrao' => $raio,
                'tipo_alarme_padrao' => $tipo,
                'som_alarme' => $som
            ];
            loginUser($newUser);

            jsonResponse(['success' => true, 'message' => 'Cadastro concluído com sucesso!']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao registrar usuário: ' . $e->getMessage()], 500);
        }
        break;

    case 'check':
        jsonResponse([
            'isLoggedIn' => isLoggedIn(),
            'user' => isLoggedIn() ? getCurrentUser($pdo) : null
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Ação inválida.'], 400);
}

