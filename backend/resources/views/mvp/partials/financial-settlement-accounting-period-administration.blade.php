<section class="panel" data-accounting-period-administration>
    <div class="head">
        <div>
            <h2>Administrace &#250;&#269;etn&#237;ch obdob&#237;</h2>
            <p class="muted">Otev&#237;r&#225;n&#237;, uzav&#237;r&#225;n&#237; a znovuotev&#237;r&#225;n&#237; obdob&#237; s optimistickou reviz&#237; a auditn&#237; histori&#237;.</p>
        </div>
        <button type="button" class="secondary" data-accounting-period-refresh>Obnovit obdob&#237;</button>
    </div>

    <form class="filters" data-accounting-period-create>
        <div class="field"><label for="accountingPeriodStart">Obdob&#237; od</label><input id="accountingPeriodStart" name="period_start" type="date" required></div>
        <div class="field"><label for="accountingPeriodEnd">Obdob&#237; do</label><input id="accountingPeriodEnd" name="period_end" type="date" required></div>
        <div class="field"><label for="accountingPeriodCurrency">M&#283;na</label><input id="accountingPeriodCurrency" name="currency" maxlength="3" value="CZK" required></div>
        <div class="field"><label for="accountingPeriodReason">D&#367;vod otev&#345;en&#237;</label><input id="accountingPeriodReason" name="reason" maxlength="1000" value="Otev&#345;en&#237; &#250;&#269;etn&#237;ho obdob&#237;." required></div>
        <button type="submit">Otev&#345;&#237;t obdob&#237;</button>
    </form>

    <form class="filters" data-accounting-period-filters style="margin-top:14px">
        <div class="field"><label for="accountingPeriodStatus">Stav</label><select id="accountingPeriodStatus" name="status"><option value="">V&#353;echny</option><option value="open">Otev&#345;en&#233;</option><option value="closed">Uzav&#345;en&#233;</option><option value="reopened">Znovuotev&#345;en&#233;</option></select></div>
        <div class="field"><label for="accountingPeriodFilterCurrency">M&#283;na</label><input id="accountingPeriodFilterCurrency" name="currency" maxlength="3"></div>
        <div class="field"><label for="accountingPeriodFromDate">P&#345;ekryv od</label><input id="accountingPeriodFromDate" name="from_date" type="date"></div>
        <div class="field"><label for="accountingPeriodToDate">P&#345;ekryv do</label><input id="accountingPeriodToDate" name="to_date" type="date"></div>
        <button type="submit">Filtrovat</button>
        <button type="button" class="secondary" data-accounting-period-reset>Zru&#353;it filtr</button>
    </form>

    <p class="message hidden" data-accounting-period-message></p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Obdob&#237;</th><th>M&#283;na</th><th>Stav</th><th>Revize</th><th>Posledn&#237; d&#367;vod</th><th>Audit</th><th>Akce</th></tr></thead>
            <tbody data-accounting-period-rows></tbody>
        </table>
    </div>
    <div class="panel hidden" data-accounting-period-detail></div>
</section>

