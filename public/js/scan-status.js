/* Same-origin stored-result presentation only. Provider processing belongs to the worker. */
(() => {
  'use strict';
  const active = new Set(['queued', 'running', 'waiting']);
  const states = new Set([...active, 'completed', 'failed', 'not_applicable']);
  const advisories = new Set(['Flagged', 'Review Recommended', 'No Detections Reported', 'Unresolved']);
  function display(result) {
    const labels = {queued: 'Queued', running: 'Checking', waiting: 'Waiting / retrying', not_applicable: 'Not applicable'};
    if (!states.has(result.processingState) || result.processingState === 'failed') return 'Unresolved';
    return labels[result.processingState] || (advisories.has(result.advisoryStatus) ? result.advisoryStatus : 'Unresolved');
  }
  function evidenceLabel(result) {
    return result.evidenceMode === 'fixture' ? 'Offline fixture — simulated provider evidence' : (result.evidenceMode === 'provider' ? 'Provider analysis' : '');
  }
  if (typeof module !== 'undefined' && module.exports) module.exports = {display, evidenceLabel};
  if (typeof document === 'undefined') return;
  document.querySelectorAll('[data-scan-status]').forEach(panel => {
    const get = selector => panel.querySelector(selector);
    const form = get('[data-scan-rescan]');
    const button = form?.querySelector('button');
    const token = form?.querySelector('[name="requestToken"]');
    const error = get('[data-scan-error]');
    let timer, running = false, posting = false, stopped = false, retryToken = null, lastState = null, backoff = 15000;
    async function request(url, options = {}) {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 10000);
      try {
        const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', ...options, signal: controller.signal});
        if (!response.ok) {
          const failure = new Error('request_failed');
          failure.permanent = [401, 403, 404, 419, 422].includes(response.status);
          throw failure;
        }
        const result = await response.json();
        if (result.contractVersion !== 1) throw new Error('invalid_contract');
        return result;
      } finally { clearTimeout(timeout); }
    }
    function schedule(delay = backoff) {
      clearTimeout(timer);
      if (!document.hidden && !stopped && !posting && (lastState === null || active.has(lastState))) timer = setTimeout(refresh, delay);
    }
    function render(result) {
      if (!states.has(result.processingState) || !('fileId' in result) || (result.fileId !== null && (!Number.isSafeInteger(result.fileId) || result.fileId < 1))) throw new Error('invalid_contract');
      lastState = result.processingState;
      get('[data-scan-label]').textContent = display(result);
      get('[data-scan-evidence]').textContent = evidenceLabel(result);
      get('[data-scan-evidence]').hidden = !evidenceLabel(result);
      get('[data-scan-reason]').textContent = typeof result.reason === 'string' ? result.reason.replaceAll('_', ' ') : 'Evidence unavailable';
      get('[data-scan-time]').textContent = typeof result.checkedAt === 'string' ? `Checked ${result.checkedAt}` : '';
      const download = get('[data-scan-download]');
      if (String(result.fileId ?? '') !== panel.dataset.scanFile) {
        panel.dataset.scanFile = String(result.fileId ?? '');
        download.textContent = 'Download current attachment';
      }
      download.hidden = result.fileId === null;
      if (result.fileId === null) download.removeAttribute('href');
      else download.href = panel.dataset.scanDownloadBase + result.fileId;
      if (token && retryToken === null) token.value = typeof result.rescanToken === 'string' ? result.rescanToken : '';
      if (button) button.disabled = posting || result.fileId === null || !token.value;
    }
    async function history() {
      if (!panel.dataset.scanHistory) return;
      const result = await request(panel.dataset.scanHistory);
      if (!Array.isArray(result.history)) throw new Error('invalid_contract');
      const list = document.createElement('ol');
      for (const item of result.history) {
        const row = document.createElement('li');
        row.style.margin = '12px 0';
        const label = document.createElement('strong');
        label.textContent = display(item);
        row.append(label, document.createTextNode(item.origin === 'teacher_rescan' ? ' · Teacher request' : ' · Automatic submission'));
        if (evidenceLabel(item)) {
          const provenance = document.createElement('p');
          provenance.textContent = evidenceLabel(item); row.append(provenance);
        }
        const details = document.createElement('p');
        details.textContent = `${typeof item.reason === 'string' ? item.reason.replaceAll('_', ' ') : 'Evidence unavailable'}${typeof item.completedAt === 'string' ? ' · Checked ' + item.completedAt : ''}`;
        row.append(details);
        if (item.stats && typeof item.stats === 'object') {
          const counts = document.createElement('p');
          counts.textContent = Object.entries(item.stats).filter(([, count]) => Number.isSafeInteger(count) && count >= 0).map(([name, count]) => `${name}: ${count}`).join(' · ');
          row.append(counts);
        }
        list.append(row);
      }
      get('[data-scan-history-list]').replaceChildren(result.history.length ? list : document.createTextNode('No scan attempts recorded for this attachment.'));
    }
    async function refresh() {
      if (running || posting || stopped || document.hidden) return;
      clearTimeout(timer); running = true;
      try {
        render(await request(panel.dataset.scanStatus));
        backoff = 15000; error.textContent = '';
        try { await history(); } catch { error.textContent = 'History refresh failed. Previous history remains visible.'; }
      } catch (failure) {
        error.textContent = failure.permanent ? 'Scan details are unavailable. Reload or sign in again.' : 'Refresh failed. Last displayed advisory remains visible; retrying when active.';
        if (failure.permanent) { stopped = true; if (button) button.disabled = true; }
        backoff = Math.min(backoff * 2, 120000);
        // Retry failed initial/terminal reads too, unless authorization forbids it.
        if (!stopped) lastState = null;
      } finally { running = false; schedule(); }
    }
    form?.addEventListener('submit', async event => {
      event.preventDefault();
      if (posting || !token.value || stopped) return;
      posting = true; clearTimeout(timer); button.disabled = true;
      retryToken ??= token.value;
      const body = new FormData(form); body.set('requestToken', retryToken);
      try {
        const result = await request(form.action, {method: 'POST', body});
        if (!Number.isSafeInteger(result.attemptId) || !states.has(result.processingState)) throw new Error('invalid_contract');
        retryToken = null; token.value = ''; lastState = result.processingState;
        get('[data-scan-label]').textContent = display(result);
        error.textContent = 'Fresh scan request recorded.';
      } catch (failure) {
        error.textContent = 'Scan request could not be confirmed. Try again to check the same request; it will not create duplicate work.';
        if (failure.permanent) { stopped = true; error.textContent = 'Scan request rejected. Reload the page and try again.'; }
      } finally {
        posting = false; button.disabled = stopped || (!token.value && retryToken === null);
        if (!stopped) { if (retryToken === null) await refresh(); else schedule(); }
      }
    });
    document.addEventListener('visibilitychange', () => {
      clearTimeout(timer);
      if (!document.hidden && !stopped && (lastState === null || active.has(lastState))) refresh();
    });
    refresh();
  });
})();
