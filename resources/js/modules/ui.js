// Comportamentos gerais de interface
document.addEventListener('DOMContentLoaded', () => {
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new window.bootstrap.Tooltip(el));

    // Confirmação simples em formulários
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Evita duplo envio
    document.querySelectorAll('form:not([data-no-lock])').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;
            setTimeout(() => {
                form.querySelectorAll('button[type="submit"]').forEach((b) => (b.disabled = true));
            }, 0);
        });
    });

    // Galeria em modal
    const modal = document.getElementById('modalFoto');
    if (modal) {
        modal.addEventListener('show.bs.modal', (e) => {
            const src = e.relatedTarget?.dataset.foto;
            if (src) modal.querySelector('img').src = src;
        });
    }
});
