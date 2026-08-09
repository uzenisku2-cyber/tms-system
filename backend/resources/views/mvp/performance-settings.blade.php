<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <title>
        Provozní limity tras · TMS System
    </title>

    <style>
        :root {
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            color: #18212f;
            background: #f4f6f8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f6f8;
        }

        button,
        input {
            font: inherit;
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px 1fr;
        }

        .sidebar {
            background: #17202b;
            color: #ffffff;
            padding: 28px 22px;
        }

        .brand {
            margin-bottom: 34px;
            font-size: 24px;
            font-weight: 800;
        }

        .pilot {
            display: block;
            margin-top: 3px;
            color: #98a2b3;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .nav {
            display: grid;
            gap: 7px;
        }

        .nav a {
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            color: #d0d5dd;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .nav a:hover,
        .nav a.active {
            background: #253241;
            color: #ffffff;
        }

        .main {
            padding: 30px;
        }

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 7px;
            font-size: 28px;
        }

        .subtitle {
            max-width: 840px;
            color: #667085;
            line-height: 1.5;
        }

        .api-state {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border: 1px solid #abefc6;
            border-radius: 999px;
            background: #ecfdf3;
            color: #027a48;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #12b76a;
        }

        .notice {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid #e4e7ec;
            border-radius: 10px;
            background: #ffffff;
            color: #475467;
            font-size: 13px;
            line-height: 1.5;
        }

        .notice strong {
            color: #344054;
        }

        .grid {
            display: grid;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
            gap: 18px;
        }

        .card {
            padding: 20px;
            border: 1px solid #e4e7ec;
            border-radius: 12px;
            background: #ffffff;
            box-shadow:
                0 1px 2px rgba(16, 24, 40, .04);
        }

        .card h2 {
            margin-bottom: 6px;
            font-size: 18px;
        }

        .muted {
            color: #667085;
            font-size: 12px;
            line-height: 1.5;
        }

        .fields {
            display: grid;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
            gap: 14px;
            margin-top: 18px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field label {
            color: #344054;
            font-size: 12px;
            font-weight: 800;
        }

        .field input {
            width: 100%;
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            background: #ffffff;
            color: #101828;
            outline: none;
        }

        .field input:focus {
            border-color: #75e0a7;
            box-shadow:
                0 0 0 3px rgba(18, 183, 106, .08);
        }

        .field small {
            min-height: 16px;
            color: #98a2b3;
            font-size: 11px;
            line-height: 1.35;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 18px;
        }

        .button {
            min-height: 40px;
            padding: 8px 13px;
            border: 1px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
        }

        .button-primary {
            border-color: #079455;
            background: #079455;
            color: #ffffff;
        }

        .button-secondary {
            border-color: #d0d5dd;
            background: #ffffff;
            color: #344054;
        }

        .button-danger {
            border-color: #fecdca;
            background: #fff5f4;
            color: #b42318;
        }

        .message {
            margin-top: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f2f4f7;
            color: #475467;
            font-size: 12px;
            font-weight: 700;
        }

        .message.ok {
            background: #ecfdf3;
            color: #027a48;
        }

        .message.error {
            background: #fef3f2;
            color: #b42318;
        }

        .effective {
            display: grid;
            gap: 7px;
            margin-top: 14px;
            padding: 12px;
            border-radius: 8px;
            background: #f9fafb;
            color: #475467;
            font-size: 12px;
        }

        .effective-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .effective-row strong {
            color: #344054;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #667085;
            font-size: 11px;
            font-weight: 700;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .legend-dot.warning {
            background: #fff7ed;
            box-shadow:
                inset 3px 0 0 #f79009;
        }

        .legend-dot.critical {
            background: #fef3f2;
            box-shadow:
                inset 3px 0 0 #d92d20;
        }

        @media (max-width: 980px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                padding: 18px;
            }

            .main {
                padding: 18px;
            }

            .grid,
            .fields {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            TMS System
            <span class="pilot">
                MVP / Pilot Launch
            </span>
        </div>

        <nav
            class="nav"
            aria-label="Hlavní navigace"
        >
            <a href="/app">
                Denní provoz
            </a>

            <a href="/carriers">
                Dopravci a řidiči
            </a>

            <a href="/daily-report-settings">
                Nastavení denního výkazu
            </a>

            <a
                class="active"
                href="/performance-settings"
            >
                Provozní limity tras
            </a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>
                    Provozní limity tras
                </h1>

                <div class="subtitle">
                    Nastavte, co je pro vaši organizaci ještě běžný provoz.
                    Konkrétní trasa může mít vlastní výjimku – například městská
                    trasa může tolerovat vyšší podíl přesměrování než vesnická.
                </div>
            </div>

            <div class="api-state">
                <span class="dot"></span>
                API připojeno
            </div>
        </div>

        <div class="notice">
            <strong>
                Provozní limit není ceník.
            </strong>
            Zde nastavené hodnoty pouze řídí barevné upozornění v přehledu tras.
            Finanční příplatek podle procenta přesměrování bude definován
            samostatně ve verzi ceníku.
        </div>

        <div class="grid">
            <section class="card">
                <h2>
                    Výchozí limity organizace
                </h2>

                <div class="muted">
                    Výchozí systémové hodnoty jsou 15 % pro výdejní místa
                    a 10 % pro odchylku kilometrů. Ostatní limity jsou
                    vypnuté, dokud je výslovně nenastavíte.
                </div>

                <div
                    id="organizationFields"
                    class="fields"
                ></div>

                <div class="actions">
                    <button
                        id="saveOrganization"
                        class="button button-primary"
                        type="button"
                    >
                        Uložit výchozí limity
                    </button>
                </div>

                <div
                    id="organizationMessage"
                    class="message"
                >
                    Načítám nastavení…
                </div>

                <div class="legend">
                    <span class="legend-item">
                        <span
                            class="legend-dot warning"
                        ></span>
                        oranžová = mimo nastavený limit
                    </span>

                    <span class="legend-item">
                        <span
                            class="legend-dot critical"
                        ></span>
                        červená = více než 5 procentních bodů mimo limit
                    </span>
                </div>
            </section>

            <section class="card">
                <h2>
                    Výjimka konkrétní trasy
                </h2>

                <div class="muted">
                    Zadejte číslo trasy. Prázdné pole limitu znamená
                    „zdědit hodnotu organizace“. Výjimka se váže na stabilní
                    číslo trasy, nikoliv na konkrétní denní jízdu.
                </div>

                <div
                    class="fields"
                    style="grid-template-columns: 1fr;"
                >
                    <div class="field">
                        <label for="routeNumber">
                            Číslo trasy
                        </label>

                        <input
                            id="routeNumber"
                            type="text"
                            maxlength="100"
                            placeholder="např. 35"
                        >

                        <small>
                            Používá stejnou normalizaci čísla trasy jako denní výkaz.
                        </small>
                    </div>
                </div>

                <div class="actions">
                    <button
                        id="loadRoute"
                        class="button button-secondary"
                        type="button"
                    >
                        Načíst trasu
                    </button>
                </div>

                <div
                    id="routeFields"
                    class="fields"
                ></div>

                <div
                    id="effectiveRoute"
                    class="effective"
                ></div>

                <div class="actions">
                    <button
                        id="saveRoute"
                        class="button button-primary"
                        type="button"
                    >
                        Uložit výjimku
                    </button>

                    <button
                        id="deleteRoute"
                        class="button button-danger"
                        type="button"
                    >
                        Zrušit výjimku
                    </button>
                </div>

                <div
                    id="routeMessage"
                    class="message"
                >
                    Zadejte číslo trasy.
                </div>
            </section>
        </div>
    </main>
</div>

<script>
(() => {
    'use strict';

    const TOKEN_KEY =
        'tms_mvp_token';

    const ORGANIZATION_ID =
        '1';

    const endpoint =
        '/api/v1/daily-reports/performance-policies';

    const metricDefinitions = [
        {
            key: 'redirected_max_percent',
            label: 'Výdejní místo max.',
            hint: 'Podíl přesměrovaných zásilek z naložených.',
        },
        {
            key: 'kilometre_deviation_max_percent',
            label: 'Odchylka kilometrů max.',
            hint: 'Rozdíl skutečných a plánovaných kilometrů.',
        },
        {
            key: 'delivered_address_min_percent',
            label: 'Doručeno na adresu min.',
            hint: 'Prázdné = bez minimálního limitu.',
        },
        {
            key: 'rejected_max_percent',
            label: 'Odmítnuto zákazníkem max.',
            hint: 'Prázdné = bez maximálního limitu.',
        },
        {
            key: 'not_delivered_max_percent',
            label: 'Nedoručeno max.',
            hint: 'Prázdné = bez maximálního limitu.',
        },
    ];

    const organizationFields =
        document.getElementById(
            'organizationFields'
        );

    const routeFields =
        document.getElementById(
            'routeFields'
        );

    const organizationMessage =
        document.getElementById(
            'organizationMessage'
        );

    const routeMessage =
        document.getElementById(
            'routeMessage'
        );

    const routeNumber =
        document.getElementById(
            'routeNumber'
        );

    const effectiveRoute =
        document.getElementById(
            'effectiveRoute'
        );

    let configuration = null;

    const token = () =>
        sessionStorage.getItem(
            TOKEN_KEY
        );

    const headers = (
        json = false
    ) => {
        const value = token();

        const result = {
            Accept: 'application/json',
            'X-Organization-ID':
                ORGANIZATION_ID,
        };

        if (value) {
            result.Authorization =
                `Bearer ${value}`;
        }

        if (json) {
            result['Content-Type'] =
                'application/json';
        }

        return result;
    };

    const apiError = async (
        response
    ) => {
        try {
            const body =
                await response.json();

            return body?.message
                || body?.error
                || `HTTP ${response.status}`;
        } catch (error) {
            return `HTTP ${response.status}`;
        }
    };

    const ensureAuthorized = (
        response
    ) => {
        if (response.status !== 401) {
            return true;
        }

        sessionStorage.removeItem(
            TOKEN_KEY
        );

        window.location.href = '/app';

        return false;
    };

    const setMessage = (
        element,
        text,
        type = ''
    ) => {
        element.textContent = text;

        element.className =
            `message ${type}`.trim();
    };

    const getPayload = (
        body
    ) => body?.data ?? body;

    const normalizeRoute = (
        value
    ) => String(
        value ?? ''
    )
        .trim()
        .toLocaleLowerCase('cs-CZ');

    const createField = (
        container,
        definition,
        prefix,
        inherit
    ) => {
        const wrapper =
            document.createElement(
                'div'
            );

        wrapper.className = 'field';

        const label =
            document.createElement(
                'label'
            );

        label.htmlFor =
            `${prefix}-${definition.key}`;

        label.textContent =
            definition.label;

        const input =
            document.createElement(
                'input'
            );

        input.id =
            `${prefix}-${definition.key}`;

        input.type = 'number';
        input.min = '0';
        input.max = '100';
        input.step = '0.01';
        input.inputMode = 'decimal';

        const hint =
            document.createElement(
                'small'
            );

        hint.textContent = inherit
            ? `${definition.hint} Prázdné = zdědit.`
            : definition.hint;

        wrapper.appendChild(label);
        wrapper.appendChild(input);
        wrapper.appendChild(hint);

        container.appendChild(
            wrapper
        );
    };

    metricDefinitions.forEach(
        (definition) => {
            createField(
                organizationFields,
                definition,
                'org',
                false
            );

            createField(
                routeFields,
                definition,
                'route',
                true
            );
        }
    );

    const inputValue = (
        prefix,
        key
    ) => {
        const value =
            document.getElementById(
                `${prefix}-${key}`
            ).value.trim();

        return value === ''
            ? null
            : value;
    };

    const payloadFromInputs = (
        prefix
    ) => Object.fromEntries(
        metricDefinitions.map(
            ({ key }) => [
                key,
                inputValue(
                    prefix,
                    key
                ),
            ]
        )
    );

    const fillInputs = (
        prefix,
        thresholds
    ) => {
        metricDefinitions.forEach(
            ({ key }) => {
                const input =
                    document.getElementById(
                        `${prefix}-${key}`
                    );

                const value =
                    thresholds?.[key];

                input.value =
                    value === null
                    || value === undefined
                        ? ''
                        : String(value);
            }
        );
    };

    const loadConfiguration =
        async () => {
            const response =
                await fetch(
                    endpoint,
                    {
                        headers: headers(),
                    }
                );

            if (!ensureAuthorized(response)) {
                return null;
            }

            if (!response.ok) {
                throw new Error(
                    await apiError(
                        response
                    )
                );
            }

            configuration =
                getPayload(
                    await response.json()
                );

            fillInputs(
                'org',
                configuration
                    ?.effective_organization_defaults
            );

            setMessage(
                organizationMessage,
                configuration
                    ?.organization_defaults
                    ? 'Načteny uložené limity organizace.'
                    : 'Používají se systémové výchozí hodnoty 15 % / 10 %.',
                'ok'
            );

            return configuration;
        };

    const organizationOverrideForRoute = (
        route
    ) => {
        const normalized =
            normalizeRoute(route);

        return (
            configuration
                ?.route_overrides
            || []
        ).find(
            (item) =>
                normalizeRoute(
                    item
                        ?.route_number_normalized
                )
                === normalized
        ) || null;
    };

    const renderEffectiveRoute = (
        effective
    ) => {
        effectiveRoute.innerHTML = '';

        metricDefinitions.forEach(
            ({ key, label }) => {
                const row =
                    document.createElement(
                        'div'
                    );

                row.className =
                    'effective-row';

                const name =
                    document.createElement(
                        'span'
                    );

                name.textContent = label;

                const value =
                    document.createElement(
                        'strong'
                    );

                const threshold =
                    effective
                        ?.thresholds
                        ?.[key];

                const source =
                    effective
                        ?.sources
                        ?.[key];

                const sourceLabel = {
                    route: 'trasa',
                    organization: 'organizace',
                    system: 'systém',
                }[source] || source || '';

                value.textContent =
                    threshold === null
                    || threshold === undefined
                        ? 'vypnuto'
                        : `${Number(
                            threshold
                        ).toLocaleString(
                            'cs-CZ',
                            {
                                maximumFractionDigits: 2,
                            }
                        )} % · ${sourceLabel}`;

                row.appendChild(name);
                row.appendChild(value);

                effectiveRoute.appendChild(
                    row
                );
            }
        );
    };

    const loadRouteOverride =
        async () => {
            const route =
                routeNumber.value.trim();

            if (!route) {
                setMessage(
                    routeMessage,
                    'Zadejte číslo trasy.',
                    'error'
                );

                return;
            }

            if (!configuration) {
                await loadConfiguration();
            }

            const override =
                organizationOverrideForRoute(
                    route
                );

            fillInputs(
                'route',
                override?.thresholds || {}
            );

            const response =
                await fetch(
                    `${endpoint}/effective?route_number=${encodeURIComponent(
                        route
                    )}`,
                    {
                        headers: headers(),
                    }
                );

            if (!ensureAuthorized(response)) {
                return;
            }

            if (!response.ok) {
                setMessage(
                    routeMessage,
                    await apiError(response),
                    'error'
                );

                return;
            }

            const effective =
                getPayload(
                    await response.json()
                );

            renderEffectiveRoute(
                effective
            );

            setMessage(
                routeMessage,
                override
                    ? 'Načtena uložená výjimka trasy.'
                    : 'Trasa zatím používá limity organizace.',
                'ok'
            );
        };

    document
        .getElementById(
            'saveOrganization'
        )
        .addEventListener(
            'click',
            async () => {
                const response =
                    await fetch(
                        `${endpoint}/organization`,
                        {
                            method: 'PUT',
                            headers:
                                headers(true),
                            body:
                                JSON.stringify(
                                    payloadFromInputs(
                                        'org'
                                    )
                                ),
                        }
                    );

                if (!ensureAuthorized(response)) {
                    return;
                }

                if (!response.ok) {
                    setMessage(
                        organizationMessage,
                        await apiError(
                            response
                        ),
                        'error'
                    );

                    return;
                }

                setMessage(
                    organizationMessage,
                    'Výchozí limity organizace byly uloženy.',
                    'ok'
                );

                await loadConfiguration();

                if (
                    routeNumber.value.trim()
                ) {
                    await loadRouteOverride();
                }
            }
        );

    document
        .getElementById(
            'loadRoute'
        )
        .addEventListener(
            'click',
            loadRouteOverride
        );

    document
        .getElementById(
            'saveRoute'
        )
        .addEventListener(
            'click',
            async () => {
                const route =
                    routeNumber.value.trim();

                if (!route) {
                    setMessage(
                        routeMessage,
                        'Nejdříve zadejte číslo trasy.',
                        'error'
                    );

                    return;
                }

                const response =
                    await fetch(
                        `${endpoint}/routes/${encodeURIComponent(
                            route
                        )}`,
                        {
                            method: 'PUT',
                            headers:
                                headers(true),
                            body:
                                JSON.stringify(
                                    payloadFromInputs(
                                        'route'
                                    )
                                ),
                        }
                    );

                if (!ensureAuthorized(response)) {
                    return;
                }

                if (!response.ok) {
                    setMessage(
                        routeMessage,
                        await apiError(
                            response
                        ),
                        'error'
                    );

                    return;
                }

                setMessage(
                    routeMessage,
                    'Výjimka trasy byla uložena.',
                    'ok'
                );

                await loadConfiguration();
                await loadRouteOverride();
            }
        );

    document
        .getElementById(
            'deleteRoute'
        )
        .addEventListener(
            'click',
            async () => {
                const route =
                    routeNumber.value.trim();

                if (!route) {
                    setMessage(
                        routeMessage,
                        'Nejdříve zadejte číslo trasy.',
                        'error'
                    );

                    return;
                }

                const response =
                    await fetch(
                        `${endpoint}/routes/${encodeURIComponent(
                            route
                        )}`,
                        {
                            method: 'DELETE',
                            headers:
                                headers(),
                        }
                    );

                if (!ensureAuthorized(response)) {
                    return;
                }

                if (!response.ok) {
                    setMessage(
                        routeMessage,
                        await apiError(
                            response
                        ),
                        'error'
                    );

                    return;
                }

                fillInputs(
                    'route',
                    {}
                );

                setMessage(
                    routeMessage,
                    'Výjimka trasy byla zrušena. Trasa znovu dědí limity organizace.',
                    'ok'
                );

                await loadConfiguration();
                await loadRouteOverride();
            }
        );

    loadConfiguration()
        .catch(
            (error) => {
                setMessage(
                    organizationMessage,
                    `Nastavení se nepodařilo načíst: ${error.message}`,
                    'error'
                );
            }
        );
})();
</script>
</body>
</html>