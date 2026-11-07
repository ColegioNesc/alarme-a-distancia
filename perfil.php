<?php
/**
 * Página de Perfil e Preferências
 * Suporte a Favoritos com Mini-Mapa Interativo e Busca com Autocomplete
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

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_preferencias') {
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
            <div class="card-header py-3">
                <h5 class="fw-bold mb-0 text-body"><i class="bi bi-person-gear me-2 text-primary"></i> Preferências Padrão</h5>
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
                    <div class="fw-bold fs-6 text-body"><?= escape($user['nome']) ?></div>
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
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-body"><i class="bi bi-star-fill text-warning me-2"></i> Locais Salvos</h5>
                <button type="button" class="btn btn-sm btn-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddFavorito" onclick="setTimeout(initFavModalMap, 200);">
                    <i class="bi bi-plus-lg me-1"></i> Novo Local
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($favoritos)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-geo-alt fs-2 text-secondary"></i>
                        <p class="small mt-2 mb-0">Nenhum favorito cadastrado ainda.</p>
                        <small class="text-muted">Salve locais frequentes (como Casa, Trabalho ou Faculdade) para definir o destino com 1 toque.</small>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($favoritos as $fav): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent py-3 border-bottom">
                                <div class="flex-grow-1 me-2" style="min-width: 0;">
                                    <div class="fw-bold text-body text-truncate">
                                        <i class="bi bi-pin-map-fill text-danger me-1"></i> <?= escape($fav['titulo']) ?>
                                    </div>
                                    <small class="text-muted d-block text-truncate" title="<?= escape($fav['endereco']) ?>">
                                        <?= escape($fav['endereco']) ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                    <a href="index.php" class="btn btn-sm btn-outline-primary rounded-pill py-1 px-2"
                                       onclick="sessionStorage.setItem('prefill_lat', '<?= $fav['lat'] ?>'); sessionStorage.setItem('prefill_lng', '<?= $fav['lng'] ?>'); sessionStorage.setItem('prefill_nome', '<?= addslashes(escape($fav['titulo'])) ?>');"
                                       title="Usar agora como destino">
                                        <i class="bi bi-play-fill"></i> Ir
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="removerFavorito(<?= $fav['id'] ?>, this)" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Favorito com Mini-Mapa Interativo e PIN Móvel -->
<div class="modal fade" id="modalAddFavorito" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow border-0" style="border-radius: 18px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-body">
                    <i class="bi bi-star-fill text-warning me-2"></i> Novo Local Favorito
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body py-2">
                <!-- Nome / Título do Local -->
                <div class="mb-3">
                    <label for="favTitulo" class="form-label small fw-bold">Nome do Local (Ex: Casa, Trabalho, Faculdade):</label>
                    <input type="text" class="form-control" id="favTitulo" placeholder="Ex: Casa" autocomplete="off">
                </div>

                <!-- Campo de Endereço com Busca e Sugestões em Tempo Real -->
                <div class="mb-3">
                    <label for="favEndereco" class="form-label small fw-bold">Endereço de Destino:</label>
                    <div class="position-relative">
                        <div class="input-group">
                            <input type="text" class="form-control" id="favEndereco" placeholder="Digite uma rua, bairro ou local..." autocomplete="off">
                            <button class="btn btn-primary px-3 fw-bold" type="button" id="btnFavBuscar" title="Buscar no Mapa">
                                <i class="bi bi-search me-1"></i> Buscar
                            </button>
                        </div>
                        <!-- Lista Dropdown de Sugestões -->
                        <div id="favSearchSuggestions" class="autocomplete-suggestions d-none"></div>
                    </div>
                    <div class="form-text small mt-1">
                        Digite para ver sugestões em tempo real ou selecione e posicione o pino no mapa abaixo.
                    </div>
                </div>

                <!-- Coordenadas Ocultas (substituem os campos numéricos de lat/lng conforme solicitado) -->
                <input type="hidden" id="favLat" value="">
                <input type="hidden" id="favLng" value="">

                <!-- Mini Mapa com PIN Draggable -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold mb-0">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> Posição no Mapa:
                        </label>
                        <span id="favPinStatus" class="badge bg-secondary-subtle text-secondary small">
                            Aguardando marcação
                        </span>
                    </div>

                    <!-- Container do Mapa Leaflet -->
                    <div id="favMap" style="height: 250px; width: 100%; border-radius: 12px; border: 1px solid var(--card-border); z-index: 1;"></div>

                    <div class="d-flex align-items-center justify-content-between mt-2 text-muted small">
                        <span>
                            <i class="bi bi-hand-index-thumb text-primary me-1"></i>
                            Arraste o <b>PIN vermelho</b> ou toque no mapa para ajustar o ponto exato.
                        </span>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small flex-shrink-0 ms-2" id="btnFavMinhaLocalizacao" title="Centralizar onde estou agora">
                            <i class="bi bi-crosshair me-1"></i> Onde estou
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill fw-bold px-4" id="btnSalvarFavorito">
                    <i class="bi bi-check-lg me-1"></i> Salvar Local
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Utilitário para escapar caracteres HTML em renderizações
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// -------------------------------------------------------------
// GERENCIADOR DO MODAL DE FAVORITOS (LEAFLET + AUTOCOMPLETE)
// -------------------------------------------------------------
let favMap = null;
let favMarker = null;
let favDebounceTimer = null;
let favReverseDebounce = null;
let cachedPinIcon = null;

// Ícone SVG vetorizado do PIN vermelho criado sob demanda após o Leaflet estar pronto
function getFavPinIcon() {
    if (!cachedPinIcon && typeof L !== 'undefined') {
        cachedPinIcon = L.divIcon({
            className: 'fav-custom-pin-wrapper',
            html: `
                <div style="position: relative; width: 34px; height: 34px; transform: translate(-50%, -100%);">
                    <svg viewBox="0 0 24 24" width="34" height="34" style="filter: drop-shadow(0 3px 6px rgba(0,0,0,0.45)); cursor: grab;">
                        <path fill="#dc3545" stroke="#ffffff" stroke-width="1.2" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                        <circle cx="12" cy="9" r="2.8" fill="#ffffff"/>
                    </svg>
                </div>
            `,
            iconSize: [0, 0],
            iconAnchor: [0, 0],
            popupAnchor: [0, -34]
        });
    }
    return cachedPinIcon;
}

function initFavModalMap() {
    if (typeof L === 'undefined') {
        setTimeout(initFavModalMap, 150);
        return;
    }

    const container = document.getElementById('favMap');
    if (!container) return;

    if (!favMap) {
        favMap = L.map('favMap', {
            zoomControl: true
        }).setView([-23.5505, -46.6333], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(favMap);

        // Ao clicar diretamente no mapa, move o PIN para o ponto clicado
        favMap.on('click', (e) => {
            const { lat, lng } = e.latlng;
            setFavPinPosition(lat, lng, false);
            reverseGeocodeFav(lat, lng);
        });

        // Tenta pré-centralizar no GPS do usuário caso ainda não haja ponto marcado
        if (navigator.geolocation && !favMarker) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    if (!favMarker && favMap) {
                        favMap.setView([pos.coords.latitude, pos.coords.longitude], 14);
                    }
                },
                () => {},
                { timeout: 4000 }
            );
        }
    }

    // Essencial no Bootstrap Modal: redimensiona o mapa após o modal estar 100% visível
    setTimeout(() => {
        if (favMap) favMap.invalidateSize();
    }, 200);
}

function setFavPinPosition(lat, lng, pan = true) {
    lat = parseFloat(lat);
    lng = parseFloat(lng);
    if (isNaN(lat) || isNaN(lng)) return;

    document.getElementById('favLat').value = lat.toFixed(6);
    document.getElementById('favLng').value = lng.toFixed(6);

    const badge = document.getElementById('favPinStatus');
    if (badge) {
        badge.className = 'badge bg-success-subtle text-success small';
        badge.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i> Ponto definido (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
    }

    if (favMarker) {
        favMarker.setLatLng([lat, lng]);
    } else if (favMap) {
        favMarker = L.marker([lat, lng], {
            icon: getFavPinIcon(),
            draggable: true
        }).addTo(favMap);

        favMarker.bindPopup('<b>Ponto de Descida</b><br><small>Arraste para ajustar</small>');

        // Evento ao soltar o pino arrastado
        favMarker.on('dragend', () => {
            const pos = favMarker.getLatLng();
            document.getElementById('favLat').value = pos.lat.toFixed(6);
            document.getElementById('favLng').value = pos.lng.toFixed(6);
            if (badge) {
                badge.className = 'badge bg-success-subtle text-success small';
                badge.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i> Ponto ajustado (${pos.lat.toFixed(4)}, ${pos.lng.toFixed(4)})`;
            }
            reverseGeocodeFav(pos.lat, pos.lng);
        });
    }

    if (pan && favMap) {
        favMap.setView([lat, lng], Math.max(favMap.getZoom(), 16));
    }
}

// Geocodificação reversa ao soltar o pino no mapa
async function reverseGeocodeFav(lat, lng) {
    clearTimeout(favReverseDebounce);
    favReverseDebounce = setTimeout(async () => {
        try {
            const resp = await fetch(`api/geocode.php?lat=${lat}&lng=${lng}`);
            const data = await resp.json();
            if (data?.display_name) {
                const addrInput = document.getElementById('favEndereco');
                if (addrInput && (!addrInput.value || addrInput.value.startsWith('Ponto'))) {
                    addrInput.value = data.display_name;
                }
                const badge = document.getElementById('favPinStatus');
                if (badge && data.title) {
                    badge.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i> ${escapeHtml(data.title)}`;
                }
            }
        } catch (e) {}
    }, 350);
}

function hideFavSuggestions() {
    const box = document.getElementById('favSearchSuggestions');
    if (box) box.classList.add('d-none');
}

async function fetchFavSuggestions(query) {
    const box = document.getElementById('favSearchSuggestions');
    if (!box) return;

    box.innerHTML = `
        <div class="p-2 text-center text-muted small">
            <span class="spinner-border spinner-border-sm me-2 text-primary"></span> Buscando endereços...
        </div>
    `;
    box.classList.remove('d-none');

    let items = [];

    // 1. Consulta o endpoint local com fallback (rápido e sem bloqueios de CORS)
    try {
        const resp = await fetch(`api/geocode.php?q=${encodeURIComponent(query)}`);
        const data = await resp.json();
        if (Array.isArray(data) && data.length > 0) {
            items = data;
        }
    } catch (errProxy) {}

    // 2. Fallback direto Photon Komoot caso necessário
    if (items.length === 0) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 1800);
            const pResp = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=5&lang=pt`, {
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            const pData = await pResp.json();
            if (pData?.features?.length > 0) {
                items = pData.features.map(f => {
                    const p = f.properties;
                    const c = f.geometry.coordinates;
                    const title = p.name || p.street || 'Local';
                    const parts = [p.street, p.housenumber, p.district || p.suburb, p.city, p.state].filter(Boolean);
                    const full = Array.from(new Set([title, ...parts])).join(', ');
                    return {
                        title: title,
                        display_name: full || title,
                        lat: c[1],
                        lng: c[0]
                    };
                });
            }
        } catch (e) {}
    }

    if (items.length === 0) {
        box.innerHTML = `
            <div class="p-3 text-center text-muted small">
                <i class="bi bi-geo-alt me-1"></i> Nenhuma sugestão encontrada para "<b>${escapeHtml(query)}</b>"
                <div class="mt-1"><small>Pressione <b>Buscar</b> ou toque direto no mapa para marcar.</small></div>
            </div>
        `;
        box.classList.remove('d-none');
        return;
    }

    box.innerHTML = '';
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'suggestion-item d-flex align-items-center py-2 px-3 border-bottom';
        const titleText = item.title || item.display_name.split(',')[0];
        div.innerHTML = `
            <i class="bi bi-geo-alt-fill text-danger fs-5 me-2 flex-shrink-0"></i>
            <div class="flex-grow-1 text-truncate" style="min-width: 0;">
                <div class="fw-bold text-body text-truncate">${escapeHtml(titleText)}</div>
                <small class="text-muted text-truncate d-block">${escapeHtml(item.display_name)}</small>
            </div>
        `;
        div.onclick = (e) => {
            e.stopPropagation();
            selectFavSuggestion(item);
        };
        box.appendChild(div);
    });
}

function selectFavSuggestion(item) {
    const addrInput = document.getElementById('favEndereco');
    const titleInput = document.getElementById('favTitulo');

    if (addrInput) addrInput.value = item.display_name;
    if (titleInput && !titleInput.value.trim()) {
        titleInput.value = item.title || item.display_name.split(',')[0];
    }

    setFavPinPosition(item.lat, item.lng, true);
    hideFavSuggestions();
    if (window.UI) UI.showToast('Local selecionado! Você pode arrastar o pino se desejar ajustar.', 'info');
}

async function searchFavAddress() {
    const input = document.getElementById('favEndereco');
    const query = input ? input.value.trim() : '';
    if (!query) {
        if (window.UI) UI.showToast('Digite um endereço para buscar.', 'warning');
        return;
    }

    const btn = document.getElementById('btnFavBuscar');
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    try {
        let found = null;
        try {
            const resp = await fetch(`api/geocode.php?q=${encodeURIComponent(query)}`);
            const data = await resp.json();
            if (Array.isArray(data) && data.length > 0) {
                found = data[0];
            }
        } catch (e) {}

        if (!found) {
            try {
                const resp = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=1&lang=pt`);
                const data = await resp.json();
                if (data?.features?.length > 0) {
                    const feat = data.features[0];
                    found = {
                        title: feat.properties.name || feat.properties.street || query,
                        display_name: [feat.properties.name, feat.properties.street, feat.properties.city, feat.properties.state].filter(Boolean).join(', ') || query,
                        lat: feat.geometry.coordinates[1],
                        lng: feat.geometry.coordinates[0]
                    };
                }
            } catch (e) {}
        }

        if (found) {
            selectFavSuggestion(found);
        } else {
            if (window.UI) UI.showToast('Endereço não localizado. Toque no mapa para posicionar manualmente.', 'warning');
        }
    } catch (e) {
        if (window.UI) UI.showToast('Erro ao buscar endereço.', 'danger');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
}

// -------------------------------------------------------------
// INICIALIZAÇÃO DE EVENTOS DO PERFIL
// -------------------------------------------------------------
let perfilEventsInitialized = false;
let isSavingFavorito = false;

function setupPerfilEvents() {
    if (perfilEventsInitialized) return;
    perfilEventsInitialized = true;

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
        if (!somSelect || !customBox) return;
        if (somSelect.value === 'custom') {
            customBox.classList.remove('d-none');
            const savedName = localStorage.getItem('app_custom_sound_name');
            if (savedName && customFeedback) {
                customFeedback.textContent = 'Áudio carregado: ' + savedName;
            }
        } else {
            customBox.classList.add('d-none');
        }
    };
    if (somSelect) {
        somSelect.addEventListener('change', () => {
            updatePerfilCustomBox();
            if (window.SoundSystem) SoundSystem.preview(somSelect.value);
        });
        updatePerfilCustomBox();
    }

    if (btnPreview && somSelect) {
        btnPreview.addEventListener('click', () => {
            if (window.SoundSystem) SoundSystem.preview(somSelect.value, 2000);
            if (window.UI) UI.showToast('Tocando prévia do som: ' + somSelect.options[somSelect.selectedIndex].text, 'info');
        });
    }

    if (customFileInput) {
        customFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file && window.SoundSystem) {
                SoundSystem.saveCustomAudioFile(file, (ok, msg) => {
                    if (ok) {
                        if (customFeedback) customFeedback.textContent = 'Áudio carregado: ' + msg;
                        if (window.UI) UI.showToast('Áudio salvo com sucesso!', 'success');
                        SoundSystem.preview('custom', 2500);
                    } else {
                        if (window.UI) UI.showToast(msg, 'danger');
                    }
                });
            }
        });
    }

    // Modal de Favorito: Inicialização do Mapa
    const modalFavoritoEl = document.getElementById('modalAddFavorito');
    if (modalFavoritoEl) {
        modalFavoritoEl.addEventListener('shown.bs.modal', () => {
            initFavModalMap();
        });

        modalFavoritoEl.addEventListener('hidden.bs.modal', () => {
            hideFavSuggestions();
        });
    }

    // Input de Endereço no Modal de Favorito
    const favEnderecoInput = document.getElementById('favEndereco');
    const btnFavBuscar = document.getElementById('btnFavBuscar');
    const btnFavMinhaLoc = document.getElementById('btnFavMinhaLocalizacao');

    if (favEnderecoInput) {
        favEnderecoInput.addEventListener('input', (e) => {
            clearTimeout(favDebounceTimer);
            const query = e.target.value.trim();
            if (query.length < 2) {
                hideFavSuggestions();
                return;
            }
            favDebounceTimer = setTimeout(() => fetchFavSuggestions(query), 220);
        });

        favEnderecoInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchFavAddress();
            }
        });
    }

    if (btnFavBuscar) {
        btnFavBuscar.addEventListener('click', () => {
            searchFavAddress();
        });
    }

    if (btnFavMinhaLoc) {
        btnFavMinhaLoc.addEventListener('click', () => {
            if (!navigator.geolocation) {
                if (window.UI) UI.showToast('GPS não suportado neste dispositivo.', 'warning');
                return;
            }
            if (window.UI) UI.showToast('Obtendo sinal do GPS...', 'info');
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    setFavPinPosition(pos.coords.latitude, pos.coords.longitude, true);
                    reverseGeocodeFav(pos.coords.latitude, pos.coords.longitude);
                    if (window.UI) UI.showToast('Pino posicionado na sua localização atual!', 'success');
                },
                () => {
                    if (window.UI) UI.showToast('Não foi possível obter a localização do GPS.', 'danger');
                },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        });
    }

    // Fechar sugestões ao clicar fora
    document.addEventListener('click', (e) => {
        const box = document.getElementById('favSearchSuggestions');
        const input = document.getElementById('favEndereco');
        if (box && !box.contains(e.target) && e.target !== input) {
            hideFavSuggestions();
        }
    });

    // Salvar Favorito com trava rigorosa contra duplo clique / duplo evento
    const btnSalvar = document.getElementById('btnSalvarFavorito');
    if (btnSalvar) {
        btnSalvar.onclick = async (e) => {
            if (e) e.preventDefault();
            if (isSavingFavorito) return;

            const titulo = document.getElementById('favTitulo').value.trim();
            const endereco = document.getElementById('favEndereco').value.trim();
            const lat = parseFloat(document.getElementById('favLat').value);
            const lng = parseFloat(document.getElementById('favLng').value);

            if (!titulo) {
                if (window.UI) UI.showToast('Preencha o nome do local (Ex: Casa, Trabalho).', 'warning');
                document.getElementById('favTitulo').focus();
                return;
            }

            if (isNaN(lat) || isNaN(lng)) {
                if (window.UI) UI.showToast('Escolha um endereço nas sugestões ou toque no mapa para posicionar o pino.', 'warning');
                return;
            }

            isSavingFavorito = true;
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';

            const enderecoFinal = endereco || `Ponto marcado (${lat.toFixed(4)}, ${lng.toFixed(4)})`;

            try {
                const resp = await fetch('api/favoritos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'adicionar', titulo, endereco: enderecoFinal, lat, lng })
                });
                const data = await resp.json();
                if (data.success) {
                    if (window.UI) UI.showToast('Local salvo com sucesso!', 'success');
                    setTimeout(() => location.reload(), 600);
                } else {
                    if (window.UI) UI.showToast(data.message || 'Erro ao salvar local.', 'danger');
                    isSavingFavorito = false;
                    btnSalvar.disabled = false;
                    btnSalvar.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar Local';
                }
            } catch (err) {
                if (window.UI) UI.showToast('Erro de comunicação com o servidor.', 'danger');
                isSavingFavorito = false;
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar Local';
            }
        };
    }
}

// Inicialização segura única
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupPerfilEvents);
} else {
    setupPerfilEvents();
}

/**
 * Exclusão resiliente de local favorito com feedback visual imediato
 */
