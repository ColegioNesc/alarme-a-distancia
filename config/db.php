<?php
/**
 * Configuração de Conexão com o Banco de Dados (PDO)
 * Projeto: Alarme a Distância
 * Suporta tanto o ambiente Localhost (XAMPP) quanto InfinityFree (Produção)
 */

// Detecta se está executando localmente ou no servidor de hospedagem
$isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']) 
               || php_sapi_name() === 'cli'
               || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);

if ($isLocalhost) {
    // ----------------------------------------------------
    // Configurações para LOCALHOST (XAMPP)
    // ----------------------------------------------------
    $dbHost = 'localhost';
    $dbName = 'alarme_db';
    $dbUser = 'root';
    $dbPass = '';
    $dbPort = '3306';
} else {
    // ----------------------------------------------------
    // Configurações para INFINITYFREE (Produção)
    // Preencha com os dados exibidos no painel do InfinityFree:
    // Painel InfinityFree -> MySQL Details
    // ----------------------------------------------------
    $dbHost = 'sqlXXX.infinityfree.com'; // Ex: sql205.epizy.com ou sqlXXX.infinityfree.com
    $dbName = 'epiz_XXXXXXXX_alarme_db'; // Nome completo do banco no InfinityFree
    $dbUser = 'epiz_XXXXXXXX';           // Usuário MySQL fornecido pelo InfinityFree
    $dbPass = 'SUA_SENHA_DO_INFINITY';   // Senha da sua conta/painel do InfinityFree
    $dbPort = '3306';
}

$charset = 'utf8mb4';
$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    if ($isLocalhost) {
        // Mensagem detalhada em desenvolvimento local
        die("<h3>Erro de Conexão com o Banco de Dados Local:</h3> " . htmlspecialchars($e->getMessage()));
    } else {
        // Mensagem segura em produção
        error_log("Erro de conexão com o banco no InfinityFree: " . $e->getMessage());
        die("<h3>Não foi possível conectar ao banco de dados no momento. Verifique as credenciais no config/db.php.</h3>");
    }
}

