<?php
/**
 * Cabeçalho Comum (Header)
 * Suporte a Tema Claro / Noturno (100% Gratuito sem chaves ou APIs pagas)
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$title = $pageTitle ?? 'Alarme a Distância - Seu Alerta de Ponto de Descida';
$isLogged = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0d6efd">
    <meta name="description" content="Alarme a Distância - Monitoramento em tempo real via GPS para não perder o ponto de descida">
    <title><?= escape($title) ?></title>

    <!-- Script de Inicialização Rápida de Tema (evita piscar de tela branca) -->
    <script>
        (function() {
            const saved = localStorage.getItem('app_theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = saved ? saved : (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <!-- PWA e Metatags Mobile First -->
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Alarme">
    <meta name="application-name" content="Alarme">
    
    <!-- Ícones PWA e Favicons -->
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
    <link rel="icon" type="image/svg+xml" href="assets/icons/icon.svg">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/icons/icon-512.png">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=1.7">
</head>
<body>

<!-- Navbar Mobile First -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container-fluid px-3">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <span class="brand-icon me-2">
                <i class="bi bi-bell-fill"></i>
            </span>
            <span>Alarme a Distância</span>
        </a>

        <!-- Botão Alternador de Modo Noturno / Claro (Sempre visível no topo mobile) -->
        <div class="d-flex align-items-center ms-auto me-2 me-lg-0 ms-lg-2 order-lg-2">
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-2 py-1 d-flex align-items-center" id="themeToggleBtn" title="Alternar Modo Claro / Noturno">
                <i class="bi bi-moon-stars-fill me-1" id="themeIcon"></i>
                <span id="themeLabel" class="small d-none d-sm-inline">Tema</span>
            </button>
        </div>

        <button class="navbar-toggler border-0 order-lg-3" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Menu">
            <i class="bi bi-list fs-2 text-white"></i>
        </button>

        <div class="collapse navbar-collapse order-lg-1" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 mt-2 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active fw-bold' : '' ?>" href="index.php">
                        <i class="bi bi-geo-alt-fill me-1"></i> Monitor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'historico.php' ? 'active fw-bold' : '' ?>" href="historico.php">
                        <i class="bi bi-clock-history me-1"></i> Histórico
                    </a>
                </li>
                <?php if ($isLogged): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active fw-bold' : '' ?>" href="perfil.php">
                            <i class="bi bi-person-gear me-1"></i> Perfil & Preferências
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="nav-user-actions d-flex align-items-center gap-2">
                <?php if ($isLogged): ?>
                    <span class="text-white small me-2 d-none d-lg-inline-flex align-items-center">
                        <i class="bi bi-person-circle me-1 opacity-75"></i> Olá, <strong class="ms-1"><?= escape($_SESSION['user_nome'] ?? 'Usuário') ?></strong>
                    </span>
                    <a href="logout.php" class="btn btn-sm btn-outline-light rounded-pill px-3">
                        <i class="bi bi-box-arrow-right me-1"></i> Sair
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-light text-primary fw-bold rounded-pill px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                    </a>
                    <a href="registro.php" class="btn btn-sm btn-outline-light rounded-pill px-3">
                        <i class="bi bi-person-plus me-1"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Container Principal da Aplicação -->
<main class="container my-3 pb-5" style="max-width: 900px;">
