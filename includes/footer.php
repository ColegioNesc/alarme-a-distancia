</main>

<!-- Overlay de Alarme Urgente em Tela Cheia (RF08, RF09) -->
<div id="alarmOverlay" class="alarm-fullscreen-overlay d-none">
    <div class="alarm-pulsing-box text-center text-white p-4">
        <div class="alarm-bell-icon mb-3">
            <i class="bi bi-bell-fill"></i>
        </div>
        <h1 class="fw-black mb-2 text-uppercase tracking-wider">VOCÊ ESTÁ CHEGANDO!</h1>
        <p class="fs-5 mb-4 text-white-50">Seu ponto de descida está dentro do raio configurado.</p>
        <div class="destination-callout mb-4 p-3 rounded-4 bg-dark bg-opacity-50">
            <small class="text-warning text-uppercase fw-bold d-block mb-1">Destino Alvo:</small>
            <span id="alarmDestinationText" class="fw-bold fs-5 text-white">--</span>
        </div>
        <button id="btnTurnOffAlarm" type="button" class="btn btn-warning btn-lg fw-black px-5 py-3 rounded-pill text-dark shadow-lg fs-4 w-100">
            <i class="bi bi-stop-circle-fill me-2"></i> DESLIGAR ALARME / CHEGUEI
        </button>
    </div>
</div>

<!-- Modal Genérico de Confirmação e Avisos (Regra de Agents: Modais em vez de alert/confirm nativos) -->
<div class="modal fade" id="appGenericModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="appGenericModalTitle">Aviso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body py-3" id="appGenericModalBody">
                <!-- Conteúdo dinâmico -->
            </div>
            <div class="modal-footer border-0 pt-0" id="appGenericModalFooter">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Container Fixo para Toast Notifications do Bootstrap -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;" id="toastContainer"></div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Scripts Modulares da Aplicação -->
<script src="assets/js/ui.js"></script>
<script src="assets/js/audio.js"></script>
<script src="assets/js/app.js"></script>

</body>
</html>

