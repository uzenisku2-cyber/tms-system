<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dopravci · DRAYVIA</title>
    <style>
        :root {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #18212f;
            background: #f4f6f8;
        }

        * {
            box-sizing: border-box;
        }

        [hidden] {
            display: none !important;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f6f8;
        }

        button,
        input,
        select {
            font: inherit;
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px 1fr;
        }

        .sidebar {
            background: #17202b;
            color: white;
            padding: 28px 22px;
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 34px;
        }

        .pilot {
            display: inline-block;
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
            color: white;
            background: #283544;
            font-weight: 700;
        }

        .main {
            padding: 34px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 26px;
        }

        h1 {
            margin: 0;
            font-size: 29px;
        }

        .subtitle {
            margin-top: 7px;
            color: #667085;
        }

        .api-state {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #475467;
            font-size: 14px;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #12b76a;
        }

        .stack {
            display: grid;
            gap: 22px;
        }

        .card {
            background: white;
            border: 1px solid #e4e7ec;
            border-radius: 13px;
            box-shadow: 0 2px 7px rgba(16, 24, 40, .04);
            padding: 22px;
        }

        .card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
        }

        .card h2 {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .muted {
            color: #667085;
            font-size: 14px;
            line-height: 1.5;
        }

        .saved-line {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .saved-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #ecfdf3;
            color: #027a48;
            font-size: 12px;
            font-weight: 800;
        }

        .saved-badge::before {
            content: "✓";
        }

        .btn {
            border: 0;
            border-radius: 9px;
            padding: 10px 15px;
            cursor: pointer;
            font-weight: 750;
            white-space: nowrap;
        }

        .btn-primary {
            background: #17202b;
            color: white;
        }

        .btn-success {
            background: #12b76a;
            color: white;
        }

        .btn-light {
            background: #f2f4f7;
            color: #344054;
            border: 1px solid #e4e7ec;
        }

        .btn:disabled {
            opacity: .55;
            cursor: wait;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(150px, 1fr));
            gap: 15px 22px;
            margin-top: 20px;
        }

        .summary-item {
            min-width: 0;
        }

        .summary-label {
            color: #667085;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .summary-value {
            color: #101828;
            font-size: 14px;
            font-weight: 650;
            overflow-wrap: anywhere;
        }

        .form-panel {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #eaecf0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(150px, 1fr));
            gap: 15px;
        }

        .driver-grid {
            grid-template-columns: repeat(3, minmax(150px, 1fr));
        }

        .wide {
            grid-column: span 2;
        }

        label {
            display: block;
            margin: 0 0 7px;
            font-size: 14px;
            font-weight: 700;
        }

        input,
        select {
            width: 100%;
            border: 1px solid #d0d5dd;
            border-radius: 9px;
            padding: 11px 12px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus {
            border-color: #7f8ea3;
            box-shadow: 0 0 0 3px rgba(127, 142, 163, .12);
        }

        .actions {
            margin-top: 17px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .message {
            display: none;
            margin-top: 14px;
            border-radius: 8px;
            padding: 11px 12px;
            font-size: 14px;
        }

        .message.ok {
            display: block;
            background: #ecfdf3;
            color: #027a48;
        }

        .message.error {
            display: block;
            background: #fef3f2;
            color: #b42318;
        }

        .security-note {
            margin-top: 16px;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid #eaecf0;
            border-radius: 9px;
            color: #475467;
            font-size: 13px;
            line-height: 1.5;
        }

        .section-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th,
        td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #eaecf0;
            font-size: 14px;
            vertical-align: middle;
        }

        th {
            color: #667085;
            font-weight: 650;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: #ecfdf3;
            color: #027a48;
            font-size: 12px;
            font-weight: 700;
        }

        .empty {
            padding: 30px 10px 8px;
            text-align: center;
            color: #667085;
        }

        .back {
            color: #344054;
            text-decoration: none;
            font-size: 14px;
            font-weight: 650;
        }

        .subsection {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #eaecf0;
        }

        @media (max-width: 1200px) {
            .summary-grid,
            .form-grid,
            .driver-grid {
                grid-template-columns: repeat(2, minmax(150px, 1fr));
            }
        }

        @media (max-width: 850px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .main {
                padding: 20px;
            }

            .summary-grid,
            .form-grid,
            .driver-grid {
                grid-template-columns: 1fr;
            }

            .wide {
                grid-column: span 1;
            }

            .card-head {
                flex-direction: column;
            }
        }

        /* S020-03H DRIVER STATUS + INLINE HISTORY */
        .driver-filter-bar {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            margin: 14px 0;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid #eaecf0;
            border-radius: 12px;
        }

        .driver-filter-bar label {
            display: block;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .driver-filter-bar select {
            min-width: 190px;
        }

        .driver-filter-note {
            padding-bottom: 8px;
        }

        tr.driver-row-active > td {
            background: #f0fdf4;
        }

        tr.driver-row-inactive > td {
            background: #fff5f5;
        }

        .driver-status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .driver-status-active {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .driver-status-inactive {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        tr.driver-history-row > td {
            background: #ffffff;
            padding: 0;
        }

        .driver-history-inline {
            margin: 0;
            padding: 18px;
            border-top: 2px solid #eaecf0;
            border-bottom: 2px solid #eaecf0;
            background: #ffffff;
        }

        .driver-history-inline .history-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .driver-history-inline .history-head h4 {
            margin: 0 0 5px;
        }

        .driver-history-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .driver-history-inline table {
            margin-top: 10px;
        }

        .driver-inline-form {
            margin: 14px 0;
            padding: 14px;
            border: 1px solid #d0d5dd;
            border-radius: 12px;
            background: #f9fafb;
        }

        @media (max-width: 800px) {
            .driver-filter-bar,
            .driver-history-inline .history-head {
                align-items: stretch;
                flex-direction: column;
            }
        }

        /* S020-03I DRIVER SEARCH */
        .driver-filter-controls {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
        }

        #driver-search {
            min-width: 280px;
        }

        @media (max-width: 800px) {
            .driver-filter-controls {
                align-items: stretch;
                flex-direction: column;
            }

            #driver-search {
                min-width: 0;
                width: 100%;
            }
        }
            .ares-box {
            margin-top: 16px;
            padding: 16px;
            border: 1px solid #d0d5dd;
            border-radius: 12px;
            background: #f8fafc;
        }

        .ares-box[hidden] {
            display: none;
        }

        .ares-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            font-weight: 800;
        }

        .ares-status.ok {
            color: #067647;
        }

        .ares-status.error {
            color: #b42318;
        }

        .ares-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: currentColor;
            flex: 0 0 auto;
        }

        .ares-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 20px;
        }

        .ares-item {
            min-width: 0;
        }

        .ares-item.wide {
            grid-column: 1 / -1;
        }

        .ares-label {
            display: block;
            margin-bottom: 3px;
            color: #667085;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .ares-value {
            color: #18212f;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .ico-row {
            display: grid;
            grid-template-columns: minmax(0, 220px) auto;
            gap: 10px;
            align-items: end;
        }

        @media (max-width: 720px) {
            .ico-row,
            .ares-grid {
                grid-template-columns: 1fr;
            }

            .ares-item.wide {
                grid-column: auto;
            }
        }
        .manual-carrier-box {
            margin-top: 16px;
            padding: 16px;
            border: 1px solid #f0b44d;
            border-radius: 12px;
            background: #fffaeb;
        }

        .manual-carrier-title {
            margin-bottom: 12px;
            font-weight: 800;
            color: #93370d;
        }

        .carrier-verification {
            font-weight: 800;
            white-space: nowrap;
        }

        .carrier-verification.verified {
            color: #067647;
        }

        .carrier-verification.pending {
            color: #b54708;
        }

        .carrier-action-group {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
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
            <a class="active" href="/carriers">Dopravci a řidiči</a>
            <a href="/daily-report-settings">Nastavení denního výkazu</a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>Dopravci a řidiči</h1>
                <div class="subtitle">Uložená data nejdřív zobrazujeme. Formulář se otevře až při úpravě nebo přidání.</div>
            </div>

            <div>
                <div class="api-state"><span class="dot"></span> API připojeno</div>
                <div style="margin-top:8px;text-align:right">
                    <a class="back" href="/app">← Denní provoz</a>
                </div>
            </div>
        </div>

        <div class="stack">
            <section class="card">
                <div class="card-head">
                    <div>
                        <div class="saved-line">
                            <h2>Moje firma</h2>
                            <span class="saved-badge">Uloženo</span>
                        </div>
                        <div class="muted">Hlavní organizace TMS.</div>
                    </div>

                    <button id="company-edit-button" class="btn btn-success" type="button">
                        Upravit údaje firmy
                    </button>
                </div>

                <div id="company-summary" class="summary-grid"></div>

                <div id="company-edit-panel" class="form-panel" hidden>
                    <form id="company-form">
                        <div class="form-grid">
                            <div class="wide">
                                <label for="company-name">Název firmy</label>
                                <input id="company-name" name="name" maxlength="255" required>
                            </div>

                            <div>
                                <label for="registration-number">IČO</label>
                                <input id="registration-number" name="registration_number" maxlength="32">
                            </div>

                            <div>
                                <label for="vat-number">DIČ</label>
                                <input id="vat-number" name="vat_number" maxlength="32">
                            </div>

                            <div class="wide">
                                <label for="street">Ulice a číslo</label>
                                <input id="street" name="street" maxlength="255">
                            </div>

                            <div>
                                <label for="city">Město</label>
                                <input id="city" name="city" maxlength="100">
                            </div>

                            <div>
                                <label for="postal-code">PSČ</label>
                                <input id="postal-code" name="postal_code" maxlength="32">
                            </div>

                            <div>
                                <label for="country-code">Země</label>
                                <select id="country-code" name="country_code">
                                    <option value="CZ">Česko (CZ)</option>
                                </select>
                            </div>

                            <div class="wide">
                                <label for="contact-email">Kontaktní e-mail</label>
                                <input id="contact-email" name="contact_email" type="email" maxlength="255">
                            </div>

                            <div>
                                <label for="contact-phone">Telefon</label>
                                <input id="contact-phone" name="contact_phone" maxlength="64">
                            </div>
                        </div>

                        <div class="actions">
                            <button id="company-cancel-button" class="btn btn-light" type="button">Zrušit</button>
                            <button id="company-save-button" class="btn btn-primary" type="submit">Uložit změny</button>
                        </div>
                    </form>
                </div>

                <div id="company-message" class="message"></div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2>Řidiči mojí firmy</h2>
                        <div class="muted">Řidiče přidáváme až po stisknutí tlačítka. Uložené řidiče upravujeme samostatně.</div>
                    </div>

                    <button id="driver-add-button" class="btn btn-primary" type="button">
                        + Přidat řidiče
                    </button>
                </div>

                <div id="driver-create-panel" class="form-panel" hidden>
                    <form id="driver-form">
                        <div class="form-grid driver-grid">
                            <div>
                                <label for="driver-first-name">Jméno</label>
                                <input id="driver-first-name" name="first_name" maxlength="100" required>
                            </div>

                            <div>
                                <label for="driver-last-name">Příjmení</label>
                                <input id="driver-last-name" name="last_name" maxlength="100" required>
                            </div>

                            <div>
                                <label for="driver-email">Přihlašovací e-mail</label>
                                <input id="driver-email" name="email" type="email" maxlength="255" autocomplete="off" required>
                            </div>

                            <div class="wide">
                                <div id="driver-account-state" class="security-note">
                                    Po zadání e-mailu TMS ověří, zda jde o nový nebo existující účet.
                                </div>
                            </div>

                            <div>
                                <label for="driver-phone">Telefon (nepovinné)</label>
                                <input id="driver-phone" name="phone" maxlength="64">
                            </div>

                            <div>
                                <label for="driver-external-id">ID řidiče (Zásilkovna)</label>
                                <input
                                    id="driver-external-id"
                                    name="external_driver_id"
                                    inputmode="numeric"
                                    maxlength="32"
                                    pattern="[0-9]+"
                                    placeholder="např. 33102"
                                >
                                <div class="muted" style="margin-top:6px">
                                    Pro párování tras s podklady z depa. Lze doplnit i později.
                                </div>
                            </div>

                            <div>
                                <label for="driver-license">Číslo ŘP (nepovinné)</label>
                                <input id="driver-license" name="license_number" maxlength="100">
                            </div>

                            <div>
                                <label for="driver-license-category">Skupina ŘP (nepovinné)</label>
                                <select id="driver-license-category" name="license_category">
                                    <option value="">Nevyplněno</option>
                                    <option value="AM">AM</option>
                                    <option value="A1">A1</option>
                                    <option value="A2">A2</option>
                                    <option value="A">A</option>
                                    <option value="B1">B1</option>
                                    <option value="B">B</option>
                                    <option value="C1">C1</option>
                                    <option value="C">C</option>
                                    <option value="D1">D1</option>
                                    <option value="D">D</option>
                                    <option value="B+E">B+E</option>
                                    <option value="C1+E">C1+E</option>
                                    <option value="C+E">C+E</option>
                                    <option value="D1+E">D1+E</option>
                                    <option value="D+E">D+E</option>
                                    <option value="T">T</option>
                                </select>
                            </div>

                            <div id="driver-password-field" hidden>
                                <label for="driver-password">Dočasné heslo</label>
                                <input id="driver-password" name="password" type="password" minlength="10" maxlength="128" autocomplete="new-password">
                            </div>

                            <div id="driver-password-confirmation-field" hidden>
                                <label for="driver-password-confirmation">Heslo znovu</label>
                                <input id="driver-password-confirmation" name="password_confirmation" type="password" minlength="10" maxlength="128" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="security-note">
                            U nového účtu nastavíte dočasné heslo. U existujícího člena firmy TMS zachová jeho účet, heslo, členství i současná oprávnění a pouze doplní profil řidiče.
                        </div>

                        <div class="actions">
                            <button id="driver-create-cancel-button" class="btn btn-light" type="button">Zrušit</button>
                            <button id="driver-save-button" class="btn btn-primary" type="submit">Vytvořit řidiče a účet</button>
                        </div>
                    </form>
                </div>

                <div id="driver-edit-panel" class="form-panel" hidden>
                    <h3 style="margin-top:0">Upravit řidiče</h3>

                    <form id="driver-edit-form">
                        <input id="edit-driver-id" type="hidden">

                        <div class="form-grid driver-grid">
                            <div>
                                <label for="edit-driver-first-name">Jméno</label>
                                <input id="edit-driver-first-name" name="first_name" maxlength="100" required>
                            </div>

                            <div>
                                <label for="edit-driver-last-name">Příjmení</label>
                                <input id="edit-driver-last-name" name="last_name" maxlength="100" required>
                            </div>

                            <div>
                                <label for="edit-driver-email">Přihlašovací e-mail</label>
                                <input id="edit-driver-email" name="email" type="email" maxlength="255" required>
                                <div class="muted" style="margin-top:6px">
                                    Tento e-mail lze změnit. Nová hodnota bude zároveň novým přihlašovacím e-mailem účtu.
                                </div>
                            </div>

                            <div>
                                <label for="edit-driver-phone">Telefon (nepovinné)</label>
                                <input id="edit-driver-phone" name="phone" maxlength="64">
                            </div>

                            <div>
                                <label for="edit-driver-external-id">ID řidiče (Zásilkovna)</label>
                                <input
                                    id="edit-driver-external-id"
                                    name="external_driver_id"
                                    inputmode="numeric"
                                    maxlength="32"
                                    pattern="[0-9]+"
                                    placeholder="např. 33102"
                                >
                                <div class="muted" style="margin-top:6px">
                                    Identifikátor z podkladů Zásilkovny; musí být jedinečný.
                                </div>
                            </div>

                            <div>
                                <label for="edit-driver-license">Číslo ŘP (nepovinné)</label>
                                <input id="edit-driver-license" name="license_number" maxlength="100">
                            </div>

                            <div>
                                <label for="edit-driver-license-category">Skupina ŘP (nepovinné)</label>
                                <select id="edit-driver-license-category" name="license_category">
                                    <option value="">Nevyplněno</option>
                                    <option value="AM">AM</option>
                                    <option value="A1">A1</option>
                                    <option value="A2">A2</option>
                                    <option value="A">A</option>
                                    <option value="B1">B1</option>
                                    <option value="B">B</option>
                                    <option value="C1">C1</option>
                                    <option value="C">C</option>
                                    <option value="D1">D1</option>
                                    <option value="D">D</option>
                                    <option value="B+E">B+E</option>
                                    <option value="C1+E">C1+E</option>
                                    <option value="C+E">C+E</option>
                                    <option value="D1+E">D1+E</option>
                                    <option value="D+E">D+E</option>
                                    <option value="T">T</option>
                                </select>
                            </div>
                        </div>

                        <div class="actions">
                            <button id="driver-edit-cancel-button" class="btn btn-light" type="button">Zrušit</button>
                            <button id="driver-edit-save-button" class="btn btn-primary" type="submit">Uložit změny řidiče</button>
                        </div>
                    </form>
                </div>

                <div class="driver-filter-bar">
                    <div class="driver-filter-controls">
                        <div>
                            <label for="driver-status-filter">Zobrazit řidiče</label>
                            <select id="driver-status-filter">
                                <option value="all" selected>Všichni</option>
                                <option value="active">Aktivní</option>
                                <option value="inactive">Neaktivní</option>
                            </select>
                        </div>

                        <div>
                            <label for="driver-search">Vyhledat řidiče</label>
                            <input
                                id="driver-search"
                                type="search"
                                placeholder="Jméno, ID Zásilkovna, e-mail, telefon, ŘP…"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="muted driver-filter-note">
                        Výchozí pořadí: nejprve aktivní, potom neaktivní.
                    </div>
                </div>
                <div id="driver-message" class="message"></div>
                <div id="driver-list" class="empty">Načítám řidiče…</div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2>Externí dopravci</h2>
                        <div class="muted">Stejný princip: nejdřív přidat, potom uložený záznam upravovat.</div>
                    </div>

                    <button id="carrier-add-button" class="btn btn-primary" type="button">
                        + Přidat externího dopravce
                    </button>
                </div>

                <div id="carrier-create-panel" class="form-panel" hidden>
                    <form id="carrier-form">
                        <div class="form-grid">
                            <div class="wide">
                                <label for="carrier-ico">IČO</label>

                                <div class="ico-row">
                                    <input
                                        id="carrier-ico"
                                        name="registration_number"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        maxlength="8"
                                        pattern="[0-9]{8}"
                                        placeholder="např. 12345678"
                                        required
                                    >

                                    <button
                                        id="carrier-ares-button"
                                        class="btn btn-light"
                                        type="button"
                                    >
                                        Ověřit v ARES
                                    </button>
                                </div>

                                <div class="muted" style="margin-top:6px">
                                    DRAYVIA se nejprve pokusí načíst
                                    oficiální údaje z ARES.
                                </div>
                            </div>

                            <div>
                                <label for="carrier-relationship-valid-from">
                                    Spolupráce od
                                </label>

                                <input
                                    id="carrier-relationship-valid-from"
                                    name="relationship_valid_from"
                                    type="date"
                                    required
                                >

                                <div class="muted" style="margin-top:6px">
                                    Skutečný začátek spolupráce s master dopravcem.
                                </div>
                            </div>
                        </div>

                        <div
                            id="carrier-ares-result"
                            class="ares-box"
                            hidden
                        >
                            <div
                                id="carrier-ares-status"
                                class="ares-status"
                            ></div>

                            <div class="ares-grid">
                                <div class="ares-item wide">
                                    <span class="ares-label">Obchodní jméno</span>
                                    <div id="carrier-ares-name" class="ares-value"></div>
                                </div>

                                <div class="ares-item">
                                    <span class="ares-label">IČO</span>
                                    <div id="carrier-ares-ico" class="ares-value"></div>
                                </div>

                                <div class="ares-item">
                                    <span class="ares-label">DPH</span>
                                    <div id="carrier-ares-vat-status" class="ares-value"></div>
                                </div>

                                <div class="ares-item">
                                    <span class="ares-label">DIČ</span>
                                    <div id="carrier-ares-vat-number" class="ares-value"></div>
                                </div>

                                <div class="ares-item">
                                    <span class="ares-label">Země</span>
                                    <div id="carrier-ares-country" class="ares-value"></div>
                                </div>

                                <div class="ares-item wide">
                                    <span class="ares-label">Sídlo</span>
                                    <div id="carrier-ares-address" class="ares-value"></div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:12px">
                            <button
                                id="carrier-manual-button"
                                class="btn btn-light"
                                type="button"
                                hidden
                            >
                                Vyplnit ručně
                            </button>
                        </div>

                        <div
                            id="carrier-manual-panel"
                            class="manual-carrier-box"
                            hidden
                        >
                            <div class="manual-carrier-title">
                                RUČNÍ ZADÁNÍ — ČEKÁ NA POZDĚJŠÍ OVĚŘENÍ V ARES
                            </div>

                            <div class="form-grid">
                                <div class="wide">
                                    <label for="carrier-manual-name">
                                        Název dopravce
                                    </label>
                                    <input
                                        id="carrier-manual-name"
                                        data-manual-carrier-field
                                        maxlength="255"
                                        disabled
                                    >
                                </div>

                                <div>
                                    <label for="carrier-manual-vat-status">
                                        DPH
                                    </label>
                                    <select
                                        id="carrier-manual-vat-status"
                                        data-manual-carrier-field
                                        disabled
                                    >
                                        <option value="">Vyberte</option>
                                        <option value="payer">Plátce DPH</option>
                                        <option value="non_payer">Neplátce DPH</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="carrier-manual-vat-number">
                                        DIČ
                                    </label>
                                    <input
                                        id="carrier-manual-vat-number"
                                        data-manual-carrier-field
                                        maxlength="32"
                                        disabled
                                    >
                                </div>

                                <div class="wide">
                                    <label for="carrier-manual-street">
                                        Ulice a číslo
                                    </label>
                                    <input
                                        id="carrier-manual-street"
                                        data-manual-carrier-field
                                        maxlength="255"
                                        disabled
                                    >
                                </div>

                                <div>
                                    <label for="carrier-manual-city">
                                        Město
                                    </label>
                                    <input
                                        id="carrier-manual-city"
                                        data-manual-carrier-field
                                        maxlength="100"
                                        disabled
                                    >
                                </div>

                                <div>
                                    <label for="carrier-manual-postal-code">
                                        PSČ
                                    </label>
                                    <input
                                        id="carrier-manual-postal-code"
                                        data-manual-carrier-field
                                        maxlength="32"
                                        disabled
                                    >
                                </div>

                                <div>
                                    <label for="carrier-manual-country">
                                        Země
                                    </label>
                                    <select
                                        id="carrier-manual-country"
                                        data-manual-carrier-field
                                        disabled
                                    >
                                        <option value="CZ" selected>
                                            Česko (CZ)
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="muted" style="margin-top:10px">
                                Dopravce bude uložen jako neověřený.
                                DRAYVIA jej umožní ověřit v ARES později.
                            </div>
                        </div>

                        <div class="actions">
                            <button
                                id="carrier-create-cancel-button"
                                class="btn btn-light"
                                type="button"
                            >
                                Zrušit
                            </button>

                            <button
                                id="carrier-save-button"
                                class="btn btn-primary"
                                type="submit"
                                disabled
                            >
                                Uložit dopravce
                            </button>
                        </div>
                    </form>
                </div>

                <div
                    id="carrier-edit-panel"
                    class="form-panel"
                    hidden
                >
                    <h3 style="margin-top:0">
                        Upravit dopravce
                    </h3>

                    <form id="carrier-edit-form">
                        <input
                            id="edit-carrier-id"
                            type="hidden"
                        >

                        <div class="form-grid">
                            <div>
                                <label for="edit-carrier-ico">
                                    IČO
                                </label>
                                <input
                                    id="edit-carrier-ico"
                                    readonly
                                >
                                <div
                                    class="muted"
                                    style="margin-top:6px"
                                >
                                    IČO identifikuje právní subjekt
                                    a po založení se běžně nemění.
                                </div>
                            </div>

                            <div>
                                <label
                                    for="edit-carrier-relationship-valid-from"
                                >
                                    Spolupráce od
                                </label>
                                <input
                                    id="edit-carrier-relationship-valid-from"
                                    type="date"
                                    required
                                >
                            </div>

                            <div class="wide">
                                <label for="edit-carrier-name">
                                    Název dopravce
                                </label>
                                <input
                                    id="edit-carrier-name"
                                    maxlength="255"
                                    required
                                >
                            </div>

                            <div>
                                <label
                                    for="edit-carrier-vat-status"
                                >
                                    DPH
                                </label>
                                <select
                                    id="edit-carrier-vat-status"
                                    required
                                >
                                    <option value="payer">
                                        Plátce DPH
                                    </option>
                                    <option value="non_payer">
                                        Neplátce DPH
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    for="edit-carrier-vat-number"
                                >
                                    DIČ
                                </label>
                                <input
                                    id="edit-carrier-vat-number"
                                    maxlength="32"
                                >
                            </div>

                            <div class="wide">
                                <label
                                    for="edit-carrier-street"
                                >
                                    Ulice a číslo
                                </label>
                                <input
                                    id="edit-carrier-street"
                                    maxlength="255"
                                >
                            </div>

                            <div>
                                <label
                                    for="edit-carrier-city"
                                >
                                    Město
                                </label>
                                <input
                                    id="edit-carrier-city"
                                    maxlength="100"
                                >
                            </div>

                            <div>
                                <label
                                    for="edit-carrier-postal-code"
                                >
                                    PSČ
                                </label>
                                <input
                                    id="edit-carrier-postal-code"
                                    maxlength="32"
                                >
                            </div>

                            <div>
                                <label
                                    for="edit-carrier-country-code"
                                >
                                    Země
                                </label>
                                <select
                                    id="edit-carrier-country-code"
                                >
                                    <option value="CZ">
                                        Česko (CZ)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div
                            id="edit-carrier-ares-state"
                            class="security-note"
                            style="margin-top:16px"
                        ></div>

                        <div class="actions">
                            <button
                                id="carrier-edit-cancel-button"
                                class="btn btn-light"
                                type="button"
                            >
                                Zrušit
                            </button>

                            <button
                                id="carrier-edit-ares-button"
                                class="btn btn-light"
                                type="button"
                            >
                                Prověřit v ARES
                            </button>

                            <button
                                id="carrier-edit-save-button"
                                class="btn btn-primary"
                                type="submit"
                            >
                                Uložit změny
                            </button>
                        </div>
                    </form>
                </div>
                <div id="carrier-message" class="message"></div>
                <div id="carrier-list" class="empty">Načítám dopravce…</div>
            </section>
        </div>
    </main>
</div>

<script>
(() => {
    const token = sessionStorage.getItem('tms_mvp_token');

    if (!token) {
        window.location.replace('/login');
        return;
    }

    let companyData = {};
    let driverItems = [];
    let carrierItems = [];

    const headers = (json = false) => {
        const result = {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
            'X-Organization-ID': '1',
        };

        if (json) {
            result['Content-Type'] = 'application/json';
        }

        return result;
    };

    const unauthorizedOrForbidden = (response) => {
        if (response.status === 401) {
            sessionStorage.removeItem('tms_mvp_token');
            window.location.replace('/login');
            return true;
        }

        if (response.status === 403) {
            window.location.replace('/app');
            return true;
        }

        return false;
    };

    const showMessage = (element, text, type) => {
        element.textContent = text;
        element.className = `message ${type}`;
    };

    const clearMessage = (element) => {
        element.textContent = '';
        element.className = 'message';
    };

    const apiError = async (response) => {
        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            return `API chyba (${response.status}).`;
        }

        const firstErrors = payload?.errors
            ? Object.values(payload.errors).flat()
            : [];

        return firstErrors[0]
            || payload?.message
            || `API chyba (${response.status}).`;
    };

    const summaryItem = (label, value) => {
        const item = document.createElement('div');
        item.className = 'summary-item';

        const labelElement = document.createElement('div');
        labelElement.className = 'summary-label';
        labelElement.textContent = label;

        const valueElement = document.createElement('div');
        valueElement.className = 'summary-value';
        valueElement.textContent = value || '—';

        item.append(labelElement, valueElement);
        return item;
    };

    const companySummary = document.getElementById('company-summary');
    const companyEditPanel = document.getElementById('company-edit-panel');
    const companyEditButton = document.getElementById('company-edit-button');
    const companyCancelButton = document.getElementById('company-cancel-button');
    const companyForm = document.getElementById('company-form');
    const companySaveButton = document.getElementById('company-save-button');
    const companyMessage = document.getElementById('company-message');

    const renderCompanySummary = () => {
        companySummary.replaceChildren(
            summaryItem('Název firmy', companyData.name),
            summaryItem('IČO', companyData.registration_number),
            summaryItem('DIČ', companyData.vat_number),
            summaryItem('Adresa', [companyData.street, companyData.city, companyData.postal_code].filter(Boolean).join(', ')),
            summaryItem('Země', companyData.country_code === 'CZ' ? 'Česko (CZ)' : companyData.country_code),
            summaryItem('Kontaktní e-mail', companyData.contact_email),
            summaryItem('Telefon', companyData.contact_phone),
        );
    };

    const populateCompanyForm = () => {
        document.getElementById('company-name').value = companyData.name ?? '';
        document.getElementById('registration-number').value = companyData.registration_number ?? '';
        document.getElementById('vat-number').value = companyData.vat_number ?? '';
        document.getElementById('street').value = companyData.street ?? '';
        document.getElementById('city').value = companyData.city ?? '';
        document.getElementById('postal-code').value = companyData.postal_code ?? '';
        document.getElementById('country-code').value = 'CZ';
        document.getElementById('contact-email').value = companyData.contact_email ?? '';
        document.getElementById('contact-phone').value = companyData.contact_phone ?? '';
    };

    const loadCompany = async () => {
        const response = await fetch('/api/v1/organization-profile', {
            headers: headers(),
        });

        if (unauthorizedOrForbidden(response)) {
            return;
        }

        if (!response.ok) {
            showMessage(companyMessage, await apiError(response), 'error');
            return;
        }

        const payload = await response.json();
        companyData = payload?.data || {};
        renderCompanySummary();
        populateCompanyForm();
    };

    companyEditButton.addEventListener('click', () => {
        clearMessage(companyMessage);
        populateCompanyForm();
        companyEditPanel.hidden = false;
        companyEditButton.hidden = true;
        document.getElementById('company-name').focus();
    });

    companyCancelButton.addEventListener('click', () => {
        companyEditPanel.hidden = true;
        companyEditButton.hidden = false;
        clearMessage(companyMessage);
    });

    companyForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        companySaveButton.disabled = true;
        clearMessage(companyMessage);

        const payload = Object.fromEntries(
            new FormData(companyForm).entries(),
        );

        try {
            const response = await fetch('/api/v1/organization-profile', {
                method: 'PATCH',
                headers: headers(true),
                body: JSON.stringify(payload),
            });

            if (unauthorizedOrForbidden(response)) {
                return;
            }

            if (!response.ok) {
                showMessage(companyMessage, await apiError(response), 'error');
                return;
            }

            const result = await response.json();
            companyData = result?.data || payload;
            renderCompanySummary();
            companyEditPanel.hidden = true;
            companyEditButton.hidden = false;
            showMessage(companyMessage, result?.message || 'Údaje firmy byly uloženy.', 'ok');
        } finally {
            companySaveButton.disabled = false;
        }
    });

    const driverAddButton = document.getElementById('driver-add-button');
    const driverCreatePanel = document.getElementById('driver-create-panel');
    const driverCreateCancelButton = document.getElementById('driver-create-cancel-button');
    const driverForm = document.getElementById('driver-form');
    const driverSaveButton = document.getElementById('driver-save-button');
    const driverEditPanel = document.getElementById('driver-edit-panel');
    const driverEditForm = document.getElementById('driver-edit-form');
    const driverEditCancelButton = document.getElementById('driver-edit-cancel-button');
    const driverEditSaveButton = document.getElementById('driver-edit-save-button');
    const driverMessage = document.getElementById('driver-message');
    const driverList = document.getElementById('driver-list');
    const driverStatusFilter = document.getElementById('driver-status-filter');
    const driverSearchInput = document.getElementById('driver-search');
    const driverEmailInput = document.getElementById('driver-email');
    const driverAccountState = document.getElementById('driver-account-state');
    const driverPasswordField = document.getElementById('driver-password-field');
    const driverPasswordConfirmationField = document.getElementById('driver-password-confirmation-field');
    const driverPasswordInput = document.getElementById('driver-password');
    const driverPasswordConfirmationInput = document.getElementById('driver-password-confirmation');

    let driverAccountMode = 'unknown';
    let driverResolvedEmail = '';
    let expandedDriverId = null;
    let expandedDriverMode = 'history';
    let expandedEndAssignmentId = null;
    let driverHistoryMessage = '';

    const formatDate = (value) => {
        if (!value) {
            return 'dosud';
        }

        const [year, month, day] = value.split('-');

        return `${day}.${month}.${year}`;
    };

    const todayIso = () => {
        const now = new Date();
        const local = new Date(
            now.getTime() - (now.getTimezoneOffset() * 60000),
        );

        return local.toISOString().slice(0, 10);
    };

    const setDriverPasswordMode = (required) => {
        driverPasswordField.hidden = !required;
        driverPasswordConfirmationField.hidden = !required;
        driverPasswordInput.required = required;
        driverPasswordConfirmationInput.required = required;

        if (!required) {
            driverPasswordInput.value = '';
            driverPasswordConfirmationInput.value = '';
        }
    };

    const setDriverAccountState = (text, state = 'neutral') => {
        driverAccountState.textContent = text;

        if (state === 'success') {
            driverAccountState.style.background = '#ecfdf3';
            driverAccountState.style.borderColor = '#abefc6';
            driverAccountState.style.color = '#027a48';
            return;
        }

        if (state === 'error') {
            driverAccountState.style.background = '#fef3f2';
            driverAccountState.style.borderColor = '#fecdca';
            driverAccountState.style.color = '#b42318';
            return;
        }

        driverAccountState.style.background = '#f8fafc';
        driverAccountState.style.borderColor = '#eaecf0';
        driverAccountState.style.color = '#475467';
    };

    const resetDriverAccountResolution = () => {
        driverAccountMode = 'unknown';
        driverResolvedEmail = '';
        setDriverPasswordMode(false);
        driverSaveButton.textContent = 'Pokračovat';
        setDriverAccountState(
            'Po zadání e-mailu TMS ověří, zda jde o nový nebo existující účet.',
        );
    };

    const resolveDriverAccount = async () => {
        const email = driverEmailInput.value.trim().toLowerCase();

        if (!email || !driverEmailInput.checkValidity()) {
            driverAccountMode = 'blocked';
            driverResolvedEmail = email;
            setDriverPasswordMode(false);
            setDriverAccountState(
                'Zadejte platný přihlašovací e-mail.',
                'error',
            );
            return false;
        }

        if (
            driverResolvedEmail === email
            && (driverAccountMode === 'new' || driverAccountMode === 'existing')
        ) {
            return true;
        }

        driverAccountMode = 'checking';
        driverResolvedEmail = email;
        driverSaveButton.disabled = true;
        setDriverPasswordMode(false);
        setDriverAccountState('Ověřuji účet…');

        try {
            const response = await fetch(
                `/api/v1/own-drivers/account-lookup?email=${encodeURIComponent(email)}`,
                {
                    headers: headers(),
                },
            );

            if (unauthorizedOrForbidden(response)) {
                return false;
            }

            if (!response.ok) {
                driverAccountMode = 'blocked';
                setDriverAccountState(
                    await apiError(response),
                    'error',
                );
                return false;
            }

            const payload = await response.json();
            const account = payload?.data || {};

            if (!account.exists) {
                driverAccountMode = 'new';
                setDriverPasswordMode(true);
                driverSaveButton.textContent = 'Vytvořit řidiče a účet';
                setDriverAccountState(
                    'Nový účet – nastavte dočasné heslo.',
                );
                return true;
            }

            if (!account.linkable) {
                driverAccountMode = 'blocked';
                setDriverPasswordMode(false);
                driverSaveButton.textContent = 'Nelze připojit';
                setDriverAccountState(
                    account.message || 'Tento účet nelze připojit jako řidiče.',
                    'error',
                );
                return false;
            }

            driverAccountMode = 'existing';
            setDriverPasswordMode(false);
            driverSaveButton.textContent = 'Připojit existující účet jako řidiče';

            const membership = account.membership_type
                ? ` · členství ${account.membership_type}`
                : '';

            setDriverAccountState(
                `Existující účet: ${account.account_name || email}${membership}. Heslo ani současná oprávnění se nezmění.`,
                'success',
            );

            return true;
        } catch (error) {
            driverAccountMode = 'blocked';
            setDriverPasswordMode(false);
            setDriverAccountState(
                'Nepodařilo se ověřit účet.',
                'error',
            );
            return false;
        } finally {
            driverSaveButton.disabled = false;
        }
    };

    const closeDriverPanels = () => {
        driverCreatePanel.hidden = true;
        driverEditPanel.hidden = true;
        driverAddButton.hidden = false;
        resetDriverAccountResolution();
    };

    driverAddButton.addEventListener('click', () => {
        clearMessage(driverMessage);
        driverEditPanel.hidden = true;
        driverCreatePanel.hidden = false;
        driverAddButton.hidden = true;
        resetDriverAccountResolution();
        document.getElementById('driver-first-name').focus();
    });

    driverCreateCancelButton.addEventListener('click', () => {
        driverForm.reset();
        closeDriverPanels();
        clearMessage(driverMessage);
    });

    driverEditCancelButton.addEventListener('click', () => {
        driverEditForm.reset();
        closeDriverPanels();
        clearMessage(driverMessage);
    });

    driverEmailInput.addEventListener('input', () => {
        const email = driverEmailInput.value.trim().toLowerCase();

        if (email !== driverResolvedEmail) {
            resetDriverAccountResolution();
        }
    });

    driverEmailInput.addEventListener('blur', () => {
        resolveDriverAccount();
    });

    const editDriver = (driver) => {
        clearMessage(driverMessage);
        expandedDriverId = null;
        driverCreatePanel.hidden = true;
        driverEditPanel.hidden = false;
        driverAddButton.hidden = true;

        document.getElementById('edit-driver-id').value = driver.id;
        document.getElementById('edit-driver-first-name').value = driver.first_name ?? '';
        document.getElementById('edit-driver-last-name').value = driver.last_name ?? '';
        document.getElementById('edit-driver-email').value = driver.email ?? '';
        document.getElementById('edit-driver-phone').value = driver.phone ?? '';
        document.getElementById('edit-driver-external-id').value = driver.external_driver_id ?? '';
        document.getElementById('edit-driver-license').value = driver.license_number ?? '';
        document.getElementById('edit-driver-license-category').value = driver.license_category ?? '';

        renderDrivers();
        document.getElementById('edit-driver-first-name').focus();
    };

    const loadDriverAssignments = async (driver) => {
        const response = await fetch(
            `/api/v1/own-drivers/${driver.id}/assignments`,
            {
                headers: headers(),
            },
        );

        if (!response.ok) {
            return {
                current: null,
                items: [],
            };
        }

        const payload = await response.json();

        return payload?.data || {
            current: null,
            items: [],
        };
    };

    const isDriverActive = (driver) => {
        return Boolean(driver.assignment_history?.current);
    };

    const latestDriverAssignment = (driver) => {
        const items = driver.assignment_history?.items || [];

        return items.length > 0
            ? items[0]
            : null;
    };

    const normalizeDriverSearch = (value) => {
        return String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('cs-CZ')
            .trim();
    };

    const driverMatchesSearch = (driver, search) => {
        if (!search) {
            return true;
        }

        const assignments = driver.assignment_history?.items || [];

        const haystack = [
            driver.full_name,
            driver.first_name,
            driver.last_name,
            driver.external_driver_id,
            driver.email,
            driver.phone,
            driver.license_number,
            driver.license_category,
            ...assignments.map((item) => item.organization_name),
        ]
            .map(normalizeDriverSearch)
            .join(' ');

        return haystack.includes(search);
    };

    const filteredAndSortedDrivers = () => {
        const filter = driverStatusFilter?.value || 'all';
        const search = normalizeDriverSearch(
            driverSearchInput?.value || '',
        );

        return [...driverItems]
            .filter((driver) => {
                const active = isDriverActive(driver);

                if (
                    filter === 'active'
                    && !active
                ) {
                    return false;
                }

                if (
                    filter === 'inactive'
                    && active
                ) {
                    return false;
                }

                return driverMatchesSearch(
                    driver,
                    search,
                );
            })
            .sort((left, right) => {
                const activeDifference =
                    Number(isDriverActive(right)) - Number(isDriverActive(left));

                if (activeDifference !== 0) {
                    return activeDifference;
                }

                return (left.full_name || left.email || '')
                    .localeCompare(
                        right.full_name || right.email || '',
                        'cs',
                    );
            });
    };

    const loadDrivers = async () => {
        const response = await fetch('/api/v1/own-drivers', {
            headers: headers(),
        });

        if (unauthorizedOrForbidden(response)) {
            return;
        }

        if (!response.ok) {
            driverList.className = 'empty';
            driverList.textContent = await apiError(response);
            return;
        }

        const payload = await response.json();
        const rawDrivers = payload?.data?.items || [];

        const histories = await Promise.all(
            rawDrivers.map((driver) => loadDriverAssignments(driver)),
        );

        driverItems = rawDrivers.map((driver, index) => ({
            ...driver,
            assignment_history: histories[index],
        }));

        renderDrivers();
    };

    const createStatusPill = (active) => {
        const pill = document.createElement('span');
        pill.className = active
            ? 'driver-status-pill driver-status-active'
            : 'driver-status-pill driver-status-inactive';
        pill.textContent = active
            ? 'Aktivní'
            : 'Neaktivní';

        return pill;
    };

    const createHistoryStatusPill = (assignment) => {
        const active = assignment.status === 'active';
        const pill = document.createElement('span');
        pill.className = active
            ? 'driver-status-pill driver-status-active'
            : 'driver-status-pill driver-status-inactive';

        if (assignment.status === 'scheduled') {
            pill.textContent = 'Naplánováno';
            return pill;
        }

        pill.textContent = active
            ? 'Aktivní'
            : 'Ukončeno';

        return pill;
    };

    const populateOrganizationSelect = (select) => {
        select.replaceChildren();

        const options = [];

        if (companyData?.id) {
            options.push({
                id: companyData.id,
                name: `${companyData.name} (Moje firma)`,
            });
        }

        carrierItems.forEach((carrier) => {
            options.push({
                id: carrier.id,
                name: carrier.name,
            });
        });

        options.forEach((organization) => {
            const option = document.createElement('option');
            option.value = organization.id;
            option.textContent = organization.name;
            select.appendChild(option);
        });
    };

    const showInlineDriverMessage = (container, text, kind = 'ok') => {
        container.textContent = text;
        container.className = `message ${kind}`;
        container.hidden = false;
    };

    const buildAssignmentCreateForm = (driver, host) => {
        const panel = document.createElement('div');
        panel.className = 'driver-inline-form';

        const title = document.createElement('h4');
        title.textContent = 'Přidat období spolupráce';
        title.style.marginTop = '0';

        const form = document.createElement('form');

        const grid = document.createElement('div');
        grid.className = 'form-grid';

        const organizationWrap = document.createElement('div');
        organizationWrap.className = 'wide';

        const organizationLabel = document.createElement('label');
        organizationLabel.textContent = 'Dopravce / organizace';

        const organizationSelect = document.createElement('select');
        organizationSelect.required = true;
        organizationSelect.name = 'organization_id';
        populateOrganizationSelect(organizationSelect);

        organizationWrap.append(
            organizationLabel,
            organizationSelect,
        );

        const fromWrap = document.createElement('div');
        const fromLabel = document.createElement('label');
        fromLabel.textContent = 'Spolupráce od';
        const fromInput = document.createElement('input');
        fromInput.type = 'date';
        fromInput.name = 'valid_from';
        fromInput.required = true;
        fromInput.value = todayIso();
        fromWrap.append(fromLabel, fromInput);

        const untilWrap = document.createElement('div');
        const untilLabel = document.createElement('label');
        untilLabel.textContent = 'Spolupráce do (nepovinné)';
        const untilInput = document.createElement('input');
        untilInput.type = 'date';
        untilInput.name = 'valid_until';
        untilWrap.append(untilLabel, untilInput);

        const reasonWrap = document.createElement('div');
        reasonWrap.className = 'wide';
        const reasonLabel = document.createElement('label');
        reasonLabel.textContent = 'Důvod ukončení (nepovinné)';
        const reasonInput = document.createElement('input');
        reasonInput.name = 'end_reason';
        reasonInput.maxLength = 1000;
        reasonWrap.append(reasonLabel, reasonInput);

        grid.append(
            organizationWrap,
            fromWrap,
            untilWrap,
            reasonWrap,
        );

        const actions = document.createElement('div');
        actions.className = 'actions';

        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = 'btn btn-light';
        cancelButton.textContent = 'Zrušit';

        const saveButton = document.createElement('button');
        saveButton.type = 'submit';
        saveButton.className = 'btn btn-primary';
        saveButton.textContent = 'Uložit období';

        actions.append(
            cancelButton,
            saveButton,
        );

        const message = document.createElement('div');
        message.className = 'message';
        message.hidden = true;

        form.append(
            grid,
            actions,
            message,
        );

        panel.append(
            title,
            form,
        );

        cancelButton.addEventListener('click', () => {
            expandedDriverMode = 'history';
            driverHistoryMessage = '';
            renderDrivers();
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            saveButton.disabled = true;

            const payload = Object.fromEntries(
                new FormData(form).entries(),
            );

            if (!payload.valid_until) {
                delete payload.valid_until;
            }

            if (!payload.end_reason) {
                delete payload.end_reason;
            }

            try {
                const response = await fetch(
                    `/api/v1/own-drivers/${driver.id}/assignments`,
                    {
                        method: 'POST',
                        headers: headers(true),
                        body: JSON.stringify(payload),
                    },
                );

                if (unauthorizedOrForbidden(response)) {
                    return;
                }

                if (!response.ok) {
                    showInlineDriverMessage(
                        message,
                        await apiError(response),
                        'error',
                    );
                    return;
                }

                const result = await response.json();
                driverHistoryMessage =
                    result?.message || 'Období spolupráce bylo přidáno.';
                expandedDriverMode = 'history';
                await loadDrivers();
            } finally {
                saveButton.disabled = false;
            }
        });

        host.appendChild(panel);
    };

    const buildAssignmentEndForm = (driver, assignment, host) => {
        const panel = document.createElement('div');
        panel.className = 'driver-inline-form';

        const title = document.createElement('h4');
        title.textContent = `Ukončit spolupráci · ${assignment.organization_name}`;
        title.style.marginTop = '0';

        const form = document.createElement('form');

        const grid = document.createElement('div');
        grid.className = 'form-grid';

        const dateWrap = document.createElement('div');
        const dateLabel = document.createElement('label');
        dateLabel.textContent = 'Spolupráce do';
        const dateInput = document.createElement('input');
        dateInput.type = 'date';
        dateInput.name = 'valid_until';
        dateInput.required = true;
        dateInput.value = todayIso();
        dateWrap.append(dateLabel, dateInput);

        const reasonWrap = document.createElement('div');
        reasonWrap.className = 'wide';
        const reasonLabel = document.createElement('label');
        reasonLabel.textContent = 'Důvod ukončení (nepovinné)';
        const reasonInput = document.createElement('input');
        reasonInput.name = 'end_reason';
        reasonInput.maxLength = 1000;
        reasonWrap.append(reasonLabel, reasonInput);

        grid.append(
            dateWrap,
            reasonWrap,
        );

        const actions = document.createElement('div');
        actions.className = 'actions';

        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = 'btn btn-light';
        cancelButton.textContent = 'Zrušit';

        const saveButton = document.createElement('button');
        saveButton.type = 'submit';
        saveButton.className = 'btn btn-primary';
        saveButton.textContent = 'Ukončit období';

        actions.append(
            cancelButton,
            saveButton,
        );

        const message = document.createElement('div');
        message.className = 'message';
        message.hidden = true;

        form.append(
            grid,
            actions,
            message,
        );

        panel.append(
            title,
            form,
        );

        cancelButton.addEventListener('click', () => {
            expandedDriverMode = 'history';
            expandedEndAssignmentId = null;
            driverHistoryMessage = '';
            renderDrivers();
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            saveButton.disabled = true;

            const payload = Object.fromEntries(
                new FormData(form).entries(),
            );

            if (!payload.end_reason) {
                delete payload.end_reason;
            }

            try {
                const response = await fetch(
                    `/api/v1/own-drivers/${driver.id}/assignments/${assignment.id}/end`,
                    {
                        method: 'PATCH',
                        headers: headers(true),
                        body: JSON.stringify(payload),
                    },
                );

                if (unauthorizedOrForbidden(response)) {
                    return;
                }

                if (!response.ok) {
                    showInlineDriverMessage(
                        message,
                        await apiError(response),
                        'error',
                    );
                    return;
                }

                const result = await response.json();
                driverHistoryMessage =
                    result?.message || 'Období spolupráce bylo ukončeno.';
                expandedDriverMode = 'history';
                expandedEndAssignmentId = null;
                await loadDrivers();
            } finally {
                saveButton.disabled = false;
            }
        });

        host.appendChild(panel);
    };

    const buildDriverHistoryRow = (driver, columnCount) => {
        const detailRow = document.createElement('tr');
        detailRow.className = 'driver-history-row';

        const cell = document.createElement('td');
        cell.colSpan = columnCount;

        const wrapper = document.createElement('div');
        wrapper.className = 'driver-history-inline';

        const head = document.createElement('div');
        head.className = 'history-head';

        const titleWrap = document.createElement('div');

        const title = document.createElement('h4');
        title.textContent = `Historie spolupráce · ${driver.full_name || driver.email}`;

        const subtitle = document.createElement('div');
        subtitle.className = 'muted';
        subtitle.textContent =
            'Každé nové působení u dopravce je samostatné období. Starší období zůstávají zachována.';

        titleWrap.append(
            title,
            subtitle,
        );

        head.append(
            titleWrap,
        );

        const actions = document.createElement('div');
        actions.className = 'driver-history-actions';

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'btn btn-primary';
        addButton.textContent = '+ Přidat období spolupráce';
        addButton.addEventListener('click', () => {
            expandedDriverMode = 'create';
            expandedEndAssignmentId = null;
            driverHistoryMessage = '';
            renderDrivers();
        });

        actions.appendChild(addButton);

        wrapper.append(
            head,
            actions,
        );

        if (driverHistoryMessage) {
            const message = document.createElement('div');
            message.className = 'message ok';
            message.textContent = driverHistoryMessage;
            wrapper.appendChild(message);
        }

        if (expandedDriverMode === 'create') {
            buildAssignmentCreateForm(
                driver,
                wrapper,
            );
        }

        const items = driver.assignment_history?.items || [];

        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'empty';
            empty.textContent = 'Zatím není uložené žádné období spolupráce.';
            wrapper.appendChild(empty);
        } else {
            const table = document.createElement('table');

            const headElement = document.createElement('thead');
            const headRow = document.createElement('tr');

            ['Organizace', 'Od', 'Do', 'Stav', 'Důvod ukončení', ''].forEach((label) => {
                const th = document.createElement('th');
                th.textContent = label;
                headRow.appendChild(th);
            });

            headElement.appendChild(headRow);
            table.appendChild(headElement);

            const body = document.createElement('tbody');

            items.forEach((assignment) => {
                const row = document.createElement('tr');

                const organizationCell = document.createElement('td');
                organizationCell.textContent = assignment.organization_name || '—';

                const fromCell = document.createElement('td');
                fromCell.textContent = formatDate(assignment.valid_from);

                const untilCell = document.createElement('td');
                untilCell.textContent = assignment.valid_until
                    ? formatDate(assignment.valid_until)
                    : 'dosud';

                const statusCell = document.createElement('td');
                statusCell.appendChild(
                    createHistoryStatusPill(assignment),
                );

                const reasonCell = document.createElement('td');
                reasonCell.textContent = assignment.end_reason || 'Nevyplněno';

                const actionCell = document.createElement('td');

                if (
                    assignment.status === 'active'
                    && assignment.valid_until === null
                ) {
                    const endButton = document.createElement('button');
                    endButton.type = 'button';
                    endButton.className = 'btn btn-light';
                    endButton.textContent = 'Ukončit spolupráci';
                    endButton.addEventListener('click', () => {
                        expandedDriverMode = 'end';
                        expandedEndAssignmentId = assignment.id;
                        driverHistoryMessage = '';
                        renderDrivers();
                    });

                    actionCell.appendChild(endButton);
                }

                row.append(
                    organizationCell,
                    fromCell,
                    untilCell,
                    statusCell,
                    reasonCell,
                    actionCell,
                );

                body.appendChild(row);
            });

            table.appendChild(body);
            wrapper.appendChild(table);
        }

        if (
            expandedDriverMode === 'end'
            && expandedEndAssignmentId !== null
        ) {
            const assignment = items.find(
                (item) => item.id === expandedEndAssignmentId,
            );

            if (assignment) {
                buildAssignmentEndForm(
                    driver,
                    assignment,
                    wrapper,
                );
            }
        }

        cell.appendChild(wrapper);
        detailRow.appendChild(cell);

        return detailRow;
    };

    const renderDrivers = () => {
        driverList.replaceChildren();

        const visibleDrivers = filteredAndSortedDrivers();

        if (driverItems.length === 0) {
            driverList.className = 'empty';
            driverList.textContent = 'Zatím není založen žádný vlastní řidič.';
            return;
        }

        if (visibleDrivers.length === 0) {
            driverList.className = 'empty';
            driverList.textContent = 'Filtru neodpovídá žádný řidič.';
            return;
        }

        driverList.className = '';

        const table = document.createElement('table');
        const head = document.createElement('thead');
        const headRow = document.createElement('tr');

        const columns = [
            'Řidič',
            'ID Zásilkovna',
            'Login',
            'Telefon',
            'ŘP',
            'Profil',
            'Stav',
            'Spolupráce',
            '',
        ];

        columns.forEach((label) => {
            const th = document.createElement('th');
            th.textContent = label;
            headRow.appendChild(th);
        });

        head.appendChild(headRow);
        table.appendChild(head);

        const body = document.createElement('tbody');

        visibleDrivers.forEach((driver) => {
            const active = isDriverActive(driver);
            const current = driver.assignment_history?.current || null;
            const latest = latestDriverAssignment(driver);

            const row = document.createElement('tr');
            row.className = active
                ? 'driver-row-active'
                : 'driver-row-inactive';

            const driverCell = document.createElement('td');
            driverCell.textContent =
                [driver.last_name, driver.first_name]
                    .filter(Boolean)
                    .join(' ')
                || driver.full_name
                || '—';

            const externalIdCell = document.createElement('td');
            externalIdCell.textContent =
                driver.external_driver_id || 'Nevyplněno';

            const emailCell = document.createElement('td');
            emailCell.textContent = driver.email || '—';

            const phoneCell = document.createElement('td');
            phoneCell.textContent = driver.phone || 'Nevyplněno';

            const licenseCell = document.createElement('td');
            licenseCell.textContent = driver.license_number
                ? `${driver.license_number}${driver.license_category ? ` (${driver.license_category})` : ''}`
                : 'Nevyplněno';

            const profileCell = document.createElement('td');
            profileCell.textContent = 'Řidič';

            const statusCell = document.createElement('td');
            statusCell.appendChild(
                createStatusPill(active),
            );

            const cooperationCell = document.createElement('td');

            if (current) {
                cooperationCell.textContent =
                    `${current.organization_name} · od ${formatDate(current.valid_from)}`;
            } else if (latest) {
                const until = latest.valid_until
                    ? ` · do ${formatDate(latest.valid_until)}`
                    : '';

                cooperationCell.textContent =
                    `Poslední: ${latest.organization_name}${until}`;
            } else {
                cooperationCell.textContent = 'Bez historie spolupráce';
            }

            const actionCell = document.createElement('td');

            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'btn btn-success';
            editButton.textContent = 'Upravit řidiče';
            editButton.addEventListener('click', () => editDriver(driver));

            const historyButton = document.createElement('button');
            historyButton.type = 'button';
            historyButton.className = 'btn btn-success';
            historyButton.style.marginLeft = '8px';
            historyButton.textContent =
                expandedDriverId === driver.id
                    ? 'Zavřít historii'
                    : 'Historie spolupráce';

            historyButton.addEventListener('click', () => {
                if (expandedDriverId === driver.id) {
                    expandedDriverId = null;
                    expandedDriverMode = 'history';
                    expandedEndAssignmentId = null;
                    driverHistoryMessage = '';
                } else {
                    expandedDriverId = driver.id;
                    expandedDriverMode = 'history';
                    expandedEndAssignmentId = null;
                    driverHistoryMessage = '';
                }

                renderDrivers();
            });

            actionCell.append(
                editButton,
                historyButton,
            );

            row.append(
                driverCell,
                externalIdCell,
                emailCell,
                phoneCell,
                licenseCell,
                profileCell,
                statusCell,
                cooperationCell,
                actionCell,
            );

            body.appendChild(row);

            if (expandedDriverId === driver.id) {
                body.appendChild(
                    buildDriverHistoryRow(
                        driver,
                        columns.length,
                    ),
                );
            }
        });

        table.appendChild(body);
        driverList.appendChild(table);
    };

    driverStatusFilter.addEventListener('change', () => {
        expandedDriverId = null;
        expandedDriverMode = 'history';
        expandedEndAssignmentId = null;
        driverHistoryMessage = '';
        renderDrivers();
    });

    driverSearchInput.addEventListener('input', () => {
        expandedDriverId = null;
        expandedDriverMode = 'history';
        expandedEndAssignmentId = null;
        driverHistoryMessage = '';
        renderDrivers();
    });

    driverForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearMessage(driverMessage);

        const resolved = await resolveDriverAccount();

        if (!resolved) {
            return;
        }

        driverSaveButton.disabled = true;

        const payload = Object.fromEntries(
            new FormData(driverForm).entries(),
        );

        if (driverAccountMode === 'existing') {
            delete payload.password;
            delete payload.password_confirmation;
        }

        try {
            const response = await fetch('/api/v1/own-drivers', {
                method: 'POST',
                headers: headers(true),
                body: JSON.stringify(payload),
            });

            if (unauthorizedOrForbidden(response)) {
                return;
            }

            if (!response.ok) {
                showMessage(
                    driverMessage,
                    await apiError(response),
                    'error',
                );
                return;
            }

            const result = await response.json();

            driverForm.reset();
            closeDriverPanels();
            await loadDrivers();

            showMessage(
                driverMessage,
                result?.message || 'Řidič byl vytvořen.',
                'ok',
            );
        } finally {
            driverSaveButton.disabled = false;
        }
    });

    driverEditForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        driverEditSaveButton.disabled = true;
        clearMessage(driverMessage);

        const driverId = document.getElementById('edit-driver-id').value;
        const payload = Object.fromEntries(
            new FormData(driverEditForm).entries(),
        );

        try {
            const response = await fetch(`/api/v1/own-drivers/${driverId}`, {
                method: 'PATCH',
                headers: headers(true),
                body: JSON.stringify(payload),
            });

            if (unauthorizedOrForbidden(response)) {
                return;
            }

            if (!response.ok) {
                showMessage(
                    driverMessage,
                    await apiError(response),
                    'error',
                );
                return;
            }

            const result = await response.json();

            driverEditForm.reset();
            closeDriverPanels();
            await loadDrivers();

            showMessage(
                driverMessage,
                result?.message || 'Řidič byl upraven.',
                'ok',
            );
        } finally {
            driverEditSaveButton.disabled = false;
        }
    });
    const carrierAddButton = document.getElementById('carrier-add-button');
    const carrierCreatePanel = document.getElementById('carrier-create-panel');
    const carrierCreateCancelButton = document.getElementById('carrier-create-cancel-button');
    const carrierForm = document.getElementById('carrier-form');
    const carrierSaveButton = document.getElementById('carrier-save-button');
    const carrierEditPanel = document.getElementById('carrier-edit-panel');
    const carrierEditForm = document.getElementById('carrier-edit-form');
    const carrierEditCancelButton = document.getElementById('carrier-edit-cancel-button');
    const carrierEditSaveButton = document.getElementById('carrier-edit-save-button');
    const carrierMessage = document.getElementById('carrier-message');
    const carrierList = document.getElementById('carrier-list');

    const carrierIcoInput = document.getElementById('carrier-ico');
    const carrierRelationshipValidFrom = document.getElementById('carrier-relationship-valid-from');
    const carrierAresButton = document.getElementById('carrier-ares-button');
    const carrierAresResult = document.getElementById('carrier-ares-result');
    const carrierAresStatus = document.getElementById('carrier-ares-status');
    const carrierAresName = document.getElementById('carrier-ares-name');
    const carrierAresIco = document.getElementById('carrier-ares-ico');
    const carrierAresVatStatus = document.getElementById('carrier-ares-vat-status');
    const carrierAresVatNumber = document.getElementById('carrier-ares-vat-number');
    const carrierAresCountry = document.getElementById('carrier-ares-country');
    const carrierAresAddress = document.getElementById('carrier-ares-address');

    const carrierManualButton = document.getElementById('carrier-manual-button');
    const carrierManualPanel = document.getElementById('carrier-manual-panel');
    const carrierManualName = document.getElementById('carrier-manual-name');
    const carrierManualVatStatus = document.getElementById('carrier-manual-vat-status');
    const carrierManualVatNumber = document.getElementById('carrier-manual-vat-number');
    const carrierManualStreet = document.getElementById('carrier-manual-street');
    const carrierManualCity = document.getElementById('carrier-manual-city');
    const carrierManualPostalCode = document.getElementById('carrier-manual-postal-code');
    const carrierManualCountry = document.getElementById('carrier-manual-country');

    let verifiedCarrierIco = '';
    let manualCarrierMode = false;

    const vatStatusLabel = (value) => {
        if (value === 'payer') {
            return 'PLÁTCE DPH';
        }

        if (value === 'non_payer') {
            return 'NEPLÁTCE DPH';
        }

        return value || '—';
    };

    const carrierAddress = (carrier) => [
        carrier?.street,
        carrier?.city,
        carrier?.postal_code,
    ].filter(Boolean).join(', ') || '—';

    const updateManualCarrierValidity = () => {
        if (!manualCarrierMode) {
            return;
        }

        carrierManualVatNumber.required =
            carrierManualVatStatus.value === 'payer';

        const nameOk =
            carrierManualName.value.trim() !== '';

        const vatOk =
            carrierManualVatStatus.value === 'payer'
            || carrierManualVatStatus.value === 'non_payer';

        const vatNumberOk =
            carrierManualVatStatus.value !== 'payer'
            || carrierManualVatNumber.value.trim() !== '';

        const relationshipDateOk =
            carrierRelationshipValidFrom.value !== '';

        carrierSaveButton.disabled =
            !(
                nameOk
                && vatOk
                && vatNumberOk
                && relationshipDateOk
            );
    };

    const setManualCarrierMode = (enabled) => {
        manualCarrierMode = enabled;

        carrierManualPanel.hidden = !enabled;

        document
            .querySelectorAll('[data-manual-carrier-field]')
            .forEach((field) => {
                field.disabled = !enabled;
            });

        carrierManualName.required = enabled;
        carrierManualVatStatus.required = enabled;

        if (!enabled) {
            carrierManualVatNumber.required = false;
        }

        if (enabled) {
            verifiedCarrierIco = '';
            carrierAresResult.hidden = true;
            updateManualCarrierValidity();
            carrierManualName.focus();
        }
    };

    const resetCarrierCreateState = () => {
        verifiedCarrierIco = '';
        manualCarrierMode = false;

        carrierSaveButton.disabled = true;

        carrierAresResult.hidden = true;
        carrierAresStatus.className = 'ares-status';
        carrierAresStatus.replaceChildren();

        carrierAresName.textContent = '';
        carrierAresIco.textContent = '';
        carrierAresVatStatus.textContent = '';
        carrierAresVatNumber.textContent = '';
        carrierAresCountry.textContent = '';
        carrierAresAddress.textContent = '';

        carrierManualButton.hidden = true;

        carrierManualPanel.hidden = true;

        document
            .querySelectorAll('[data-manual-carrier-field]')
            .forEach((field) => {
                field.disabled = true;
            });

        carrierManualName.required = false;
        carrierManualVatStatus.required = false;
        carrierManualVatNumber.required = false;
    };

    const renderAresCarrier = (carrier) => {
        setManualCarrierMode(false);

        verifiedCarrierIco =
            carrier.registration_number || '';

        carrierManualButton.hidden = true;

        carrierAresResult.hidden = false;
        carrierAresStatus.className = 'ares-status ok';

        const dot = document.createElement('span');
        dot.className = 'ares-dot';

        const text = document.createElement('span');
        text.textContent = 'IČO OVĚŘENO V ARES';

        carrierAresStatus.replaceChildren(dot, text);

        carrierAresName.textContent =
            carrier.name || '—';

        carrierAresIco.textContent =
            carrier.registration_number || '—';

        carrierAresVatStatus.textContent =
            vatStatusLabel(carrier.vat_status);

        carrierAresVatNumber.textContent =
            carrier.vat_number || '—';

        carrierAresCountry.textContent =
            carrier.country_code || '—';

        carrierAresAddress.textContent =
            carrier.full_address ||
            carrierAddress(carrier);

        carrierSaveButton.disabled =
            carrierRelationshipValidFrom.value === '';
    };

    const closeCarrierPanels = () => {
        carrierCreatePanel.hidden = true;
        carrierEditPanel.hidden = true;
        carrierAddButton.hidden = false;
    };

    carrierAddButton.addEventListener('click', () => {
        clearMessage(carrierMessage);

        carrierEditPanel.hidden = true;
        carrierCreatePanel.hidden = false;
        carrierAddButton.hidden = true;

        carrierForm.reset();
        resetCarrierCreateState();

        carrierIcoInput.focus();
    });

    carrierCreateCancelButton.addEventListener('click', () => {
        carrierForm.reset();
        resetCarrierCreateState();
        closeCarrierPanels();
        clearMessage(carrierMessage);
    });

    carrierIcoInput.addEventListener('input', () => {
        carrierIcoInput.value =
            carrierIcoInput.value
                .replace(/\D/g, '')
                .slice(0, 8);

        resetCarrierCreateState();
        clearMessage(carrierMessage);
    });

    carrierRelationshipValidFrom.addEventListener('input', () => {
        if (manualCarrierMode) {
            updateManualCarrierValidity();
            return;
        }

        carrierSaveButton.disabled =
            !(
                verifiedCarrierIco !== ''
                && carrierRelationshipValidFrom.value !== ''
            );
    });
    carrierManualButton.addEventListener('click', () => {
        clearMessage(carrierMessage);
        carrierManualButton.hidden = true;
        setManualCarrierMode(true);
    });

    [
        carrierManualName,
        carrierManualVatStatus,
        carrierManualVatNumber,
        carrierManualStreet,
        carrierManualCity,
        carrierManualPostalCode,
        carrierManualCountry,
    ].forEach((field) => {
        field.addEventListener(
            'input',
            updateManualCarrierValidity,
        );

        field.addEventListener(
            'change',
            updateManualCarrierValidity,
        );
    });

    carrierAresButton.addEventListener('click', async () => {
        const ico =
            carrierIcoInput.value.trim();

        resetCarrierCreateState();
        clearMessage(carrierMessage);

        if (!/^\d{8}$/.test(ico)) {
            showMessage(
                carrierMessage,
                'IČO musí obsahovat přesně 8 číslic.',
                'error',
            );

            carrierIcoInput.focus();
            return;
        }

        carrierAresButton.disabled = true;
        carrierIcoInput.disabled = true;

        try {
            const response = await fetch(
                `/api/v1/carriers/ares/${encodeURIComponent(ico)}`,
                {
                    headers: headers(),
                },
            );

            if (unauthorizedOrForbidden(response)) {
                return;
            }

            if (!response.ok) {
                showMessage(
                    carrierMessage,
                    `${await apiError(response)} Údaje můžete zadat ručně a ověřit později.`,
                    'error',
                );

                carrierManualButton.hidden = false;
                return;
            }

            const payload =
                await response.json();

            const carrier =
                payload?.data;

            if (
                !carrier
                || carrier.registration_number !== ico
            ) {
                showMessage(
                    carrierMessage,
                    'ARES vrátil neočekávaná data. Údaje můžete zadat ručně.',
                    'error',
                );

                carrierManualButton.hidden = false;
                return;
            }

            renderAresCarrier(carrier);
        } catch {
            showMessage(
                carrierMessage,
                'ARES se nepodařilo kontaktovat. Údaje můžete zadat ručně a ověřit později.',
                'error',
            );

            carrierManualButton.hidden = false;
        } finally {
            carrierAresButton.disabled = false;
            carrierIcoInput.disabled = false;
        }
    });

    let editCarrierItem = null;
    let editCarrierAresVerified = false;

    const editCarrierAresState =
        document.getElementById(
            'edit-carrier-ares-state',
        );

    const editCarrierAresButton =
        document.getElementById(
            'carrier-edit-ares-button',
        );

    const setEditCarrierAresState = (
        text,
        state = 'neutral',
    ) => {
        editCarrierAresState.textContent = text;

        if (state === 'success') {
            editCarrierAresState.style.background =
                '#ecfdf3';
            editCarrierAresState.style.color =
                '#067647';
            return;
        }

        if (state === 'warning') {
            editCarrierAresState.style.background =
                '#fffaeb';
            editCarrierAresState.style.color =
                '#b54708';
            return;
        }

        editCarrierAresState.style.background =
            '#f2f4f7';
        editCarrierAresState.style.color =
            '#475467';
    };

    const populateCarrierEditForm = (carrier) => {
        document.getElementById(
            'edit-carrier-id',
        ).value = carrier.id ?? '';

        document.getElementById(
            'edit-carrier-ico',
        ).value =
            carrier.registration_number ?? '';

        document.getElementById(
            'edit-carrier-relationship-valid-from',
        ).value =
            carrier.relationship_valid_from ?? '';

        document.getElementById(
            'edit-carrier-name',
        ).value =
            carrier.name ?? '';

        document.getElementById(
            'edit-carrier-vat-status',
        ).value =
            carrier.vat_status ?? 'non_payer';

        document.getElementById(
            'edit-carrier-vat-number',
        ).value =
            carrier.vat_number ?? '';

        document.getElementById(
            'edit-carrier-street',
        ).value =
            carrier.street ?? '';

        document.getElementById(
            'edit-carrier-city',
        ).value =
            carrier.city ?? '';

        document.getElementById(
            'edit-carrier-postal-code',
        ).value =
            carrier.postal_code ?? '';

        document.getElementById(
            'edit-carrier-country-code',
        ).value =
            carrier.country_code ?? 'CZ';
    };

    const openCarrierEdit = (carrier) => {
        clearMessage(carrierMessage);

        editCarrierItem = carrier;
        editCarrierAresVerified = false;

        carrierCreatePanel.hidden = true;
        carrierEditPanel.hidden = false;
        carrierAddButton.hidden = true;

        populateCarrierEditForm(carrier);

        if (carrier.ares_verified_at) {
            setEditCarrierAresState(
                'ARES: ULOŽENÉ ÚDAJE JSOU OZNAČENÉ JAKO OVĚŘENÉ.',
                'success',
            );
        } else {
            setEditCarrierAresState(
                'ARES: ČEKÁ NA OVĚŘENÍ.',
                'warning',
            );
        }

        carrierEditPanel.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    };

    carrierEditCancelButton.addEventListener(
        'click',
        () => {
            carrierEditForm.reset();
            editCarrierItem = null;
            editCarrierAresVerified = false;
            closeCarrierPanels();
            clearMessage(carrierMessage);
        },
    );

    const editAresManagedFields = [
        'edit-carrier-name',
        'edit-carrier-vat-status',
        'edit-carrier-vat-number',
        'edit-carrier-street',
        'edit-carrier-city',
        'edit-carrier-postal-code',
        'edit-carrier-country-code',
    ];

    editAresManagedFields.forEach((id) => {
        const field =
            document.getElementById(id);

        const markManualChange = () => {
            if (!editCarrierItem) {
                return;
            }

            editCarrierAresVerified = false;

            setEditCarrierAresState(
                'RUČNĚ UPRAVENO — PO ULOŽENÍ BUDE POTŘEBA ÚDAJE ZNOVU PROVĚŘIT V ARES.',
                'warning',
            );
        };

        field.addEventListener(
            'input',
            markManualChange,
        );

        field.addEventListener(
            'change',
            markManualChange,
        );
    });

    editCarrierAresButton.addEventListener(
        'click',
        async () => {
            if (!editCarrierItem) {
                return;
            }

            const ico =
                document.getElementById(
                    'edit-carrier-ico',
                ).value.trim();

            clearMessage(carrierMessage);

            editCarrierAresButton.disabled = true;

            try {
                const response = await fetch(
                    `/api/v1/carriers/ares/${encodeURIComponent(ico)}`,
                    {
                        headers: headers(),
                    },
                );

                if (unauthorizedOrForbidden(response)) {
                    return;
                }

                if (!response.ok) {
                    setEditCarrierAresState(
                        'ARES SE NEPODAŘILO OVĚŘIT. ULOŽENÁ DATA ZŮSTALA BEZE ZMĚNY.',
                        'warning',
                    );

                    showMessage(
                        carrierMessage,
                        await apiError(response),
                        'error',
                    );

                    return;
                }

                const payload =
                    await response.json();

                const ares =
                    payload?.data;

                if (
                    !ares
                    || ares.registration_number !== ico
                ) {
                    throw new Error(
                        'Unexpected ARES payload.',
                    );
                }

                const before = {
                    name:
                        document.getElementById(
                            'edit-carrier-name',
                        ).value,
                    vat_status:
                        document.getElementById(
                            'edit-carrier-vat-status',
                        ).value,
                    vat_number:
                        document.getElementById(
                            'edit-carrier-vat-number',
                        ).value,
                    street:
                        document.getElementById(
                            'edit-carrier-street',
                        ).value,
                    city:
                        document.getElementById(
                            'edit-carrier-city',
                        ).value,
                    postal_code:
                        document.getElementById(
                            'edit-carrier-postal-code',
                        ).value,
                    country_code:
                        document.getElementById(
                            'edit-carrier-country-code',
                        ).value,
                };

                document.getElementById(
                    'edit-carrier-name',
                ).value =
                    ares.name ?? '';

                document.getElementById(
                    'edit-carrier-vat-status',
                ).value =
                    ares.vat_status ?? 'non_payer';

                document.getElementById(
                    'edit-carrier-vat-number',
                ).value =
                    ares.vat_number ?? '';

                document.getElementById(
                    'edit-carrier-street',
                ).value =
                    ares.street ?? '';

                document.getElementById(
                    'edit-carrier-city',
                ).value =
                    ares.city ?? '';

                document.getElementById(
                    'edit-carrier-postal-code',
                ).value =
                    ares.postal_code ?? '';

                document.getElementById(
                    'edit-carrier-country-code',
                ).value =
                    ares.country_code ?? 'CZ';

                const changed =
                    before.name !== (ares.name ?? '')
                    || before.vat_status !==
                        (ares.vat_status ?? 'non_payer')
                    || before.vat_number !==
                        (ares.vat_number ?? '')
                    || before.street !==
                        (ares.street ?? '')
                    || before.city !==
                        (ares.city ?? '')
                    || before.postal_code !==
                        (ares.postal_code ?? '')
                    || before.country_code !==
                        (ares.country_code ?? 'CZ');

                editCarrierAresVerified = true;

                setEditCarrierAresState(
                    changed
                        ? 'ARES NALEZL ZMĚNY. FORMULÁŘ JE PŘEDVYPLNĚN AKTUÁLNÍMI ÚDAJI — ULOŽTE ZMĚNY.'
                        : 'ARES: ULOŽENÉ ÚDAJE SOUHLASÍ S AKTUÁLNÍMI ÚDAJI.',
                    'success',
                );
            } catch {
                editCarrierAresVerified = false;

                setEditCarrierAresState(
                    'ARES SE NEPODAŘILO OVĚŘIT. ULOŽENÁ DATA ZŮSTALA BEZE ZMĚNY.',
                    'warning',
                );
            } finally {
                editCarrierAresButton.disabled = false;
            }
        },
    );
    const renderCarriers = () => {
        carrierList.replaceChildren();

        if (carrierItems.length === 0) {
            carrierList.className = 'empty';
            carrierList.textContent =
                'Zatím není založen žádný externí dopravce.';
            return;
        }

        carrierList.className = '';

        const table =
            document.createElement('table');

        const head =
            document.createElement('thead');

        const headRow =
            document.createElement('tr');

        [
            'Dopravce',
            'IČO',
            'DPH',
            'DIČ',
            'Sídlo',
            'Spolupráce od',
            'ARES',
            'Stav',
            '',
        ].forEach((label) => {
            const th =
                document.createElement('th');

            th.textContent = label;

            headRow.appendChild(th);
        });

        head.appendChild(headRow);
        table.appendChild(head);

        const body =
            document.createElement('tbody');

        carrierItems.forEach((carrier) => {
            const row =
                document.createElement('tr');

            [
                carrier.name || '—',
                carrier.registration_number || '—',
                vatStatusLabel(carrier.vat_status),
                carrier.vat_number || '—',
                carrierAddress(carrier),
                carrier.relationship_valid_from || '—',
            ].forEach((value) => {
                const cell =
                    document.createElement('td');

                cell.textContent = value;

                row.appendChild(cell);
            });

            const verificationCell =
                document.createElement('td');

            const verification =
                document.createElement('span');

            if (carrier.ares_verified_at) {
                verification.className =
                    'carrier-verification verified';

                verification.textContent =
                    'OVĚŘENO';
            } else {
                verification.className =
                    'carrier-verification pending';

                verification.textContent =
                    'ČEKÁ NA OVĚŘENÍ';
            }

            verificationCell.appendChild(
                verification,
            );

            row.appendChild(
                verificationCell,
            );

            const statusCell =
                document.createElement('td');

            statusCell.textContent =
                carrier.status === 'active'
                    ? 'AKTIVNÍ'
                    : (carrier.status || '—');

            row.appendChild(statusCell);

            const actionCell =
                document.createElement('td');

            const actionGroup =
                document.createElement('div');

            actionGroup.className =
                'carrier-action-group';


const editButton =
                document.createElement('button');

            editButton.type = 'button';
            editButton.className = 'btn btn-light';
            editButton.textContent = 'Upravit';

            editButton.addEventListener(
                'click',
                () => openCarrierEdit(carrier),
            );

            actionGroup.appendChild(
                editButton,
            );

            actionCell.appendChild(
                actionGroup,
            );

            row.appendChild(actionCell);

            body.appendChild(row);
        });

        table.appendChild(body);
        carrierList.appendChild(table);
    };

    const loadCarriers = async () => {
        const response = await fetch(
            '/api/v1/carriers',
            {
                headers: headers(),
            },
        );

        if (unauthorizedOrForbidden(response)) {
            return;
        }

        if (!response.ok) {
            carrierList.className = 'empty';
            carrierList.textContent =
                await apiError(response);

            return;
        }

        const payload =
            await response.json();

        carrierItems =
            payload?.data?.items || [];

        renderCarriers();
    };

    carrierForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const ico =
            carrierIcoInput.value.trim();

        const relationshipValidFrom =
            carrierRelationshipValidFrom.value;

        clearMessage(carrierMessage);

        if (relationshipValidFrom === '') {
            showMessage(
                carrierMessage,
                'Vyplňte datum začátku spolupráce.',
                'error',
            );

            carrierRelationshipValidFrom.focus();
            return;
        }

        let payload;

        if (manualCarrierMode) {
            updateManualCarrierValidity();

            if (carrierSaveButton.disabled) {
                showMessage(
                    carrierMessage,
                    'Doplňte povinné údaje ručního zadání.',
                    'error',
                );
                return;
            }

            payload = {
                registration_number: ico,
                relationship_valid_from: relationshipValidFrom,
                manual_entry: true,
                name:
                    carrierManualName.value.trim(),
                vat_status:
                    carrierManualVatStatus.value,
                vat_number:
                    carrierManualVatNumber.value.trim() || null,
                street:
                    carrierManualStreet.value.trim() || null,
                city:
                    carrierManualCity.value.trim() || null,
                postal_code:
                    carrierManualPostalCode.value.trim() || null,
                country_code:
                    carrierManualCountry.value || 'CZ',
            };
        } else {
            if (
                verifiedCarrierIco === ''
                || verifiedCarrierIco !== ico
            ) {
                showMessage(
                    carrierMessage,
                    'Nejdříve ověřte IČO v ARES nebo použijte ruční zadání.',
                    'error',
                );

                return;
            }

            payload = {
                registration_number: ico,
                relationship_valid_from: relationshipValidFrom,
            };
        }

        carrierSaveButton.disabled = true;
        carrierAresButton.disabled = true;
        carrierIcoInput.disabled = true;

        try {
            const response = await fetch(
                '/api/v1/carriers',
                {
                    method: 'POST',
                    headers: headers(true),
                    body: JSON.stringify(payload),
                },
            );

            if (unauthorizedOrForbidden(response)) {
                return;
            }

            if (!response.ok) {
                showMessage(
                    carrierMessage,
                    await apiError(response),
                    'error',
                );

                return;
            }

            const result =
                await response.json();

            carrierForm.reset();
            resetCarrierCreateState();
            closeCarrierPanels();

            await loadCarriers();

            showMessage(
                carrierMessage,
                result?.message ||
                    'Dopravce byl vytvořen.',
                'ok',
            );
        } finally {
            carrierAresButton.disabled = false;
            carrierIcoInput.disabled = false;

            if (manualCarrierMode) {
                updateManualCarrierValidity();
            } else if (verifiedCarrierIco !== '') {
                carrierSaveButton.disabled = false;
            }
        }
    });

    carrierEditForm.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            if (!editCarrierItem) {
                return;
            }

            carrierEditSaveButton.disabled = true;
            clearMessage(carrierMessage);

            const vatStatus =
                document.getElementById(
                    'edit-carrier-vat-status',
                ).value;

            const vatNumber =
                document.getElementById(
                    'edit-carrier-vat-number',
                ).value.trim();

            if (
                vatStatus === 'payer'
                && vatNumber === ''
            ) {
                showMessage(
                    carrierMessage,
                    'DIČ je u plátce DPH povinné.',
                    'error',
                );

                carrierEditSaveButton.disabled = false;
                return;
            }

            const relationshipValidFrom =
                document.getElementById(
                    'edit-carrier-relationship-valid-from',
                ).value;

            if (relationshipValidFrom === '') {
                showMessage(
                    carrierMessage,
                    'Vyplňte datum začátku spolupráce.',
                    'error',
                );

                carrierEditSaveButton.disabled = false;
                return;
            }

            const payload = {
                name:
                    document.getElementById(
                        'edit-carrier-name',
                    ).value.trim(),
                vat_status:
                    vatStatus,
                vat_number:
                    vatNumber || null,
                street:
                    document.getElementById(
                        'edit-carrier-street',
                    ).value.trim() || null,
                city:
                    document.getElementById(
                        'edit-carrier-city',
                    ).value.trim() || null,
                postal_code:
                    document.getElementById(
                        'edit-carrier-postal-code',
                    ).value.trim() || null,
                country_code:
                    document.getElementById(
                        'edit-carrier-country-code',
                    ).value || 'CZ',
                relationship_valid_from:
                    relationshipValidFrom,
                verify_with_ares:
                    editCarrierAresVerified,
            };

            try {
                const response = await fetch(
                    `/api/v1/carriers/${editCarrierItem.id}`,
                    {
                        method: 'PATCH',
                        headers: headers(true),
                        body: JSON.stringify(payload),
                    },
                );

                if (unauthorizedOrForbidden(response)) {
                    return;
                }

                if (!response.ok) {
                    showMessage(
                        carrierMessage,
                        await apiError(response),
                        'error',
                    );
                    return;
                }

                const result =
                    await response.json();

                carrierEditForm.reset();

                editCarrierItem = null;
                editCarrierAresVerified = false;

                closeCarrierPanels();

                await loadCarriers();

                showMessage(
                    carrierMessage,
                    result?.message ||
                        'Údaje dopravce byly uloženy.',
                    'ok',
                );
            } finally {
                carrierEditSaveButton.disabled = false;
            }
        },
    );
    Promise.all([
        loadCompany(),
        loadDrivers(),
        loadCarriers(),
    ]).catch(() => {
        showMessage(companyMessage, 'Nepodařilo se načíst data.', 'error');
    });
})();
</script>
</body>
</html>