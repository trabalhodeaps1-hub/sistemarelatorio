/**
 * Polling do contador de notificações (sino) a cada 60s.
 * Pausa quando a aba não está visível.
 */
const INTERVALO = 60_000;

function iniciar(badge) {
    const url = badge.dataset.url;
    if (!url) return;

    let anterior = parseInt(badge.textContent, 10) || 0;

    const atualizar = async () => {
        if (document.hidden) return;
        try {
            const resp = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (resp.status === 401 || resp.redirected) {
                window.location.reload();
                return;
            }
            if (!resp.ok) return;
            const { nao_lidas: total } = await resp.json();

            badge.textContent = total > 99 ? '99+' : String(total);
            badge.classList.toggle('d-none', total === 0);

            if (total > anterior) {
                badge.classList.add('animate__pulse');
                document.title = `(${total}) ${document.title.replace(/^\(\d+\+?\)\s*/, '')}`;
            }
            anterior = total;
        } catch {
            // rede indisponível: tenta de novo no próximo ciclo
        }
    };

    setInterval(atualizar, INTERVALO);
    document.addEventListener('visibilitychange', () => !document.hidden && atualizar());
}

document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('notif-contador');
    if (badge) iniciar(badge);
});