<script>
(() => {
    const root = document.querySelector('[data-accounting-period-administration]');
    if (!root) return;
    const endpoint = '/api/v1/financial-settlement-accounting-periods';
    const token = sessionStorage.getItem('tms_mvp_token') || '';
    const organization = sessionStorage.getItem('tms_mvp_organization_id') || '1';
    const createForm = root.querySelector('[data-accounting-period-create]');
    const filterForm = root.querySelector('[data-accounting-period-filters]');
    const rows = root.querySelector('[data-accounting-period-rows]');
    const detail = root.querySelector('[data-accounting-period-detail]');
    const message = root.querySelector('[data-accounting-period-message]');
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
    const statusLabel = value => ({open:'Otev\u0159en\u00e9', closed:'Uzav\u0159en\u00e9', reopened:'Znovuotev\u0159en\u00e9'})[value] || value;
    const eventLabel = value => ({accounting_period_opened:'Obdob\u00ed otev\u0159eno', accounting_period_closed:'Obdob\u00ed uzav\u0159eno', accounting_period_reopened:'Obdob\u00ed znovu otev\u0159eno'})[value] || value;
    const dateTime = value => value ? new Intl.DateTimeFormat('cs-CZ', {dateStyle:'medium', timeStyle:'short'}).format(new Date(value)) : '\u2014';
    const showMessage = (text, error = false) => { message.textContent = text; message.className = text ? `message${error ? ' error' : ''}` : 'message hidden'; };
    const request = async (url, options = {}) => {
        const headers = new Headers(options.headers || {});
        headers.set('Accept', 'application/json');
        headers.set('X-Organization-ID', organization);
        if (token) headers.set('Authorization', `Bearer ${token}`);
        const response = await fetch(url, {...options, headers});
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
        return payload;
    };
    const actionButtons = item => {
        if (item.status === 'closed') return `<button type="button" class="secondary" data-period-action="reopen" data-period-id="${escapeHtml(item.public_id)}" data-period-revision="${escapeHtml(item.revision)}">Znovu otev&#345;&#237;t</button>`;
        return `<button type="button" data-period-action="close" data-period-id="${escapeHtml(item.public_id)}" data-period-revision="${escapeHtml(item.revision)}">Uzav&#345;&#237;t</button>`;
    };
    const renderList = items => {
        rows.innerHTML = items.length ? items.map(item => `<tr><td><b>${escapeHtml(item.period_start)} &#8211; ${escapeHtml(item.period_end)}</b><br><span class="muted">${escapeHtml(item.public_id)}</span></td><td>${escapeHtml(item.currency)}</td><td><span class="badge ${escapeHtml(item.status)}">${statusLabel(item.status)}</span></td><td>${escapeHtml(item.revision)}</td><td>${escapeHtml(item.last_reason || '\u2014')}</td><td><button type="button" class="secondary" data-period-detail="${escapeHtml(item.public_id)}">Historie (${escapeHtml(item.event_count)})</button></td><td><div class="bank-actions">${actionButtons(item)}</div></td></tr>`).join('') : '<tr><td colspan="7">Vybran&#233;mu filtru neodpov&#237;d&#225; &#382;&#225;dn&#233; &#250;&#269;etn&#237; obdob&#237;.</td></tr>';
    };
    const renderDetail = item => {
        const events = (item.events || []).map(event => `<div class="audit-item"><b>${eventLabel(event.event_type)}</b> | revize ${escapeHtml(event.revision)}<br><span class="muted">${dateTime(event.occurred_at)}</span></div>`).join('');
        detail.innerHTML = `<div class="head"><div><h3>${escapeHtml(item.period_start)} &#8211; ${escapeHtml(item.period_end)} | ${escapeHtml(item.currency)}</h3><p class="muted">${statusLabel(item.status)} | revize ${escapeHtml(item.revision)}</p></div><button type="button" class="secondary" data-close-accounting-period-detail>Zav&#345;&#237;t</button></div>${events || '<p class="muted">Bez auditn&#237;ch ud&#225;lost&#237;.</p>'}`;
        detail.classList.remove('hidden');
    };
    const load = async () => {
        showMessage('');
        rows.innerHTML = '<tr><td colspan="7">Na&#269;&#237;t&#225;m&#8230;</td></tr>';
        const params = new URLSearchParams(new FormData(filterForm));
        [...params.keys()].forEach(key => { if (!params.get(key)) params.delete(key); });
        params.set('per_page', '100');
        try { renderList((await request(`${endpoint}?${params}`)).data || []); }
        catch (error) { rows.innerHTML = ''; showMessage(`Obdob\u00ed nelze na\u010d\u00edst: ${error.message}`, true); }
    };
    const transition = async (id, revision, action) => {
        const label = action === 'close' ? 'uzav\u0159en\u00ed' : 'znovuotev\u0159en\u00ed';
        const reason = prompt(`Uve\u010fte d\u016fvod ${label} obdob\u00ed (alespo\u0148 3 znaky):`, action === 'close' ? 'Kontrolovan\u00e9 uzav\u0159en\u00ed \u00fa\u010detn\u00edho obdob\u00ed.' : 'Kontrolovan\u00e9 znovuotev\u0159en\u00ed \u00fa\u010detn\u00edho obdob\u00ed.');
        if (reason === null || reason.trim().length < 3) return;
        try {
            await request(`${endpoint}/${id}/${action}`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({idempotency_key:crypto.randomUUID(), expected_revision:Number(revision), reason:reason.trim()})});
            showMessage(action === 'close' ? '\u00da\u010detn\u00ed obdob\u00ed bylo uzav\u0159eno.' : '\u00da\u010detn\u00ed obdob\u00ed bylo znovu otev\u0159eno.');
            detail.classList.add('hidden');
            await load();
        } catch (error) { showMessage(error.message, true); }
    };
    createForm.addEventListener('submit', async event => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(createForm));
        data.currency = String(data.currency || '').toUpperCase();
        data.idempotency_key = crypto.randomUUID();
        try {
            await request(endpoint, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)});
            showMessage('Nov\u00e9 \u00fa\u010detn\u00ed obdob\u00ed bylo otev\u0159eno.');
            await load();
        } catch (error) { showMessage(error.message, true); }
    });
    filterForm.addEventListener('submit', event => { event.preventDefault(); load(); });
    root.querySelector('[data-accounting-period-reset]').addEventListener('click', () => { filterForm.reset(); detail.classList.add('hidden'); load(); });
    root.querySelector('[data-accounting-period-refresh]').addEventListener('click', load);
    rows.addEventListener('click', async event => {
        const action = event.target.closest('[data-period-action]');
        if (action) { await transition(action.dataset.periodId, action.dataset.periodRevision, action.dataset.periodAction); return; }
        const show = event.target.closest('[data-period-detail]');
        if (!show) return;
        try { renderDetail((await request(`${endpoint}/${show.dataset.periodDetail}`)).data); }
        catch (error) { showMessage(error.message, true); }
    });
    detail.addEventListener('click', event => { if (event.target.closest('[data-close-accounting-period-detail]')) detail.classList.add('hidden'); });
    load();
})();
</script>
