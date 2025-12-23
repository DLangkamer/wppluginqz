(function () {
  async function post(endpoint, payload) {
    const res = await fetch(`${QF.restUrl}${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': QF.nonce
      },
      body: JSON.stringify(payload)
    });
    return await res.json();
  }

  function renderQuestion(container, token, node) {
    const html = `
      <div class="qf-title">${escapeHtml(node.title || '')}</div>
      <div class="qf-answers">
        ${node.answers.map((a, idx) => `
          <button class="qf-btn" data-idx="${idx}">${escapeHtml(a.text || '')}</button>
        `).join('')}
      </div>
    `;
    container.innerHTML = html;

    container.querySelectorAll('.qf-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        container.innerHTML = `<div class="qf-loading">Carregando…</div>`;
        const data = await post('/answer', { token, answer_index: parseInt(btn.dataset.idx, 10) });

        if (data.type === 'question') renderQuestion(container, token, data.node);
        else renderResult(container, data.node);
      });
    });
  }

  function renderResult(container, node) {
    const cta = node.cta_url
      ? `<a class="qf-btn qf-cta" href="${escapeAttr(node.cta_url)}">${escapeHtml(node.cta_text || 'Continuar')}</a>`
      : '';

    container.innerHTML = `
      <div class="qf-title">${escapeHtml(node.title || '')}</div>
      <div class="qf-content">${node.content || ''}</div>
      <div class="qf-actions">${cta}</div>
    `;
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, s => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[s]));
  }
  function escapeAttr(str){ return escapeHtml(str).replace(/"/g,'&quot;'); }

  document.addEventListener('DOMContentLoaded', async () => {
    document.querySelectorAll('.qf-wrap').forEach(async wrap => {
      const quizId = parseInt(wrap.dataset.quizId, 10);
      const body = wrap.querySelector('.qf-body');

      const start = await post('/start', { quiz_id: quizId });
      if (start.error) {
        body.innerHTML = `<div class="qf-error">${escapeHtml(start.error)}</div>`;
        return;
      }

      const token = start.token;
      renderQuestion(body, token, start.node);
    });
  });
})();
