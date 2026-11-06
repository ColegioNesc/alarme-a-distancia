/**
 * Gerenciador de Interface, Toasts, Modais e Tema Noturno / Claro
 */
const ThemeManager = {
    init() {
        const toggleBtn = document.getElementById('themeToggleBtn');
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        this.updateUI(currentTheme);

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                this.toggleTheme();
            });
        }
    },

    toggleTheme() {
        const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        this.setTheme(next);
    },

    setTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('app_theme', theme);
        this.updateUI(theme);

        // Se o mapa estiver instanciado, atualiza estilo do mapa
        if (window.App && typeof App.onThemeChange === 'function') {
            App.onThemeChange(theme);
        }

        UI.showToast(`Modo ${theme === 'dark' ? 'Noturno' : 'Claro'} ativado.`, 'info');
    },

    updateUI(theme) {
        const icon = document.getElementById('themeIcon');
        const label = document.getElementById('themeLabel');

        if (icon) {
            if (theme === 'dark') {
                icon.className = 'bi bi-sun-fill text-warning';
            } else {
                icon.className = 'bi bi-moon-stars-fill text-white';
            }
        }
        if (label) {
            label.textContent = theme === 'dark' ? 'Claro' : 'Noturno';
        }
    }
};

const UI = {
    /**
     * Exibe notificação em formato Toast
     * @param {string} message 
     * @param {'primary'|'success'|'danger'|'warning'|'info'} type 
     */
    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toastId = 'toast-' + Date.now();
        const iconMap = {
            primary: 'bi-info-circle-fill',
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-bell-fill'
        };
        const icon = iconMap[type] || 'bi-info-circle-fill';

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${type} border-0 shadow`;
        toastEl.id = toastId;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="bi ${icon} me-2 fs-5"></i>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    },

    /**
     * Exibe Modal no padrão do sistema com callback de confirmação
     * Substitui o window.confirm() nativo
     */
    confirmAction(title, message, onConfirm) {
        const modalEl = document.getElementById('appGenericModal');
        if (!modalEl) return;

        document.getElementById('appGenericModalTitle').textContent = title;
        document.getElementById('appGenericModalBody').innerHTML = `<p class="mb-0 text-secondary">${message}</p>`;

        const footer = document.getElementById('appGenericModalFooter');
        footer.innerHTML = `
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-sm btn-danger rounded-pill px-4" id="btnModalConfirmAction">Confirmar</button>
        `;

        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();

        const btnConfirm = document.getElementById('btnModalConfirmAction');
        btnConfirm.onclick = () => {
            bsModal.hide();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        };
    },

    /**
     * Exibe Modal de Alerta
     * Substitui o window.alert() nativo
     */
    showAlert(title, message) {
        const modalEl = document.getElementById('appGenericModal');
        if (!modalEl) return;

        document.getElementById('appGenericModalTitle').textContent = title;
        document.getElementById('appGenericModalBody').innerHTML = `<p class="mb-0 text-secondary">${message}</p>`;

        const footer = document.getElementById('appGenericModalFooter');
        footer.innerHTML = `
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" data-bs-dismiss="modal">OK</button>
        `;

        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
});
