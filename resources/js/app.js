// Bootstrap JS importieren
import * as bootstrap from 'bootstrap';

// ApexCharts global verfügbar machen (für Alpine.js x-data)
import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

// Bootstrap global verfügbar machen
window.bootstrap = bootstrap;

// ── Globaler DB-Fehler-Toast ─────────────────────────────────────────────────

window.showDbErrorToast = function (message) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const id = 'toast-' + Date.now();
    container.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    const el = document.getElementById(id);
    const toast = new bootstrap.Toast(el, { delay: 10000 });
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
};

// Livewire-Hook: 500-Fehler abfangen und Toast zeigen
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            if (status === 500) {
                preventDefault(); // Livewire-eigene Fehlermeldung unterdrücken
                showDbErrorToast(
                    'Serverfehler (500) — wahrscheinlich ein Datenbankproblem. ' +
                    '<a href="javascript:location.reload()" class="text-white text-decoration-underline">Seite neu laden</a> für Details.'
                );
            } else if (status === 403) {
                // Demo-Modus-Sperre (DemoModeWriteBlockedException) — eigene, akkurate Meldung
                preventDefault();
                let message = 'Diese Demo ist read-only — Änderungen werden nicht gespeichert.';
                try {
                    message = JSON.parse(content).message || message;
                } catch (e) { /* Fallback-Text reicht */ }
                showDbErrorToast(message);
            }
        });
    });
});
