<section class="mt-8 rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-accounting-posting-administration>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Accounting audit</p>
            <h2 class="text-xl font-semibold text-slate-900">Posting lifecycle</h2>
            <p class="mt-1 text-sm text-slate-600">Read-only view of executions, reversals, corrections and balanced entries.</p>
        </div>
        <form class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5" data-accounting-posting-filters>
            <input name="accounting_reference" class="rounded-md border-slate-300 text-sm" placeholder="Accounting reference">
            <select name="status" class="rounded-md border-slate-300 text-sm"><option value="">All statuses</option><option value="posted">Posted</option><option value="executed">Executed</option></select>
            <input name="currency" maxlength="3" class="rounded-md border-slate-300 text-sm" placeholder="Currency">
            <input name="from_date" type="date" class="rounded-md border-slate-300 text-sm">
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Filter</button>
        </form>
    </div>
    <p class="mt-4 hidden rounded-md bg-amber-50 p-3 text-sm text-amber-800" data-accounting-posting-message></p>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="px-3 py-2">Date</th><th class="px-3 py-2">Reference</th><th class="px-3 py-2">Role / status</th><th class="px-3 py-2">Amount</th><th class="px-3 py-2">Lifecycle</th><th class="px-3 py-2"></th></tr></thead>
            <tbody class="divide-y divide-slate-100" data-accounting-posting-rows></tbody>
        </table>
    </div>
    <div class="mt-5 hidden rounded-lg bg-slate-50 p-4" data-accounting-posting-detail></div>
</section>

<script>
(() => {
    const root = document.querySelector('[data-accounting-posting-administration]');
    if (!root) return;
    const form = root.querySelector('[data-accounting-posting-filters]');
    const rows = root.querySelector('[data-accounting-posting-rows]');
    const detail = root.querySelector('[data-accounting-posting-detail]');
    const message = root.querySelector('[data-accounting-posting-message]');
    const endpoint = '/api/v1/financial-settlement-accounting-postings';
    const token = sessionStorage.getItem('tms_mvp_token') || '';
    const organization = sessionStorage.getItem('tms_mvp_organization_id') || '1';
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
    const money = (minor, currency) => `${new Intl.NumberFormat('cs-CZ', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Number(minor || 0) / 100)} ${escapeHtml(currency || '')}`;
    const date = value => value ? new Intl.DateTimeFormat('cs-CZ').format(new Date(value)) : 'â€”';
    const request = async url => {
        const headers = {'Accept': 'application/json', 'X-Organization-ID': organization};
        if (token) headers.Authorization = `Bearer ${token}`;
        const response = await fetch(url, {headers});
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    };
    const lifecycle = item => [item.handoff && 'handoff', 'execution', item.reversal && 'reversal', item.correction && 'correction'].filter(Boolean).join(' â†’ ');
    const renderList = items => {
        rows.innerHTML = items.length ? items.map(item => `<tr><td class="px-3 py-3">${date(item.posting_date)}</td><td class="px-3 py-3 font-medium">${escapeHtml(item.accounting_reference || 'â€”')}</td><td class="px-3 py-3">${escapeHtml(item.role)} / ${escapeHtml(item.status)}</td><td class="px-3 py-3 tabular-nums">${money(item.amount_minor, item.currency)}</td><td class="px-3 py-3 text-xs text-slate-500">${escapeHtml(lifecycle(item))}</td><td class="px-3 py-3"><button type="button" class="font-semibold text-indigo-700" data-posting-id="${escapeHtml(item.public_id)}">Detail</button></td></tr>`).join('') : '<tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">No accounting postings match the selected filters.</td></tr>';
    };
    const renderDetail = item => {
        const entries = (item.entries || []).map(entry => `<tr><td class="px-2 py-1">${escapeHtml(entry.sequence_number)}</td><td class="px-2 py-1">${escapeHtml(entry.side)}</td><td class="px-2 py-1 font-mono">${escapeHtml(entry.account_code)}</td><td class="px-2 py-1 text-right tabular-nums">${money(entry.amount_minor, entry.currency)}</td></tr>`).join('');
        const timeline = (item.timeline || []).map(event => `<li><span class="font-semibold">${escapeHtml(event.stage)}</span> Â· ${escapeHtml(event.type || 'event')} Â· ${date(event.occurred_at)}</li>`).join('');
        detail.innerHTML = `<div class="flex justify-between gap-4"><div><h3 class="font-semibold text-slate-900">${escapeHtml(item.accounting_reference || item.public_id)}</h3><p class="text-sm text-slate-600">${escapeHtml(lifecycle(item))}</p></div><button type="button" data-close-posting-detail>Close</button></div><div class="mt-4 grid gap-4 lg:grid-cols-2"><div><h4 class="text-sm font-semibold">Balanced entries</h4><table class="mt-2 w-full text-sm"><tbody>${entries || '<tr><td>No entries.</td></tr>'}</tbody></table></div><div><h4 class="text-sm font-semibold">Audit timeline</h4><ol class="mt-2 space-y-2 text-sm">${timeline || '<li>No events.</li>'}</ol></div></div>`;
        detail.classList.remove('hidden');
    };
    const load = async () => {
        message.classList.add('hidden');
        rows.innerHTML = '<tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">Loadingâ€¦</td></tr>';
        const params = new URLSearchParams(new FormData(form));
        [...params.keys()].forEach(key => { if (!params.get(key)) params.delete(key); });
        params.set('per_page', '50');
        try { renderList((await request(`${endpoint}?${params}`)).data || []); }
        catch (error) { message.textContent = `Unable to load accounting postings: ${error.message}`; message.classList.remove('hidden'); rows.innerHTML = ''; }
    };
    form.addEventListener('submit', event => { event.preventDefault(); load(); });
    rows.addEventListener('click', async event => { const button = event.target.closest('[data-posting-id]'); if (!button) return; try { renderDetail((await request(`${endpoint}/${button.dataset.postingId}`)).data); } catch (error) { message.textContent = `Unable to load detail: ${error.message}`; message.classList.remove('hidden'); } });
    detail.addEventListener('click', event => { if (event.target.closest('[data-close-posting-detail]')) detail.classList.add('hidden'); });
    load();
})();
</script>
