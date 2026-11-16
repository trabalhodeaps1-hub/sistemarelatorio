/**
 * Select dependente Área → Equipamento.
 * O select de área tem data-equipamentos-url com o marcador __AREA__.
 * Opção "0" = Estrutura / Não se aplica (grava equipamento_id null).
 *
 * Uso nos filtros: data-sem-estrutura="1" no select de equipamento
 * troca a opção "Estrutura" por "Todos".
 */
async function carregar(area, select) {
    const url = area.dataset.equipamentosUrl.replace('__AREA__', area.value);
    const selecionado = select.dataset.selecionado ?? '';
    const filtro = select.dataset.semEstrutura === '1';

    select.innerHTML = '<option value="">Carregando...</option>';
    select.disabled = true;

    if (!area.value) {
        select.innerHTML = `<option value="">${filtro ? 'Todos' : 'Selecione a área primeiro'}</option>`;
        select.disabled = filtro ? false : true;
        return;
    }

    try {
        const resp = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!resp.ok) throw new Error(resp.statusText);
        const lista = await resp.json();

        const opcoes = filtro
            ? ['<option value="">Todos</option>']
            : ['<option value="">Selecione...</option>', '<option value="0">Estrutura / Não se aplica</option>'];

        lista.forEach((e) => {
            const opt = document.createElement('option');
            opt.value = e.id;
            opt.textContent = e.rotulo;
            opcoes.push(opt.outerHTML);
        });

        select.innerHTML = opcoes.join('');
        if (selecionado !== '' && select.querySelector(`option[value="${selecionado}"]`)) {
            select.value = selecionado;
        }
    } catch {
        select.innerHTML = '<option value="">Erro ao carregar equipamentos</option>';
    } finally {
        select.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-equipamentos-url]').forEach((area) => {
        const alvo = document.getElementById(area.dataset.equipamentosAlvo || 'equipamento_id');
        if (!alvo) return;

        area.addEventListener('change', () => {
            alvo.dataset.selecionado = '';
            carregar(area, alvo);
        });

        carregar(area, alvo);
    });
});
