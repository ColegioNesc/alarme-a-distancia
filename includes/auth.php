<?php
/**
 * Gerenciamento de Sessão e Autenticação
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se há um usuário autenticado na sessão
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Retorna os dados completos do usuário logado
 */
function getCurrentUser(PDO $pdo): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, nome, email, raio_padrao, tipo_alarme_padrao, som_alarme, criado_em 
            FROM usuarios 
            WHERE id = ? 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Inicia a sessão para o usuário informado
 */
function loginUser(array $user): void {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['raio_padrao'] = (int)($user['raio_padrao'] ?? 300);
    $_SESSION['tipo_alarme_padrao'] = $user['tipo_alarme_padrao'] ?? 'ambos';
    $_SESSION['som_alarme'] = $user['som_alarme'] ?? 'sirene';
}

/**
 * Encerra a sessão do usuário
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

