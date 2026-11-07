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
     * Garante a existência do elemento Modal Genérico no DOM
     */
    getOrCreateModalElement() {
        let modalEl = document.getElementById('appGenericModal');
        if (!modalEl) {
            modalEl = document.createElement('div');
            modalEl.className = 'modal fade';
            modalEl.id = 'appGenericModal';
            modalEl.tabIndex = -1;
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow border-0" style="border-radius: 16px;">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold" id="appGenericModalTitle">Aviso</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body py-3" id="appGenericModalBody"></div>
                        <div class="modal-footer border-0 pt-0" id="appGenericModalFooter">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modalEl);
        }
        return modalEl;
    },

    /**
     * Exibe Modal no padrão do sistema com callback de confirmação
     * 100% no padrão visual da aplicação (NUNCA usa confirm nativo do navegador)
     */
    confirmAction(title, message, onConfirm) {
        const modalEl = this.getOrCreateModalElement();
        const titleEl = document.getElementById('appGenericModalTitle');
        const bodyEl = document.getElementById('appGenericModalBody');
        const footerEl = document.getElementById('appGenericModalFooter');

        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = `<p class="mb-0 text-secondary">${message}</p>`;

        if (footerEl) {
            footerEl.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnModalCancelAction" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-danger rounded-pill px-4" id="btnModalConfirmAction">Confirmar</button>
            `;
        }

        const closeModal = () => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                try {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.hide();
                } catch (e) {}
            }
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            document.body.classList.remove('modal-open');
            const backdrop = document.getElementById('appManualModalBackdrop');
            if (backdrop) backdrop.remove();
        };

        const showModal = () => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                try {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                    return;
                } catch (e) {
                    console.warn('Bootstrap modal init fallback to pure DOM display:', e);
                }
            }
            // Fallback puramente visual no DOM (garante modal visual mesmo sem JS do Bootstrap)
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.style.zIndex = '1060';
            document.body.classList.add('modal-open');
            let backdrop = document.getElementById('appManualModalBackdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'appManualModalBackdrop';
                backdrop.className = 'modal-backdrop fade show';
                backdrop.style.zIndex = '1050';
                document.body.appendChild(backdrop);
            }
        };

        const btnConfirm = document.getElementById('btnModalConfirmAction');
        if (btnConfirm) {
            btnConfirm.onclick = () => {
                closeModal();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            };
        }

        const btnCancel = document.getElementById('btnModalCancelAction');
        if (btnCancel) {
            btnCancel.onclick = () => {
                closeModal();
            };
        }

        showModal();
    },

    /**
     * Exibe Modal de Alerta
     * 100% no padrão visual da aplicação (NUNCA usa alert nativo do navegador)
     */
    showAlert(title, message) {
        const modalEl = this.getOrCreateModalElement();
        const titleEl = document.getElementById('appGenericModalTitle');
        const bodyEl = document.getElementById('appGenericModalBody');
        const footerEl = document.getElementById('appGenericModalFooter');

        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = `<p class="mb-0 text-secondary">${message}</p>`;

        if (footerEl) {
            footerEl.innerHTML = `
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" id="btnModalOkAlert" data-bs-dismiss="modal">OK</button>
            `;
        }

        const closeModal = () => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                try {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.hide();
                } catch (e) {}
            }
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            document.body.classList.remove('modal-open');
            const backdrop = document.getElementById('appManualModalBackdrop');
            if (backdrop) backdrop.remove();
        };

        const showModal = () => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                try {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                    return;
                } catch (e) {}
            }
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.style.zIndex = '1060';
            document.body.classList.add('modal-open');
            let backdrop = document.getElementById('appManualModalBackdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'appManualModalBackdrop';
                backdrop.className = 'modal-backdrop fade show';
                backdrop.style.zIndex = '1050';
                document.body.appendChild(backdrop);
            }
        };

        const btnOk = document.getElementById('btnModalOkAlert');
        if (btnOk) {
            btnOk.onclick = () => {
                closeModal();
            };
        }

        showModal();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
});
