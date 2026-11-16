/**
 * Seleção de fotos com preview, remoção individual e limite de quantidade.
 * Acumula as fotos de várias seleções (no celular, cada disparo da câmera
 * substitui a seleção anterior), mantendo tudo em um DataTransfer.
 *
 * Marcação esperada:
 *   <input type="file" id="fotos" name="fotos[]" data-max="5" data-existentes="0" data-max-mb="10">
 *   <button id="btn-camera">  <div id="fotos-preview">  <div id="fotos-aviso">
 *   (opcional) checkboxes .remover-foto para fotos já salvas
 */
const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

function iniciar(input) {
    const preview = document.getElementById('fotos-preview');
    const aviso = document.getElementById('fotos-aviso');
    const botao = document.getElementById('btn-camera');
    const max = parseInt(input.dataset.max || '5', 10);
    const maxBytes = parseFloat(input.dataset.maxMb || '10') * 1024 * 1024;
    const existentes = parseInt(input.dataset.existentes || '0', 10);
    let selecionadas = [];

    const removidas = () => document.querySelectorAll('.remover-foto:checked').length;
    const vagas = () => max - (existentes - removidas()) - selecionadas.length;

    const sincronizar = () => {
        const dt = new DataTransfer();
        selecionadas.forEach((f) => dt.items.add(f));
        input.files = dt.files;
    };

    const atualizarBotao = () => {
        if (!botao) return;
        const restam = vagas();
        botao.disabled = restam <= 0;
        botao.innerHTML = restam <= 0
            ? '<i class="bi bi-check2-circle"></i> Limite de fotos atingido'
            : `<i class="bi bi-camera"></i> Tirar / escolher foto <span class="badge text-bg-light">${restam} restante(s)</span>`;
    };

    const renderizar = () => {
        preview.innerHTML = '';
        selecionadas.forEach((arquivo, i) => {
            const url = URL.createObjectURL(arquivo);
            const div = document.createElement('div');
            div.className = 'foto-preview';
            div.innerHTML = `
                <img src="${url}" alt="Prévia ${i + 1}">
                <button type="button" class="btn btn-danger btn-remover" data-indice="${i}" aria-label="Remover foto">
                    <i class="bi bi-x"></i>
                </button>
                <div class="small text-muted text-center mt-1">${(arquivo.size / 1024 / 1024).toFixed(1)} MB</div>`;
            div.querySelector('img').onload = () => URL.revokeObjectURL(url);
            preview.appendChild(div);
        });
        atualizarBotao();
    };

    botao?.addEventListener('click', () => {
        // O input precisa ser esvaziado para que o change dispare de novo
        const dt = new DataTransfer();
        input.files = dt.files;
        input.click();
    });

    input.addEventListener('change', () => {
        aviso.textContent = '';
        const novos = Array.from(input.files || []);
        const erros = [];

        for (const arquivo of novos) {
            if (vagas() <= 0) {
                erros.push(`Máximo de ${max} fotos por ocorrência.`);
                break;
            }
            if (arquivo.type && !TIPOS.includes(arquivo.type)) {
                erros.push(`"${arquivo.name}" não é JPG, PNG ou WEBP.`);
                continue;
            }
            if (arquivo.size > maxBytes) {
                erros.push(`"${arquivo.name}" passa de ${input.dataset.maxMb || 10} MB.`);
                continue;
            }
            selecionadas.push(arquivo);
        }

        aviso.textContent = [...new Set(erros)].join(' ');
        sincronizar();
        renderizar();
    });

    preview.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remover');
        if (!btn) return;
        selecionadas.splice(parseInt(btn.dataset.indice, 10), 1);
        aviso.textContent = '';
        sincronizar();
        renderizar();
    });

    document.querySelectorAll('.remover-foto').forEach((cb) =>
        cb.addEventListener('change', () => {
            if (vagas() < 0) {
                cb.checked = false;
                aviso.textContent = `Máximo de ${max} fotos por ocorrência.`;
            }
            atualizarBotao();
        }),
    );

    // Bloqueia envio sem nenhuma foto
    input.form?.addEventListener('submit', (e) => {
        const total = existentes - removidas() + selecionadas.length;
        if (total < 1) {
            e.preventDefault();
            e.stopImmediatePropagation();
            aviso.textContent = 'Envie pelo menos 1 foto do problema.';
            botao?.focus();
        }
    }, true);

    atualizarBotao();
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('fotos');
    if (input) iniciar(input);
});
