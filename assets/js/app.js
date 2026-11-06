/**
 * Aplicação Principal: Alarme a Distância
 * Controle de GPS em Tempo Real, Leaflet, Geocodificação (Google / Photon / Nominatim) e Alarme Sonoro
 */
const App = {
    map: null,
    tileLayer: null,
    userMarker: null,
    destMarker: null,
    radiusCircle: null,

    userLat: null,
    userLng: null,
    destLat: null,
    destLng: null,
    destName: '',

    watchId: null,
    wakeLock: null,
    activeTripId: null,
    googleAutocomplete: null,

    tripState: 'inativo', // 'inativo' | 'ativo' | 'pausado' | 'alarmando'

    init() {
        this.initMap();
        this.bindEvents();
        this.checkPrefill();
        this.requestInitialPosition();
    },

    /**
     * Inicializa o mapa com Leaflet e OpenStreetMap (100% Gratuito e sem chaves de API)
     */
    initMap() {
        this.map = L.map('map', {
            zoomControl: true
        }).setView([-23.5505, -46.6333], 13);

        // OpenStreetMap oficial: livre de chaves, sem limites de cota e sem marcas d'água
        this.tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);

        // Clique direto no mapa para definir destino (sempre funcional)
        this.map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            this.setDestination(lat, lng, `Ponto no Mapa (${lat.toFixed(4)}, ${lng.toFixed(4)})`);
        });
    },

    /**
     * Notificação de mudança de tema
     * O modo noturno no mapa é tratado de forma instantânea e gratuita via filtro CSS no OpenStreetMap
     */
    onThemeChange(theme) {
        // Redesenha para garantir consistência visual imediata
        if (this.map) {
            setTimeout(() => this.map.invalidateSize(), 100);
        }
    },

    /**
     * Vincula eventos de botões, inputs, busca e sons
     */
    bindEvents() {
        const btnStart = document.getElementById('btnStartTrip');
        const btnPause = document.getElementById('btnPauseTrip');
        const btnResume = document.getElementById('btnResumeTrip');
        const btnCancel = document.getElementById('btnCancelTrip');
        const btnCenter = document.getElementById('btnCenterUser');
        const btnTest = document.getElementById('btnTestAlarm');
        const btnTurnOff = document.getElementById('btnTurnOffAlarm');
        const btnSearch = document.getElementById('btnSearchDestination');
        const radiusInput = document.getElementById('radiusInput');
        const destInput = document.getElementById('destinationInput');
        const soundSelect = document.getElementById('soundSelect');
        const btnPreviewSound = document.getElementById('btnPreviewSound');
        const customAudioFile = document.getElementById('customAudioFile');
        const customSoundBox = document.getElementById('customSoundBox');
        const customFeedback = document.getElementById('customAudioFeedback');

        if (btnStart) btnStart.onclick = () => this.startMonitoring();
        if (btnPause) btnPause.onclick = () => this.pauseMonitoring();
        if (btnResume) btnResume.onclick = () => this.resumeMonitoring();
        if (btnCancel) btnCancel.onclick = () => this.cancelMonitoring();

        // Centralizar GPS
        if (btnCenter) {
            btnCenter.onclick = () => {
                if (this.userLat && this.userLng) {
                    this.map.setView([this.userLat, this.userLng], 16);
                } else {
                    UI.showToast('Obtendo sinal do GPS...', 'info');
                    this.requestInitialPosition();
                }
            };
        }

        // Testar Alerta
        if (btnTest) {
            btnTest.onclick = () => {
                const tipo = document.getElementById('alarmTypeSelect').value;
                const som = document.getElementById('soundSelect').value;
                SoundSystem.trigger(tipo, som);
                UI.showToast('Testando alerta (toque para desligar no botão de parar)...', 'warning');
                setTimeout(() => SoundSystem.stopAll(), 3500);
            };
        }

        if (btnTurnOff) {
            btnTurnOff.onclick = () => this.turnOffAlarm();
        }

        // Raio
        if (radiusInput) {
            radiusInput.oninput = (e) => {
                const radius = parseInt(e.target.value);
                document.getElementById('radiusLabel').textContent = radius + ' metros';
                document.getElementById('radiusDisplay').textContent = radius + 'm';
                this.updateRadiusCircle(radius);
                this.updateDistanceDisplay();
            };
        }

        // Atalhos de raio rápido
        document.querySelectorAll('.btn-quick-radius').forEach(btn => {
            btn.onclick = () => {
                const r = parseInt(btn.dataset.radius);
                if (radiusInput) {
                    radiusInput.value = r;
                    document.getElementById('radiusLabel').textContent = r + ' metros';
                    document.getElementById('radiusDisplay').textContent = r + 'm';
                }
                this.updateRadiusCircle(r);
                this.updateDistanceDisplay();
            };
        });

        // ----------------------------------------------------
        // BUSCA DE DESTINO (DIGITADA, BOTÃO BUSCAR E ENTER)
        // ----------------------------------------------------
        if (btnSearch && destInput) {
            btnSearch.onclick = () => {
                this.searchAndSetDestination(destInput.value);
            };
        }

        if (destInput) {
            // Tecla Enter no campo de busca
            destInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.searchAndSetDestination(destInput.value);
                }
            });

            // Autocomplete com debounce
            let debounceTimer = null;
            destInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                const query = e.target.value.trim();
                if (query.length < 3) {
                    this.hideSuggestions();
                    return;
                }
                debounceTimer = setTimeout(() => this.fetchSuggestions(query), 350);
            });
        }

        // Fechar sugestões ao clicar fora
        document.addEventListener('click', (e) => {
            const box = document.getElementById('searchSuggestions');
            const destIn = document.getElementById('destinationInput');
            if (box && !box.contains(e.target) && e.target !== destIn) {
                this.hideSuggestions();
            }
        });

        // ----------------------------------------------------
        // GERENCIAMENTO DE SONS E PREVIEWS
        // ----------------------------------------------------
        const updateCustomBox = () => {
            if (!soundSelect || !customSoundBox) return;
            if (soundSelect.value === 'custom') {
                customSoundBox.classList.remove('d-none');
                const savedName = localStorage.getItem('app_custom_sound_name');
                if (savedName && customFeedback) {
                    customFeedback.textContent = 'Áudio carregado: ' + savedName;
                }
            } else {
                customSoundBox.classList.add('d-none');
            }
        };

        if (soundSelect) {
            soundSelect.addEventListener('change', () => {
                updateCustomBox();
                // Toca prévia rápida de 1.5s automaticamente ao mudar
                SoundSystem.preview(soundSelect.value, 1500);
            });
            updateCustomBox();
        }

        if (btnPreviewSound && soundSelect) {
            btnPreviewSound.onclick = () => {
                SoundSystem.preview(soundSelect.value, 2000);
                UI.showToast('Tocando: ' + soundSelect.options[soundSelect.selectedIndex].text, 'info');
            };
        }

        if (customAudioFile) {
            customAudioFile.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    SoundSystem.saveCustomAudioFile(file, (ok, msg) => {
                        if (ok) {
                            if (customFeedback) customFeedback.textContent = 'Áudio carregado: ' + msg;
                            UI.showToast('Arquivo de som personalizado carregado com sucesso!', 'success');
                            SoundSystem.preview('custom', 2500);
                        } else {
                            UI.showToast(msg, 'danger');
                        }
                    });
                }
            });
        }
    },

    /**
     * Busca e define o destino digitado pelo usuário (Enter ou Botão Buscar)
     */
    async searchAndSetDestination(query) {
        query = (query || '').trim();
        if (!query) {
            UI.showToast('Por favor, digite o nome de uma rua, bairro ou local para buscar.', 'warning');
            return;
        }

        const btnSearch = document.getElementById('btnSearchDestination');
        const originalBtnHtml = btnSearch ? btnSearch.innerHTML : '';
        if (btnSearch) {
            btnSearch.disabled = true;
            btnSearch.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Buscando...';
        }

        try {
            // 1. Tenta API Geocoder interna (CORS-safe, consulta Photon e Nominatim)
            let foundLocation = null;

            try {
                const resp = await fetch(`api/geocode.php?q=${encodeURIComponent(query)}`);
                const results = await resp.json();
                if (Array.isArray(results) && results.length > 0) {
                    foundLocation = results[0];
                }
            } catch (errProxy) {
                console.warn('Proxy geocode falhou, tentando fallback direto', errProxy);
            }

            // 2. Se falhar, tenta consulta direta Photon Komoot
            if (!foundLocation) {
                try {
                    const photonResp = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=3&lang=pt`);
                    const photonData = await photonResp.json();
                    if (photonData?.features?.length > 0) {
                        const feat = photonData.features[0];
                        const coords = feat.geometry.coordinates;
                        const props = feat.properties;
                        const label = [props.name, props.street, props.city, props.state].filter(Boolean).join(', ');
                        foundLocation = {
                            lat: coords[1],
                            lng: coords[0],
                            display_name: label || query
                        };
                    }
                } catch (ePhoton) {}
            }

            // 3. Se falhar, tenta consulta direta Nominatim OpenStreetMap
            if (!foundLocation) {
                try {
                    const nomResp = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=br&limit=3`);
                    const nomData = await nomResp.json();
                    if (Array.isArray(nomData) && nomData.length > 0) {
                        foundLocation = {
                            lat: nomData[0].lat,
                            lng: nomData[0].lon,
                            display_name: nomData[0].display_name
                        };
                    }
                } catch (eNom) {}
            }

            // Conclusão da busca
            if (foundLocation && !isNaN(foundLocation.lat) && !isNaN(foundLocation.lng)) {
                this.setDestination(parseFloat(foundLocation.lat), parseFloat(foundLocation.lng), foundLocation.display_name);
                this.hideSuggestions();
                UI.showToast(`Destino encontrado: ${foundLocation.display_name}`, 'success');
            } else {
                UI.showToast('Nenhum endereço encontrado para o texto digitado. Você também pode marcar o ponto clicando direto no mapa!', 'warning');
            }
        } catch (err) {
            console.error('Erro na busca de endereço:', err);
            UI.showToast('Erro ao buscar endereço. Você pode clicar diretamente no mapa para marcar o ponto de descida.', 'danger');
        } finally {
            if (btnSearch) {
                btnSearch.disabled = false;
                btnSearch.innerHTML = originalBtnHtml;
            }
        }
    },

    /**
     * Busca sugestões enquanto o usuário digita
     */
    async fetchSuggestions(query) {
        const box = document.getElementById('searchSuggestions');
        if (!box) return;

        try {
            // Consulta API Geocode interna
            const resp = await fetch(`api/geocode.php?q=${encodeURIComponent(query)}`);
            const data = await resp.json();

            box.innerHTML = '';
            if (!Array.isArray(data) || data.length === 0) {
                box.innerHTML = '<div class="suggestion-item text-muted"><i class="bi bi-info-circle me-1"></i> Pressione Buscar ou Enter para localizar</div>';
                box.classList.remove('d-none');
                return;
            }

            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `<i class="bi bi-geo-alt-fill me-2 text-danger"></i><strong>${item.display_name}</strong>`;
                div.onclick = () => {
                    this.setDestination(parseFloat(item.lat), parseFloat(item.lng), item.display_name);
                    this.hideSuggestions();
                };
                box.appendChild(div);
            });
            box.classList.remove('d-none');
        } catch (err) {
            console.warn('Erro ao carregar sugestões:', err);
        }
    },

    hideSuggestions() {
        const box = document.getElementById('searchSuggestions');
        if (box) box.classList.add('d-none');
    },

    /**
     * Define o destino e ajusta os marcadores no mapa
     */
    setDestination(lat, lng, name = 'Destino') {
        this.destLat = parseFloat(lat);
        this.destLng = parseFloat(lng);
        this.destName = name;

        const input = document.getElementById('destinationInput');
        if (input) input.value = name;

        if (this.destMarker) {
            this.destMarker.setLatLng([this.destLat, this.destLng]);
        } else {
            const destIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
            this.destMarker = L.marker([this.destLat, this.destLng], { icon: destIcon }).addTo(this.map);
        }
        this.destMarker.bindPopup(`<b>Ponto de Descida</b><br>${name}`).openPopup();

        const radius = parseInt(document.getElementById('radiusInput')?.value || 300);
        this.updateRadiusCircle(radius);
        this.updateDistanceDisplay();

        if (this.userLat && this.userLng) {
            const bounds = L.latLngBounds([[this.userLat, this.userLng], [this.destLat, this.destLng]]);
            this.map.fitBounds(bounds, { padding: [50, 50] });
        } else {
            this.map.setView([this.destLat, this.destLng], 15);
        }
    },

    updateRadiusCircle(radius) {
        if (!this.destLat || !this.destLng) return;
        if (this.radiusCircle) {
            this.radiusCircle.setLatLng([this.destLat, this.destLng]);
            this.radiusCircle.setRadius(radius);
        } else {
            this.radiusCircle = L.circle([this.destLat, this.destLng], {
                radius: radius,
                color: '#dc3545',
                fillColor: '#dc3545',
                fillOpacity: 0.2
            }).addTo(this.map);
        }
    },

    /**
     * Solicita posição inicial do GPS
     */
    requestInitialPosition() {
        if (!navigator.geolocation) {
            UI.showAlert('GPS Não Suportado', 'Seu navegador não possui suporte à geolocalização.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.userLat = pos.coords.latitude;
                this.userLng = pos.coords.longitude;
                this.updateUserMarker();
                this.updateDistanceDisplay();
            },
            (err) => {
                console.warn('Aguardando sinal de GPS do dispositivo:', err.message);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    },

    updateUserMarker() {
        if (!this.userLat || !this.userLng) return;
        if (this.userMarker) {
            this.userMarker.setLatLng([this.userLat, this.userLng]);
        } else {
            this.userMarker = L.marker([this.userLat, this.userLng]).addTo(this.map);
            this.userMarker.bindPopup('<b>Você está aqui</b>');
        }
    },

    /**
     * Inicia monitoramento contínuo
     */
    async startMonitoring() {
        const destInput = document.getElementById('destinationInput');

        // Se o usuário digitou mas não clicou em buscar, tenta buscar automaticamente
        if ((!this.destLat || !this.destLng) && destInput && destInput.value.trim() !== '') {
            await this.searchAndSetDestination(destInput.value.trim());
        }

        if (!this.destLat || !this.destLng) {
            UI.showToast('Por favor, digite um destino na busca ou clique no mapa antes de iniciar.', 'warning');
            return;
        }

        if (!navigator.geolocation) {
            UI.showAlert('GPS Indisponível', 'Ative o GPS do seu dispositivo para usar o monitoramento.');
            return;
        }

        // Tenta obter Wake Lock (RNF02)
        try {
            if ('wakeLock' in navigator) {
                this.wakeLock = await navigator.wakeLock.request('screen');
            }
        } catch (e) {
            console.log('Wake Lock indisponível');
        }

        // Registra viagem na API
        const radius = parseInt(document.getElementById('radiusInput')?.value || 300);
        const tipoAlarme = document.getElementById('alarmTypeSelect')?.value || 'ambos';
        const somAlarme = document.getElementById('soundSelect')?.value || 'sirene';

        try {
            const resp = await fetch('api/trajeto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'iniciar',
                    destino_nome: this.destName || 'Destino Marcado',
                    destino_lat: this.destLat,
                    destino_lng: this.destLng,
                    raio_metros: radius,
                    tipo_alarme: tipoAlarme,
                    som_alarme: somAlarme
                })
            });
            const data = await resp.json();
            if (data.trip_id) {
                this.activeTripId = data.trip_id;
            }
        } catch (e) {
            console.log('Monitoramento local ativo');
        }

        this.tripState = 'ativo';
        this.updateControlsUI();

        // Inicia acompanhamento contínuo GPS
        this.watchId = navigator.geolocation.watchPosition(
            (pos) => this.onPositionUpdate(pos),
            (err) => this.onPositionError(err),
            { enableHighAccuracy: true, maximumAge: 0, timeout: 8000 }
        );

        UI.showToast('Monitoramento de trajeto iniciado!', 'success');
    },

    onPositionUpdate(pos) {
        this.userLat = pos.coords.latitude;
        this.userLng = pos.coords.longitude;
        this.updateUserMarker();

        const dist = this.calculateDistance(this.userLat, this.userLng, this.destLat, this.destLng);
        this.updateDistanceDisplay(dist);

        const radius = parseInt(document.getElementById('radiusInput')?.value || 300);

        // Disparo do Alarme (RN-04, RF06, RF07)
        if (this.tripState === 'ativo' && dist <= radius) {
            this.triggerAlarm();
        }
    },

    onPositionError(err) {
        UI.showAlert(
            'GPS Desativado ou Sem Sinal',
            'Não foi possível obter sua localização precisa. Por favor, verifique se o GPS está ativado nas configurações do seu celular.'
        );
    },

    /**
     * Cálculo de distância usando Fórmula de Haversine
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        if (!lat1 || !lon1 || !lat2 || !lon2) return null;
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return Math.round(R * c);
    },

    updateDistanceDisplay(distance = null) {
        const valEl = document.getElementById('currentDistanceValue');
        const unitEl = document.getElementById('currentDistanceUnit');
        const subtext = document.getElementById('distanceSubtext');

        if (distance === null) {
            if (this.userLat && this.userLng && this.destLat && this.destLng) {
                distance = this.calculateDistance(this.userLat, this.userLng, this.destLat, this.destLng);
            }
        }

        if (distance === null || isNaN(distance)) {
            if (valEl) valEl.textContent = '--';
            if (unitEl) unitEl.textContent = 'm';
            return;
        }

        if (distance >= 1000) {
            if (valEl) valEl.textContent = (distance / 1000).toFixed(1);
            if (unitEl) unitEl.textContent = 'km';
        } else {
            if (valEl) valEl.textContent = distance;
            if (unitEl) unitEl.textContent = 'm';
        }

        if (subtext) {
            const radius = parseInt(document.getElementById('radiusInput')?.value || 300);
            if (distance <= radius) {
                subtext.innerHTML = '<span class="text-warning fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> DENTRO DO RAIO DE ALERTA!</span>';
            } else {
                subtext.textContent = `Faltam ${distance - radius > 0 ? (distance - radius) + 'm para o alarme tocar' : 'poucos metros'}.`;
            }
        }
    },

    /**
     * Dispara o alarme sonoro e tela de urgência
     */
    triggerAlarm() {
        this.tripState = 'alarmando';
        const tipoAlarme = document.getElementById('alarmTypeSelect')?.value || 'ambos';
        const somAlarme = document.getElementById('soundSelect')?.value || 'sirene';

        SoundSystem.trigger(tipoAlarme, somAlarme);

        const overlay = document.getElementById('alarmOverlay');
        const destText = document.getElementById('alarmDestinationText');
        if (destText) destText.textContent = this.destName || 'Seu Ponto de Descida';
        if (overlay) overlay.classList.remove('d-none');

        // Atualiza viagem na API
        if (this.activeTripId) {
            fetch('api/trajeto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'atualizar', trip_id: this.activeTripId, status: 'ativo', acionado: 1 })
            }).catch(() => {});
        }
    },

    /**
     * Desliga o alarme manualmente (RF09)
     */
    turnOffAlarm() {
        SoundSystem.stopAll();
        const overlay = document.getElementById('alarmOverlay');
        if (overlay) overlay.classList.add('d-none');

        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        if (this.wakeLock) {
            this.wakeLock.release().catch(() => {});
            this.wakeLock = null;
        }

        if (this.activeTripId) {
            fetch('api/trajeto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'concluir', trip_id: this.activeTripId })
            }).catch(() => {});
        }

        this.tripState = 'inativo';
        this.updateControlsUI();

        UI.showAlert('Viagem Concluída', 'Você chegou ao seu destino! O alarme foi desativado com sucesso.');
    },

    pauseMonitoring() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        this.tripState = 'pausado';
        this.updateControlsUI();
        UI.showToast('Monitoramento pausado.', 'warning');
    },

    resumeMonitoring() {
        this.tripState = 'ativo';
        this.updateControlsUI();
        this.watchId = navigator.geolocation.watchPosition(
            (pos) => this.onPositionUpdate(pos),
            (err) => this.onPositionError(err),
            { enableHighAccuracy: true, maximumAge: 0, timeout: 8000 }
        );
        UI.showToast('Monitoramento retomado.', 'success');
    },

    cancelMonitoring() {
        UI.confirmAction(
            'Cancelar Viagem',
            'Deseja realmente cancelar o monitoramento desta viagem?',
            () => {
                if (this.watchId) {
                    navigator.geolocation.clearWatch(this.watchId);
                    this.watchId = null;
                }
                SoundSystem.stopAll();
                this.tripState = 'inativo';
                this.updateControlsUI();
                UI.showToast('Viagem cancelada.', 'secondary');
            }
        );
    },

    updateControlsUI() {
        const btnStart = document.getElementById('btnStartTrip');
        const btnPause = document.getElementById('btnPauseTrip');
        const btnResume = document.getElementById('btnResumeTrip');
        const btnCancel = document.getElementById('btnCancelTrip');
        const statusBadge = document.getElementById('statusBadge');

        if (this.tripState === 'ativo') {
            btnStart?.classList.add('d-none');
            btnPause?.classList.remove('d-none');
            btnResume?.classList.add('d-none');
            btnCancel?.classList.remove('d-none');
            if (statusBadge) {
                statusBadge.className = 'status-badge-tracker ativo';
                statusBadge.innerHTML = '<span class="badge bg-success me-1">Ativo</span> Monitorando GPS...';
            }
        } else if (this.tripState === 'pausado') {
            btnStart?.classList.add('d-none');
            btnPause?.classList.add('d-none');
            btnResume?.classList.remove('d-none');
            btnCancel?.classList.remove('d-none');
            if (statusBadge) {
                statusBadge.className = 'status-badge-tracker pausado';
                statusBadge.innerHTML = '<span class="badge bg-warning text-dark me-1">Pausado</span> Monitoramento em pausa';
            }
        } else {
            btnStart?.classList.remove('d-none');
            btnPause?.classList.add('d-none');
            btnResume?.classList.add('d-none');
            btnCancel?.classList.add('d-none');
            if (statusBadge) {
                statusBadge.className = 'status-badge-tracker inativo';
                statusBadge.innerHTML = '<span class="badge bg-secondary me-1">Parado</span> Monitoramento inativo';
            }
        }
    },

    checkPrefill() {
        const lat = sessionStorage.getItem('prefill_lat');
        const lng = sessionStorage.getItem('prefill_lng');
        const nome = sessionStorage.getItem('prefill_nome');

        if (lat && lng) {
            this.setDestination(parseFloat(lat), parseFloat(lng), nome || 'Destino');
            sessionStorage.removeItem('prefill_lat');
            sessionStorage.removeItem('prefill_lng');
            sessionStorage.removeItem('prefill_nome');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('map')) {
        App.init();
    }
});
