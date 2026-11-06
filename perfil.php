<?php
/**
 * Página de Perfil e Preferências
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser($pdo);
$mensagemSucesso = '';
$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_preferencias') {
    $raio = (int)($_POST['raio_padrao'] ?? 300);
    $tipo = in_array($_POST['tipo_alarme_padrao'] ?? '', ['som', 'vibracao', 'ambos']) ? $_POST['tipo_alarme_padrao'] : 'ambos';
    $som = in_array($_POST['som_alarme'] ?? '', ['sirene', 'beep', 'despertador', 'radar', 'buzina', 'melodia', 'sino', 'urgente', 'custom']) ? $_POST['som_alarme'] : 'sirene';

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET raio_padrao = ?, tipo_alarme_padrao = ?, som_alarme = ? WHERE id = ?");
        $stmt->execute([$raio, $tipo, $som, $_SESSION['user_id']]);
        $_SESSION['raio_padrao'] = $raio;
        $_SESSION['tipo_alarme_padrao'] = $tipo;
        $_SESSION['som_alarme'] = $som;
        $mensagemSucesso = 'Preferências salvas com sucesso!';
        $user = getCurrentUser($pdo);
    } catch (Exception $e) {
        $mensagemErro = 'Erro ao atualizar preferências.';
    }
}

// Favoritos
$stmtFav = $pdo->prepare("SELECT id, titulo, endereco, lat, lng, raio_preferido FROM destinos_favoritos WHERE usuario_id = ? ORDER BY titulo ASC");
$stmtFav->execute([$_SESSION['user_id']]);
$favoritos = $stmtFav->fetchAll();

$pageTitle = "Meu Perfil - Alarme a Distância";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-3">
    <!-- Card de Dados e Preferências -->
    <div class="col-md-6">
        <div class="card card-app shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-gear me-2 text-primary"></i> Preferências Padrão</h5>
            </div>
            <div class="card-body">
                <?php if ($mensagemSucesso): ?>
                    <div class="alert alert-success py-2 small"><?= $mensagemSucesso ?></div>
                <?php endif; ?>
                <?php if ($mensagemErro): ?>
                    <div class="alert alert-danger py-2 small"><?= $mensagemErro ?></div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label text-muted small mb-0">Nome do Usuário</label>
                    <div class="fw-bold fs-6 text-dark"><?= escape($user['nome']) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small mb-0">E-mail Cadastrado</label>
                    <div class="fw-semibold text-secondary"><?= escape($user['email']) ?></div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="salvar_preferencias">
                    <div class="mb-3">
                        <label for="raio_padrao" class="form-label fw-bold small">Raio Padrão de Aviso:</label>
                        <select class="form-select" id="raio_padrao" name="raio_padrao">
                            <option value="150" <?= $user['raio_padrao'] == 150 ? 'selected' : '' ?>>150 metros</option>
                            <option value="200" <?= $user['raio_padrao'] == 200 ? 'selected' : '' ?>>200 metros</option>
                            <option value="300" <?= $user['raio_padrao'] == 300 ? 'selected' : '' ?>>300 metros</option>
                            <option value="500" <?= $user['raio_padrao'] == 500 ? 'selected' : '' ?>>500 metros</option>
                            <option value="1000" <?= $user['raio_padrao'] == 1000 ? 'selected' : '' ?>>1 km</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_alarme_padrao" class="form-label fw-bold small">Tipo de Alarme Padrão:</label>
                        <select class="form-select" id="tipo_alarme_padrao" name="tipo_alarme_padrao">
                            <option value="ambos" <?= $user['tipo_alarme_padrao'] == 'ambos' ? 'selected' : '' ?>>🔔 Som + Vibração</option>
                            <option value="som" <?= $user['tipo_alarme_padrao'] == 'som' ? 'selected' : '' ?>>🔊 Apenas Som</option>
                            <option value="vibracao" <?= $user['tipo_alarme_padrao'] == 'vibracao' ? 'selected' : '' ?>>📳 Apenas Vibração</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="som_alarme" class="form-label fw-bold small mb-0">Som do Alarme:</label>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 small" id="btnPreviewPerfilSound">
                                <i class="bi bi-play-fill"></i> Ouvir
                            </button>
                        </div>
                        <select class="form-select" id="som_alarme" name="som_alarme">
                            <option value="sirene" <?= $user['som_alarme'] == 'sirene' ? 'selected' : '' ?>>🚨 Sirene de Emergência</option>
                            <option value="beep" <?= $user['som_alarme'] == 'beep' ? 'selected' : '' ?>>⏱️ Bip Digital Rápido</option>
                            <option value="despertador" <?= $user['som_alarme'] == 'despertador' ? 'selected' : '' ?>>⏰ Despertador Clássico</option>
                            <option value="radar" <?= $user['som_alarme'] == 'radar' ? 'selected' : '' ?>>📡 Pulso de Sonar / Radar</option>
                            <option value="buzina" <?= $user['som_alarme'] == 'buzina' ? 'selected' : '' ?>>📢 Buzina de Trânsito</option>
                            <option value="melodia" <?= $user['som_alarme'] == 'melodia' ? 'selected' : '' ?>>🎵 Melodia Despertador</option>
                            <option value="sino" <?= $user['som_alarme'] == 'sino' ? 'selected' : '' ?>>🔔 Carrilhão de Sino</option>
                            <option value="urgente" <?= $user['som_alarme'] == 'urgente' ? 'selected' : '' ?>>⚠️ Alarme Nuclear / Urgente</option>
                            <option value="custom" <?= $user['som_alarme'] == 'custom' ? 'selected' : '' ?>>📁 Áudio do Celular/PC (Personalizado)</option>
                        </select>

                        <!-- Box para upload de som no perfil -->
                        <div id="customPerfilSoundBox" class="mt-2 p-2 rounded border bg-light-subtle d-none">
                            <label for="customPerfilAudioFile" class="form-label small fw-bold mb-1">
                                <i class="bi bi-upload me-1"></i> Escolher áudio personalizado:
                            </label>
                            <input type="file" id="customPerfilAudioFile" class="form-control form-control-sm" accept="audio/*">
                            <div id="customPerfilAudioFeedback" class="form-text small text-success mt-1"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="selectTemaPerfil" class="form-label fw-bold small">Tema Visual:</label>
                        <select class="form-select" id="selectTemaPerfil" onchange="ThemeManager.setTheme(this.value)">
                            <option value="light">☀️ Modo Claro (Padrão)</option>
                            <option value="dark">🌙 Modo Noturno</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold">
                        <i class="bi bi-save me-1"></i> Salvar Preferências
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Card de Destinos Favoritos -->
    <div class="col-md-6">
        <div class="card card-app shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-star-fill text-warning me-2"></i> Locais Salvos</h5>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddFavorito">
                    <i class="bi bi-plus-lg me-1"></i> Novo Local
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($favoritos)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-geo-alt fs-2"></i>
                        <p class="small mt-2">Nenhum favorito cadastrado. Salve pontos como Casa ou Trabalho para selecionar com 1 clique.</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($favoritos as $fav): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-bold text-dark"><i class="bi bi-pin-map text-danger me-1"></i> <?= escape($fav['titulo']) ?></div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?= escape($fav['endereco']) ?></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removerFavorito(<?= $fav['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Favorito -->
<div class="modal fade" id="modalAddFavorito" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 16px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Novo Local Favorito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body py-2">
                <div class="mb-3">
                    <label for="favTitulo" class="form-label small fw-bold">Nome do Local (Ex: Casa, Trabalho):</label>
                    <input type="text" class="form-control" id="favTitulo" placeholder="Ex: Casa">
                </div>
                <div class="mb-3">
                    <label for="favEndereco" class="form-label small fw-bold">Endereço:</label>
                    <input type="text" class="form-control" id="favEndereco" placeholder="Digite o endereço...">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="favLat" class="form-label small fw-bold">Latitude:</label>
                        <input type="number" step="any" class="form-control form-control-sm" id="favLat" placeholder="-23.5505">
                    </div>
                    <div class="col-6">
                        <label for="favLng" class="form-label small fw-bold">Longitude:</label>
                        <input type="number" step="any" class="form-control form-control-sm" id="favLng" placeholder="-46.6333">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary rounded-pill fw-bold" id="btnSalvarFavorito">Salvar Local</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Sincroniza tema no select do perfil
    const selectTema = document.getElementById('selectTemaPerfil');
    if (selectTema) {
        selectTema.value = localStorage.getItem('app_theme') || 'light';
    }

    // Prévia e upload de som no perfil
    const somSelect = document.getElementById('som_alarme');
    const btnPreview = document.getElementById('btnPreviewPerfilSound');
    const customBox = document.getElementById('customPerfilSoundBox');
    const customFileInput = document.getElementById('customPerfilAudioFile');
    const customFeedback = document.getElementById('customPerfilAudioFeedback');

    const updatePerfilCustomBox = () => {
        if (somSelect.value === 'custom') {
            customBox.classList.remove('d-none');
            const savedName = localStorage.getItem('app_custom_sound_name');
            if (savedName) {
                customFeedback.textContent = 'Áudio carregado: ' + savedName;
            }
        } else {
            customBox.classList.add('d-none');
        }
    };
    if (somSelect) {
        somSelect.addEventListener('change', () => {
            updatePerfilCustomBox();
            SoundSystem.preview(somSelect.value);
        });
        updatePerfilCustomBox();
    }

    if (btnPreview && somSelect) {
        btnPreview.addEventListener('click', () => {
            SoundSystem.preview(somSelect.value, 2000);
            UI.showToast('Tocando prévia do som: ' + somSelect.options[somSelect.selectedIndex].text, 'info');
        });
    }

    if (customFileInput) {
        customFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                SoundSystem.saveCustomAudioFile(file, (ok, msg) => {
                    if (ok) {
                        customFeedback.textContent = 'Áudio carregado: ' + msg;
                        UI.showToast('Áudio salvo com sucesso!', 'success');
                        SoundSystem.preview('custom', 2500);
                    } else {
                        UI.showToast(msg, 'danger');
                    }
                });
            }
        });
    }

    const btnSalvar = document.getElementById('btnSalvarFavorito');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', async () => {
            const titulo = document.getElementById('favTitulo').value.trim();
            const endereco = document.getElementById('favEndereco').value.trim();
            const lat = parseFloat(document.getElementById('favLat').value);
            const lng = parseFloat(document.getElementById('favLng').value);

            if (!titulo || !endereco || isNaN(lat) || isNaN(lng)) {
                UI.showToast('Preencha o título, endereço e coordenadas válidas.', 'warning');
                return;
            }

            try {
                const resp = await fetch('api/favoritos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'adicionar', titulo, endereco, lat, lng })
                });
                const data = await resp.json();
                if (data.success) {
                    UI.showToast('Local salvo com sucesso!', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    UI.showToast(data.message, 'danger');
                }
            } catch (e) {
                UI.showToast('Erro ao salvar local.', 'danger');
            }
        });
    }
});

function removerFavorito(id) {
    UI.confirmAction(
        'Excluir Favorito',
        'Tem certeza que deseja excluir este local favorito?',
        async () => {
            try {
                const resp = await fetch('api/favoritos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'excluir', id })
                });
                const data = await resp.json();
                if (data.success) {
                    UI.showToast('Local removido.', 'info');
                    setTimeout(() => location.reload(), 600);
                } else {
                    UI.showToast(data.message, 'danger');
                }
            } catch (e) {
                UI.showToast('Erro ao excluir favorito.', 'danger');
            }
        }
    );
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