window.removerFavorito = function(id, btnElement) {
    if (!id) return;

    const executeDelete = async () => {
        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '<span class="spinner-border spinner-border-sm text-danger" style="width: 1rem; height: 1rem;"></span>';
        }

        try {
            const resp = await fetch('api/favoritos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'excluir', id: parseInt(id, 10) })
            });
            const data = await resp.json();
            if (data.success) {
                if (window.UI) UI.showToast('Local removido com sucesso!', 'info');
                if (btnElement) {
                    const itemLi = btnElement.closest('li');
                    if (itemLi) {
                        itemLi.style.transition = 'all 0.3s ease';
                        itemLi.style.opacity = '0';
                        itemLi.style.transform = 'translateX(20px)';
                        setTimeout(() => itemLi.remove(), 320);
                    }
                }
                setTimeout(() => location.reload(), 600);
            } else {
                if (window.UI) UI.showToast(data.message || 'Erro ao remover local.', 'danger');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = '<i class="bi bi-trash"></i>';
                }
            }
        } catch (e) {
            if (window.UI) UI.showToast('Erro de comunicação ao remover favorito.', 'danger');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = '<i class="bi bi-trash"></i>';
            }
        }
    };

    if (window.UI && typeof UI.confirmAction === 'function') {
        UI.confirmAction(
            'Excluir Favorito',
            'Tem certeza que deseja excluir este local favorito?',
            executeDelete
        );
    } else {
        executeDelete();
    }
};
</script>
