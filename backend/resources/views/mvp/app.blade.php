<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TMS System – Pilot</title>
    <style>
        :root {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172033;
            background: #f4f6f9;
            font-synthesis: none;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(53, 92, 125, .08), transparent 28rem),
                #f4f6f9;
        }

        button,
        input {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        .hidden {
            display: none !important;
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .login-card {
            width: min(440px, 100%);
            background: #fff;
            border: 1px solid #e4e8ef;
            border-radius: 18px;
            box-shadow: 0 22px 70px rgba(32, 48, 70, .12);
            padding: 34px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            background: #172033;
            color: #fff;
            font-weight: 800;
            letter-spacing: .03em;
        }

        .brand-title {
            font-weight: 800;
            font-size: 19px;
        }

        .brand-subtitle {
            color: #6b7484;
            font-size: 13px;
            margin-top: 2px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
            letter-spacing: -.02em;
        }

        .lead {
            margin: 0 0 26px;
            color: #687386;
            line-height: 1.55;
        }

        .field {
            display: grid;
            gap: 7px;
            margin-bottom: 16px;
        }

        .field label {
            font-size: 13px;
            font-weight: 700;
            color: #3d4656;
        }

        .field input {
            width: 100%;
            border: 1px solid #ccd3df;
            border-radius: 10px;
            padding: 12px 13px;
            outline: none;
            background: #fff;
        }

        .field input:focus {
            border-color: #596b85;
            box-shadow: 0 0 0 3px rgba(89, 107, 133, .12);
        }

        .primary-button,
        .secondary-button,
        .danger-button {
            border: 0;
            border-radius: 10px;
            padding: 11px 15px;
            font-weight: 700;
        }

        .primary-button {
            width: 100%;
            background: #172033;
            color: #fff;
        }

        .primary-button:disabled {
            opacity: .55;
            cursor: wait;
        }

        .secondary-button {
            background: #edf0f5;
            color: #253147;
        }

        .danger-button {
            background: #fff0f0;
            color: #9d2f2f;
        }

        .message {
            margin-top: 16px;
            padding: 11px 12px;
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.45;
        }

        .message.error {
            background: #fff1f1;
            color: #9b2c2c;
            border: 1px solid #ffd1d1;
        }

        .message.info {
            background: #eef6ff;
            color: #274e79;
            border: 1px solid #d7e9ff;
        }

        .app-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
        }

        .sidebar {
            background: #172033;
            color: #e8edf5;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 26px;
        }

        .sidebar .brand {
            margin-bottom: 0;
        }

        .sidebar .brand-mark {
            background: #fff;
            color: #172033;
        }

        .sidebar .brand-subtitle {
            color: #aeb8c8;
        }

        .nav {
            display: grid;
            gap: 7px;
        }

        .nav-item {
            border: 0;
            text-align: left;
            color: #c7d0dd;
            background: transparent;
            border-radius: 9px;
            padding: 10px 11px;
            font-weight: 650;
        }

        .nav-item.active {
            color: #fff;
            background: rgba(255, 255, 255, .11);
        }

        .sidebar-footer {
            margin-top: auto;
            display: grid;
            gap: 10px;
        }

        .user-box {
            padding: 11px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .08);
            font-size: 12px;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .content {
            min-width: 0;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }

        .eyebrow {
            color: #758096;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: 6px;
        }

        .topbar h1 {
            font-size: 27px;
        }

        .topbar p {
            color: #687386;
            margin: 7px 0 0;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 11px;
            background: #edf8f1;
            color: #28704a;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            border: 1px solid #e1e6ee;
            border-radius: 14px;
            padding: 19px;
            box-shadow: 0 6px 22px rgba(32, 48, 70, .04);
        }

        .metric-label {
            color: #788397;
            font-size: 12px;
            font-weight: 750;
            margin-bottom: 9px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .metric-note {
            margin-top: 6px;
            font-size: 12px;
            color: #8a94a5;
        }

        .section-card {
            background: #fff;
            border: 1px solid #e1e6ee;
            border-radius: 14px;
            overflow: hidden;
        }

        .section-head {
            padding: 18px 20px;
            border-bottom: 1px solid #e8ecf2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .section-head h2 {
            margin: 0;
            font-size: 17px;
        }

        .section-head p {
            margin: 5px 0 0;
            color: #788397;
            font-size: 12px;
        }

        .table-wrap {
            width: 100%;
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 930px;
        }

        th,
        td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #edf0f4;
            font-size: 12px;
            vertical-align: middle;
        }

        th {
            color: #6d7788;
            background: #fafbfc;
            font-weight: 800;
            position: sticky;
            top: 0;
        }

        td {
            color: #30394a;
        }

        .badge {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 999px;
            background: #edf0f5;
            font-size: 11px;
            font-weight: 800;
        }

        .attention {
            color: #a04f16;
            font-weight: 800;
        }

        .empty-state {
            padding: 34px 20px;
            color: #778295;
            text-align: center;
        }

        .api-error {
            margin: 16px 20px 20px;
            padding: 12px 13px;
            background: #fff6e9;
            border: 1px solid #f7dfb7;
            color: #7c5624;
            border-radius: 10px;
            font-size: 12px;
            white-space: pre-wrap;
        }

        .pilot-banner {
            margin-bottom: 20px;
            border: 1px solid #dbe6f4;
            background: #f5f9ff;
            border-radius: 12px;
            padding: 15px 17px;
            color: #38516f;
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 920px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .content {
                padding: 20px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
            }
        }

        /* S020-04A DAILY REPORT ENTRY */
        .daily-entry-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-bottom: 14px;
        }

        .daily-entry-panel {
            background: #fff;
            border: 1px solid #e1e6ee;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 6px 22px rgba(32, 48, 70, .04);
        }

        .daily-entry-panel[hidden] {
            display: none !important;
        }

        .daily-entry-panel h2 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        .daily-entry-subtitle {
            color: #788397;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .daily-entry-driver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            background: #edf8f1;
            color: #28704a;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .daily-entry-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .daily-entry-field {
            display: grid;
            gap: 6px;
        }

        .daily-entry-field.wide {
            grid-column: span 2;
        }

        .daily-entry-field.full {
            grid-column: 1 / -1;
        }

        .daily-entry-field label {
            font-size: 12px;
            font-weight: 800;
            color: #3d4656;
        }

        .daily-entry-field input,
        .daily-entry-field textarea {
            width: 100%;
            border: 1px solid #ccd3df;
            border-radius: 10px;
            padding: 10px 11px;
            outline: none;
            background: #fff;
            font: inherit;
        }

        .daily-entry-field textarea {
            min-height: 78px;
            resize: vertical;
        }

        .daily-entry-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .daily-entry-message {
            margin: 0 0 16px;
            padding: 11px 12px;
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.45;
        }

        .daily-entry-message.ok {
            background: #ecfdf3;
            border: 1px solid #abefc6;
            color: #027a48;
        }

        .daily-entry-message.error {
            background: #fef3f2;
            border: 1px solid #fecdca;
            color: #b42318;
        }

        @media (max-width: 1100px) {
            .daily-entry-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .daily-entry-grid {
                grid-template-columns: 1fr;
            }

            .daily-entry-field.wide,
            .daily-entry-field.full {
                grid-column: auto;
            }
        }

        /* S020-04E2 DYNAMIC EFFECTIVE FORM */
        #dailyReportCreatePanel .daily-entry-grid {
            grid-template-columns: minmax(0, 1fr);
            max-width: 520px;
        }

        #dailyReportCreatePanel .daily-entry-config-state {
            max-width: 520px;
        }

        #dailyReportCreatePanel .daily-entry-footer {
            justify-content: flex-start;
            max-width: 520px;
        }

        #dailyReportCreatePanel .daily-entry-field.full {
            grid-column: auto;
        }

        .daily-entry-config-state {
            margin: 14px 0;
            padding: 10px 12px;
            border: 1px solid #d8dee8;
            border-radius: 10px;
            background: #f8fafc;
            color: #526071;
            font-size: 12px;
            line-height: 1.45;
        }

        .daily-entry-config-state.ok {
            border-color: #abefc6;
            background: #ecfdf3;
            color: #027a48;
        }

        .daily-entry-config-state.error {
            border-color: #fecdca;
            background: #fef3f2;
            color: #b42318;
        }

        .daily-entry-field select {
            width: 100%;
            border: 1px solid #ccd3df;
            border-radius: 10px;
            padding: 10px 11px;
            outline: none;
            background: #fff;
            font: inherit;
        }

        .daily-entry-required {
            color: #b42318;
        }

        .daily-entry-custom-badge {
            display: inline-flex;
            margin-left: 6px;
            padding: 1px 5px;
            border-radius: 999px;
            background: #eef2f7;
            color: #667085;
            font-size: 10px;
            font-weight: 700;
            vertical-align: middle;
        }

        /* S020-04E2D ROUTE WORKFLOW */
        .route-status-written {
            background: #ecfdf3;
            color: #027a48;
        }

        .route-status-waiting {
            background: #eff8ff;
            color: #175cd3;
        }

        .route-status-correction {
            background: #fef3f2;
            color: #b42318;
        }

        .route-status-corrected {
            background: #fffaeb;
            color: #b54708;
        }

        .route-status-approved {
            background: #ecfdf3;
            color: #027a48;
        }

        .route-status-closed {
            background: #f2f4f7;
            color: #475467;
        }

        .route-actions {
            white-space: nowrap;
        }

        .route-action-button {
            width: auto;
            min-width: 110px;
            padding: 7px 10px;
            font-size: 12px;
        }

        .route-edit-note {
            margin: 10px 0 0;
            padding: 9px 11px;
            border-radius: 9px;
            background: #fffaeb;
            color: #7a2e0e;
            font-size: 12px;
        }

        /* S020-04E2D4 SIGNED KM DIFFERENCE */
        .kilometre-difference-ok {
            background: #ecfdf3;
            color: #027a48;
            font-weight: 700;
        }

        .kilometre-difference-alert {
            background: #fef3f2;
            color: #b42318;
            font-weight: 700;
        }

        /* S020-04E2D5 PARCEL BALANCE */
        .parcel-balance-state {
            max-width: 520px;
            margin: 12px 0 0;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .parcel-balance-state.ok {
            background: #ecfdf3;
            color: #027a48;
        }

        .parcel-balance-state.error,
        .parcel-balance-error {
            background: #fef3f2;
            color: #b42318;
            font-weight: 700;
        }

        /* S020-04E2E ROUTE HISTORY FILTERS */
        .route-history-filters {
            display: grid;
            gap: 12px;
            margin: 14px 0 18px;
            padding: 14px;
            border: 1px solid #e4e7ec;
            border-radius: 14px;
            background: #f9fafb;
        }

        .route-filter-row {
            display: grid;
            grid-template-columns: 110px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .route-filter-label {
            padding-top: 8px;
            color: #475467;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .route-filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .route-filter-chip {
            min-height: 34px;
            padding: 7px 11px;
            border: 1px solid #d0d5dd;
            border-radius: 999px;
            background: #ffffff;
            color: #344054;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .route-filter-chip:hover {
            border-color: #98a2b3;
            background: #f2f4f7;
        }

        .route-filter-chip.active {
            border-color: #101828;
            box-shadow: 0 0 0 2px rgba(16, 24, 40, .10);
        }

        .route-filter-chip.route-status-correction {
            border-color: #fecdca;
        }

        .route-filter-chip.route-status-approved {
            border-color: #abefc6;
        }

        .route-custom-period {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: end;
            padding-left: 122px;
        }

        .route-custom-period label {
            display: grid;
            gap: 5px;
            color: #475467;
            font-size: 12px;
            font-weight: 700;
        }

        .route-custom-period input {
            min-height: 38px;
            padding: 7px 9px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            background: #ffffff;
        }

        .route-filter-summary {
            min-height: 20px;
            color: #475467;
            font-size: 13px;
            font-weight: 700;
        }

        @media (max-width: 760px) {
            .route-filter-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .route-filter-label {
                padding-top: 0;
            }

            .route-custom-period {
                padding-left: 0;
            }
        }

        /* S020-04E2F WORKFLOW COLORS + CZECH DATE */
        .route-status-written,
        .route-status-corrected,
        .route-status-approved,
        .route-status-closed {
            background: #ecfdf3;
            color: #027a48;
        }

        .route-status-waiting {
            background: #fffaeb;
            color: #b54708;
        }

        .route-status-correction {
            background: #fef3f2;
            color: #b42318;
        }

        .route-filter-chip {
            border-color: #abefc6;
            background: #ecfdf3;
            color: #027a48;
        }

        .route-filter-chip:hover {
            border-color: #75e0a7;
            background: #dcfae6;
        }

        .route-filter-chip.active {
            border-color: #079455;
            background: #d1fadf;
            color: #05603a;
            box-shadow: 0 0 0 2px rgba(7, 148, 85, .12);
        }

        .route-filter-chip.route-status-waiting {
            border-color: #d1d5db;
            background: #f3f4f6;
            color: #4b5563;
        }

        .route-filter-chip.route-status-waiting:hover,
        .route-filter-chip.route-status-waiting.active {
            border-color: #fdb022;
            background: #fef0c7;
            color: #93370d;
        }

        .route-filter-chip.route-status-correction {
            border-color: #fecdca;
            background: #fef3f2;
            color: #b42318;
        }

        .route-filter-chip.route-status-correction:hover,
        .route-filter-chip.route-status-correction.active {
            border-color: #f04438;
            background: #fee4e2;
            color: #912018;
        }

        .route-filter-chip.route-status-written,
        .route-filter-chip.route-status-corrected,
        .route-filter-chip.route-status-approved,
        .route-filter-chip.route-status-closed {
            border-color: #abefc6;
            background: #ecfdf3;
            color: #027a48;
        }

        .route-action-button {
            border: 1px solid transparent;
            border-radius: 9px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            transition:
                background .15s ease,
                border-color .15s ease,
                color .15s ease;
        }

        .route-action-positive {
            border-color: #abefc6;
            background: #ecfdf3;
            color: #027a48;
        }

        .route-action-positive:hover {
            border-color: #75e0a7;
            background: #dcfae6;
            color: #05603a;
        }

        .route-action-correction {
            border-color: #fecdca;
            background: #fef3f2;
            color: #b42318;
        }

        .route-action-correction:hover {
            border-color: #f04438;
            background: #fee4e2;
            color: #912018;
        }

        .route-action-button:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        /* S020-04E3D4 ROUTE PERFORMANCE OVERVIEW */
        .route-performance-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin: 0 0 14px;
            padding: 12px 14px;
            border: 1px solid #e4e7ec;
            border-radius: 10px;
            background: #f9fafb;
        }

        .route-performance-toolbar strong {
            display: block;
            color: #344054;
            font-size: 13px;
        }

        .route-performance-toolbar span {
            display: block;
            margin-top: 2px;
            color: #667085;
            font-size: 12px;
            font-weight: 500;
        }

        .route-performance-settings-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 8px 12px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            background: #ffffff;
            color: #344054;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .route-performance-settings-link:hover {
            border-color: #98a2b3;
            background: #f2f4f7;
        }

        .route-filter-chip.route-custom-period-trigger {
            border-color: #d0d5dd !important;
            background: #ffffff !important;
            color: #344054 !important;
            box-shadow: none !important;
        }

        .route-filter-chip.route-custom-period-trigger:hover {
            border-color: #98a2b3 !important;
            background: #f9fafb !important;
            color: #344054 !important;
        }

        .route-filter-chip.route-custom-period-trigger.active {
            border-color: #75e0a7 !important;
            background: #ffffff !important;
            color: #344054 !important;
            box-shadow:
                0 0 0 2px rgba(7, 148, 85, .08) !important;
        }

        td.performance-metric {
            min-width: 108px;
            vertical-align: middle;
        }

        .performance-value {
            font-weight: 800;
            color: #344054;
            line-height: 1.1;
        }

        .performance-percent {
            display: block;
            margin-top: 4px;
            color: #667085;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.1;
        }

        td.performance-warning {
            background: #fff7ed;
            box-shadow:
                inset 3px 0 0 #f79009;
        }

        td.performance-warning .performance-value,
        td.performance-warning .performance-percent {
            color: #b54708;
        }

        td.performance-critical {
            background: #fef3f2;
            box-shadow:
                inset 3px 0 0 #d92d20;
        }

        td.performance-critical .performance-value,
        td.performance-critical .performance-percent {
            color: #b42318;
        }

        td.performance-neutral {
            background: transparent;
        }

        .route-filter-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 4px;
        }

        .route-filter-summary-row .route-filter-summary {
            flex: 1 1 auto;
        }

        .route-clear-filters {
            flex: 0 0 auto;
            min-height: 34px;
            padding: 7px 11px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            background: #ffffff;
            color: #344054;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .route-clear-filters:hover {
            border-color: #98a2b3;
            background: #f9fafb;
        }

        @media (max-width: 760px) {
            .route-performance-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        /* S020-04E3D5 ROUTE LIST READABILITY */
        #reportTableBody td {
            text-align: center;
            font-weight: 700;
            vertical-align: middle;
        }

        #reportTableBody td:first-child {
            text-align: left;
        }

        #reportTableBody .route-actions {
            text-align: center;
        }

        .table-wrap table thead th {
            text-align: center;
        }

        .table-wrap table thead th:first-child {
            text-align: left;
        }

        .route-performance-toolbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .route-clear-filters {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .route-clear-filters:disabled {
            border-color: #e4e7ec;
            background: #f2f4f7;
            color: #98a2b3;
            cursor: default;
            opacity: 1;
        }

        @media (max-width: 760px) {
            .route-performance-toolbar-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        /* S020-04E3D6 DRIVER-SAFE PERFORMANCE UI */
        .route-filter-reset-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-top: 6px;
        }

    /* S020-04E3D8 unified route overview row weight */
    .route-overview-unified-weight tbody td,
    .route-overview-unified-weight tbody td * {
        font-weight: 700;
    }
</style>
</head>
<body>
    <main id="loginPage" class="login-page">
        <section class="login-card" aria-labelledby="loginTitle">
            <div class="brand">
                <div class="brand-mark">TMS</div>
                <div>
                    <div class="brand-title">TMS System</div>
                    <div class="brand-subtitle">MVP / Pilot Launch</div>
                </div>
            </div>

            <h1 id="loginTitle">Přihlášení</h1>
            <p class="lead">První použitelná verze TMS pro práci s reálnými provozními daty.</p>

            <form id="loginForm">
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" autocomplete="username" required>
                </div>

                <div class="field">
                    <label for="password">Heslo</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>

                <button id="loginButton" class="primary-button" type="submit">Přihlásit se</button>
            </form>

            <div id="loginMessage" class="message error hidden" role="alert"></div>
        </section>
    </main>

    <main id="appShell" class="app-shell hidden">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">TMS</div>
                <div>
                    <div class="brand-title">TMS System</div>
                    <div class="brand-subtitle">Pilotní provoz</div>
                </div>
            </div>

            <nav class="nav" aria-label="Hlavní navigace">
                <button class="nav-item active" type="button">Trasy</button>
                <button id="carriersNavButton" class="nav-item" type="button">Dopravci a řidiči</button>
<a href="/settings" class="nav-item" data-testid="management-settings-link">Nastavení</a>
                <button class="nav-item" type="button" disabled>Finance – další krok</button>
            </nav>

            <div class="sidebar-footer">
                <div id="userBox" class="user-box">Přihlášený uživatel</div>
                <button id="logoutButton" class="danger-button" type="button">Odhlásit se</button>
            </div>
        </aside>

        <section class="content">
            <header class="topbar">
                <div>
                    <div class="eyebrow">Sprint 020 · MVP / Pilot Launch</div>
                    <h1>Trasy</h1>
                    <p>První obrazovka nad existujícím TMS API.</p>
                </div>

                <div class="status-pill">
                    <span class="status-dot"></span>
                    API připojeno
                </div>
            </header>

            <div class="pilot-banner">
                Cíl pilotu: co nejrychleji zpracovat historická data v TMS a odstranit nutnost vytvářet hlavní měsíční Excel.
                Tato obrazovka je první pracovní vrstva nad již existujícím backendem.
            </div>

            <section class="grid" aria-label="Rychlý přehled">
                <article class="card">
                    <div class="metric-label">Načtené trasy</div>
                    <div id="reportCount" class="metric-value">—</div>
                    <div class="metric-note">Aktuální stránka API</div>
                </article>

                <article class="card">
                    <div class="metric-label">Celkem v API</div>
                    <div id="reportTotal" class="metric-value">—</div>
                    <div class="metric-note">Podle stránkování backendu</div>
                </article>

                <article class="card">
                    <div class="metric-label">Vyžaduje pozornost km</div>
                    <div id="attentionCount" class="metric-value">—</div>
                    <div class="metric-note">Odchylka označená backendem</div>
                </article>
            </section>

            <div class="daily-entry-actions">
                <button id="dailyReportAddButton" class="primary-button" type="button" style="width:auto">
                    + Zapsat trasu
                </button>
            </div>

            <div id="dailyReportSavedMessage" class="daily-entry-message ok hidden"></div>

            <section id="dailyReportCreatePanel" class="daily-entry-panel" hidden>
                <h2>Detail trasy</h2>
                <div class="daily-entry-subtitle">
                    Zde zapisujete údaje konkrétní trasy. U zapsané trasy lze údaje upravit, dokud není odeslána ke schválení.
                </div>

                <div id="dailyReportDriverIdentity" class="daily-entry-driver">
                    Ověřuji profil řidiče…
                </div>

                <form id="dailyReportCreateForm">
                    <div class="daily-entry-grid">
                        <div class="daily-entry-field">
                            <label id="dailyServiceDateLabel" for="dailyServiceDate">Datum</label>
                            <input id="dailyServiceDate" name="service_date" type="date" required>
                        </div>
                    </div>

                    <div id="dailyReportFormConfigurationState" class="daily-entry-config-state">
                        Vyberte datum jízdy. TMS načte formulář platný pro tento den.
                    </div>

                    <div id="dailyReportDynamicFields" class="daily-entry-grid"></div>

                    <div id="dailyReportParcelBalance" class="parcel-balance-state hidden"></div>

                    <div id="dailyReportCreateMessage" class="daily-entry-message error hidden"></div>

                    <div class="daily-entry-footer">
                        <button id="dailyReportCancelButton" class="secondary-button" type="button">
                            Zrušit
                        </button>

                        <button id="dailyReportSaveButton" class="primary-button" type="submit" style="width:auto" disabled>
                            Uložit trasu
                        </button>
                    </div>
                </form>
            </section>
            <section class="section-card">
                <div class="section-head">
                    <div>
                        <h2>Zapsané trasy</h2>
                        <p>Data jsou načítána z GET /api/v1/daily-reports.</p>
                    </div>

                    <button id="refreshButton" class="secondary-button" type="button">Obnovit</button>
                </div>

                <div id="reportError" class="api-error hidden"></div>

                <div id="routeHistoryFilters" class="route-history-filters">

                    <div class="route-filter-row">
                        <div class="route-filter-label">Rok</div>
                        <div id="routeYearButtons" class="route-filter-buttons"></div>
                    </div>

                    <div class="route-filter-row">
                        <div class="route-filter-label">Měsíc</div>
                        <div id="routeMonthButtons" class="route-filter-buttons"></div>
                    </div>

                    <div class="route-filter-row">
                        <div class="route-filter-label">Rychlé období</div>
                        <div id="routeQuickPeriodButtons" class="route-filter-buttons"></div>
                    </div>

                    <div id="routeCustomPeriodPanel" class="route-custom-period hidden">
                        <label>
                            Od
                            <input id="routeCustomFrom" type="date">
                        </label>

                        <label>
                            Do
                            <input id="routeCustomTo" type="date">
                        </label>

                        <button id="routeCustomApply" class="primary-button" type="button">
                            Použít
                        </button>

                        <button id="routeCustomCancel" class="secondary-button" type="button">
                            Zrušit
                        </button>
                    </div>

                    <div class="route-filter-row">
                        <div class="route-filter-label">Stav trasy</div>
                        <div id="routeStatusButtons" class="route-filter-buttons"></div>
                    </div>

                    <div class="route-filter-reset-row">
                        <button
                            id="routeClearFilters"
                            class="route-clear-filters"
                            type="button"
                            title="Zrušit období i stavový filtr a zobrazit všechny trasy"
                        >
                            ✕ Zrušit filtry
                        </button>
                    </div>

                    <div id="routeFilterSummary" class="route-filter-summary"></div>
                </div>
                <div class="table-wrap">
                    <table class="route-overview-unified-weight">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Trasa</th>
                                <th>ID řidiče</th>
                                <th>Naloženo</th>
                                <th>Doručeno na adresu</th>
                                <th>Výdejní místo</th>
                                <th>Odmítnuto zákazníkem</th>
                                <th>Nedoručeno</th>
                                <th>Plán km</th>
                                <th>Skut. km</th>
                                <th>Rozdíl nájezdu</th>
                                <th>Stav</th>
                                <th>Akce</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody"></tbody>
                    </table>

                    <div id="emptyState" class="empty-state hidden">
                        Zatím nejsou zapsané žádné trasy.
                    </div>
                </div>
            </section>
        </section>
    </main>

    <script>
        (() => {
            'use strict';

            const tokenKey = 'tms_mvp_token';

            const loginPage = document.getElementById('loginPage');
            const appShell = document.getElementById('appShell');
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const loginMessage = document.getElementById('loginMessage');
            const userBox = document.getElementById('userBox');
            const logoutButton = document.getElementById('logoutButton');
            const refreshButton = document.getElementById('refreshButton');
            const reportError = document.getElementById('reportError');
            const reportTableBody = document.getElementById('reportTableBody');
            const emptyState = document.getElementById('emptyState');
            const reportCount = document.getElementById('reportCount');
            const reportTotal = document.getElementById('reportTotal');
            const attentionCount = document.getElementById('attentionCount');
            const carriersNavButton = document.getElementById('carriersNavButton');
            const dailyReportAddButton = document.getElementById('dailyReportAddButton');
            const dailyReportCreatePanel = document.getElementById('dailyReportCreatePanel');
            const dailyReportCreateForm = document.getElementById('dailyReportCreateForm');
            const dailyReportCancelButton = document.getElementById('dailyReportCancelButton');
            const dailyReportSaveButton = document.getElementById('dailyReportSaveButton');
            const dailyReportCreateMessage = document.getElementById('dailyReportCreateMessage');
            const dailyReportSavedMessage = document.getElementById('dailyReportSavedMessage');
            const dailyReportDriverIdentity = document.getElementById('dailyReportDriverIdentity');
            const dailyReportDynamicFields = document.getElementById('dailyReportDynamicFields');
            const dailyReportParcelBalance = document.getElementById('dailyReportParcelBalance');
            const dailyReportFormConfigurationState = document.getElementById('dailyReportFormConfigurationState');
            const dailyServiceDate = document.getElementById('dailyServiceDate');
            const dailyServiceDateLabel = document.getElementById('dailyServiceDateLabel');

            let token = sessionStorage.getItem(tokenKey) || '';
            let currentDriver = null;
            let effectiveDailyReportConfiguration = null;
            let dailyReportConfigurationRequestSerial = 0;
            let dailyReportEditItem = null;

            const getPayload = (body) => body && Object.prototype.hasOwnProperty.call(body, 'data')
                ? body.data
                : body;

            const readError = (body, fallback) => {
                if (!body) {
                    return fallback;
                }

                if (body.errors && typeof body.errors === 'object') {
                    const messages = Object.values(body.errors)
                        .flat()
                        .filter(Boolean);

                    if (messages.length > 0) {
                        return messages.join(' ');
                    }
                }

                if (
                    typeof body.message === 'string'
                    && body.message.trim() !== ''
                ) {
                    return body.message;
                }

                return fallback;
            };

            const api = async (path, options = {}) => {
                const headers = {
                    Accept: 'application/json',
                    ...(options.headers || {}),
                };

                if (options.body !== undefined) {
                    headers['Content-Type'] = 'application/json';
                }

                if (token) {
                    headers.Authorization = `Bearer ${token}`;
                }

                // Pilot organization context. The backend still verifies that
                // the authenticated user has an active membership in it.
                headers['X-Organization-ID'] = '1';

                const response = await fetch(path, {
                    ...options,
                    headers,
                });

                let body = null;

                try {
                    body = await response.json();
                } catch {
                    body = null;
                }

                if (!response.ok) {
                    const error = new Error(readError(body, `HTTP ${response.status}`));
                    error.status = response.status;
                    error.body = body;
                    throw error;
                }

                return body;
            };

            const showLogin = (message = '') => {
                appShell.classList.add('hidden');
                loginPage.classList.remove('hidden');

                if (message) {
                    loginMessage.textContent = message;
                    loginMessage.classList.remove('hidden');
                } else {
                    loginMessage.textContent = '';
                    loginMessage.classList.add('hidden');
                }
            };

            const showApp = () => {
                loginPage.classList.add('hidden');
                appShell.classList.remove('hidden');
                loginMessage.classList.add('hidden');
            };

            const clearSession = () => {
                token = '';
                sessionStorage.removeItem(tokenKey);
            };

            const createCell = (value, className = '') => {
                const cell = document.createElement('td');
                cell.textContent = value === null || value === undefined || value === ''
                    ? '—'
                    : String(value);

                if (className) {
                    cell.className = className;
                }

                return cell;
            };

            const routeStatusPresentation = (status) => {
                return {
                    draft: {
                        label: 'Zapsáno řidičem',
                        className: 'route-status-written',
                    },
                    submitted: {
                        label: 'Čeká na schválení',
                        className: 'route-status-waiting',
                    },
                    under_review: {
                        label: 'Čeká na schválení',
                        className: 'route-status-waiting',
                    },
                    correction_requested: {
                        label: 'Vyžaduje opravu',
                        className: 'route-status-correction',
                    },
                    corrected: {
                        label: 'Opraveno řidičem',
                        className: 'route-status-corrected',
                    },
                    approved: {
                        label: 'Schváleno',
                        className: 'route-status-approved',
                    },
                    closed: {
                        label: 'Uzavřeno',
                        className: 'route-status-closed',
                    },
                }[status] || {
                    label: status || '—',
                    className: '',
                };
            };

            const statusBadge = (status) => {
                const cell = document.createElement('td');
                const badge = document.createElement('span');
                const presentation =
                    routeStatusPresentation(status);

                badge.className = [
                    'badge',
                    presentation.className,
                ].filter(Boolean).join(' ');

                badge.textContent = presentation.label;
                cell.appendChild(badge);

                return cell;
            };

            const routeActionCell = (item) => {
                const cell = document.createElement('td');
                cell.className = 'route-actions';

                if (
                    item.status === 'draft'
                    || item.status === 'correction_requested'
                ) {
                    const editButton =
                        document.createElement('button');

                    editButton.type = 'button';
                    editButton.className = [
                        'route-action-button',
                        item.status === 'correction_requested'
                            ? 'route-action-correction'
                            : 'route-action-positive',
                    ].join(' ');

                    editButton.textContent =
                        item.status === 'correction_requested'
                            ? 'Opravit zapsané údaje'
                            : 'Upravit zapsané údaje';

                    editButton.addEventListener(
                        'click',
                        () => openDailyReportEdit(item)
                    );

                    cell.appendChild(editButton);
                    return cell;
                }

                if (item.status === 'corrected') {
                    const resubmitButton =
                        document.createElement('button');

                    resubmitButton.type = 'button';
                    resubmitButton.className =
                        'route-action-button route-action-positive';

                    resubmitButton.textContent =
                        'Odeslat ke schválení';

                    resubmitButton.addEventListener(
                        'click',
                        () => resubmitCorrectedRoute(
                            item,
                            resubmitButton
                        )
                    );

                    cell.appendChild(resubmitButton);
                    return cell;
                }

                const locked = document.createElement('span');
                locked.className = 'muted';
                locked.textContent = '—';
                cell.appendChild(locked);

                return cell;
            };

            const formatWholeKilometres = (value) => {
                if (
                    value === null
                    || value === undefined
                    || value === ''
                ) {
                    return null;
                }

                const numericValue = Number(value);

                return Number.isFinite(numericValue)
                    ? String(Math.round(numericValue))
                    : value;
            };

            const routeKilometreDifferencePresentation = (item, limit) => {
                const plannedKm = Number(item?.planned_km);
                const differenceKm = Number(
                    item?.calculated?.difference_km
                );

                if (
                    !Number.isFinite(plannedKm)
                    || plannedKm <= 0
                    || !Number.isFinite(differenceKm)
                ) {
                    return {
                        text: 'Nelze vypočítat',
                        className:
                            'kilometre-difference-alert',
                    };
                }

                const rawPercentage =
                    differenceKm / plannedKm * 100;

                const percentage =
                    Math.abs(rawPercentage) < 0.005
                        ? 0
                        : rawPercentage;

                const sign =
                    percentage >= 0
                        ? '+'
                        : '-';

                const text =
                    `${sign}${Math.abs(percentage).toFixed(2)}%`;

                return {
                    text,
                    className:
                        performanceSeverityClass(
                            Math.abs(percentage),
                            limit,
                            'max'
                        ),
                };
            };

            const formatCzechDate = (value) => {
                if (
                    typeof value !== 'string'
                    || !/^\d{4}-\d{2}-\d{2}$/.test(value)
                ) {
                    return value || '—';
                }

                const [
                    year,
                    month,
                    day,
                ] = value.split('-');

                return `${day}.${month}.${year}`;
            };
            let routePerformancePolicyConfiguration = null;

            const performancePolicySystemDefaults = {
                redirected_max_percent: '15.00',
                kilometre_deviation_max_percent: '10.00',
                delivered_address_min_percent: null,
                rejected_max_percent: null,
                not_delivered_max_percent: null,
            };

            const normalizeRoutePerformanceKey = (
                routeNumber
            ) => String(
                routeNumber ?? ''
            )
                .trim()
                .toLocaleLowerCase('cs-CZ');

            const loadPerformancePolicyConfiguration =
                async () => {
                    const body = await api(
                        '/api/v1/daily-reports/performance-policies'
                    );

                    routePerformancePolicyConfiguration =
                        getPayload(body) || {};

                    return routePerformancePolicyConfiguration;
                };

            const performanceThreshold = (
                source,
                key
            ) => {
                if (
                    !source
                    || !Object.prototype.hasOwnProperty.call(
                        source,
                        key
                    )
                    || source[key] === null
                    || source[key] === ''
                ) {
                    return null;
                }

                const numeric = Number(
                    source[key]
                );

                return Number.isFinite(numeric)
                    ? numeric
                    : null;
            };

            const performancePolicyForRoute = (
                routeNumber
            ) => {
                const configuration =
                    routePerformancePolicyConfiguration
                    || {};

                const organizationDefaults = {
                    ...performancePolicySystemDefaults,
                    ...(
                        configuration
                            .effective_organization_defaults
                        || {}
                    ),
                };

                const normalized =
                    normalizeRoutePerformanceKey(
                        routeNumber
                    );

                const routeOverride = (
                    configuration.route_overrides
                    || []
                ).find(
                    (candidate) =>
                        String(
                            candidate
                                ?.route_number_normalized
                            ?? ''
                        )
                            .toLocaleLowerCase(
                                'cs-CZ'
                            )
                        === normalized
                );

                const routeThresholds =
                    routeOverride?.thresholds
                    || {};

                const thresholds = {};

                Object.keys(
                    performancePolicySystemDefaults
                ).forEach(
                    (key) => {
                        const routeValue =
                            performanceThreshold(
                                routeThresholds,
                                key
                            );

                        if (routeValue !== null) {
                            thresholds[key] =
                                routeValue;
                            return;
                        }

                        thresholds[key] =
                            performanceThreshold(
                                organizationDefaults,
                                key
                            );
                    }
                );

                return {
                    thresholds,
                    routeOverride,
                };
            };

            const performancePercent = (
                numerator,
                loaded
            ) => {
                const part = Number(
                    numerator
                );

                const total = Number(
                    loaded
                );

                if (
                    !Number.isFinite(part)
                    || !Number.isFinite(total)
                    || total <= 0
                ) {
                    return null;
                }

                return (
                    Math.round(
                        (part / total)
                        * 1000
                    )
                    / 10
                );
            };

            const performanceSeverityClass = (
                value,
                limit,
                comparison
            ) => {
                const numericValue =
                    Number(value);

                const numericLimit =
                    Number(limit);

                if (
                    value === null
                    || value === undefined
                    || limit === null
                    || limit === undefined
                    || !Number.isFinite(
                        numericValue
                    )
                    || !Number.isFinite(
                        numericLimit
                    )
                ) {
                    return 'performance-neutral';
                }

                const violates =
                    comparison === 'min'
                        ? numericValue
                            < numericLimit
                        : numericValue
                            > numericLimit;

                if (!violates) {
                    return 'performance-neutral';
                }

                const distance =
                    comparison === 'min'
                        ? numericLimit
                            - numericValue
                        : numericValue
                            - numericLimit;

                return distance >= 5
                    ? 'performance-critical'
                    : 'performance-warning';
            };

            const createPerformanceCell = (
                count,
                percent,
                limit,
                comparison
            ) => {
                const cell =
                    document.createElement(
                        'td'
                    );

                cell.classList.add(
                    'performance-metric'
                );

                cell.classList.add(
                    performanceSeverityClass(
                        percent,
                        limit,
                        comparison
                    )
                );

                const value =
                    document.createElement(
                        'div'
                    );

                value.className =
                    'performance-value';

                value.textContent =
                    count === null
                    || count === undefined
                        ? '—'
                        : String(count);

                cell.appendChild(value);

                if (
                    percent !== null
                    && percent !== undefined
                ) {
                    const percentage =
                        document.createElement(
                            'span'
                        );

                    percentage.className =
                        'performance-percent';

                    percentage.textContent =
                        `${percent.toLocaleString(
                            'cs-CZ',
                            {
                                minimumFractionDigits: 1,
                                maximumFractionDigits: 1,
                            }
                        )} %`;

                    cell.appendChild(
                        percentage
                    );
                }

                if (
                    limit !== null
                    && limit !== undefined
                ) {
                    const relation =
                        comparison === 'min'
                            ? 'min.'
                            : 'max.';

                    cell.title =
                        `Nastavený limit ${relation} ${Number(
                            limit
                        ).toLocaleString(
                            'cs-CZ',
                            {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 2,
                            }
                        )} %`;
                }

                return cell;
            };
            const renderReports = (items, pagination) => {
                reportTableBody.replaceChildren();

                const safeItems = Array.isArray(items) ? items : [];
                const attention = safeItems.filter(
                    (item) => item?.calculated?.requires_kilometre_attention === true
                ).length;

                reportCount.textContent = String(safeItems.length);
                reportTotal.textContent = String(pagination?.total ?? safeItems.length);
                attentionCount.textContent = String(attention);

                emptyState.classList.toggle('hidden', safeItems.length !== 0);

                safeItems.forEach((item) => {
                    const row = document.createElement('tr');
                    const performancePolicy =
                        performancePolicyForRoute(
                            item.route_number
                        );

                    const kilometreDifference =
                        routeKilometreDifferencePresentation(
                            item,
                            performancePolicy
                                .thresholds
                                .kilometre_deviation_max_percent
                        );

                    row.appendChild(createCell(formatCzechDate(item.service_date)));
                    row.appendChild(createCell(item.route_number));
                    row.appendChild(createCell(
                        item.performed_by_driver_external_id
                            || item.performed_by_driver_name
                            || item.performed_by_driver_id
                    ));
                    const loadedParcels =
                        item.loaded_parcels;

                    const deliveredAddressPercent =
                        performancePercent(
                            item.delivered_parcels,
                            loadedParcels
                        );

                    const redirectedPercent =
                        performancePercent(
                            item.redirected_parcels,
                            loadedParcels
                        );

                    const rejectedPercent =
                        performancePercent(
                            item.undelivered_parcels,
                            loadedParcels
                        );

                    const notDeliveredParcels =
                        item?.calculated
                            ?.not_delivered_parcels;

                    const notDeliveredPercent =
                        performancePercent(
                            notDeliveredParcels,
                            loadedParcels
                        );

                    row.appendChild(
                        createCell(
                            loadedParcels
                        )
                    );

                    row.appendChild(
                        createPerformanceCell(
                            item.delivered_parcels,
                            deliveredAddressPercent,
                            performancePolicy
                                .thresholds
                                .delivered_address_min_percent,
                            'min'
                        )
                    );

                    row.appendChild(
                        createPerformanceCell(
                            item.redirected_parcels,
                            redirectedPercent,
                            performancePolicy
                                .thresholds
                                .redirected_max_percent,
                            'max'
                        )
                    );

                    row.appendChild(
                        createPerformanceCell(
                            item.undelivered_parcels,
                            rejectedPercent,
                            performancePolicy
                                .thresholds
                                .rejected_max_percent,
                            'max'
                        )
                    );

                    row.appendChild(
                        createPerformanceCell(
                            notDeliveredParcels,
                            notDeliveredPercent,
                            performancePolicy
                                .thresholds
                                .not_delivered_max_percent,
                            'max'
                        )
                    );
row.appendChild(createCell(formatWholeKilometres(item.planned_km)));
                    row.appendChild(createCell(formatWholeKilometres(item.actual_km)));
                    row.appendChild(createCell(
                        kilometreDifference.text,
                        kilometreDifference.className
                    ));
                    row.appendChild(statusBadge(item.status));
                    row.appendChild(routeActionCell(item));

                    reportTableBody.appendChild(row);
                });
            };

            const routeHistoryStatusGroups = [
                {
                    key: 'written',
                    label: 'Zapsáno řidičem',
                    statuses: ['draft'],
                    className: 'route-status-written',
                },
                {
                    key: 'waiting',
                    label: 'Čeká na schválení',
                    statuses: [
                        'submitted',
                        'under_review',
                    ],
                    className: 'route-status-waiting',
                },
                {
                    key: 'correction',
                    label: 'Vyžaduje opravu',
                    statuses: [
                        'correction_requested',
                    ],
                    className: 'route-status-correction',
                },
                {
                    key: 'corrected',
                    label: 'Opraveno řidičem',
                    statuses: ['corrected'],
                    className: 'route-status-corrected',
                },
                {
                    key: 'approved',
                    label: 'Schváleno',
                    statuses: ['approved'],
                    className: 'route-status-approved',
                },
                {
                    key: 'closed',
                    label: 'Uzavřeno',
                    statuses: ['closed'],
                    className: 'route-status-closed',
                },
            ];

            const routeMonthNames = [
                '',
                'Leden',
                'Únor',
                'Březen',
                'Duben',
                'Květen',
                'Červen',
                'Červenec',
                'Srpen',
                'Září',
                'Říjen',
                'Listopad',
                'Prosinec',
            ];

            const routeFilterState = {
                initialized: false,
                selectedYear: null,
                monthKey: null,
                periodKey: null,
                from: null,
                to: null,
                statusGroup: null,
            };

            const routeFiltersAreActive = () =>
                routeFilterState.monthKey !== null
                || routeFilterState.periodKey !== null
                || routeFilterState.from !== null
                || routeFilterState.to !== null
                || routeFilterState.statusGroup !== null
                || routeFilterState.selectedYear !== null;

            const clearRouteFilters = async () => {
                routeFilterState.initialized = true;
                routeFilterState.selectedYear = null;
                routeFilterState.monthKey = null;
                routeFilterState.periodKey = null;
                routeFilterState.from = null;
                routeFilterState.to = null;
                routeFilterState.statusGroup = null;

                const customPanel =
                    document.getElementById(
                        'routeCustomPeriodPanel'
                    );

                customPanel?.classList.add(
                    'hidden'
                );

                reportError.classList.add(
                    'hidden'
                );

                await loadReports();
            };
            const routeMonthRange = (
                year,
                month
            ) => {
                const paddedMonth =
                    String(month).padStart(2, '0');

                const lastDay =
                    new Date(
                        year,
                        month,
                        0
                    ).getDate();

                return {
                    from:
                        `${year}-${paddedMonth}-01`,
                    to:
                        `${year}-${paddedMonth}-${String(lastDay).padStart(2, '0')}`,
                };
            };

            const routeMonthKeyFromDate = (
                date
            ) => {
                if (
                    typeof date !== 'string'
                    || date.length < 7
                ) {
                    return null;
                }

                return date.slice(0, 7);
            };

            const routeFilterButton = (
                label,
                active,
                onClick,
                className = '',
                title = ''
            ) => {
                const button =
                    document.createElement(
                        'button'
                    );

                button.type = 'button';
                button.className = [
                    'route-filter-chip',
                    active ? 'active' : '',
                    className,
                ].filter(Boolean).join(' ');

                button.textContent = label;
                button.title = title;
                button.addEventListener(
                    'click',
                    onClick
                );

                return button;
            };

            const routeStatusGroupCount = (
                statusCounts,
                group
            ) => {
                return group.statuses.reduce(
                    (
                        total,
                        status
                    ) => {
                        return total
                            + Number(
                                statusCounts?.[status]
                                || 0
                            );
                    },
                    0
                );
            };

            const routeAllStatusCount = (
                statusCounts
            ) => {
                return Object.values(
                    statusCounts || {}
                ).reduce(
                    (
                        total,
                        count
                    ) => total + Number(count || 0),
                    0
                );
            };

            const setRouteMonthFilter = (
                month
            ) => {
                const range =
                    routeMonthRange(
                        Number(month.year),
                        Number(month.month)
                    );

                routeFilterState.selectedYear =
                    Number(month.year);

                routeFilterState.monthKey =
                    String(month.key);

                routeFilterState.periodKey =
                    `month:${month.key}`;

                routeFilterState.from =
                    range.from;

                routeFilterState.to =
                    range.to;

                routeFilterState.statusGroup =
                    null;
            };

            const setRoutePeriodFilter = (
                key,
                period
            ) => {
                routeFilterState.periodKey =
                    key;

                routeFilterState.monthKey =
                    null;

                routeFilterState.from =
                    period.from;

                routeFilterState.to =
                    period.to;

                routeFilterState.statusGroup =
                    null;

                if (
                    typeof period.from === 'string'
                    && period.from.length >= 4
                ) {
                    routeFilterState.selectedYear =
                        Number(
                            period.from.slice(0, 4)
                        );
                }
            };

            const selectRouteMonthFromDate = (
                date
            ) => {
                const key =
                    routeMonthKeyFromDate(
                        date
                    );

                if (!key) {
                    return;
                }

                const [
                    yearText,
                    monthText,
                ] = key.split('-');

                const month = {
                    key,
                    year: Number(yearText),
                    month: Number(monthText),
                };

                setRouteMonthFilter(month);
                routeFilterState.initialized = true;
            };

            const initializeRouteHistoryPeriod = (
                navigation
            ) => {
                if (routeFilterState.initialized) {
                    return false;
                }

                routeFilterState.initialized = true;

                const months =
                    Array.isArray(
                        navigation?.months
                    )
                        ? navigation.months
                        : [];

                if (months.length === 0) {
                    return false;
                }

                const currentMonthKey =
                    routeMonthKeyFromDate(
                        navigation?.today
                    );

                const selectedMonth =
                    months.find(
                        (month) =>
                            String(month.key)
                            === currentMonthKey
                    )
                    || months[0];

                setRouteMonthFilter(
                    selectedMonth
                );

                return true;
            };

            const routeFilterPeriodLabel = () => {
                if (
                    routeFilterState.periodKey
                    === 'custom'
                ) {
                    return (
                        `${formatCzechDate(routeFilterState.from)} až ${formatCzechDate(routeFilterState.to)}`
                    );
                }

                if (
                    routeFilterState.periodKey
                    && routeFilterState.periodKey
                        .startsWith('month:')
                ) {
                    const [
                        yearText,
                        monthText,
                    ] = String(
                        routeFilterState.monthKey
                        || ''
                    ).split('-');

                    const month =
                        Number(monthText);

                    return (
                        `${routeMonthNames[month] || monthText} ${yearText}`
                    );
                }

                return '';
            };

            const renderRouteHistoryFilters = (
                navigation,
                pagination
            ) => {
                const yearButtons =
                    document.getElementById(
                        'routeYearButtons'
                    );

                const monthButtons =
                    document.getElementById(
                        'routeMonthButtons'
                    );

                const quickButtons =
                    document.getElementById(
                        'routeQuickPeriodButtons'
                    );

                const statusButtons =
                    document.getElementById(
                        'routeStatusButtons'
                    );

                const customPanel =
                    document.getElementById(
                        'routeCustomPeriodPanel'
                    );

                const customFrom =
                    document.getElementById(
                        'routeCustomFrom'
                    );

                const customTo =
                    document.getElementById(
                        'routeCustomTo'
                    );

                const customApply =
                    document.getElementById(
                        'routeCustomApply'
                    );

                const customCancel =
                    document.getElementById(
                        'routeCustomCancel'
                    );

                yearButtons.replaceChildren();
                monthButtons.replaceChildren();
                quickButtons.replaceChildren();
                statusButtons.replaceChildren();

                const years =
                    Array.isArray(navigation?.years)
                        ? navigation.years
                        : [];

                const months =
                    Array.isArray(navigation?.months)
                        ? navigation.months
                        : [];

                if (
                    routeFilterState.selectedYear === null
                    && years.length > 0
                ) {
                    routeFilterState.selectedYear =
                        Number(years[0].year);
                }

                years.forEach((year) => {
                    const numericYear =
                        Number(year.year);

                    yearButtons.appendChild(
                        routeFilterButton(
                            String(numericYear),
                            routeFilterState.selectedYear
                                === numericYear,
                            async () => {
                                const yearMonths =
                                    months.filter(
                                        (month) =>
                                            Number(month.year)
                                            === numericYear
                                    );

                                if (
                                    yearMonths.length
                                    === 0
                                ) {
                                    return;
                                }

                                setRouteMonthFilter(
                                    yearMonths[0]
                                );

                                await loadReports();
                            },
                            '',
                            `${Number(year.total || 0)} tras`
                        )
                    );
                });

                months
                    .filter(
                        (month) =>
                            Number(month.year)
                            === routeFilterState.selectedYear
                    )
                    .forEach((month) => {
                        const monthNumber =
                            Number(month.month);

                        monthButtons.appendChild(
                            routeFilterButton(
                                `${routeMonthNames[monthNumber]} ${month.year}`,
                                routeFilterState.monthKey
                                    === String(month.key),
                                async () => {
                                    setRouteMonthFilter(
                                        month
                                    );

                                    await loadReports();
                                },
                                '',
                                `${Number(month.total || 0)} tras`
                            )
                        );
                    });

                const quickPeriods =
                    navigation?.quick_periods
                    && typeof navigation.quick_periods
                        === 'object'
                        ? navigation.quick_periods
                        : {};

                Object.entries(
                    quickPeriods
                ).forEach(
                    ([
                        key,
                        period,
                    ]) => {
                        if (
                            Number(period?.total || 0)
                            <= 0
                        ) {
                            return;
                        }

                        quickButtons.appendChild(
                            routeFilterButton(
                                String(
                                    period.label
                                    || key
                                ),
                                routeFilterState.periodKey
                                    === key,
                                async () => {
                                    setRoutePeriodFilter(
                                        key,
                                        period
                                    );

                                    await loadReports();
                                },
                                '',
                                `${Number(period.total || 0)} tras`
                            )
                        );
                    }
                );

                quickButtons.appendChild(
                    routeFilterButton(
                        '📅 Vlastní období',
                        routeFilterState.periodKey
                            === 'custom',
                        () => {
                            customFrom.value =
                                routeFilterState.from
                                || '';

                            customTo.value =
                                routeFilterState.to
                                || '';

                            customPanel.classList
                                .remove('hidden');

                            customFrom.focus();
                        },
                        'route-custom-period-trigger',
                        'Zadat vlastní datum Od–Do'
                    )
                );

                customApply.onclick =
                    async () => {
                        const from =
                            customFrom.value;

                        const to =
                            customTo.value;

                        if (
                            !from
                            || !to
                            || from > to
                        ) {
                            reportError.textContent =
                                'Vlastní období musí mít platné datum Od a Do.';

                            reportError.classList
                                .remove('hidden');

                            return;
                        }

                        routeFilterState.periodKey =
                            'custom';

                        routeFilterState.monthKey =
                            null;

                        routeFilterState.from =
                            from;

                        routeFilterState.to =
                            to;

                        routeFilterState.statusGroup =
                            null;

                        routeFilterState.selectedYear =
                            Number(
                                from.slice(0, 4)
                            );

                        customPanel.classList
                            .add('hidden');

                        await loadReports();
                    };

                customCancel.onclick =
                    () => {
                        customPanel.classList
                            .add('hidden');
                    };

                const statusCounts =
                    navigation?.status_counts
                    && typeof navigation.status_counts
                        === 'object'
                        ? navigation.status_counts
                        : {};

                const allCount =
                    routeAllStatusCount(
                        statusCounts
                    );

                statusButtons.appendChild(
                    routeFilterButton(
                        `Vše ${allCount}`,
                        routeFilterState.statusGroup
                            === null,
                        async () => {
                            routeFilterState.statusGroup =
                                null;

                            await loadReports();
                        }
                    )
                );

                routeHistoryStatusGroups
                    .forEach((group) => {
                        const count =
                            routeStatusGroupCount(
                                statusCounts,
                                group
                            );

                        if (count <= 0) {
                            return;
                        }

                        statusButtons.appendChild(
                            routeFilterButton(
                                `${group.label} ${count}`,
                                routeFilterState.statusGroup
                                    === group.key,
                                async () => {
                                    routeFilterState.statusGroup =
                                        group.key;

                                    await loadReports();
                                },
                                group.className
                            )
                        );
                    });

                const selectedStatus =
                    routeHistoryStatusGroups.find(
                        (group) =>
                            group.key
                            === routeFilterState.statusGroup
                    );

                const summaryParts = [];

                const periodLabel =
                    routeFilterPeriodLabel();

                if (periodLabel) {
                    summaryParts.push(
                        `Období: ${periodLabel}`
                    );
                } else {
                    summaryParts.push(
                        'Období: Všechny trasy'
                    );
                }

                summaryParts.push(
                    `Stav: ${selectedStatus?.label || 'Vše'}`
                );

                summaryParts.push(
                    `Nalezeno: ${Number(pagination?.total || 0)}`
                );

                document.getElementById(
                    'routeFilterSummary'
                ).textContent =
                    summaryParts.join(' · ');

                const clearFiltersButton =
                    document.getElementById(
                        'routeClearFilters'
                    );

                const hasActiveRouteFilters =
                    routeFiltersAreActive();

                clearFiltersButton.disabled =
                    !hasActiveRouteFilters;

                clearFiltersButton.title =
                    hasActiveRouteFilters
                        ? 'Zrušit období i stavový filtr a zobrazit všechny trasy'
                        : 'Žádný filtr není aktivní';

                clearFiltersButton.onclick =
                    hasActiveRouteFilters
                        ? clearRouteFilters
                        : null;
            };

            const buildRouteHistoryQuery = () => {
                const params =
                    new URLSearchParams();

                params.set(
                    'per_page',
                    '100'
                );

                params.set(
                    'sort_by',
                    'service_date'
                );

                params.set(
                    'sort_dir',
                    'desc'
                );

                if (routeFilterState.from) {
                    params.set(
                        'service_date_from',
                        routeFilterState.from
                    );
                }

                if (routeFilterState.to) {
                    params.set(
                        'service_date_to',
                        routeFilterState.to
                    );
                }

                if (
                    routeFilterState.statusGroup
                ) {
                    params.set(
                        'status_group',
                        routeFilterState.statusGroup
                    );
                }

                return params.toString();
            };
            const routeItemsFromPayload = (
                payload
            ) => {
                const items =
                    payload?.items?.data
                    ?? payload?.items
                    ?? [];

                return Array.isArray(items)
                    ? items
                    : [];
            };

            const loadCompleteRouteHistory = async (
                firstPayload,
                query
            ) => {
                const items = [
                    ...routeItemsFromPayload(
                        firstPayload
                    ),
                ];

                const firstPagination =
                    firstPayload?.pagination
                    || {};

                const lastPage = Math.max(
                    1,
                    Number(
                        firstPagination.last_page
                        || 1
                    )
                );

                for (
                    let page = 2;
                    page <= lastPage;
                    page += 1
                ) {
                    const pageParams =
                        new URLSearchParams(
                            query
                        );

                    pageParams.set(
                        'page',
                        String(page)
                    );

                    const pageBody =
                        await api(
                            `/api/v1/daily-reports?${pageParams.toString()}`
                        );

                    const pagePayload =
                        getPayload(pageBody)
                        || {};

                    items.push(
                        ...routeItemsFromPayload(
                            pagePayload
                        )
                    );
                }

                return {
                    items,
                    pagination: {
                        ...firstPagination,
                        current_page: 1,
                        last_page: 1,
                        per_page: items.length,
                        total: Number(
                            firstPagination.total
                            ?? items.length
                        ),
                    },
                };
            };
            const loadReports = async () => {
                reportError.classList.add('hidden');
                refreshButton.disabled = true;

                try {
                    await loadPerformancePolicyConfiguration();

                    const query =
                        buildRouteHistoryQuery();

                    const body = await api(
                        `/api/v1/daily-reports?${query}`
                    );

                    const payload =
                        getPayload(body) || {};

                    const navigation =
                        payload.navigation || {};

                    if (
                        initializeRouteHistoryPeriod(
                            navigation
                        )
                    ) {
                        await loadReports();
                        return;
                    }

                    const completeHistory =
                        await loadCompleteRouteHistory(
                            payload,
                            query
                        );

                    renderReports(
                        completeHistory.items,
                        completeHistory.pagination
                    );

                    renderRouteHistoryFilters(
                        navigation,
                        completeHistory.pagination
                    );
                } catch (error) {
                    if (error.status === 401) {
                        clearSession();
                        showLogin(
                            'Přihlášení vypršelo. Přihlaste se znovu.'
                        );
                        return;
                    }

                    reportError.textContent =
                        `Trasy se nepodařilo načíst: ${error.message}`;

                    reportError.classList.remove(
                        'hidden'
                    );

                    renderReports([], {});
                } finally {
                    refreshButton.disabled = false;
                }
            };

            const driverCandidates = (body) => {
                const payload = getPayload(body);

                if (Array.isArray(payload)) {
                    return payload;
                }

                if (Array.isArray(payload?.data)) {
                    return payload.data;
                }

                if (Array.isArray(payload?.items)) {
                    return payload.items;
                }

                if (Array.isArray(payload?.items?.data)) {
                    return payload.items.data;
                }

                return [];
            };

            const loadCurrentDriver = async () => {
                const body = await api('/api/v1/drivers');
                const drivers = driverCandidates(body);

                currentDriver = drivers.length > 0
                    ? drivers[0]
                    : null;

                if (!currentDriver) {
                    dailyReportDriverIdentity.textContent =
                        'Přihlášený účet nemá řidičský profil.';
                    dailyReportAddButton.disabled = true;
                    return;
                }

                const driverName = [
                    currentDriver.first_name,
                    currentDriver.last_name,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .trim();

                dailyReportDriverIdentity.textContent =
                    `Řidič: ${driverName || currentDriver.email || `ID ${currentDriver.id}`}`;

                dailyReportAddButton.disabled = false;
            };

            const loadIdentity = async () => {
                const body = await api('/api/v1/auth/me');
                const user = getPayload(body) || {};

                const identity = user.email
                    || user.name
                    || user.public_id
                    || 'Přihlášený uživatel';

                userBox.textContent = identity;

                await loadCurrentDriver();
            };

            const clearDailyReportConfiguration = () => {
                effectiveDailyReportConfiguration = null;
                dailyReportDynamicFields.replaceChildren();
                dailyReportSaveButton.disabled = true;
                dailyServiceDateLabel.textContent = 'Datum';

                dailyReportFormConfigurationState.classList.remove(
                    'ok',
                    'error'
                );
                dailyReportFormConfigurationState.textContent =
                    'Vyberte datum jízdy. TMS načte formulář platný pro tento den.';
            };

            const setConfigurationState = (
                message,
                state = ''
            ) => {
                dailyReportFormConfigurationState.textContent = message;
                dailyReportFormConfigurationState.classList.remove(
                    'ok',
                    'error'
                );

                if (state) {
                    dailyReportFormConfigurationState.classList.add(state);
                }
            };

            const resetDailyReportForm = (preserveDate = false) => {
                const serviceDate = dailyServiceDate.value;

                dailyReportCreateForm.reset();
                clearDailyReportConfiguration();

                if (preserveDate && serviceDate) {
                    dailyServiceDate.value = serviceDate;
                }

                dailyServiceDate.disabled = false;
                dailyReportEditItem = null;
                dailyReportSaveButton.textContent =
                    'Uložit trasu';

                dailyReportCreateMessage.textContent = '';
                dailyReportCreateMessage.classList.add('hidden');
            };

            const closeDailyReportForm = () => {
                dailyReportCreatePanel.hidden = true;
                dailyReportAddButton.hidden = false;
                resetDailyReportForm(false);
            };

            const customFieldInputName = (key) =>
                `custom_field_values[${key}]`;

            const createRequiredSuffix = () => {
                const marker = document.createElement('span');
                marker.className = 'daily-entry-required';
                marker.textContent = ' *';

                return marker;
            };

            const createCustomBadge = () => {
                const badge = document.createElement('span');
                badge.className = 'daily-entry-custom-badge';
                badge.textContent = 'vlastní';

                return badge;
            };

            const createBooleanSelect = (field, name) => {
                const select = document.createElement('select');
                select.name = name;
                select.required = field.required === true;

                const empty = document.createElement('option');
                empty.value = '';
                empty.textContent = field.required
                    ? 'Vyberte…'
                    : 'Nevyplněno';

                const yes = document.createElement('option');
                yes.value = '1';
                yes.textContent = 'Ano';

                const no = document.createElement('option');
                no.value = '0';
                no.textContent = 'Ne';

                select.append(empty, yes, no);

                return select;
            };

            const canonicalControl = (field) => {
                const key = field.key;

                if (key === 'operational_notes') {
                    const textarea = document.createElement('textarea');
                    textarea.name = key;
                    textarea.required = field.required === true;

                    return textarea;
                }

                const input = document.createElement('input');
                input.name = key;
                input.required = field.required === true;

                if (key === 'route_number') {
                    input.type = 'text';
                    input.maxLength = 100;

                    return input;
                }

                if (
                    key === 'departure_time'
                    || key === 'arrival_time'
                ) {
                    input.type = 'time';

                    return input;
                }

                if (
                    key === 'loaded_parcels'
                    || key === 'delivered_parcels'
                    || key === 'redirected_parcels'
                    || key === 'undelivered_parcels'
                ) {
                    input.type = 'number';
                    input.min = '0';
                    input.step = '1';

                    return input;
                }

                if (
                    key === 'actual_km'
                    || key === 'planned_km'
                ) {
                    input.type = 'number';
                    input.min = '0';
                    input.step = '1';
                    input.inputMode = 'numeric';

                    return input;
                }

                if (key === 'surcharge_amount') {
                    input.type = 'number';
                    input.min = '0';
                    input.step = '0.01';

                    return input;
                }

                input.type = 'text';

                return input;
            };

            const customControl = (field) => {
                const name = customFieldInputName(field.key);

                if (field.type === 'boolean') {
                    return createBooleanSelect(field, name);
                }

                const input = document.createElement('input');
                input.name = name;
                input.dataset.customKey = field.key;
                input.dataset.customType = field.type;
                input.required = field.required === true;

                if (field.type === 'time') {
                    input.type = 'time';
                } else if (field.type === 'number') {
                    input.type = 'number';
                    input.step = 'any';
                } else if (field.type === 'money') {
                    input.type = 'number';
                    input.step = '0.01';
                } else {
                    input.type = 'text';
                }

                return input;
            };

            const dailyReportFieldLabel = (field) => {
                if (
                    field?.key
                    === 'undelivered_parcels'
                ) {
                    return 'Odmítnuto zákazníkem';
                }

                return String(
                    field?.label
                    || field?.key
                    || ''
                );
            };

            const renderDailyReportConfiguration = (configuration) => {
                dailyReportDynamicFields.replaceChildren();

                const fields = Array.isArray(configuration?.fields)
                    ? [...configuration.fields]
                    : [];

                fields.sort(
                    (left, right) =>
                        Number(left?.order || 0)
                        - Number(right?.order || 0)
                );

                const serviceDateField = fields.find(
                    (field) => field?.key === 'service_date'
                );

                if (serviceDateField?.label) {
                    dailyServiceDateLabel.textContent =
                        serviceDateField.label;
                }

                fields
                    .filter(
                        (field) =>
                            field?.visible === true
                            && field?.key !== 'service_date'
                    )
                    .forEach((field) => {
                        const wrapper = document.createElement('div');
                        wrapper.className =
                            field.key === 'operational_notes'
                                ? 'daily-entry-field full'
                                : 'daily-entry-field';

                        const label = document.createElement('label');
                        label.textContent =
                            dailyReportFieldLabel(field);

                        if (field.required === true) {
                            label.appendChild(createRequiredSuffix());
                        }

                        if (field.custom === true) {
                            label.appendChild(createCustomBadge());
                        }

                        const control = field.custom === true
                            ? customControl(field)
                            : canonicalControl(field);

                        control.dataset.fieldKey = field.key;

                        if (field.custom === true) {
                            control.dataset.customKey = field.key;
                            control.dataset.customType = field.type;
                        }

                        const controlId =
                            `dailyField_${String(field.key)
                                .replace(/[^a-zA-Z0-9_-]/g, '_')}`;

                        control.id = controlId;
                        label.htmlFor = controlId;

                        wrapper.append(label, control);
                        dailyReportDynamicFields.appendChild(wrapper);
                    });

                effectiveDailyReportConfiguration = configuration;
                dailyReportSaveButton.disabled = false;

                const validUntil = configuration.valid_until
                    ? ` až ${configuration.valid_until}`
                    : ' bez omezení';

                setConfigurationState(
                    `Načtena verze ${configuration.version} · platnost od ${configuration.valid_from}${validUntil}.`,
                    'ok'
                );
            };

            const loadEffectiveDailyReportConfiguration = async (
                serviceDate
            ) => {
                const requestSerial =
                    ++dailyReportConfigurationRequestSerial;

                effectiveDailyReportConfiguration = null;
                dailyReportDynamicFields.replaceChildren();
                dailyReportSaveButton.disabled = true;

                if (!serviceDate) {
                    clearDailyReportConfiguration();
                    return;
                }

                setConfigurationState(
                    'Načítám formulář platný pro zadané datum…'
                );

                try {
                    const body = await api(
                        `/api/v1/daily-report-form/effective?service_date=${encodeURIComponent(serviceDate)}`
                    );

                    if (
                        requestSerial
                        !== dailyReportConfigurationRequestSerial
                    ) {
                        return;
                    }

                    const payload = getPayload(body) || {};
                    const configuration =
                        payload.configuration ?? null;

                    if (!configuration) {
                        clearDailyReportConfiguration();
                        setConfigurationState(
                            'Pro zadané datum není nastavena platná konfigurace denního výkazu.',
                            'error'
                        );
                        return;
                    }

                    renderDailyReportConfiguration(
                        configuration
                    );
                } catch (error) {
                    if (
                        requestSerial
                        !== dailyReportConfigurationRequestSerial
                    ) {
                        return;
                    }

                    clearDailyReportConfiguration();

                    if (error.status === 401) {
                        clearSession();
                        showLogin(
                            'Přihlášení vypršelo. Přihlaste se znovu.'
                        );
                        return;
                    }

                    setConfigurationState(
                        `Formulář se nepodařilo načíst: ${error.message}`,
                        'error'
                    );
                }
            };

            const openDailyReportForm = async () => {
                resetDailyReportForm(false);
                dailyReportSavedMessage.classList.add('hidden');
                dailyReportCreateMessage.classList.add('hidden');
                dailyReportCreatePanel.hidden = false;
                dailyReportAddButton.hidden = true;

                if (dailyServiceDate.value) {
                    await loadEffectiveDailyReportConfiguration(
                        dailyServiceDate.value
                    );
                } else {
                    clearDailyReportConfiguration();
                }

                dailyServiceDate.focus();
            };

            const normalizeRouteEditValue = (field, value) => {
                if (
                    value === null
                    || value === undefined
                ) {
                    return '';
                }

                if (
                    (
                        field?.key === 'departure_time'
                        || field?.key === 'arrival_time'
                        || field?.type === 'time'
                    )
                    && typeof value === 'string'
                ) {
                    return value.slice(0, 5);
                }

                if (
                    field?.key === 'actual_km'
                    || field?.key === 'planned_km'
                ) {
                    const numericValue = Number(value);

                    if (
                        Number.isFinite(numericValue)
                        && Number.isInteger(numericValue)
                    ) {
                        return String(numericValue);
                    }
                }

                return String(value);
            };

            const populateDailyReportEditValues = (item) => {
                const fields = Array.isArray(
                    effectiveDailyReportConfiguration?.fields
                )
                    ? effectiveDailyReportConfiguration.fields
                    : [];

                fields
                    .filter(
                        (field) =>
                            field?.visible === true
                            && field?.key !== 'service_date'
                    )
                    .forEach((field) => {
                        const control =
                            dailyReportDynamicFields.querySelector(
                                `[data-field-key="${CSS.escape(field.key)}"]`
                            );

                        if (!control) {
                            return;
                        }

                        const value =
                            field.custom === true
                                ? item.custom_field_values?.[
                                    field.key
                                ]
                                : item[field.key];

                        control.value =
                            normalizeRouteEditValue(
                                field,
                                value
                            );
                    });

                updateRouteParcelBalance();
            };

            const openDailyReportEdit = async (item) => {
                resetDailyReportForm(false);

                dailyReportEditItem = item;
                dailyServiceDate.value =
                    item.service_date || '';

                // Datum zůstává při běžné editaci chráněné,
                // protože určuje historickou konfiguraci formuláře.
                dailyServiceDate.disabled = true;

                dailyReportCreatePanel.hidden = false;
                dailyReportAddButton.hidden = true;
                dailyReportSavedMessage.classList.add('hidden');

                dailyReportSaveButton.textContent =
                    item.status === 'correction_requested'
                        ? 'Uložit opravu'
                        : 'Uložit změny';

                await loadEffectiveDailyReportConfiguration(
                    dailyServiceDate.value
                );

                if (!effectiveDailyReportConfiguration) {
                    return;
                }

                populateDailyReportEditValues(item);

                setConfigurationState(
                    item.status === 'correction_requested'
                        ? 'Trasa vyžaduje opravu. Upravte požadované údaje a změny uložte.'
                        : 'Upravujete již zapsanou trasu. Datum jízdy zůstává kvůli historické konfiguraci beze změny.',
                    item.status === 'correction_requested'
                        ? 'error'
                        : 'ok'
                );
            };

            const resubmitCorrectedRoute = async (
                item,
                button
            ) => {
                button.disabled = true;
                reportError.classList.add('hidden');

                try {
                    await api(
                        `/api/v1/daily-reports/${encodeURIComponent(item.public_id)}/resubmit`,
                        {
                            method: 'POST',
                            body: JSON.stringify({
                                expected_version:
                                    Number(item.current_version),
                                reason:
                                    'Oprava řidiče byla dokončena.',
                            }),
                        }
                    );

                    dailyReportSavedMessage.textContent =
                        'Opravená trasa byla odeslána ke schválení.';
                    dailyReportSavedMessage.classList.remove('hidden');

                    await loadReports();
                } catch (error) {
                    if (error.status === 401) {
                        clearSession();
                        showLogin(
                            'Přihlášení vypršelo. Přihlaste se znovu.'
                        );
                        return;
                    }

                    reportError.textContent =
                        `Trasu se nepodařilo odeslat ke schválení: ${error.message}`;
                    reportError.classList.remove('hidden');
                } finally {
                    button.disabled = false;
                }
            };
            const routeParcelCount = (key) => {
                const control =
                    dailyReportDynamicFields.querySelector(
                        `[data-field-key="${CSS.escape(key)}"]`
                    );

                if (!control || control.value.trim() === '') {
                    return null;
                }

                const value = Number(control.value);

                return Number.isFinite(value)
                    ? value
                    : null;
            };

            const currentRouteParcelBalance = () => {
                const loaded =
                    routeParcelCount('loaded_parcels');

                const delivered =
                    routeParcelCount('delivered_parcels');

                const pickup =
                    routeParcelCount('redirected_parcels');

                const rejected =
                    routeParcelCount('undelivered_parcels');

                if (
                    loaded === null
                    || delivered === null
                    || pickup === null
                    || rejected === null
                ) {
                    return null;
                }

                return loaded
                    - delivered
                    - pickup
                    - rejected;
            };

            const updateRouteParcelBalance = () => {
                const balance =
                    currentRouteParcelBalance();

                dailyReportParcelBalance.classList.remove(
                    'ok',
                    'error'
                );

                if (balance === null) {
                    dailyReportParcelBalance.classList.add(
                        'hidden'
                    );

                    dailyReportSaveButton.disabled =
                        !effectiveDailyReportConfiguration;

                    return;
                }

                dailyReportParcelBalance.classList.remove(
                    'hidden'
                );

                if (balance < 0) {
                    dailyReportParcelBalance.classList.add(
                        'error'
                    );

                    dailyReportParcelBalance.textContent =
                        `Chyba v zápisu: součet doručeno + výdejní místo + odmítnuto zákazníkem je o ${Math.abs(balance)} ks vyšší než naloženo.`;

                    dailyReportSaveButton.disabled = true;

                    return;
                }

                dailyReportParcelBalance.classList.add('ok');
                dailyReportParcelBalance.textContent =
                    `Nedoručeno: ${balance} ks`;

                dailyReportSaveButton.disabled =
                    !effectiveDailyReportConfiguration;
            };
            const valueFromControl = (control) => {
                if (!control) {
                    return null;
                }

                const value =
                    typeof control.value === 'string'
                        ? control.value.trim()
                        : control.value;

                return value === ''
                    ? null
                    : value;
            };

            const buildDailyReportPayload = () => {
                if (!effectiveDailyReportConfiguration) {
                    throw new Error(
                        'Pro zadané datum není načten platný formulář.'
                    );
                }

                const serviceDate = dailyServiceDate.value;

                if (!serviceDate) {
                    throw new Error('Vyplňte datum jízdy.');
                }

                const payload = {
                    performed_by_driver_id: Number(currentDriver.id),
                    service_date: serviceDate,
                };

                const customFieldValues = {};
                const fields = Array.isArray(
                    effectiveDailyReportConfiguration.fields
                )
                    ? effectiveDailyReportConfiguration.fields
                    : [];

                fields
                    .filter(
                        (field) =>
                            field?.visible === true
                            && field?.key !== 'service_date'
                    )
                    .forEach((field) => {
                        const control =
                            dailyReportDynamicFields.querySelector(
                                `[data-field-key="${CSS.escape(field.key)}"]`
                            );

                        const value = valueFromControl(control);

                        if (field.custom === true) {
                            if (value !== null) {
                                customFieldValues[field.key] = value;
                            }

                            return;
                        }

                        if (
                            field.key === 'loaded_parcels'
                            || field.key === 'delivered_parcels'
                            || field.key === 'redirected_parcels'
                            || field.key === 'undelivered_parcels'
                        ) {
                            payload[field.key] =
                                value === null
                                    ? null
                                    : Number(value);

                            return;
                        }

                        if (field.key === 'operational_notes') {
                            payload[field.key] = value;
                            return;
                        }

                        payload[field.key] = value;
                    });

                const parcelBalance =
                    currentRouteParcelBalance();

                if (
                    parcelBalance !== null
                    && parcelBalance < 0
                ) {
                    throw new Error(
                        'Chyba v zápisu počtu zásilek. Doručeno + výdejní místo + odmítnuto zákazníkem nesmí být vyšší než naloženo.'
                    );
                }

                if (
                    Object.keys(customFieldValues).length > 0
                ) {
                    payload.custom_field_values =
                        customFieldValues;
                }

                if (
                    Object.prototype.hasOwnProperty.call(
                        payload,
                        'actual_km'
                    )
                    && payload.actual_km !== null
                ) {
                    payload.actual_km_source = 'manual';
                }

                return payload;
            };

            const bootstrapApp = async () => {
                if (!token) {
                    showLogin();
                    return;
                }

                try {
                    await loadIdentity();
                    showApp();
                    await loadReports();
                } catch (error) {
                    clearSession();
                    showLogin(
                        error.status === 401
                            ? 'Přihlášení vypršelo. Přihlaste se znovu.'
                            : `Nepodařilo se ověřit relaci: ${error.message}`
                    );
                }
            };

            loginForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                loginButton.disabled = true;
                loginMessage.classList.add('hidden');

                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;

                try {
                    const body = await api('/api/v1/auth/login', {
                        method: 'POST',
                        body: JSON.stringify({ email, password }),
                    });

                    const payload = getPayload(body) || {};
                    const receivedToken = payload.token;

                    if (typeof receivedToken !== 'string' || receivedToken.length === 0) {
                        throw new Error('API nevrátilo přístupový token.');
                    }

                    token = receivedToken;
                    sessionStorage.setItem(tokenKey, token);
                    document.getElementById('password').value = '';

                    await bootstrapApp();
                } catch (error) {
                    clearSession();
                    showLogin(`Přihlášení se nezdařilo: ${error.message}`);
                } finally {
                    loginButton.disabled = false;
                }
            });

            logoutButton.addEventListener('click', async () => {
                try {
                    await api('/api/v1/auth/logout', { method: 'POST' });
                } catch {
                    // Local session is cleared even when logout API is unavailable.
                } finally {
                    clearSession();
                    showLogin();
                }
            });

            refreshButton.addEventListener('click', loadReports);

            carriersNavButton.addEventListener('click', () => {
                window.location.href = '/carriers';
            });

            dailyReportAddButton.addEventListener('click', () => {
                if (!currentDriver) {
                    return;
                }

                openDailyReportForm();
            });

            dailyReportCancelButton.addEventListener('click', () => {
                closeDailyReportForm();
            });

            dailyReportDynamicFields.addEventListener(
                'input',
                updateRouteParcelBalance
            );

            dailyServiceDate.addEventListener(
                'change',
                async () => {
                    dailyReportCreateMessage.textContent = '';
                    dailyReportCreateMessage.classList.add('hidden');

                    await loadEffectiveDailyReportConfiguration(
                        dailyServiceDate.value
                    );
                }
            );

            dailyReportCreateForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (!currentDriver) {
                    dailyReportCreateMessage.textContent =
                        'Přihlášený účet nemá řidičský profil.';
                    dailyReportCreateMessage.classList.remove('hidden');
                    return;
                }

                if (!effectiveDailyReportConfiguration) {
                    dailyReportCreateMessage.textContent =
                        'Pro zadané datum není načten platný formulář.';
                    dailyReportCreateMessage.classList.remove('hidden');
                    return;
                }

                if (!dailyReportCreateForm.reportValidity()) {
                    return;
                }

                dailyReportSaveButton.disabled = true;
                dailyReportCreateMessage.classList.add('hidden');
                dailyReportSavedMessage.classList.add('hidden');

                try {
                    const payload = buildDailyReportPayload();
                    const editingItem = dailyReportEditItem;

                    if (editingItem) {
                        const editPayload = {
                            ...payload,
                            expected_version:
                                Number(
                                    editingItem.current_version
                                ),
                        };

                        delete editPayload.performed_by_driver_id;

                        // Datum je při běžné editaci chráněné.
                        delete editPayload.service_date;

                        if (
                            editingItem.status
                            === 'correction_requested'
                        ) {
                            editPayload.reason =
                                'Oprava údajů řidičem.';

                            await api(
                                `/api/v1/daily-reports/${encodeURIComponent(editingItem.public_id)}/correct`,
                                {
                                    method: 'POST',
                                    body: JSON.stringify(
                                        editPayload
                                    ),
                                }
                            );

                            dailyReportSavedMessage.textContent =
                                'Oprava trasy byla uložena.';
                        } else {
                            editPayload.reason =
                                'Úprava zapsané trasy řidičem.';

                            await api(
                                `/api/v1/daily-reports/${encodeURIComponent(editingItem.public_id)}`,
                                {
                                    method: 'PATCH',
                                    body: JSON.stringify(
                                        editPayload
                                    ),
                                }
                            );

                            dailyReportSavedMessage.textContent =
                                'Trasa byla upravena.';
                        }

                        resetDailyReportForm(false);
                        dailyReportCreatePanel.hidden = true;
                        dailyReportAddButton.hidden = false;
                        dailyReportSavedMessage.classList.remove('hidden');

                        await loadReports();
                        return;
                    }

                    await api('/api/v1/daily-reports', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });

                    const preservedDate = payload.service_date;

                    selectRouteMonthFromDate(
                        preservedDate
                    );

                    resetDailyReportForm(false);
                    dailyServiceDate.value = preservedDate;
                    dailyReportCreatePanel.hidden = true;
                    dailyReportAddButton.hidden = false;

                    dailyReportSavedMessage.textContent =
                        'Trasa byla uložena.';
                    dailyReportSavedMessage.classList.remove('hidden');

                    await loadReports();
                } catch (error) {
                    if (error.status === 401) {
                        clearSession();
                        showLogin(
                            'Přihlášení vypršelo. Přihlaste se znovu.'
                        );
                        return;
                    }

                    dailyReportCreateMessage.textContent =
                        `Trasu se nepodařilo uložit: ${error.message}`;
                    dailyReportCreateMessage.classList.remove('hidden');

                    if (effectiveDailyReportConfiguration) {
                        dailyReportSaveButton.disabled = false;
                    }
                }
            });



            bootstrapApp();
        })();
    </script>
</body>
</html>
