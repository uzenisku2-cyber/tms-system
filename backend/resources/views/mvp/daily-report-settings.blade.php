<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nastavení denního výkazu · TMS System</title>
    <style>
        :root {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #18212f;
            background: #f4f6f8;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f6f8;
        }

        button, input { font: inherit; }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px 1fr;
        }

        .sidebar {
            background: #17202b;
            color: #fff;
            padding: 28px 22px;
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 34px;
        }

        .pilot {
            display: block;
            margin-top: 7px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #b8c4d4;
        }

        .nav {
            display: grid;
            gap: 8px;
        }

        .nav a {
            color: #d5dde8;
            text-decoration: none;
            padding: 11px 12px;
            border-radius: 8px;
        }

        .nav a.active {
            color: #fff;
            background: #283544;
            font-weight: 700;
        }

        .main {
            padding: 30px 34px 60px;
        }

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 7px;
            font-size: 27px;
        }

        .subtitle {
            color: #667085;
            max-width: 820px;
            line-height: 1.5;
        }

        .api-state {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #027a48;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #12b76a;
        }

        .card {
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 5px 18px rgba(16, 24, 40, .035);
        }

        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .card h2, .card h3 {
            margin: 0;
        }

        .muted {
            color: #667085;
            font-size: 13px;
            line-height: 1.5;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .btn {
            border: 1px solid transparent;
            border-radius: 9px;
            padding: 9px 13px;
            cursor: pointer;
            font-weight: 750;
        }

        .btn-primary {
            background: #155eef;
            color: white;
        }

        .btn-light {
            background: white;
            border-color: #d0d5dd;
            color: #344054;
        }

        .btn-green {
            background: #ecfdf3;
            border-color: #abefc6;
            color: #027a48;
        }

        .btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .form-panel[hidden] { display: none !important; }

        .validity-grid {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) minmax(190px, .8fr);
            gap: 14px;
            align-items: end;
            margin-bottom: 20px;
        }

        label {
            display: grid;
            gap: 6px;
            color: #344054;
            font-size: 12px;
            font-weight: 800;
        }

        input[type="date"] {
            width: 100%;
            padding: 10px 11px;
            border: 1px solid #d0d5dd;
            border-radius: 9px;
            background: #fff;
        }

        .checkbox-line {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 41px;
            color: #344054;
            font-size: 13px;
            font-weight: 700;
        }

        .field-table {
            display: grid;
            gap: 8px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 52px minmax(230px, 1fr) 120px 120px 110px;
            gap: 10px;
            align-items: center;
            padding: 10px 12px;
            border: 1px solid #eaecf0;
            border-radius: 10px;
            background: #fff;
        }

        .field-order {
            font-weight: 800;
            text-align: center;
            color: #475467;
        }

        .field-name {
            font-weight: 750;
        }

        .field-system {
            color: #667085;
            font-size: 11px;
            margin-top: 3px;
        }

        .move-buttons {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
        }

        .move {
            width: 34px;
            height: 32px;
            border: 1px solid #d0d5dd;
            background: #fff;
            border-radius: 7px;
            cursor: pointer;
        }

        .move:disabled {
            opacity: .35;
            cursor: not-allowed;
        }

        .message {
            margin: 12px 0;
            padding: 11px 12px;
            border-radius: 9px;
            font-size: 13px;
            display: none;
        }

        .message.ok {
            display: block;
            color: #027a48;
            background: #ecfdf3;
            border: 1px solid #abefc6;
        }

        .message.error {
            display: block;
            color: #b42318;
            background: #fef3f2;
            border: 1px solid #fecdca;
        }

        .version-list {
            display: grid;
            gap: 12px;
        }

        .version {
            border: 1px solid #e4e7ec;
            border-radius: 12px;
            padding: 16px;
        }

        .version-head {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .status {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .status.active {
            color: #027a48;
            background: #ecfdf3;
        }

        .status.scheduled {
            color: #175cd3;
            background: #eff8ff;
        }

        .status.ended {
            color: #475467;
            background: #f2f4f7;
        }

        .field-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .chip {
            border-radius: 999px;
            padding: 5px 8px;
            background: #f2f4f7;
            color: #475467;
            font-size: 11px;
        }

        .chip.required {
            color: #027a48;
            background: #ecfdf3;
        }

        .chip.hidden-field {
            color: #667085;
            text-decoration: line-through;
        }

        .end-line {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 14px;
        }

        .end-line input {
            max-width: 180px;
        }

        .empty {
            color: #667085;
            padding: 18px 0;
        }

        .field-add-line {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin: 14px 0 4px;
        }

        .custom-field-panel {
            margin: 14px 0;
            padding: 16px;
            border: 1px solid #d0d5dd;
            border-radius: 12px;
            background: #f9fafb;
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(180px, 260px) auto;
            gap: 12px;
            align-items: end;
        }

        .custom-field-panel[hidden] {
            display: none;
        }

        .custom-field-panel label {
            display: grid;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .custom-field-panel input,
        .custom-field-panel select {
            width: 100%;
            border: 1px solid #d0d5dd;
            border-radius: 9px;
            padding: 10px 12px;
            background: #fff;
        }

        .custom-field-actions {
            display: flex;
            gap: 8px;
        }

        .field-tools {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 7px;
        }

        .field-tool {
            border: 0;
            background: transparent;
            padding: 0;
            color: #175cd3;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
        }

        .field-tool.remove {
            color: #b42318;
        }

        .version-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        @media (max-width: 1050px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                padding: 18px;
            }

            .validity-grid {
                grid-template-columns: 1fr;
            }

            .field-row {
                grid-template-columns: 42px 1fr;
            }

            .field-row > *:nth-child(n+3) {
                grid-column: 2;
            }

            .move-buttons {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            TMS System
            <span class="pilot">MVP / Pilot Launch</span>
        </div>

        <nav class="nav">
            <a href="/app">Denní provoz</a>
            <a href="/carriers">Dopravci a řidiči</a>
            <a class="active" href="/daily-report-settings">Nastavení denního výkazu</a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>Nastavení denního výkazu</h1>
                <div class="subtitle">
                    Nejvýše nadřazená organizace určuje položky, jejich pořadí a povinnost.
                    Každá verze má vlastní platnost od–do nebo platí bez omezení až do ukončení.
                </div>
            </div>

            <div class="api-state">
                <span class="dot"></span>
                API připojeno
            </div>
        </div>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2>Verze formuláře</h2>
                    <div class="muted">
                        Nová verze nepozmění staré období. Pokud současná verze platí bez omezení,
                        vytvoření nové verze ji automaticky ukončí den před začátkem nové.
                    </div>
                </div>

                <button id="add-version-button" class="btn btn-primary" type="button">
                    + Nová verze nastavení
                </button>
            </div>

            <div id="create-panel" class="form-panel" hidden>
                <div class="validity-grid">
                    <label>
                        Platnost od
                        <input id="valid-from" type="date" required>
                    </label>

                    <label>
                        Platnost do
                        <input id="valid-until" type="date">
                    </label>

                    <label class="checkbox-line">
                        <input id="unlimited" type="checkbox" checked>
                        Bez omezení – do ukončení / nahrazení
                    </label>
                </div>

                <div class="muted" style="margin-bottom:10px">
                    Datum jízdy a číslo trasy jsou systémové položky a zůstávají vždy zobrazené a povinné.
                </div>

                <div id="field-table" class="field-table"></div>

                <div class="field-add-line">
                    <button id="add-custom-field-button" class="btn btn-light" type="button">
                        + Přidat položku
                    </button>
                    <span class="muted">
                        Vlastní položka se uloží do nové verze a nezmění historické verze.
                    </span>
                </div>

                <div id="custom-field-panel" class="custom-field-panel" hidden>
                    <div>
                        <label>
                            Název položky
                            <input id="custom-field-label" type="text" maxlength="100">
                        </label>
                    </div>

                    <div>
                        <label>
                            Typ hodnoty
                            <select id="custom-field-type">
                                <option value="number">Číslo</option>
                                <option value="text">Text</option>
                                <option value="time">Čas</option>
                                <option value="money">Částka Kč</option>
                                <option value="boolean">Ano / ne</option>
                            </select>
                        </label>
                    </div>

                    <div class="custom-field-actions">
                        <button id="cancel-custom-field-button" class="btn btn-light" type="button">
                            Zrušit
                        </button>
                        <button id="save-custom-field-button" class="btn btn-primary" type="button">
                            Přidat položku
                        </button>
                    </div>
                </div>

                <div id="create-message" class="message"></div>

                <div class="actions">
                    <button id="cancel-button" class="btn btn-light" type="button">
                        Zrušit
                    </button>

                    <button id="save-button" class="btn btn-primary" type="button">
                        Uložit novou verzi
                    </button>
                </div>
            </div>
        </section>

        <section class="card">
            <h2 style="margin-bottom:14px">Historie nastavení</h2>
            <div id="list-message" class="message"></div>
            <div id="version-list" class="version-list">
                <div class="empty">Načítám nastavení…</div>
            </div>
        </section>
    </main>
</div>

<script>
(() => {
    const tokenKey = 'tms_mvp_token';
    const organizationId = 1;

    const addButton = document.getElementById('add-version-button');
    const createPanel = document.getElementById('create-panel');
    const validFrom = document.getElementById('valid-from');
    const validUntil = document.getElementById('valid-until');
    const unlimited = document.getElementById('unlimited');
    const fieldTable = document.getElementById('field-table');
    const createMessage = document.getElementById('create-message');
    const listMessage = document.getElementById('list-message');
    const saveButton = document.getElementById('save-button');
    const cancelButton = document.getElementById('cancel-button');
    const versionList = document.getElementById('version-list');

    const addCustomFieldButton = document.getElementById('add-custom-field-button');
    const customFieldPanel = document.getElementById('custom-field-panel');
    const customFieldLabel = document.getElementById('custom-field-label');
    const customFieldType = document.getElementById('custom-field-type');
    const cancelCustomFieldButton = document.getElementById('cancel-custom-field-button');
    const saveCustomFieldButton = document.getElementById('save-custom-field-button');

    let token = sessionStorage.getItem(tokenKey) || '';
    let sourceVersion = null;
    let editingCustomKey = null;
    let loadedVersions = [];

    const canonicalFields = () => ([
        { key: 'service_date', label: 'Datum', type: 'date', order: 1, visible: true, required: true, system: true, custom: false },
        { key: 'route_number', label: 'Trasa č.', type: 'text', order: 2, visible: true, required: true, system: true, custom: false },
        { key: 'departure_time', label: 'Čas odjezdu', type: 'time', order: 3, visible: true, required: true, system: false, custom: false },
        { key: 'arrival_time', label: 'Čas příjezdu', type: 'time', order: 4, visible: true, required: true, system: false, custom: false },
        { key: 'actual_km', label: 'Trasa naměřená', type: 'number', order: 5, visible: true, required: true, system: false, custom: false },
        { key: 'planned_km', label: 'Trasa plánovaná', type: 'number', order: 6, visible: true, required: true, system: false, custom: false },
        { key: 'loaded_parcels', label: 'Naloženo ks', type: 'number', order: 7, visible: true, required: true, system: false, custom: false },
        { key: 'delivered_parcels', label: 'Doručeno na adresu', type: 'number', order: 8, visible: true, required: true, system: false, custom: false },
        { key: 'redirected_parcels', label: 'Doručeno na výdejní místo', type: 'number', order: 9, visible: true, required: true, system: false, custom: false },
        { key: 'undelivered_parcels', label: 'Odmítnuté ks', type: 'number', order: 10, visible: true, required: true, system: false, custom: false },
        { key: 'surcharge_amount', label: 'Příplatek', type: 'money', order: 11, visible: true, required: false, system: false, custom: false },
        { key: 'operational_notes', label: 'Poznámka', type: 'text', order: 12, visible: true, required: false, system: false, custom: false },
    ]);

    const canonicalByKey = Object.fromEntries(
        canonicalFields().map((field) => [field.key, field]),
    );

    let fields = canonicalFields();

    const typeText = (type) => ({
        date: 'Datum',
        text: 'Text',
        number: 'Číslo',
        time: 'Čas',
        money: 'Částka Kč',
        boolean: 'Ano / ne',
    }[type] || type);

    const headers = (json = false) => {
        const result = {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
            'X-Organization-ID': String(organizationId),
        };

        if (json) {
            result['Content-Type'] = 'application/json';
        }

        return result;
    };

    const apiError = async (response) => {
        try {
            const payload = await response.json();

            if (payload?.errors) {
                const messages = Object.values(payload.errors).flat();

                if (messages.length) {
                    return messages.join(' ');
                }
            }

            return payload?.message || `HTTP ${response.status}`;
        } catch {
            return `HTTP ${response.status}`;
        }
    };

    const ensureAuthorized = (response) => {
        if (response.status === 401) {
            sessionStorage.removeItem(tokenKey);
            window.location.href = '/login';
            return false;
        }

        if (response.status === 403) {
            listMessage.className = 'message error';
            listMessage.textContent =
                'Toto nastavení může měnit pouze nejvýše nadřazená organizace s administrátorským oprávněním.';
            return false;
        }

        return true;
    };

    const setMessage = (element, text = '', type = '') => {
        element.textContent = text;
        element.className = type
            ? `message ${type}`
            : 'message';
    };

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const formatDate = (value) => {
        if (!value) {
            return 'bez omezení';
        }

        const [year, month, day] = value.split('-');

        return `${day}.${month}.${year}`;
    };

    const statusText = (status) => ({
        active: 'Aktivní',
        scheduled: 'Naplánováno',
        ended: 'Ukončeno',
    }[status] || status);

    const hydrateField = (field, index) => {
        const canonical = canonicalByKey[field.key];

        if (canonical) {
            return {
                ...canonical,
                order: Number(field.order || index + 1),
                visible: canonical.system
                    ? true
                    : Boolean(field.visible),
                required: canonical.system
                    ? true
                    : Boolean(field.required),
            };
        }

        return {
            key: String(field.key || ''),
            label: String(field.label || 'Vlastní položka'),
            type: String(field.type || 'text'),
            order: Number(field.order || index + 1),
            visible: field.visible !== false,
            required: Boolean(field.required),
            system: false,
            custom: true,
        };
    };

    const normalizeOrders = () => {
        fields.forEach((field, index) => {
            field.order = index + 1;
        });
    };

    const closeCustomEditor = () => {
        customFieldPanel.hidden = true;
        customFieldLabel.value = '';
        customFieldType.value = 'number';
        editingCustomKey = null;
        saveCustomFieldButton.textContent = 'Přidat položku';
    };

    const openCustomEditor = (field = null) => {
        editingCustomKey = field?.key || null;
        customFieldLabel.value = field?.label || '';
        customFieldType.value = field?.type || 'number';
        saveCustomFieldButton.textContent = field
            ? 'Uložit změnu položky'
            : 'Přidat položku';
        customFieldPanel.hidden = false;
        customFieldLabel.focus();
    };

    const generateCustomKey = () => {
        if (
            typeof crypto !== 'undefined'
            && typeof crypto.randomUUID === 'function'
        ) {
            return `custom_${crypto.randomUUID().replaceAll('-', '')}`;
        }

        const random = Math.random()
            .toString(36)
            .replace(/[^a-z0-9]/g, '')
            .padEnd(12, '0');

        return `custom_${Date.now().toString(36)}${random}`;
    };

    const renderFields = () => {
        normalizeOrders();

        fieldTable.innerHTML = fields.map((field, index) => {
            const locked = field.system ? 'disabled' : '';
            const descriptor = field.custom
                ? `Vlastní položka · ${typeText(field.type)}`
                : field.system
                    ? `Systémová položka · ${typeText(field.type)}`
                    : `Provozní položka · ${typeText(field.type)}`;

            const customTools = field.custom
                ? `
                    <div class="field-tools">
                        <button
                            class="field-tool edit-custom"
                            type="button"
                            data-key="${escapeHtml(field.key)}"
                        >
                            Upravit položku
                        </button>
                        <button
                            class="field-tool remove remove-custom"
                            type="button"
                            data-key="${escapeHtml(field.key)}"
                        >
                            Odebrat
                        </button>
                    </div>
                `
                : '';

            return `
                <div class="field-row" data-key="${escapeHtml(field.key)}">
                    <div class="field-order">${field.order}.</div>

                    <div>
                        <div class="field-name">${escapeHtml(field.label)}</div>
                        <div class="field-system">${escapeHtml(descriptor)}</div>
                        ${customTools}
                    </div>

                    <label class="checkbox-line">
                        <input
                            class="visible-toggle"
                            type="checkbox"
                            ${field.visible ? 'checked' : ''}
                            ${locked}
                        >
                        Zobrazit
                    </label>

                    <label class="checkbox-line">
                        <input
                            class="required-toggle"
                            type="checkbox"
                            ${field.required ? 'checked' : ''}
                            ${locked}
                        >
                        Povinné
                    </label>

                    <div class="move-buttons">
                        <button
                            class="move move-up"
                            type="button"
                            ${index === 0 ? 'disabled' : ''}
                            title="Posunout nahoru"
                        >↑</button>

                        <button
                            class="move move-down"
                            type="button"
                            ${index === fields.length - 1 ? 'disabled' : ''}
                            title="Posunout dolů"
                        >↓</button>
                    </div>
                </div>
            `;
        }).join('');

        fieldTable.querySelectorAll('.field-row').forEach((row) => {
            const key = row.dataset.key;
            const field = fields.find((item) => item.key === key);

            if (!field) {
                return;
            }

            row.querySelector('.visible-toggle').addEventListener('change', (event) => {
                field.visible = event.target.checked;

                if (!field.visible) {
                    field.required = false;
                }

                renderFields();
            });

            row.querySelector('.required-toggle').addEventListener('change', (event) => {
                field.required = event.target.checked;

                if (field.required) {
                    field.visible = true;
                }

                renderFields();
            });

            row.querySelector('.move-up').addEventListener('click', () => {
                const currentIndex = fields.findIndex((item) => item.key === key);

                if (currentIndex <= 0) {
                    return;
                }

                [fields[currentIndex - 1], fields[currentIndex]] =
                    [fields[currentIndex], fields[currentIndex - 1]];

                renderFields();
            });

            row.querySelector('.move-down').addEventListener('click', () => {
                const currentIndex = fields.findIndex((item) => item.key === key);

                if (
                    currentIndex < 0
                    || currentIndex >= fields.length - 1
                ) {
                    return;
                }

                [fields[currentIndex], fields[currentIndex + 1]] =
                    [fields[currentIndex + 1], fields[currentIndex]];

                renderFields();
            });
        });

        fieldTable.querySelectorAll('.edit-custom').forEach((button) => {
            button.addEventListener('click', () => {
                const field = fields.find(
                    (item) => item.key === button.dataset.key,
                );

                if (field?.custom) {
                    openCustomEditor(field);
                }
            });
        });

        fieldTable.querySelectorAll('.remove-custom').forEach((button) => {
            button.addEventListener('click', () => {
                fields = fields.filter(
                    (item) => item.key !== button.dataset.key,
                );
                closeCustomEditor();
                renderFields();
            });
        });
    };

    const resetCreateForm = () => {
        fields = canonicalFields();
        sourceVersion = null;
        validFrom.value = '';
        validUntil.value = '';
        unlimited.checked = true;
        validUntil.disabled = true;
        saveButton.textContent = 'Uložit novou verzi';
        closeCustomEditor();
        setMessage(createMessage);
        renderFields();
    };

    const closeCreate = () => {
        createPanel.hidden = true;
        addButton.hidden = false;
        resetCreateForm();
    };

    const openNewVersion = () => {
        resetCreateForm();
        createPanel.hidden = false;
        addButton.hidden = true;
        validFrom.focus();
    };

    const openVersionCopy = (item) => {
        sourceVersion = item.version;
        fields = (item.fields || [])
            .map(hydrateField)
            .sort((left, right) => left.order - right.order);

        validFrom.value = '';
        validUntil.value = '';
        unlimited.checked = true;
        validUntil.disabled = true;
        closeCustomEditor();

        saveButton.textContent = `Uložit jako novou verzi z verze ${item.version}`;

        setMessage(
            createMessage,
            `Upravujete kopii verze ${item.version}. Původní verze se nezmění. Zadejte nové datum Platnost od.`,
            'ok',
        );

        createPanel.hidden = false;
        addButton.hidden = true;
        renderFields();
        validFrom.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    unlimited.addEventListener('change', () => {
        validUntil.disabled = unlimited.checked;

        if (unlimited.checked) {
            validUntil.value = '';
        }
    });

    addButton.addEventListener('click', openNewVersion);
    cancelButton.addEventListener('click', closeCreate);
    addCustomFieldButton.addEventListener('click', () => openCustomEditor());
    cancelCustomFieldButton.addEventListener('click', closeCustomEditor);

    saveCustomFieldButton.addEventListener('click', () => {
        const label = customFieldLabel.value.trim();
        const type = customFieldType.value;

        if (!label) {
            setMessage(
                createMessage,
                'Zadejte název vlastní položky.',
                'error',
            );
            customFieldLabel.focus();
            return;
        }

        if (editingCustomKey) {
            const field = fields.find(
                (item) => item.key === editingCustomKey,
            );

            if (field?.custom) {
                field.label = label;
                field.type = type;
            }
        } else {
            if (fields.length >= 40) {
                setMessage(
                    createMessage,
                    'Konfigurace může obsahovat nejvýše 40 položek.',
                    'error',
                );
                return;
            }

            fields.push({
                key: generateCustomKey(),
                label,
                type,
                order: fields.length + 1,
                visible: true,
                required: false,
                system: false,
                custom: true,
            });
        }

        closeCustomEditor();
        renderFields();
        setMessage(createMessage);
    });

    const renderVersions = (items) => {
        loadedVersions = items;

        if (!items.length) {
            versionList.innerHTML = `
                <div class="empty">
                    Zatím není vytvořena žádná verze nastavení.
                </div>
            `;
            return;
        }

        versionList.innerHTML = items.map((item) => {
            const chips = (item.fields || []).map((field, index) => {
                const hydrated = hydrateField(field, index);
                const classes = [
                    'chip',
                    hydrated.required ? 'required' : '',
                    hydrated.visible ? '' : 'hidden-field',
                ].filter(Boolean).join(' ');

                const suffix = !hydrated.visible
                    ? ' · skryté'
                    : hydrated.required
                        ? ' · povinné'
                        : ' · nepovinné';

                const customSuffix = hydrated.custom
                    ? ` · vlastní · ${typeText(hydrated.type)}`
                    : '';

                return `
                    <span class="${classes}">
                        ${hydrated.order}. ${escapeHtml(hydrated.label)}${customSuffix}${suffix}
                    </span>
                `;
            }).join('');

            const endControls = item.unlimited
                ? `
                    <div class="end-line">
                        <input
                            class="end-date"
                            data-id="${item.id}"
                            type="date"
                            min="${escapeHtml(item.valid_from)}"
                        >
                        <button
                            class="btn btn-light end-button"
                            data-id="${item.id}"
                            type="button"
                        >
                            Ukončit platnost
                        </button>
                    </div>
                `
                : '';

            return `
                <article class="version">
                    <div class="version-head">
                        <div>
                            <h3>Verze ${item.version}</h3>
                            <div class="muted" style="margin-top:5px">
                                ${formatDate(item.valid_from)}
                                –
                                ${formatDate(item.valid_until)}
                            </div>
                        </div>

                        <span class="status ${escapeHtml(item.status)}">
                            ${escapeHtml(statusText(item.status))}
                        </span>
                    </div>

                    <div class="field-chips">${chips}</div>

                    <div class="version-actions">
                        <button
                            class="btn btn-primary edit-version-button"
                            data-id="${item.id}"
                            type="button"
                        >
                            Upravit / vytvořit novou verzi
                        </button>
                    </div>

                    ${endControls}
                </article>
            `;
        }).join('');

        versionList.querySelectorAll('.edit-version-button').forEach((button) => {
            button.addEventListener('click', () => {
                const item = loadedVersions.find(
                    (version) => String(version.id) === String(button.dataset.id),
                );

                if (item) {
                    openVersionCopy(item);
                }
            });
        });

        versionList.querySelectorAll('.end-button').forEach((button) => {
            button.addEventListener('click', async () => {
                const id = button.dataset.id;
                const input = versionList.querySelector(`.end-date[data-id="${id}"]`);
                const date = input?.value || '';

                if (!date) {
                    setMessage(
                        listMessage,
                        'Zadejte datum ukončení platnosti.',
                        'error',
                    );
                    input?.focus();
                    return;
                }

                button.disabled = true;
                setMessage(listMessage);

                try {
                    const response = await fetch(
                        `/api/v1/daily-report-form-configurations/${id}/end`,
                        {
                            method: 'PATCH',
                            headers: headers(true),
                            body: JSON.stringify({
                                valid_until: date,
                            }),
                        },
                    );

                    if (!ensureAuthorized(response)) {
                        return;
                    }

                    if (!response.ok) {
                        setMessage(
                            listMessage,
                            await apiError(response),
                            'error',
                        );
                        return;
                    }

                    setMessage(
                        listMessage,
                        'Platnost verze byla ukončena.',
                        'ok',
                    );

                    await loadVersions();
                } finally {
                    button.disabled = false;
                }
            });
        });
    };

    const loadVersions = async () => {
        if (!token) {
            window.location.href = '/login';
            return;
        }

        const response = await fetch(
            '/api/v1/daily-report-form-configurations',
            {
                headers: headers(),
            },
        );

        if (!ensureAuthorized(response)) {
            return;
        }

        if (!response.ok) {
            setMessage(
                listMessage,
                await apiError(response),
                'error',
            );
            return;
        }

        const payload = await response.json();
        const items = payload?.data?.items || [];

        renderVersions(items);
    };

    saveButton.addEventListener('click', async () => {
        if (!validFrom.value) {
            setMessage(
                createMessage,
                'Zadejte datum začátku platnosti nové verze.',
                'error',
            );
            validFrom.focus();
            return;
        }

        if (!unlimited.checked && !validUntil.value) {
            setMessage(
                createMessage,
                'Zadejte datum konce platnosti nebo zvolte Bez omezení.',
                'error',
            );
            validUntil.focus();
            return;
        }

        saveButton.disabled = true;
        setMessage(createMessage);

        try {
            const response = await fetch(
                '/api/v1/daily-report-form-configurations',
                {
                    method: 'POST',
                    headers: headers(true),
                    body: JSON.stringify({
                        valid_from: validFrom.value,
                        valid_until: unlimited.checked
                            ? null
                            : validUntil.value,
                        fields: fields.map((field) => ({
                            key: field.key,
                            label: field.custom ? field.label : null,
                            type: field.custom ? field.type : null,
                            order: field.order,
                            visible: field.visible,
                            required: field.required,
                        })),
                    }),
                },
            );

            if (!ensureAuthorized(response)) {
                return;
            }

            if (!response.ok) {
                setMessage(
                    createMessage,
                    await apiError(response),
                    'error',
                );
                return;
            }

            const sourceText = sourceVersion
                ? ` z verze ${sourceVersion}`
                : '';

            closeCreate();

            setMessage(
                listMessage,
                `Nová verze nastavení${sourceText} byla uložena.`,
                'ok',
            );

            await loadVersions();
        } finally {
            saveButton.disabled = false;
        }
    });

    resetCreateForm();

    loadVersions().catch(() => {
        setMessage(
            listMessage,
            'Nepodařilo se spojit s API.',
            'error',
        );
    });
})();
</script>
</body>
</html>