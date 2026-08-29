(() => {
    'use strict';

    const state = {cards: [], drivers: [], mounted: false, view: 'assignments', editing: null, saving: false};
    const escapeHtml = (value) => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    const items = (body) => {
        const data = body?.data ?? body ?? {};
        if (Array.isArray(data)) return data;
        if (Array.isArray(data.items)) return data.items;
        if (Array.isArray(data.data)) return data.data;
        return [];
    };
    const request = async (path, options = {}) => {
        const headers = {Accept: 'application/json', 'X-Organization-ID': sessionStorage.getItem('tms_mvp_organization_id') || '', ...(options.headers || {})};
        const token = sessionStorage.getItem('tms_mvp_token') || '';
        if (token) headers.Authorization = `Bearer ${token}`;
        const response = await fetch(`/api/v1${path}`, {...options, headers});
        let body = null;
        try { body = await response.json(); } catch { body = null; }
        if (!response.ok) {
            const validation = body?.errors ? Object.values(body.errors).flat().join(' ') : '';
            throw new Error(validation || body?.message || `HTTP ${response.status}`);
        }
        return body;
    };
    const date = (value, empty = 'bez omezení') => value ? new Intl.DateTimeFormat('cs-CZ').format(new Date(String(value).slice(0, 10) + 'T00:00:00')) : empty;
    const status = (value) => ({active: 'Aktivní', blocked: 'Blokovaná', expired: 'Prošlá', retired: 'Vyřazená', ended: 'Ukončeno', cancelled: 'Zrušeno'}[value] || value || '—');
    const providerStatus = (value) => ({active: 'Aktivní u poskytovatele', temporarily_blocked: 'Dočasně blokovaná', blocked: 'Blokovaná', cancelled: 'Zrušená', unknown: 'Stav nezjištěn', verification_required: 'Vyžaduje ověření'}[value] || value || 'Stav nezjištěn');
    const today = () => new Date().toISOString().slice(0, 10);
    const currentAssignment = (card) => {
        const currentDate = today();
        return [...(card.assignments || [])]
            .sort((a, b) => String(b.valid_from).localeCompare(String(a.valid_from)))
            .find((item) => item.status === 'active'
                && String(item.valid_from).slice(0, 10) <= currentDate
                && (!item.valid_until || String(item.valid_until).slice(0, 10) >= currentDate)) || null;
    };
    const expiry = (card) => {
        if (!card.expires_at) return {key: 'unknown', label: 'Expirace nezadaná', days: null};
        const end = new Date(String(card.expires_at).slice(0, 10) + 'T00:00:00');
        const start = new Date(today() + 'T00:00:00');
        const days = Math.ceil((end - start) / 86400000);
        if (days < 0) return {key: 'expired', label: 'Po expiraci', days};
        if (days <= 30) return {key: 'urgent', label: `Expiruje za ${days} dní`, days};
        if (days <= 60) return {key: 'warning', label: `Expiruje za ${days} dní`, days};
        return {key: 'valid', label: 'Platná', days};
    };
    const driverName = (id) => {
        const candidate = state.drivers.find((item) => Number(item?.driver?.id ?? item?.driver_id ?? item?.id) === Number(id));
        return candidate?.driver?.full_name
            ?? candidate?.full_name
            ?? ([candidate?.first_name, candidate?.last_name].filter(Boolean).join(' ') || null)
            ?? (id ? `Řidič #${id}` : 'Bez řidiče');
    };
    const assignmentText = (assignment) => assignment
        ? `${escapeHtml(driverName(assignment.driver_id))}<small>Odpovědná organizace #${escapeHtml(assignment.responsible_organization_id)}</small>`
        : 'Bez aktuálního přiřazení';
    const policyText = (card) => {
        const policy = (card.settlement_policies || card.settlementPolicies || [])[0];
        if (!policy) return 'Pravidlo nezadáno';
        const target = policy.settlement_target === 'driver' ? 'řidič' : 'dopravce';
        const basis = policy.amount_basis === 'net' ? 'bez DPH' : 'včetně DPH';
        return `${target}<small>${basis}</small>`;
    };
    const renderEditor = () => {
        const root = document.querySelector('[data-fuel-card-root]');
        const host = root?.querySelector('[data-fuel-card-editor-host]');
        if (!host) return;
        const card = state.editing;
        if (!card) {
            host.hidden = true;
            host.innerHTML = '';
            return;
        }
        host.hidden = false;
        host.innerHTML = `<div class="s043-card-editor-backdrop" data-fuel-card-edit-cancel></div>
            <section class="s043-card-editor" role="dialog" aria-modal="true" aria-labelledby="s043-card-editor-title">
                <div class="s043-card-editor-head">
                    <div><h4 id="s043-card-editor-title">Upravit palivovou kartu</h4><p>${escapeHtml(card.provider)} · ${escapeHtml(card.masked_card_number)}</p></div>
                    <button type="button" class="s043-card-editor-close" data-fuel-card-edit-cancel aria-label="Zavřít">×</button>
                </div>
                <div class="s043-card-immutable"><strong>Neměnné údaje</strong><span>Poskytovatel: ${escapeHtml(card.provider)}</span><span>Identifikátor: ${escapeHtml(card.provider_card_identifier)}</span></div>
                <form data-fuel-card-edit-form>
                    <div class="s043-card-editor-grid">
                        <label>Maskované číslo<input name="masked_card_number" required maxlength="64" value="${escapeHtml(card.masked_card_number)}"></label>
                        <label>Štítek<input name="label" maxlength="255" value="${escapeHtml(card.label || '')}"></label>
                        <label>Stav u poskytovatele<select name="provider_status" required>
                            ${[['active', 'Aktivní'], ['temporarily_blocked', 'Dočasně blokovaná'], ['blocked', 'Blokovaná'], ['cancelled', 'Zrušená'], ['unknown', 'Stav nezjištěn'], ['verification_required', 'Vyžaduje ověření']].map(([value, label]) => `<option value="${value}"${card.provider_status === value ? ' selected' : ''}>${label}</option>`).join('')}
                        </select></label>
                        <label>Expirace karty<input type="date" name="expires_at" value="${escapeHtml(String(card.expires_at || '').slice(0, 10))}"><small>Vyplňte pouze podle doložené expirace poskytovatele.</small></label>
                    </div>
                    <label>Poznámka ke stavu<textarea name="provider_status_note" maxlength="2000" rows="3">${escapeHtml(card.provider_status_note || '')}</textarea></label>
                    <label>Důvod změny<span class="s043-required">povinné</span><textarea name="reason" required minlength="3" maxlength="1000" rows="2" placeholder="Např. ověřeno v portálu MOL dne…"></textarea></label>
                    <p class="s043-card-editor-message" data-fuel-card-edit-message></p>
                    <div class="s043-card-editor-actions"><button type="button" data-fuel-card-edit-cancel>Zrušit</button><button type="submit" class="s043-primary"${state.saving ? ' disabled' : ''}>${state.saving ? 'Ukládám…' : 'Uložit změny'}</button></div>
                </form>
            </section>`;
        host.querySelector('[name="masked_card_number"]')?.focus();
    };
    const render = () => {
        const root = document.querySelector('[data-fuel-card-root]');
        if (!root) return;
        const provider = root.querySelector('[data-fuel-card-provider]')?.value || '';
        const selectedStatus = root.querySelector('[data-fuel-card-status]')?.value || '';
        const search = (root.querySelector('[data-fuel-card-search]')?.value || '').trim().toLocaleLowerCase('cs-CZ');
        const filtered = state.cards.filter((card) => {
            const assignments = card.assignments || [];
            const holderNames = assignments.map((item) => driverName(item.driver_id)).join(' ');
            const haystack = `${card.masked_card_number || ''} ${card.label || ''} ${holderNames} ${providerStatus(card.provider_status)}`.toLocaleLowerCase('cs-CZ');
            const assigned = currentAssignment(card);
            const viewMatches = state.view === 'cards' || assigned !== null;
            return viewMatches
                && (!provider || card.provider === provider)
                && (!selectedStatus || card.status === selectedStatus)
                && (!search || haystack.includes(search));
        });
        const rows = root.querySelector('[data-fuel-card-rows]');
        rows.innerHTML = filtered.length ? filtered.map((card) => {
            const assignments = [...(card.assignments || [])].sort((a, b) => String(b.valid_from).localeCompare(String(a.valid_from)));
            const current = currentAssignment(card);
            const cardExpiry = expiry(card);
            const history = assignments.map((item) => `<li><strong>${escapeHtml(driverName(item.driver_id))}</strong> · ${escapeHtml(date(item.valid_from))} – ${escapeHtml(date(item.valid_until))} · ${escapeHtml(status(item.status))}</li>`).join('');
            return `<tr>
                <td><strong>${escapeHtml(card.masked_card_number)}</strong><small>${escapeHtml(card.label || card.provider_card_identifier || '')}</small></td>
                <td>${escapeHtml(card.provider)}</td>
                <td>${assignmentText(current)}</td>
                <td>${escapeHtml(date(card.valid_from))}<small>do ${escapeHtml(date(card.expires_at, 'expirace nezadaná'))}</small><span class="s043-expiry s043-expiry-${escapeHtml(cardExpiry.key)}">${escapeHtml(cardExpiry.label)}</span></td>
                <td>${policyText(card)}</td>
                <td><span class="s043-card-state s043-card-state-${escapeHtml(card.status)}">${escapeHtml(status(card.status))}</span><small>${escapeHtml(providerStatus(card.provider_status))}</small></td>
                <td><details><summary>${assignments.length} období</summary><ul>${history || '<li>Bez historie</li>'}</ul></details></td>
                <td><button type="button" class="s043-card-edit-button" data-fuel-card-edit="${escapeHtml(card.public_id)}">Upravit</button></td>
            </tr>`;
        }).join('') : '<tr><td colspan="8" class="s043-card-empty">Žádné karty neodpovídají filtrům.</td></tr>';
        const activeAssignments = state.cards.filter((card) => currentAssignment(card) !== null).length;
        const expiryTrackedCards = state.cards.filter((card) => ['active', 'temporarily_blocked'].includes(card.provider_status));
        const expiring = expiryTrackedCards.filter((card) => ['warning', 'urgent'].includes(expiry(card).key)).length;
        const expired = expiryTrackedCards.filter((card) => expiry(card).key === 'expired').length;
        const missingExpiry = expiryTrackedCards.filter((card) => !card.expires_at).length;
        root.querySelector('[data-fuel-card-summary]').textContent =
            `Aktivní přiřazení: ${activeAssignments} · Karty: ${state.cards.length} · Expirace nezadaná: ${missingExpiry} · Brzy expirují: ${expiring} · Po expiraci: ${expired} · Zobrazeno: ${filtered.length}`;
        root.querySelectorAll('[data-fuel-card-view]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.fuelCardView === state.view);
            button.setAttribute('aria-selected', button.dataset.fuelCardView === state.view ? 'true' : 'false');
        });
        renderEditor();
    };
    const load = async () => {
        const root = document.querySelector('[data-fuel-card-root]');
        const message = root?.querySelector('[data-fuel-card-message]');
        if (message) message.textContent = 'Načítám karty…';
        const results = await Promise.allSettled([request('/fuel-cards'), request('/own-drivers')]);
        if (results[0].status === 'rejected') throw results[0].reason;
        state.cards = items(results[0].value);
        state.drivers = results[1].status === 'fulfilled' ? items(results[1].value) : [];
        render();
        if (message) message.textContent = `Načteno ${state.cards.length} karet.`;
    };
    const save = async (form) => {
        const card = state.editing;
        if (!card || state.saving) return;
        const data = new FormData(form);
        const masked = String(data.get('masked_card_number') || '').trim();
        if (/^\d+$/.test(masked)) throw new Error('Maskované číslo nesmí obsahovat celé skutečné číslo karty.');
        state.saving = true;
        renderEditor();
        try {
            await request(`/fuel-cards/${encodeURIComponent(card.public_id)}`, {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    masked_card_number: masked,
                    label: String(data.get('label') || '').trim() || null,
                    provider_status: String(data.get('provider_status') || ''),
                    provider_status_verified_at: new Date().toISOString(),
                    provider_status_note: String(data.get('provider_status_note') || '').trim() || null,
                    expires_at: String(data.get('expires_at') || '') || null,
                    lock_version: Number(card.lock_version),
                    reason: String(data.get('reason') || '').trim(),
                }),
            });
            state.editing = null;
            await load();
            const root = document.querySelector('[data-fuel-card-root]');
            root.querySelector('[data-fuel-card-message]').textContent = 'Karta byla auditovaně aktualizována.';
        } finally {
            state.saving = false;
        }
    };
    const mount = () => {
        const root = document.querySelector('[data-fuel-card-root]');
        if (!root || root.dataset.fuelCardMounted === '1') return;
        root.dataset.fuelCardMounted = '1';
        root.insertAdjacentHTML('afterbegin', `<style>
            .s043-card-head{display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap}.s043-card-head h3,.s043-card-head p{margin:0}.s043-card-summary{margin:18px 0 12px;padding:13px 15px;border-left:4px solid #2563eb;background:#eff6ff}.s043-card-filters{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:12px;margin-bottom:16px}.s043-card-filters label{display:grid;gap:6px;font-weight:700}.s043-card-filters input,.s043-card-filters select{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:9px;background:#fff}.s043-card-table-wrap{overflow:auto}.s043-card-table{width:100%;border-collapse:collapse}.s043-card-table th,.s043-card-table td{padding:11px 9px;text-align:left;vertical-align:top;border-bottom:1px solid #e2e8f0}.s043-card-table small{display:block;margin-top:3px;color:#64748b}.s043-card-state{display:inline-block;padding:4px 8px;border-radius:999px;background:#e2e8f0}.s043-card-state-active{background:#dcfce7;color:#166534}.s043-card-state-blocked{background:#fee2e2;color:#991b1b}.s043-card-empty{text-align:center!important;color:#64748b}.s043-card-note,.s043-card-status{color:#64748b}.s043-card-note{margin-top:14px}.s043-card-table details{min-width:180px}.s043-card-table summary{cursor:pointer;font-weight:700}.s043-card-table ul{margin:10px 0 0;padding-left:18px}.s043-card-edit-button{border:1px solid #173f7a;border-radius:8px;padding:7px 10px;background:#173f7a;color:#fff;font-weight:800}@media(max-width:800px){.s043-card-filters{grid-template-columns:1fr}}
            .s043-card-views{display:flex;gap:8px;margin:18px 0 12px}.s043-card-views button{border:1px solid #cbd5e1;border-radius:9px;padding:10px 14px;background:#fff;font-weight:800}.s043-card-views button.is-active{color:#fff;background:#172033;border-color:#172033}.s043-expiry{display:block;width:max-content;margin-top:6px;padding:3px 7px;border-radius:999px;font-size:11px;font-weight:800;background:#e2e8f0}.s043-expiry-warning{background:#fef3c7;color:#92400e}.s043-expiry-urgent,.s043-expiry-expired{background:#fee2e2;color:#991b1b}.s043-expiry-valid{background:#dcfce7;color:#166534}.s043-expiry-unknown{background:#fef3c7;color:#92400e}
            .s043-card-editor-host[hidden]{display:none}.s043-card-editor-host{position:fixed;inset:0;z-index:10000;display:grid;place-items:center;padding:20px}.s043-card-editor-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58)}.s043-card-editor{position:relative;width:min(760px,100%);max-height:calc(100vh - 40px);overflow:auto;border-radius:14px;background:#fff;padding:22px;box-shadow:0 25px 70px rgba(15,23,42,.35)}.s043-card-editor-head{display:flex;justify-content:space-between;gap:20px}.s043-card-editor-head h4,.s043-card-editor-head p{margin:0}.s043-card-editor-close{border:0;background:transparent;font-size:28px}.s043-card-immutable{display:flex;flex-wrap:wrap;gap:8px 18px;margin:18px 0;padding:12px;background:#f1f5f9;border-radius:9px}.s043-card-editor-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.s043-card-editor label{display:grid;gap:6px;margin:12px 0;font-weight:800}.s043-card-editor input,.s043-card-editor select,.s043-card-editor textarea{width:100%;box-sizing:border-box;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font:inherit}.s043-card-editor label small{font-weight:400;color:#64748b}.s043-required{color:#b91c1c;font-size:12px}.s043-card-editor-actions{display:flex;justify-content:flex-end;gap:10px}.s043-card-editor-actions button{padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-weight:800}.s043-card-editor-actions .s043-primary{background:#173f7a;color:#fff;border-color:#173f7a}.s043-card-editor-message{color:#b91c1c;min-height:20px}@media(max-width:650px){.s043-card-editor-grid{grid-template-columns:1fr}}
        </style>`);
        root.querySelector('.s043-card-head').insertAdjacentHTML('afterend', `
            <div class="s043-card-views" role="tablist" aria-label="Pohled správy palivových karet">
                <button type="button" class="is-active" data-fuel-card-view="assignments" aria-selected="true">Aktivní přiřazení</button>
                <button type="button" data-fuel-card-view="cards" aria-selected="false">Seznam karet</button>
            </div>
        `);
        const tableHead = root.querySelector('[data-fuel-card-rows]')?.closest('table')?.querySelector('thead tr');
        if (tableHead && !tableHead.querySelector('[data-s043-actions-heading]')) tableHead.insertAdjacentHTML('beforeend', '<th data-s043-actions-heading>Akce</th>');
        root.insertAdjacentHTML('beforeend', '<div class="s043-card-editor-host" data-fuel-card-editor-host hidden></div>');
        root.addEventListener('input', (event) => { if (event.target.matches('[data-fuel-card-provider],[data-fuel-card-status],[data-fuel-card-search]')) render(); });
        root.addEventListener('submit', (event) => {
            if (!event.target.matches('[data-fuel-card-edit-form]')) return;
            event.preventDefault();
            const message = event.target.querySelector('[data-fuel-card-edit-message]');
            save(event.target).catch((error) => {
                state.saving = false;
                renderEditor();
                const currentMessage = root.querySelector('[data-fuel-card-edit-message]');
                if (currentMessage) currentMessage.textContent = error.message;
                if (message) message.textContent = error.message;
            });
        });
        root.addEventListener('click', (event) => {
            const viewButton = event.target.closest('[data-fuel-card-view]');
            if (viewButton) {
                state.view = viewButton.dataset.fuelCardView;
                render();
                return;
            }
            const editButton = event.target.closest('[data-fuel-card-edit]');
            if (editButton) {
                state.editing = state.cards.find((card) => card.public_id === editButton.dataset.fuelCardEdit) || null;
                renderEditor();
                return;
            }
            if (event.target.closest('[data-fuel-card-edit-cancel]')) {
                if (!state.saving) state.editing = null;
                renderEditor();
                return;
            }
            if (event.target.closest('[data-fuel-card-refresh]')) {
                load().catch((error) => {
                    root.querySelector('[data-fuel-card-message]').textContent = error.message;
                });
            }
        });
        load().catch((error) => { root.querySelector('[data-fuel-card-message]').textContent = error.message; });
    };
    window.DrayviaFuelCardAdmin = {mount};
})();
