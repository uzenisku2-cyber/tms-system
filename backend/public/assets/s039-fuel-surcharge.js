(() => {
    'use strict';

    const apiBase = '/api/v1';
    const state = {
        mounted: false,
        surcharges: [],
        customers: [],
        drivers: [],
        carriers: [],
        step: 1,
        draft: null,
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const dataItems = (body) => {
        const data = body?.data ?? body ?? {};
        if (Array.isArray(data)) return data;
        if (Array.isArray(data.items)) return data.items;
        if (Array.isArray(data.data)) return data.data;
        return [];
    };

    const request = async (path, options = {}) => {
        const headers = {
            Accept: 'application/json',
            'X-Organization-ID': sessionStorage.getItem('tms_mvp_organization_id') || '',
            ...(options.headers || {}),
        };
        const token = sessionStorage.getItem('tms_mvp_token') || '';
        if (token) headers.Authorization = `Bearer ${token}`;
        if (options.body !== undefined) headers['Content-Type'] = 'application/json';

        const response = await fetch(`${apiBase}${path}`, {...options, headers});
        let body = null;
        try { body = await response.json(); } catch { body = null; }
        if (!response.ok) {
            const messages = body?.errors
                ? Object.values(body.errors).flat().filter(Boolean).join(' ')
                : '';
            throw new Error(messages || body?.message || `HTTP ${response.status}`);
        }
        return body;
    };

    const customerIdentity = (item) => Number(
        item?.relationship_id
        ?? item?.organization_relationship_id
        ?? item?.id
        ?? 0
    );
    const customerName = (item) => item?.customer?.name
        ?? item?.organization?.name
        ?? item?.name
        ?? `Odběratel ${customerIdentity(item)}`;
    const driverIdentity = (item) => Number(
        item?.assignment_id
        ?? item?.driver_organization_assignment_id
        ?? item?.id
        ?? 0
    );
    const driverName = (item) => item?.driver?.full_name
        ?? item?.full_name
        ?? [item?.first_name, item?.last_name].filter(Boolean).join(' ')
        ?? `Řidič ${driverIdentity(item)}`;
    const carrierIdentity = (item) => Number(
        item?.relationship_id
        ?? item?.organization_relationship_id
        ?? item?.id
        ?? 0
    );
    const carrierName = (item) => item?.external_carrier?.name
        ?? item?.carrier?.name
        ?? item?.target_organization?.name
        ?? item?.organization?.name
        ?? item?.name
        ?? `Dopravce ${carrierIdentity(item)}`;

    const money = (value) => {
        const number = Number(value ?? 0);
        return `${number.toLocaleString('cs-CZ', {minimumFractionDigits: 2, maximumFractionDigits: 4})} Kč/km`;
    };

    const formatFuelDate = (value) => {
        if (!value) return 'bez omezení';
        const parts = String(value).slice(0, 10).split('-');
        if (parts.length !== 3) return String(value);
        return new Intl.DateTimeFormat('cs-CZ').format(
            new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
        );
    };

    const fuelStatusLabel = (status) => ({
        draft: 'Koncept',
        active: 'Aktivní',
        ended: 'Ukončený',
        cancelled: 'Zrušený',
    }[status] || status || '—');
    const setStatus = (message, error = false) => {
        const node = document.querySelector('[data-fuel-surcharge-status]');
        if (!node) return;
        node.textContent = message;
        node.classList.toggle('is-error', error);
    };

    const load = async () => {
        setStatus('Načítám palivové příplatky…');
        const results = await Promise.allSettled([
            request('/fuel-surcharges'),
            request('/customers'),
            request('/own-drivers'),
            request('/external-carriers'),
        ]);
        state.surcharges = results[0].status === 'fulfilled'
            ? dataItems(results[0].value)
            : [];
        state.customers = results[1].status === 'fulfilled'
            ? dataItems(results[1].value)
            : [];
        state.drivers = results[2].status === 'fulfilled'
            ? dataItems(results[2].value)
            : [];
        state.carriers = results[3].status === 'fulfilled'
            ? dataItems(results[3].value)
            : [];
        renderFilters();
        renderTable();
        if (results[0].status === 'rejected') {
            setStatus(results[0].reason?.message || 'Příplatky se nepodařilo načíst.', true);
            return;
        }
        setStatus(`Načteno ${state.surcharges.length} záznamů.`);
    };

    const renderFilters = () => {
        const select = document.querySelector('[data-fuel-filter-customer]');
        if (!select) return;
        const current = select.value;
        select.innerHTML = '<option value="">Všichni odběratelé</option>'
            + state.customers.map((item) => {
                const id = customerIdentity(item);
                return `<option value="${id}">${escapeHtml(customerName(item))}</option>`;
            }).join('');
        select.value = current;
    };

    const customerLabelById = (id) => {
        const item = state.customers.find((candidate) => customerIdentity(candidate) === Number(id));
        return item ? customerName(item) : `Vztah odběratele ${id}`;
    };

    const renderTable = () => {
        const body = document.querySelector('[data-fuel-surcharge-rows]');
        if (!body) return;
        const customerFilter = document.querySelector('[data-fuel-filter-customer]')?.value || '';
        const statusFilter = document.querySelector('[data-fuel-filter-status]')?.value || '';
        const search = (document.querySelector('[data-fuel-filter-search]')?.value || '').trim().toLocaleLowerCase('cs');
        const items = state.surcharges.filter((item) => {
            const customerMatches = !customerFilter
                || Number(item.customer_relationship_id) === Number(customerFilter);
            const statusMatches = !statusFilter || item.status === statusFilter;
            const recipientText = (item.recipients || []).map((rate) => [
                rate.recipient_type,
                rate.driver_organization_assignment_id,
                rate.carrier_relationship_id,
            ].join(' ')).join(' ').toLocaleLowerCase('cs');
            const textMatches = !search
                || customerLabelById(item.customer_relationship_id).toLocaleLowerCase('cs').includes(search)
                || recipientText.includes(search);
            return customerMatches && statusMatches && textMatches;
        });

        if (items.length === 0) {
            body.innerHTML = '<tr><td colspan="8" class="s039-empty">Žádný palivový příplatek neodpovídá filtru.</td></tr>';
            return;
        }

        body.innerHTML = items.map((item) => {
            const recipients = Array.isArray(item.recipients) ? item.recipients : [];
            const payout = recipients.length === 0
                ? 'Bez příjemců'
                : `${recipients.length} individuálních sazeb`;
            const margins = recipients.map((rate) => Number(rate.margin_per_actual_km ?? 0));
            const margin = margins.length === 0
                ? '—'
                : `${Math.min(...margins).toLocaleString('cs-CZ')} až ${Math.max(...margins).toLocaleString('cs-CZ')} Kč/km`;
            return `<tr>
                <td><strong>${escapeHtml(customerLabelById(item.customer_relationship_id))}</strong></td>
                <td>${escapeHtml(formatFuelDate(item.valid_from))}</td>
                <td>${escapeHtml(formatFuelDate(item.valid_until))}</td>
                <td>${escapeHtml(money(item.billing_rate_per_actual_km))}<small>bez DPH</small></td>
                <td>${escapeHtml(payout)}</td>
                <td>${escapeHtml(margin)}</td>
                <td><span class="s039-state s039-state-${escapeHtml(item.status)}">${escapeHtml(fuelStatusLabel(item.status))}</span></td>
                <td><button type="button" data-fuel-detail="${escapeHtml(item.public_id)}">Podrobnosti</button></td>
            </tr>`;
        }).join('');
    };

    const blankDraft = () => ({
        customer_relationship_id: '',
        valid_from: new Date().toISOString().slice(0, 10),
        valid_until: '',
        billing_rate_per_actual_km: '',
        recipients: [],
        actual_km_confirmed: true,
        note: '',
    });

    const openWizard = () => {
        state.step = 1;
        state.draft = blankDraft();
        renderWizard();
    };

    const closeWizard = () => {
        document.querySelector('[data-fuel-wizard-layer]')?.remove();
        state.draft = null;
    };

    const recipientKey = (type, id) => `${type}:${id}`;
    const selectedRecipient = (key) => state.draft.recipients.find((item) => item.key === key);

    const stepOne = () => `
        <p>Vyberte odběratele a období platnosti. Nová sazba automaticky ukončí předchozí aktivní sazbu tohoto odběratele.</p>
        <div class="s039-grid s039-grid-3">
            <label>Odběratel
                <select data-wizard-field="customer_relationship_id">
                    <option value="">Vyberte odběratele</option>
                    ${state.customers.map((item) => `<option value="${customerIdentity(item)}" ${Number(state.draft.customer_relationship_id) === customerIdentity(item) ? 'selected' : ''}>${escapeHtml(customerName(item))}</option>`).join('')}
                </select>
            </label>
            <label>Platnost od
                <input type="date" data-wizard-field="valid_from" value="${escapeHtml(state.draft.valid_from)}">
            </label>
            <label>Platnost do <small>(nepovinné)</small>
                <input type="date" data-wizard-field="valid_until" value="${escapeHtml(state.draft.valid_until)}">
            </label>
        </div>`;

    const stepTwo = () => `
        <p>Zadejte sazbu, kterou fakturujete odběrateli za každý skutečně ujetý kilometr.</p>
        <div class="s039-net-note"><strong>Částku zadávejte bez DPH.</strong> DPH se přidá až při vystavení faktury.</div>
        <label class="s039-rate-label">Fakturovaná sazba bez DPH (Kč / skutečný km)
            <input type="number" min="0" step="0.0001" data-wizard-field="billing_rate_per_actual_km" value="${escapeHtml(state.draft.billing_rate_per_actual_km)}">
        </label>`;

    const recipientCards = (items, type, identity, label) => items.map((item) => {
        const id = identity(item);
        const key = recipientKey(type, id);
        return `<label class="s039-recipient-card">
            <input type="checkbox" data-recipient-key="${escapeHtml(key)}" data-recipient-type="${type}" data-recipient-id="${id}" ${selectedRecipient(key) ? 'checked' : ''}>
            <span><strong>${escapeHtml(label(item))}</strong><small>${type === 'own_driver' ? 'Vlastní řidič' : 'Externí dopravce'}</small></span>
        </label>`;
    }).join('');

    const stepThree = () => `
        <p>Vyberte, komu příplatek přidělíte. Není nutné vybrat nikoho; odběratelský příplatek může existovat samostatně.</p>
        <h4>Vlastní řidiči</h4>
        <div class="s039-recipient-grid">${recipientCards(state.drivers, 'own_driver', driverIdentity, driverName) || '<span class="s039-empty">Nejsou dostupní vlastní řidiči.</span>'}</div>
        <h4>Externí dopravci</h4>
        <div class="s039-recipient-grid">${recipientCards(state.carriers, 'external_carrier', carrierIdentity, carrierName) || '<span class="s039-empty">Nejsou dostupní externí dopravci.</span>'}</div>`;

    const recipientDisplayName = (recipient) => {
        if (recipient.recipient_type === 'own_driver') {
            return driverName(state.drivers.find((item) => driverIdentity(item) === recipient.driver_organization_assignment_id) || {});
        }
        return carrierName(state.carriers.find((item) => carrierIdentity(item) === recipient.carrier_relationship_id) || {});
    };

    const stepFour = () => {
        if (state.draft.recipients.length === 0) {
            return '<p>Nebyl vybrán žádný příjemce. Tento krok můžete potvrdit bez zadání výplatních sazeb.</p><div class="s039-net-note">Odběratelská sazba zůstane evidována bez přidělení řidičům nebo dopravcům.</div>';
        }
        return `<p>Každému vybranému příjemci určete jeho vlastní sazbu. Hodnota 0 Kč je platná.</p>
            <div class="s039-net-note"><strong>Všechny sazby zadávejte bez DPH.</strong> Interní rozdíl vůči odběratelské sazbě příjemce neuvidí.</div>
            <div class="s039-payout-list">${state.draft.recipients.map((recipient) => `
                <label><span><strong>${escapeHtml(recipientDisplayName(recipient))}</strong><small>${recipient.recipient_type === 'own_driver' ? 'Vlastní řidič' : 'Externí dopravce'}</small></span>
                    <input type="number" min="0" step="0.0001" data-payout-key="${escapeHtml(recipient.key)}" value="${escapeHtml(recipient.payout_rate_per_actual_km ?? '')}" placeholder="0,00">
                    <em>Kč/km bez DPH</em>
                </label>`).join('')}</div>`;
    };

    const stepFive = () => `
        <p>Palivový příplatek se vždy počítá pouze ze skutečně ujetých kilometrů evidovaných u trasy.</p>
        <label class="s039-confirm-card">
            <input type="checkbox" data-wizard-field="actual_km_confirmed" ${state.draft.actual_km_confirmed ? 'checked' : ''}>
            <span><strong>Potvrzuji výpočet ze skutečných kilometrů</strong><small>Fakturovaná částka = skutečné km × sazba odběratele. Výplata příjemce = skutečné km × jeho individuální sazba.</small></span>
        </label>`;

    const stepSix = () => {
        const customer = customerLabelById(state.draft.customer_relationship_id);
        const recipients = state.draft.recipients.length === 0
            ? '<li>Bez přiřazených příjemců</li>'
            : state.draft.recipients.map((item) => `<li>${escapeHtml(recipientDisplayName(item))}: <strong>${escapeHtml(money(item.payout_rate_per_actual_km))}</strong> bez DPH</li>`).join('');
        return `<div class="s039-summary">
            <h4>${escapeHtml(customer)}</h4>
            <dl>
                <dt>Platnost</dt><dd>${escapeHtml(formatFuelDate(state.draft.valid_from))} – ${escapeHtml(formatFuelDate(state.draft.valid_until))}</dd>
                <dt>Odběratelská sazba</dt><dd><strong>${escapeHtml(money(state.draft.billing_rate_per_actual_km))}</strong> bez DPH</dd>
                <dt>Výpočet</dt><dd>Skutečné km × příslušná sazba</dd>
            </dl>
            <h5>Příjemci a jejich individuální sazby</h5><ul>${recipients}</ul>
            <div class="s039-net-note">Interní marže zůstává viditelná pouze oprávněné správě DRAYVIA.</div>
        </div>`;
    };

    const stepContent = () => [stepOne, stepTwo, stepThree, stepFour, stepFive, stepSix][state.step - 1]();

    const renderWizard = () => {
        document.querySelector('[data-fuel-wizard-layer]')?.remove();
        const layer = document.createElement('div');
        layer.className = 's039-modal-layer';
        layer.dataset.fuelWizardLayer = '1';
        layer.innerHTML = `<section class="s039-modal" role="dialog" aria-modal="true" aria-labelledby="s039FuelWizardTitle">
            <header><small>Krok ${state.step} ze 6</small><h3 id="s039FuelWizardTitle">${[
                'Pro koho a od kdy příplatek platí?',
                'Jakou sazbu fakturujete odběrateli?',
                'Komu příplatek přidělíte?',
                'Kolik jednotlivým příjemcům vyplatíte?',
                'Z čeho se příplatek vypočítá?',
                'Kontrola a potvrzení',
            ][state.step - 1]}</h3></header>
            <div class="s039-modal-body">${stepContent()}</div>
            <footer><button type="button" data-wizard-cancel>Zrušit</button><span></span>${state.step > 1 ? '<button type="button" data-wizard-back>Zpět</button>' : ''}<button type="button" class="primary" data-wizard-next>${state.step === 6 ? 'Uložit palivový příplatek' : 'Pokračovat'}</button></footer>
        </section>`;
        document.body.appendChild(layer);
    };

    const syncWizard = () => {
        document.querySelectorAll('[data-wizard-field]').forEach((field) => {
            const key = field.dataset.wizardField;
            state.draft[key] = field.type === 'checkbox' ? field.checked : field.value;
        });
        if (state.step === 3) {
            const recipients = [];
            document.querySelectorAll('[data-recipient-key]:checked').forEach((input) => {
                const existing = selectedRecipient(input.dataset.recipientKey);
                const id = Number(input.dataset.recipientId);
                recipients.push({
                    key: input.dataset.recipientKey,
                    recipient_type: input.dataset.recipientType,
                    driver_organization_assignment_id: input.dataset.recipientType === 'own_driver' ? id : null,
                    carrier_relationship_id: input.dataset.recipientType === 'external_carrier' ? id : null,
                    payout_rate_per_actual_km: existing?.payout_rate_per_actual_km ?? '',
                });
            });
            state.draft.recipients = recipients;
        }
        if (state.step === 4) {
            document.querySelectorAll('[data-payout-key]').forEach((input) => {
                const recipient = selectedRecipient(input.dataset.payoutKey);
                if (recipient) recipient.payout_rate_per_actual_km = input.value;
            });
        }
    };

    const validateStep = () => {
        syncWizard();
        if (state.step === 1) {
            if (!Number(state.draft.customer_relationship_id)) throw new Error('Vyberte odběratele.');
            if (!state.draft.valid_from) throw new Error('Zadejte platnost od.');
            if (state.draft.valid_until && state.draft.valid_until < state.draft.valid_from) throw new Error('Platnost do nesmí být před platností od.');
        }
        if (state.step === 2 && (state.draft.billing_rate_per_actual_km === '' || Number(state.draft.billing_rate_per_actual_km) < 0)) throw new Error('Zadejte platnou odběratelskou sazbu.');
        if (state.step === 4 && state.draft.recipients.some((item) => item.payout_rate_per_actual_km === '' || Number(item.payout_rate_per_actual_km) < 0)) throw new Error('Zadejte sazbu každému vybranému příjemci.');
        if (state.step === 5 && !state.draft.actual_km_confirmed) throw new Error('Potvrďte výpočet ze skutečných kilometrů.');
    };

    const save = async () => {
        const payload = {
            customer_relationship_id: Number(state.draft.customer_relationship_id),
            billing_rate_per_actual_km: Number(state.draft.billing_rate_per_actual_km),
            valid_from: state.draft.valid_from,
            valid_until: state.draft.valid_until || null,
            note: state.draft.note || null,
            recipients: state.draft.recipients.map((item) => ({
                recipient_type: item.recipient_type,
                driver_organization_assignment_id: item.driver_organization_assignment_id,
                carrier_relationship_id: item.carrier_relationship_id,
                payout_rate_per_actual_km: Number(item.payout_rate_per_actual_km),
            })),
        };
        const button = document.querySelector('[data-wizard-next]');
        if (button) { button.disabled = true; button.textContent = 'Ukládám…'; }
        await request('/fuel-surcharges', {method: 'POST', body: JSON.stringify(payload)});
        closeWizard();
        await load();
        setStatus('Palivový příplatek byl uložen.');
    };

    const bind = (root) => {
        root.addEventListener('click', async (event) => {
            if (event.target.closest('[data-fuel-surcharge-add]')) openWizard();
            if (event.target.closest('[data-fuel-surcharge-refresh]')) load().catch((error) => setStatus(error.message, true));
        });
        root.addEventListener('input', (event) => {
            if (event.target.matches('[data-fuel-filter-customer], [data-fuel-filter-status], [data-fuel-filter-search]')) renderTable();
        });
        document.addEventListener('click', async (event) => {
            if (!state.draft) return;
            if (event.target.closest('[data-wizard-cancel]')) return closeWizard();
            if (event.target.closest('[data-wizard-back]')) { syncWizard(); state.step -= 1; return renderWizard(); }
            if (event.target.closest('[data-wizard-next]')) {
                try {
                    validateStep();
                    if (state.step === 6) await save();
                    else { state.step += 1; renderWizard(); }
                } catch (error) { window.alert(error.message); }
            }
        });
    };

    const decorateWorkspace = (root) => {
        if (root.dataset.tabsDecorated === '1') {
            return;
        }

        const sections = Array.from(
            root.querySelectorAll(':scope > section')
        );

        if (sections.length !== 3) {
            return;
        }

        root.dataset.tabsDecorated = '1';

        const navigation = document.createElement('div');

        navigation.className = 's039-phm-tabs';
        navigation.setAttribute('role', 'tablist');

        navigation.innerHTML = `
            <button
                type="button"
                class="is-active"
                role="tab"
                aria-selected="true"
                data-phm-tab="surcharges"
            >
                <strong>Palivové příplatky</strong>
                <small>Sazby odběratelů a příjemců</small>
            </button>

            <button
                type="button"
                role="tab"
                aria-selected="false"
                data-phm-tab="imports"
            >
                <strong>Tankování a importy</strong>
                <small>MOL, ORLEN a kontrola dávek</small>
            </button>

            <button
                type="button"
                role="tab"
                aria-selected="false"
                data-phm-tab="cards"
            >
                <strong>Správa palivových karet</strong>
                <small>Držitelé, odpovědnost a historie</small>
            </button>
        `;

        const surchargePane = document.createElement('div');

        surchargePane.className = 's039-phm-pane';
        surchargePane.dataset.phmPane = 'surcharges';
        surchargePane.appendChild(sections[0]);

        const importPane = document.createElement('div');

        importPane.className = 's039-phm-pane';
        importPane.dataset.phmPane = 'imports';
        importPane.hidden = true;
        importPane.appendChild(sections[2]);

        const cardsPane = document.createElement('div');
        cardsPane.className = 's039-phm-pane';
        cardsPane.dataset.phmPane = 'cards';
        cardsPane.hidden = true;
        cardsPane.appendChild(sections[1]);

        const style = document.createElement('style');

        style.textContent = `
            .s039-phm-tabs {
                display: flex;
                gap: 10px;
                width: max-content;
                max-width: 100%;
                padding: 6px;
                margin-bottom: 4px;
                border: 1px solid #d7e0eb;
                border-radius: 14px;
                background: #e9eef5;
            }

            .s039-phm-tabs button {
                display: grid;
                gap: 3px;
                min-width: 230px;
                padding: 12px 16px;
                text-align: left;
                color: #475569;
                border: 0;
                border-radius: 10px;
                background: transparent;
            }

            .s039-phm-tabs button strong {
                font-size: 15px;
            }

            .s039-phm-tabs button small {
                color: #64748b;
                font-weight: 500;
            }

            .s039-phm-tabs button.is-active {
                color: #172033;
                background: #ffffff;
                box-shadow: 0 2px 10px rgba(15, 23, 42, .10);
            }

            .s039-phm-pane > .drayvia-preview-panel {
                padding: 24px !important;
                border: 1px solid #dce3ec !important;
                border-radius: 16px !important;
                background: #ffffff !important;
                box-shadow: 0 7px 24px rgba(15, 23, 42, .05);
            }

            .s039-phm-pane[hidden] {
                display: none !important;
            }

            @media (max-width: 700px) {
                .s039-phm-tabs {
                    display: grid;
                    width: 100%;
                }

                .s039-phm-tabs button {
                    width: 100%;
                    min-width: 0;
                }
            }
        `;

        root.replaceChildren(
            style,
            navigation,
            surchargePane,
            importPane,
            cardsPane
        );

        navigation.addEventListener('click', (event) => {
            const button = event.target.closest('[data-phm-tab]');

            if (!button) {
                return;
            }

            const selectedTab = button.dataset.phmTab;

            navigation
                .querySelectorAll('[data-phm-tab]')
                .forEach((candidate) => {
                    const active = candidate === button;

                    candidate.classList.toggle(
                        'is-active',
                        active
                    );

                    candidate.setAttribute(
                        'aria-selected',
                        active ? 'true' : 'false'
                    );
                });

            root
                .querySelectorAll('[data-phm-pane]')
                .forEach((pane) => {
                    pane.hidden =
                        pane.dataset.phmPane !== selectedTab;
                });
        });
    };

    const mount = () => {
        const root = document.querySelector('[data-fuel-surcharge-root]');
        if (!root || root.dataset.mounted === '1') return;
        root.dataset.mounted = '1';
        state.mounted = true;
        decorateWorkspace(root);
        bind(root);
        load().catch((error) => setStatus(error.message, true));
    };

    window.DrayviaFuelSurcharge = {mount};
})();
