<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DRAYVIA</title>
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

        .route-actions .route-action-button
        + .route-action-button {
            margin-left: 6px;
        }

        .route-action-delete {
            border-color: #f04438;
            background:
                linear-gradient(
                    180deg,
                    #e34b4b 0%,
                    #cf3030 52%,
                    #b42318 100%
                );
            color: #ffffff;
            box-shadow:
                inset 0 1px 0
                rgba(255, 255, 255, .18);
        }

        .route-action-delete:hover {
            border-color: #912018;
            background:
                linear-gradient(
                    180deg,
                    #ef5d5d 0%,
                    #dc3838 52%,
                    #a91f17 100%
                );
            color: #ffffff;
        }
        .drayvia-delete-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(16, 24, 40, .58);
            backdrop-filter: blur(2px);
        }

        .drayvia-delete-modal {
            width: min(100%, 520px);
            padding: 24px;
            border: 1px solid #e4e7ec;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(16, 24, 40, .28);
        }

        .drayvia-delete-modal-title {
            color: #101828;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.3;
        }

        .drayvia-delete-modal-subtitle {
            margin-top: 6px;
            color: #667085;
            font-size: 13px;
        }

        .drayvia-delete-modal-details {
            margin-top: 18px;
            overflow: hidden;
            border: 1px solid #e4e7ec;
            border-radius: 11px;
            background: #f9fafb;
        }

        .drayvia-delete-modal-row {
            display: grid;
            grid-template-columns: 135px 1fr;
            gap: 12px;
            padding: 10px 12px;
            border-bottom: 1px solid #eaecf0;
        }

        .drayvia-delete-modal-row:last-child {
            border-bottom: 0;
        }

        .drayvia-delete-modal-key {
            color: #667085;
            font-size: 12px;
            font-weight: 700;
        }

        .drayvia-delete-modal-row strong {
            color: #101828;
            font-size: 13px;
        }

        .drayvia-delete-modal-warning {
            margin-top: 16px;
            padding: 11px 12px;
            border: 1px solid #fecdca;
            border-radius: 10px;
            background: #fef3f2;
            color: #912018;
            font-size: 12px;
            line-height: 1.5;
        }

        .drayvia-delete-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            margin-top: 20px;
        }

        .drayvia-delete-modal-button {
            width: auto;
            min-width: 120px;
            padding: 9px 14px;
            border: 1px solid transparent;
            border-radius: 9px;
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .drayvia-delete-modal-cancel {
            border-color: #d0d5dd;
            background: #f2f4f7;
            color: #344054;
        }

        .drayvia-delete-modal-cancel:hover {
            background: #eaecf0;
        }

        .drayvia-delete-modal-confirm {
            border-color: #b42318;
            background:
                linear-gradient(
                    180deg,
                    #e34b4b 0%,
                    #cf3030 52%,
                    #b42318 100%
                );
            color: #ffffff;
        }

        .drayvia-delete-modal-confirm:hover {
            border-color: #912018;
            background:
                linear-gradient(
                    180deg,
                    #ef5d5d 0%,
                    #dc3838 52%,
                    #a91f17 100%
                );
        }

        @media (max-width: 620px) {
            .drayvia-delete-modal-row {
                grid-template-columns: 1fr;
                gap: 3px;
            }

            .drayvia-delete-modal-actions {
                flex-direction: column-reverse;
            }

            .drayvia-delete-modal-button {
                width: 100%;
            }
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

        /* DRAYVIA-07 SIDEBAR BRAND */
        .sidebar .sidebar-brand-drayvia {
            display: block;
            text-align: center;
            margin-bottom: 18px;
        }

        .sidebar .sidebar-brand-logo-wrap {
            display: block;
            padding: 2px 0 8px;
        }

        .sidebar .sidebar-brand-logo-image {
            display: block;
            width: 148px;
            max-width: 100%;
            height: auto;
            margin: 0 auto;
        }

        .sidebar .sidebar-brand-drayvia .brand-subtitle {
            text-align: center;
            color: #d8e1f0;
            font-size: 13px;
            margin-top: 2px;
        }

        /* DRAYVIA-07E SIDEBAR LOGO FINAL */
        .sidebar .sidebar-brand-drayvia {
            display: block;
            width: 100%;
            margin: 0 0 24px 0;
            text-align: center;
        }

        .sidebar .sidebar-brand-logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            box-sizing: border-box;
            background: #ffffff;
            border-radius: 12px;
            padding: 10px 12px;
        }

        .sidebar .sidebar-brand-logo-image {
            display: block;
            width: 170px;
            max-width: 100%;
            height: auto;
            margin: 0 auto;
            object-fit: contain;
        }

        .sidebar .sidebar-brand-drayvia .brand-subtitle {
            display: block;
            margin-top: 7px;
            text-align: center;
            color: #aeb8c8;
            font-size: 12px;
        }

        /* DRAYVIA-15A MAIN NAV */
        .sidebar .drayvia-main-nav {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .sidebar .drayvia-main-nav .nav-section-label {
            margin: 17px 12px 5px;
            color: #718099;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .11em;
            text-transform: uppercase;
            user-select: none;
        }

        .sidebar .drayvia-main-nav .nav-section-label:first-of-type {
            margin-top: 14px;
        }

        .sidebar .drayvia-main-nav .nav-item {
            width: 100%;
            text-align: left;
        }

        /* DRAYVIA-15B UI PREVIEW */

        .drayvia-preview-layer {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 230px;
            z-index: 30;
            display: none;
            background: #f5f7fa;
            overflow: hidden;
        }

        .drayvia-preview-layer.is-visible {
            display: block;
        }

        .drayvia-preview-scroll {
            height: 100%;
            overflow-y: auto;
            box-sizing: border-box;
            padding: 32px 40px 70px;
        }

        .drayvia-preview-container {
            width: min(1320px, 100%);
            margin: 0 auto;
        }

        .drayvia-preview-topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 28px;
        }

        .drayvia-preview-eyebrow {
            margin-bottom: 8px;
            color: #728096;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .drayvia-preview-title {
            margin: 0;
            color: #172033;
            font-size: 30px;
            line-height: 1.15;
            font-weight: 800;
        }

        .drayvia-preview-description {
            max-width: 720px;
            margin: 8px 0 0;
            color: #687386;
            font-size: 14px;
            line-height: 1.55;
        }

        .drayvia-period-control {
            min-width: 190px;
            background: #ffffff;
            border: 1px solid #dde3eb;
            border-radius: 12px;
            padding: 10px 13px;
            box-sizing: border-box;
        }

        .drayvia-period-control label {
            display: block;
            margin-bottom: 5px;
            color: #7b8696;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .drayvia-period-control input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: #172033;
            font: inherit;
            font-weight: 700;
        }

        .drayvia-preview-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .drayvia-preview-card {
            background: #ffffff;
            border: 1px solid #e1e6ed;
            border-radius: 14px;
            padding: 18px;
            box-sizing: border-box;
        }

        .drayvia-preview-card-label {
            margin-bottom: 8px;
            color: #7b8696;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .drayvia-preview-card-value {
            color: #172033;
            font-size: 23px;
            font-weight: 800;
        }

        .drayvia-preview-card-note {
            margin-top: 7px;
            color: #7b8696;
            font-size: 12px;
            line-height: 1.45;
        }

        .drayvia-preview-panel {
            margin-top: 18px;
            background: #ffffff;
            border: 1px solid #e1e6ed;
            border-radius: 14px;
            overflow: hidden;
        }

        .drayvia-preview-panel-head {
            padding: 17px 20px;
            border-bottom: 1px solid #edf0f4;
        }

        .drayvia-preview-panel-title {
            margin: 0;
            color: #172033;
            font-size: 15px;
            font-weight: 800;
        }

        .drayvia-preview-panel-subtitle {
            margin-top: 4px;
            color: #7b8696;
            font-size: 12px;
        }

        .drayvia-preview-panel-body {
            padding: 18px 20px;
        }

        .drayvia-preview-row {
            display: grid;
            grid-template-columns: minmax(150px, 1.3fr) repeat(4, minmax(90px, 1fr));
            align-items: center;
            gap: 10px;
            min-height: 48px;
            border-bottom: 1px solid #f0f2f5;
            color: #4e5969;
            font-size: 13px;
        }

        .drayvia-preview-row:last-child {
            border-bottom: 0;
        }

        .drayvia-preview-row strong {
            color: #172033;
        }

        .drayvia-preview-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            min-width: 70px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eef3f8;
            color: #41516a;
            font-size: 11px;
            font-weight: 800;
        }

        .drayvia-preview-pill.success {
            background: #edf7f1;
            color: #28714b;
        }

        .drayvia-preview-pill.warning {
            background: #fff5df;
            color: #8b6417;
        }

        .drayvia-preview-checklist {
            display: grid;
            gap: 9px;
        }

        .drayvia-preview-check {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #eef1f4;
            color: #364154;
            font-size: 13px;
        }

        .drayvia-preview-check:last-child {
            border-bottom: 0;
        }

        .drayvia-preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .drayvia-preview-action {
            border: 1px solid #d9e0e8;
            border-radius: 10px;
            background: #ffffff;
            padding: 10px 14px;
            color: #263247;
            font: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: default;
        }

        .drayvia-preview-action.primary {
            border-color: #172033;
            background: #172033;
            color: #ffffff;
        }

        .drayvia-calendar-board {
            overflow-x: auto;
        }

        .drayvia-calendar-row {
            display: grid;
            grid-template-columns: 185px repeat(7, minmax(68px, 1fr));
            gap: 7px;
            align-items: center;
            min-width: 760px;
            padding: 7px 0;
        }

        .drayvia-calendar-name {
            color: #172033;
            font-size: 13px;
            font-weight: 800;
        }

        .drayvia-calendar-day {
            min-height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid #e1e6ed;
            border-radius: 9px;
            background: #ffffff;
            color: #667286;
            font-size: 11px;
            font-weight: 700;
        }

        .drayvia-calendar-day.working {
            background: #edf7f1;
            border-color: #cfe7d8;
            color: #28714b;
        }

        .drayvia-calendar-day.off {
            background: #f2f3f5;
            border-color: #e2e5e9;
            color: #818a98;
        }

        .drayvia-settings-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .drayvia-settings-tile {
            min-height: 105px;
            background: #ffffff;
            border: 1px solid #e1e6ed;
            border-radius: 14px;
            padding: 18px;
            box-sizing: border-box;
        }

        .drayvia-settings-tile strong {
            display: block;
            margin-bottom: 6px;
            color: #172033;
            font-size: 14px;
        }

        .drayvia-settings-tile span {
            color: #7b8696;
            font-size: 12px;
            line-height: 1.45;
        }

        @media (max-width: 1050px) {
            .drayvia-preview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .drayvia-settings-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* DRAYVIA-15B1 UPPERCASE NAV AND TITLES */

        .sidebar .drayvia-main-nav .nav-item {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .025em;
            text-transform: uppercase;
        }

        .app-shell h1,
        .drayvia-preview-title {
            font-size: 30px !important;
            font-weight: 900 !important;
            line-height: 1.15;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        /* DRAYVIA-15B2 MODULE-OWNED SETTINGS */

        /* DRAYVIA-15C OVERVIEW */

        .drayvia-overview-module-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .drayvia-overview-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(300px, .8fr);
            gap: 18px;
            align-items: start;
        }

        .drayvia-overview-layout .drayvia-preview-panel {
            margin-top: 0;
        }

        .drayvia-month-close-row {
            margin-top: 8px;
            padding-top: 18px;
            border-top: 2px solid #e5e9ef;
        }

        .drayvia-attention-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            min-height: 58px;
            border-bottom: 1px solid #eef1f4;
        }

        .drayvia-attention-item:last-child {
            border-bottom: 0;
        }

        .drayvia-attention-item > div {
            display: grid;
            gap: 3px;
        }

        .drayvia-attention-item strong {
            color: #172033;
            font-size: 13px;
            font-weight: 900;
        }

        .drayvia-attention-item span:not(.drayvia-preview-pill) {
            color: #7b8696;
            font-size: 11px;
        }

        @media (max-width: 1250px) {
            .drayvia-overview-module-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 950px) {
            .drayvia-overview-module-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .drayvia-overview-layout {
                grid-template-columns: 1fr;
            }
        }

        /* DRAYVIA-15D MONTHLY CALENDAR */

        .drayvia-calendar-summary-grid {
            display: grid;
            grid-template-columns:
                repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .drayvia-calendar-panel {
            margin-top: 0;
        }

        .drayvia-month-calendar-wrap {
            width: 100%;
            overflow: auto;
        }

        .drayvia-month-calendar {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
            table-layout: fixed;
            background: #ffffff;
        }

        .drayvia-month-calendar th {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: 13px 10px;
            border-bottom: 1px solid #dfe4eb;
            background: #f7f9fb;
            color: #172033;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .035em;
            text-align: center;
        }

        .drayvia-month-calendar th:nth-child(1) {
            width: 105px;
            text-align: left;
        }

        .drayvia-month-calendar th:nth-child(2) {
            width: 55px;
        }

        .drayvia-month-calendar th:nth-child(3) {
            width: 140px;
            text-align: left;
        }

        .drayvia-month-calendar td {
            height: 48px;
            padding: 5px 10px;
            border-bottom: 1px solid #eef1f4;
            color: #4c586a;
            font-size: 12px;
            text-align: center;
            box-sizing: border-box;
        }

        .drayvia-month-calendar-row:last-child td {
            border-bottom: 0;
        }

        .drayvia-calendar-date {
            color: #172033 !important;
            font-weight: 900;
            text-align: left !important;
        }

        .drayvia-calendar-weekday {
            color: #6f7b8e !important;
            font-weight: 900;
        }

        .drayvia-calendar-day-type {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 96px;
            padding: 6px 9px;
            border-radius: 999px;
            background: #edf7f1;
            color: #28714b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .drayvia-calendar-day-type.weekend {
            background: #eef1f5;
            color: #687487;
        }

        .drayvia-calendar-day-type.holiday {
            background: #fff3dc;
            color: #936719;
        }

        .drayvia-month-calendar-row.weekend td {
            background: #fafbfc;
        }

        .drayvia-month-calendar-row.holiday td {
            background: #fffdf8;
        }

        .drayvia-calendar-status-cell {
            padding-left: 6px !important;
            padding-right: 6px !important;
        }

        .drayvia-calendar-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 112px;
            min-height: 31px;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .025em;
        }

        .drayvia-calendar-status.working {
            border: 1px solid #bfe5cf;
            background: #eaf8f0;
            color: #177245;
        }

        .drayvia-calendar-status.off {
            border: 1px solid #e0e4e9;
            background: #f2f4f6;
            color: #758092;
        }

        .drayvia-calendar-status.unset {
            border: 1px dashed #dce1e8;
            background: #ffffff;
            color: #9aa3b1;
        }

        @media (max-width: 1250px) {
            .drayvia-calendar-summary-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }
        }

        /* DRAYVIA-15D1 CALENDAR STATUS DESIGN */

        .drayvia-month-calendar {
            min-width: 1220px;
        }

        .drayvia-month-calendar th:nth-child(3),
        .drayvia-month-calendar td:nth-child(3) {
            text-align: center !important;
        }

        .drayvia-month-calendar th:nth-child(n+4) {
            white-space: normal;
            line-height: 1.35;
        }

        .drayvia-calendar-legend {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }

        .drayvia-calendar-legend-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            min-height: 92px;
            padding: 13px 10px;
            box-sizing: border-box;
            border: 1px solid #e1e6ed;
            border-radius: 12px;
            background: #ffffff;
            text-align: center;
        }

        .drayvia-calendar-legend-item > span:last-child {
            color: #758092;
            font-size: 10px;
            line-height: 1.4;
        }

        .drayvia-calendar-legend-item.automatic {
            border-style: dashed;
        }

        .drayvia-calendar-status.vacation {
            border: 1px solid #bfd9f3;
            background: #eaf4ff;
            color: #28689f;
        }

        .drayvia-calendar-status.sick {
            border: 1px solid #efc7c7;
            background: #fff0f0;
            color: #a33b3b;
        }

        .drayvia-calendar-status.unused {
            border: 1px solid #efd7aa;
            background: #fff5df;
            color: #956616;
        }

        @media (max-width: 1250px) {
            .drayvia-calendar-legend {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        /* DRAYVIA-16F REAL DRIVER UI */

        .drayvia-real-driver-message {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 900;
        }

        .drayvia-real-driver-message.success {
            border: 1px solid #bfe5cf;
            background: #eaf8f0;
            color: #177245;
        }

        .drayvia-real-driver-message.error {
            border: 1px solid #efc7c7;
            background: #fff0f0;
            color: #a23c3c;
        }

        .drayvia-real-driver-form-panel {
            margin-top: 18px;
            padding: 20px;
            border: 1px solid #dfe5ec;
            border-radius: 14px;
            background: #ffffff;
        }

        .drayvia-real-driver-form-head {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .drayvia-real-driver-form-head h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 900;
        }

        .drayvia-real-driver-form-head p {
            margin: 5px 0 0;
            color: #7b8696;
            font-size: 11px;
        }

        .drayvia-real-driver-close {
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 8px;
            background: #f0f2f5;
            color: #526075;
            font-size: 22px;
            cursor: pointer;
        }

        .drayvia-real-driver-form-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .drayvia-real-driver-form-grid label {
            display: grid;
            gap: 6px;
        }

        .drayvia-real-driver-form-grid label > span {
            color: #5e697b;
            font-size: 10px;
            font-weight: 900;
        }

        .drayvia-real-driver-form-grid input {
            width: 100%;
            min-height: 42px;
            box-sizing: border-box;
            padding: 9px 11px;
            border: 1px solid #dce2e9;
            border-radius: 9px;
            background: #ffffff;
            color: #172033;
            font: inherit;
            font-size: 13px;
            outline: 0;
        }

        .drayvia-real-driver-form-note {
            margin-top: 14px;
            color: #7b8696;
            font-size: 10px;
        }

        .drayvia-real-driver-form-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        /* DRAYVIA-25E2B STATISTICS PAGE */

        .drayvia-driver-statistics {
            margin-top: 18px;
        }

        .drayvia-driver-stat-filters {
            margin-top: 14px;
            padding: 16px 18px;
            border: 1px solid #dce3ea;
            border-radius: 12px;
            background: #f7f9fb;
        }

        .drayvia-driver-stat-filter-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-top: 11px;
        }

        .drayvia-driver-stat-filter-row:first-child {
            margin-top: 0;
        }

        .drayvia-driver-stat-label {
            width: 120px;
            flex: 0 0 120px;
            padding-top: 8px;
            color: #44536a;
            font-size: 12px;
            font-weight: 900;
        }

        .drayvia-driver-stat-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .drayvia-driver-stat-button {
            width: auto;
            min-width: 0;
            padding: 8px 12px;
            border: 1px solid #858e97;
            border-radius: 8px;
            background: linear-gradient(
                180deg,
                #a7adb4 0%,
                #7d858e 52%,
                #626a73 100%
            );
            color: #ffffff;
            font: inherit;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
        }

        .drayvia-driver-stat-button.active {
            border-color: #21875b;
            background: linear-gradient(
                180deg,
                #38ad79 0%,
                #258b5e 52%,
                #1d704c 100%
            );
        }

        .drayvia-driver-stat-summary {
            margin-top: 14px;
            color: #40506a;
            font-size: 13px;
            font-weight: 900;
        }

        .drayvia-driver-stat-table-wrap {
            width: 100%;
            margin-top: 16px;
            overflow-x: visible;
        }

        .drayvia-driver-stat-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .drayvia-driver-stat-table th,
        .drayvia-driver-stat-table td {
            width: 8.333333%;
            box-sizing: border-box;
            overflow-wrap: anywhere;
        }

        .drayvia-driver-stat-table th {
            padding: 12px 6px;
            border-right: 2px solid #ffffff;
            border-bottom: 2px solid #ffffff;
            color: #203047;
            font-size: 11px;
            line-height: 1.25;
            font-weight: 900;
            text-align: center;
            white-space: normal;
            vertical-align: middle;
        }

        .drayvia-driver-stat-table td {
            padding: 14px 6px;
            border-right: 2px solid #ffffff;
            border-bottom: 2px solid #ffffff;
            color: #142238;
            font-size: 13px;
            line-height: 1.3;
            font-weight: 800;
            text-align: center;
            white-space: normal;
            vertical-align: middle;
        }

        /* identita */
        .drayvia-driver-stat-table th:nth-child(1),
        .drayvia-driver-stat-table td:nth-child(1) {
            background: #edf1f5;
        }

        /* trasy */
        .drayvia-driver-stat-table th:nth-child(2),
        .drayvia-driver-stat-table td:nth-child(2) {
            background: #e7edf3;
        }

        /* dny */
        .drayvia-driver-stat-table th:nth-child(3),
        .drayvia-driver-stat-table td:nth-child(3) {
            background: #e7edf3;
        }

        /* vstup - nalozeno */
        .drayvia-driver-stat-table th:nth-child(4),
        .drayvia-driver-stat-table td:nth-child(4) {
            background: #dfeefa;
        }

        /* uspesne doruceno */
        .drayvia-driver-stat-table th:nth-child(5),
        .drayvia-driver-stat-table td:nth-child(5) {
            background: #ddf2e6;
        }

        /* vydejni misto */
        .drayvia-driver-stat-table th:nth-child(6),
        .drayvia-driver-stat-table td:nth-child(6) {
            background: #dcf1f1;
        }

        /* odmitnuto zakaznikem */
        .drayvia-driver-stat-table th:nth-child(7),
        .drayvia-driver-stat-table td:nth-child(7) {
            background: #ffefd3;
        }

        /* nedoruceno */
        .drayvia-driver-stat-table th:nth-child(8),
        .drayvia-driver-stat-table td:nth-child(8) {
            background: #fbe2e0;
        }

        /* plan km */
        .drayvia-driver-stat-table th:nth-child(9),
        .drayvia-driver-stat-table td:nth-child(9) {
            background: #e7edf3;
        }

        /* skutecne km */
        .drayvia-driver-stat-table th:nth-child(10),
        .drayvia-driver-stat-table td:nth-child(10) {
            background: #dfeefa;
        }

        /* rozdil najezdu */
        .drayvia-driver-stat-table th:nth-child(11),
        .drayvia-driver-stat-table td:nth-child(11) {
            background: #e2f2e8;
        }

        /* dilci kvalita */
        .drayvia-driver-stat-table th:nth-child(12),
        .drayvia-driver-stat-table td:nth-child(12) {
            background: #e2f2e8;
        }

        .drayvia-driver-stat-identity {
            text-align: left !important;
        }

        .drayvia-driver-stat-name {
            display: block;
            color: #0f1c2f;
            font-size: 14px;
            font-weight: 900;
        }

        .drayvia-driver-stat-id {
            display: block;
            margin-top: 4px;
            color: #53657a;
            font-size: 11px;
            font-weight: 900;
        }

        .drayvia-driver-stat-primary {
            display: block;
            color: #132238;
            font-size: 13px;
            font-weight: 900;
        }

        .drayvia-driver-stat-secondary {
            display: block;
            margin-top: 3px;
            color: #53657a;
            font-size: 11px;
            font-weight: 900;
        }

        .drayvia-driver-stat-alert {
            background: #f6c9c5 !important;
            color: #94170f !important;
            font-weight: 900 !important;
        }

        .drayvia-driver-stat-warning {
            background: #ffe3b4 !important;
            color: #875000 !important;
            font-weight: 900 !important;
        }

        .drayvia-driver-stat-quality-good {
            background: #cfead9 !important;
            color: #0e5f39 !important;
            font-weight: 900 !important;
        }

        .drayvia-driver-stat-quality-bad {
            background: #f6c9c5 !important;
            color: #94170f !important;
            font-weight: 900 !important;
        }

        .drayvia-driver-stat-empty {
            padding: 26px !important;
            background: #f5f7f9 !important;
            text-align: center !important;
            font-size: 13px !important;
        }

        @media (max-width: 1150px) {
            .drayvia-driver-stat-table th {
                font-size: 10px;
                padding-left: 4px;
                padding-right: 4px;
            }

            .drayvia-driver-stat-table td {
                font-size: 12px;
                padding-left: 4px;
                padding-right: 4px;
            }

            .drayvia-driver-stat-name {
                font-size: 13px;
            }
        }
        .drayvia-real-driver-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .drayvia-real-driver-table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        .drayvia-real-driver-table th {
            padding: 13px 16px;
            border-bottom: 1px solid #e4e8ed;
            background: #f7f9fb;
            color: #596679;
            font-size: 10px;
            font-weight: 900;
            text-align: left;
        }

        .drayvia-real-driver-table td {
            padding: 13px 16px;
            border-bottom: 1px solid #eef1f4;
            color: #596679;
            font-size: 12px;
        }

        .drayvia-real-driver-name {
            color: #172033 !important;
            font-weight: 900;
        }

        .drayvia-real-driver-id {
            display: inline-flex;
            padding: 5px 9px;
            border-radius: 7px;
            background: #edf3f8;
            color: #2d4c68;
            font-weight: 900;
        }

        .drayvia-real-driver-id.missing {
            background: #fff3dc;
            color: #936719;
        }

        .drayvia-real-driver-small-button {
            border: 1px solid #dce2e9;
            border-radius: 8px;
            padding: 7px 10px;
            background: #ffffff;
            color: #667286;
            font-size: 9px;
            font-weight: 900;
        }

        .drayvia-real-driver-empty {
            padding: 30px !important;
            color: #8792a2 !important;
            font-weight: 900;
            text-align: center;
        }

        @media (max-width: 1050px) {
            .drayvia-real-driver-form-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        /* DRAYVIA-16G PERIOD SELECTOR */

        .drayvia-period-control-extended {
            min-width: 235px;
            padding: 11px 13px 12px;
        }

        .drayvia-period-control-extended > select,
        .drayvia-period-detail select,
        .drayvia-period-detail input {
            width: 100%;
            box-sizing: border-box;
            border: 0;
            outline: 0;
            background: transparent;
            color: #172033;
            font: inherit;
            font-size: 13px;
            font-weight: 900;
        }

        .drayvia-period-control-extended > select {
            margin-top: 1px;
            cursor: pointer;
        }

        .drayvia-period-detail {
            margin-top: 7px;
            padding-top: 7px;
            border-top: 1px solid #edf0f4;
        }

        .drayvia-period-value {
            color: #172033;
            font-size: 13px;
            font-weight: 900;
        }

        /* DRAYVIA GLOBAL FILTER VISUAL CONTRACT
         *
         * Neutral = graphite metallic.
         * Active = green.
         * Reset = white neutral action.
         *
         * Reuse these classes in all DRAYVIA modules.
         */

        .drayvia-filter-control {
            min-height: 38px;
            border: 1px solid #484f54 !important;
            border-radius: 999px !important;
            background:
                linear-gradient(
                    180deg,
                    #737b80 0%,
                    #5d656a 48%,
                    #4f575c 100%
                ) !important;
            color: #ffffff !important;
            font-size: 12px;
            font-weight: 800;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .20),
                0 1px 2px rgba(16, 24, 40, .18) !important;
            text-shadow:
                0 1px 1px rgba(0, 0, 0, .18);
            transition:
                background .15s ease,
                border-color .15s ease,
                box-shadow .15s ease,
                transform .15s ease;
        }

        .drayvia-filter-control:not(.active):hover {
            border-color: #3d454a !important;
            background:
                linear-gradient(
                    180deg,
                    #858d92 0%,
                    #697176 52%,
                    #596166 100%
                ) !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .25),
                0 3px 8px rgba(16, 24, 40, .16) !important;
        }

        .drayvia-filter-control.active,
        .drayvia-filter-control.active:hover {
            border-color: #067647 !important;
            background:
                linear-gradient(
                    180deg,
                    #20b978 0%,
                    #12965f 52%,
                    #087a4b 100%
                ) !important;
            color: #ffffff !important;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .25),
                0 0 0 3px rgba(18, 150, 95, .18),
                0 3px 8px rgba(6, 118, 71, .20) !important;
            transform: none;
        }

        .drayvia-filter-control:focus {
            outline: none;
        }

        .drayvia-filter-search::placeholder {
            color: rgba(255, 255, 255, .82);
            opacity: 1;
        }

        select.drayvia-filter-control option {
            background: #ffffff;
            color: #101828;
            font-weight: 700;
        }

        .drayvia-filter-reset {
            min-height: 38px !important;
            padding: 7px 11px !important;
            border: 1px solid #d0d5dd !important;
            border-radius: 8px !important;
            background: #ffffff !important;
            color: #344054 !important;
            font-size: 12px !important;
            font-weight: 800 !important;
            box-shadow: none !important;
            text-shadow: none !important;
            transform: none !important;
        }

        .drayvia-filter-reset:hover {
            border-color: #98a2b3 !important;
            background: #f2f4f7 !important;
            color: #344054 !important;
            transform: none !important;
        }
        /* DRAYVIA-24A ACTIVE FILTER CLARITY */
        .route-history-filters .route-filter-chip,
        .route-history-filters .route-filter-chip.route-status-written,
        .route-history-filters .route-filter-chip.route-status-waiting,
        .route-history-filters .route-filter-chip.route-status-correction,
        .route-history-filters .route-filter-chip.route-status-corrected,
        .route-history-filters .route-filter-chip.route-status-approved,
        .route-history-filters .route-filter-chip.route-status-closed,
        .route-history-filters .route-filter-chip.route-custom-period-trigger {
            border-color: #d0d5dd !important;
            background: #ffffff !important;
            color: #344054 !important;
            box-shadow: none !important;
        }

        .route-history-filters .route-filter-chip:hover,
        .route-history-filters .route-filter-chip.route-status-written:hover,
        .route-history-filters .route-filter-chip.route-status-waiting:hover,
        .route-history-filters .route-filter-chip.route-status-correction:hover,
        .route-history-filters .route-filter-chip.route-status-corrected:hover,
        .route-history-filters .route-filter-chip.route-status-approved:hover,
        .route-history-filters .route-filter-chip.route-status-closed:hover,
        .route-history-filters .route-filter-chip.route-custom-period-trigger:hover {
            border-color: #98a2b3 !important;
            background: #f2f4f7 !important;
            color: #344054 !important;
            box-shadow: none !important;
        }

        .route-history-filters .route-filter-chip.active,
        .route-history-filters .route-filter-chip.route-status-written.active,
        .route-history-filters .route-filter-chip.route-status-waiting.active,
        .route-history-filters .route-filter-chip.route-status-correction.active,
        .route-history-filters .route-filter-chip.route-status-corrected.active,
        .route-history-filters .route-filter-chip.route-status-approved.active,
        .route-history-filters .route-filter-chip.route-status-closed.active,
        .route-history-filters .route-filter-chip.route-custom-period-trigger.active {
            border-color: #079455 !important;
            background: #d1fadf !important;
            color: #05603a !important;
            box-shadow:
                0 0 0 2px
                rgba(7, 148, 85, .12) !important;
        }

        /* DRAYVIA-24E ROUTE OPERATIONAL UI */

        /*
         * Neutral filter:
         * graphite metallic grey inspired by automotive paint.
         */
        .route-history-filters .route-filter-chip,
        .route-history-filters .route-filter-chip.route-status-written,
        .route-history-filters .route-filter-chip.route-status-waiting,
        .route-history-filters .route-filter-chip.route-status-corrected,
        .route-history-filters .route-filter-chip.route-status-approved,
        .route-history-filters .route-filter-chip.route-status-closed,
        .route-history-filters .route-filter-chip.route-custom-period-trigger {
            border-color: #484f54 !important;
            background:
                linear-gradient(
                    180deg,
                    #737b80 0%,
                    #5d656a 48%,
                    #4f575c 100%
                ) !important;
            color: #ffffff !important;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .20),
                0 1px 2px rgba(16, 24, 40, .18) !important;
            text-shadow:
                0 1px 1px rgba(0, 0, 0, .18);
        }

        .route-history-filters .route-filter-chip:hover,
        .route-history-filters .route-filter-chip.route-status-written:hover,
        .route-history-filters .route-filter-chip.route-status-waiting:hover,
        .route-history-filters .route-filter-chip.route-status-corrected:hover,
        .route-history-filters .route-filter-chip.route-status-approved:hover,
        .route-history-filters .route-filter-chip.route-status-closed:hover,
        .route-history-filters .route-filter-chip.route-custom-period-trigger:hover {
            border-color: #3d454a !important;
            background:
                linear-gradient(
                    180deg,
                    #858d92 0%,
                    #697176 52%,
                    #596166 100%
                ) !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .25),
                0 3px 8px rgba(16, 24, 40, .16) !important;
        }

        /*
         * Requires operator action:
         * red while inactive.
         */
        .route-history-filters
        .route-filter-chip.route-status-correction {
            border-color: #a61b1b !important;
            background:
                linear-gradient(
                    180deg,
                    #e34b4b 0%,
                    #cf3030 50%,
                    #b42318 100%
                ) !important;
            color: #ffffff !important;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .22),
                0 2px 5px rgba(180, 35, 24, .22) !important;
        }

        .route-history-filters
        .route-filter-chip.route-status-correction:hover {
            border-color: #8f1717 !important;
            background:
                linear-gradient(
                    180deg,
                    #ef5d5d 0%,
                    #dc3838 50%,
                    #c1281d 100%
                ) !important;
            color: #ffffff !important;
        }

        /*
         * Active filter always wins visually.
         * Green means: THIS FILTER IS CURRENTLY APPLIED.
         */
        .route-history-filters .route-filter-chip.active,
        .route-history-filters .route-filter-chip.route-status-written.active,
        .route-history-filters .route-filter-chip.route-status-waiting.active,
        .route-history-filters .route-filter-chip.route-status-correction.active,
        .route-history-filters .route-filter-chip.route-status-corrected.active,
        .route-history-filters .route-filter-chip.route-status-approved.active,
        .route-history-filters .route-filter-chip.route-status-closed.active,
        .route-history-filters .route-filter-chip.route-custom-period-trigger.active {
            border-color: #067647 !important;
            background:
                linear-gradient(
                    180deg,
                    #20b978 0%,
                    #12965f 52%,
                    #087a4b 100%
                ) !important;
            color: #ffffff !important;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .25),
                0 0 0 3px rgba(18, 150, 95, .18),
                0 3px 8px rgba(6, 118, 71, .20) !important;
            transform: none;
            text-shadow:
                0 1px 1px rgba(0, 0, 0, .15);
        }

        .route-driver-filter-controls {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            flex-wrap: wrap;
            width: 100%;
        }

        .route-driver-filter-controls > .route-filter-buttons {
            flex: 1 1 auto;
        }

        .route-driver-picker {
            position: relative;
            flex: 0 0 280px;
            width: 280px;
            max-width: 100%;
        }

        .route-driver-picker-label {
            display: block;
            margin: 0 0 5px;
            color: #344054;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .06em;
        }

        .route-driver-search-input {
            width: 100%;
            min-height: 38px;
            padding: 8px 11px;
            border: 1px solid #7a8287;
            border-radius: 9px;
            background:
                linear-gradient(
                    180deg,
                    #f4f5f5 0%,
                    #e7e9ea 100%
                );
            color: #101828;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            outline: none;
        }

        .route-driver-search-input:focus {
            border-color: #079455;
            background: #ffffff;
            box-shadow:
                0 0 0 3px
                rgba(7, 148, 85, .13);
        }

        .route-driver-search-results {
            position: absolute;
            z-index: 80;
            top: calc(100% + 5px);
            right: 0;
            width: min(390px, 90vw);
            max-height: 320px;
            overflow-y: auto;
            padding: 6px;
            border: 1px solid #98a2b3;
            border-radius: 10px;
            background: #ffffff;
            box-shadow:
                0 14px 32px
                rgba(16, 24, 40, .18);
        }

        .route-driver-search-result {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            width: 100%;
            padding: 10px 11px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #101828;
            text-align: left;
            cursor: pointer;
            font: inherit;
        }

        .route-driver-search-result:hover,
        .route-driver-search-result:focus {
            background: #eef0f1;
            outline: none;
        }

        .route-driver-search-result.active {
            background: #d1fadf;
            color: #05603a;
        }

        .route-driver-search-identity {
            font-size: 13px;
            font-weight: 800;
        }

        .route-driver-search-result-meta {
            flex: 0 0 auto;
            color: #667085;
            font-size: 11px;
            font-weight: 600;
        }

        .route-driver-search-empty {
            padding: 11px;
            color: #667085;
            font-size: 12px;
            font-weight: 600;
        }

        #reportTableBody .route-driver-name-cell {
            text-align: left;
            white-space: nowrap;
            font-weight: 800;
        }

        #reportTableBody .route-driver-id-cell {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 980px) {
            .route-driver-picker {
                flex: 1 1 100%;
                width: 100%;
            }

            .route-driver-search-results {
                left: 0;
                right: auto;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main id="loginPage" class="login-page">
        <section class="login-card" aria-labelledby="loginTitle">
            <div class="brand">

                <div>
                    <div class="brand-title"><img src="/assets/brand/drayvia-logo-horizontal.png" alt="DRAYVIA" style="display:block;height:38px;width:auto;max-width:220px;object-fit:contain;margin:0 auto;"></div>
                    <div class="brand-subtitle"><span style="display:block;text-align:center;">Interní provoz</span></div>
                </div>
            </div>

            <h1 id="loginTitle">Přihlášení</h1>
            <p class="lead">Provozní data přehledně na jednom místě.</p>

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
            <div class="brand sidebar-brand-drayvia">
    <div class="sidebar-brand-logo-wrap">
        <img
            class="sidebar-brand-logo-image"
            src="/assets/brand/drayvia-logo-horizontal.png"
            alt="DRAYVIA"
        >
    </div>
    <div class="brand-subtitle">Interní provoz</div>
</div>

            <nav class="nav drayvia-main-nav" aria-label="Hlavní navigace">
    <button class="nav-item" type="button" data-drayvia-page="overview">Přehled</button>
<button class="nav-item" type="button" data-drayvia-page="calendar">Kalendář</button>
    <button class="nav-item active" type="button" data-drayvia-page="routes">Trasy</button>
    <button class="nav-item" type="button" data-drayvia-page="drivers">Řidiči</button>
    <button class="nav-item" id="carriersNavButton" type="button">Dopravci</button>
    <button class="nav-item" type="button" data-drayvia-page="statistics">Statistiky</button>
<button class="nav-item" type="button" data-drayvia-page="fuel">PHM</button>
    <button class="nav-item" type="button" data-drayvia-page="finance">Finance</button>
    <button class="nav-item" type="button" data-drayvia-page="bank">Banka</button>
<button class="nav-item" type="button" data-drayvia-page="imports">Importy</button>
    <button class="nav-item" type="button" data-drayvia-page="settings">Nastavení</button>
</nav>

            <div class="sidebar-footer">
                <div id="userBox" class="user-box">Přihlášený uživatel</div>
                <button id="logoutButton" class="danger-button" type="button">Odhlásit se</button>
            </div>
        </aside>

        <section class="content">
            <header class="topbar">
                <div>
                    <div class="eyebrow">Interní provoz</div>
                    <h1>Trasy</h1>
                    <p>Provozní aplikace DRAYVIA pro práci s reálnými daty.</p>
                </div>

                <div class="status-pill">
                    <span class="status-dot"></span>
                    API připojeno
                </div>
            </header>

            <div class="pilot-banner">
                Cíl interního provozu: převést historická data do DRAYVIA a nahradit hlavní měsíční Excel.
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
                        Vyberte datum jízdy. DRAYVIA načte formulář platný pro tento den.
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
                        <div class="route-filter-label">&#344;IDI&#268;</div>
                        <div class="route-driver-filter-controls">
                            <div
                                id="routeDriverButtons"
                                class="route-filter-buttons"
                            ></div>

                            <div class="route-driver-picker">
                                <label
                                    for="routeDriverSearch"
                                    class="route-driver-picker-label"
                                >
                                    VYBRAT ŘIDIČE
                                </label>

                                <input
                                    id="routeDriverSearch"
                                    class="route-driver-search-input"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Příjmení, jméno nebo ID"
                                    aria-label="Vybrat nebo vyhledat řidiče"
                                >

                                <div
                                    id="routeDriverSearchResults"
                                    class="route-driver-search-results hidden"
                                ></div>
                            </div>
                        </div>
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
                                <th>Příjmení a jméno</th>
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

            const confirmDeleteDraftRoute = (
                item
            ) => new Promise((resolve) => {
                const backdrop =
                    document.createElement('div');

                backdrop.className =
                    'drayvia-delete-modal-backdrop';

                const modal =
                    document.createElement('div');

                modal.className =
                    'drayvia-delete-modal';

                modal.setAttribute(
                    'role',
                    'dialog'
                );

                modal.setAttribute(
                    'aria-modal',
                    'true'
                );

                modal.setAttribute(
                    'aria-label',
                    'Potvrzen\u00ed smaz\u00e1n\u00ed trasy'
                );

                const title =
                    document.createElement('div');

                title.className =
                    'drayvia-delete-modal-title';

                title.textContent =
                    'Opravdu chcete smazat trasu?';

                const subtitle =
                    document.createElement('div');

                subtitle.className =
                    'drayvia-delete-modal-subtitle';

                subtitle.textContent =
                    'Zkontrolujte \u00fadaje p\u0159ed potvrzen\u00edm.';

                const details =
                    document.createElement('div');

                details.className =
                    'drayvia-delete-modal-details';

                const addRow = (
                    label,
                    value
                ) => {
                    const row =
                        document.createElement('div');

                    row.className =
                        'drayvia-delete-modal-row';

                    const key =
                        document.createElement('span');

                    key.className =
                        'drayvia-delete-modal-key';

                    key.textContent =
                        label;

                    const data =
                        document.createElement('strong');

                    data.textContent =
                        value ?? '\u2014';

                    row.append(
                        key,
                        data
                    );

                    details.appendChild(
                        row
                    );
                };

                const driverName =
                    item.performed_by_driver_name
                    || `\u0158idi\u010d ${item.performed_by_driver_id}`;

                const driverId =
                    item.performed_by_driver_external_id
                    || item.performed_by_driver_id;

                addRow(
                    'Datum',
                    formatCzechDate(
                        item.service_date
                    )
                );

                addRow(
                    'Trasa',
                    item.route_number ?? '\u2014'
                );

                addRow(
                    '\u0158idi\u010d',
                    driverName
                );

                addRow(
                    'ID \u0159idi\u010de',
                    driverId ?? '\u2014'
                );

                const warning =
                    document.createElement('div');

                warning.className =
                    'drayvia-delete-modal-warning';

                warning.textContent =
                    'Trasa zmiz\u00ed z b\u011b\u017en\u00e9ho seznamu. '
                    + 'Informace o smaz\u00e1n\u00ed z\u016fstane zachov\u00e1na.';

                const actions =
                    document.createElement('div');

                actions.className =
                    'drayvia-delete-modal-actions';

                const cancelButton =
                    document.createElement('button');

                cancelButton.type =
                    'button';

                cancelButton.className =
                    'drayvia-delete-modal-button drayvia-delete-modal-cancel';

                cancelButton.textContent =
                    'Zru\u0161it';

                const confirmButton =
                    document.createElement('button');

                confirmButton.type =
                    'button';

                confirmButton.className =
                    'drayvia-delete-modal-button drayvia-delete-modal-confirm';

                confirmButton.textContent =
                    'Ano, smazat trasu';

                actions.append(
                    cancelButton,
                    confirmButton
                );

                modal.append(
                    title,
                    subtitle,
                    details,
                    warning,
                    actions
                );

                backdrop.appendChild(
                    modal
                );

                const previousOverflow =
                    document.body.style.overflow;

                let settled =
                    false;

                const finish = (
                    result
                ) => {
                    if (settled) {
                        return;
                    }

                    settled =
                        true;

                    document.removeEventListener(
                        'keydown',
                        onKeyDown
                    );

                    document.body.style.overflow =
                        previousOverflow;

                    backdrop.remove();

                    resolve(
                        result
                    );
                };

                const onKeyDown = (
                    event
                ) => {
                    if (event.key === 'Escape') {
                        event.preventDefault();

                        finish(
                            false
                        );
                    }
                };

                cancelButton.addEventListener(
                    'click',
                    () => finish(false)
                );

                confirmButton.addEventListener(
                    'click',
                    () => finish(true)
                );

                backdrop.addEventListener(
                    'click',
                    (event) => {
                        if (event.target === backdrop) {
                            finish(false);
                        }
                    }
                );

                document.addEventListener(
                    'keydown',
                    onKeyDown
                );

                document.body.style.overflow =
                    'hidden';

                document.body.appendChild(
                    backdrop
                );

                cancelButton.focus();
            });
            const deleteDraftRoute = async (
                item,
                button
            ) => {
                const confirmed =
                    await confirmDeleteDraftRoute(
                        item
                    );

                if (!confirmed) {
                    return;
                }
                button.disabled = true;

                reportError.classList.add(
                    'hidden'
                );

                try {
                    await api(
                        `/api/v1/daily-reports/${encodeURIComponent(item.public_id)}`,
                        {
                            method: 'DELETE',
                            body: JSON.stringify({
                                expected_version:
                                    Number(
                                        item.current_version
                                    ),
                                reason:
                                    'Smazání zapsané trasy.',
                            }),
                        }
                    );

                    dailyReportSavedMessage.textContent =
                        'Trasa byla smazána.';

                    dailyReportSavedMessage
                        .classList
                        .remove(
                            'hidden'
                        );

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
                        `Trasu se nepodařilo smazat: ${error.message}`;

                    reportError.classList.remove(
                        'hidden'
                    );
                } finally {
                    button.disabled = false;
                }
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

                    cell.appendChild(
                        editButton
                    );

                    if (
                        item.status
                        === 'draft'
                    ) {
                        const deleteButton =
                            document.createElement(
                                'button'
                            );

                        deleteButton.type =
                            'button';

                        deleteButton.className =
                            'route-action-button route-action-delete';

                        deleteButton.textContent =
                            'Smazat trasu';

                        deleteButton.addEventListener(
                            'click',
                            () =>
                                deleteDraftRoute(
                                    item,
                                    deleteButton
                                )
                        );

                        cell.appendChild(
                            deleteButton
                        );
                    }
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

                    row.appendChild(
                        createCell(
                            formatCzechDate(
                                item.service_date
                            )
                        )
                    );

                    row.appendChild(
                        createCell(
                            item.route_number
                        )
                    );

                    row.appendChild(
                        createCell(
                            item.performed_by_driver_name
                                || `\u0158idi\u010d ${item.performed_by_driver_id}`,
                            'route-driver-name-cell'
                        )
                    );

                    row.appendChild(
                        createCell(
                            item.performed_by_driver_external_id
                                || item.performed_by_driver_id,
                            'route-driver-id-cell'
                        )
                    );
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
                driverId: null,
                statusGroup: null,
            };

            const routeFiltersAreActive = () =>
                routeFilterState.monthKey !== null
                || routeFilterState.periodKey !== null
                || routeFilterState.from !== null
                || routeFilterState.to !== null
                || routeFilterState.driverId !== null
                || routeFilterState.statusGroup !== null
                || routeFilterState.selectedYear !== null;

            const clearRouteFilters = async () => {
                routeFilterState.initialized = true;
                routeFilterState.selectedYear = null;
                routeFilterState.monthKey = null;
                routeFilterState.periodKey = null;
                routeFilterState.from = null;
                routeFilterState.to = null;
                routeFilterState.driverId = null;
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

            const routeFilterPeriodLabel = (navigation) => {
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

                if (routeFilterState.periodKey) {
                    const quickPeriod =
                        navigation?.quick_periods?.[
                            routeFilterState.periodKey
                        ];

                    if (quickPeriod?.label) {
                        return String(
                            quickPeriod.label
                        );
                    }
                }

                return '';
            };

            const routeDriverDisplayLabel = (driver) => {
                if (!driver) {
                    return '';
                }

                const name =
                    String(
                        driver?.name || ''
                    ).trim();

                const externalId =
                    String(
                        driver?.external_driver_id || ''
                    ).trim();

                return [
                    name,
                    externalId,
                ]
                    .filter(Boolean)
                    .join(' \u00b7 ');
            };
            const routeDriverQuickLimit = 4;

            const normalizeDriverSearchValue = (value) =>
                String(value || '')
                    .normalize('NFD')
                    .replace(
                        /[\u0300-\u036f]/g,
                        ''
                    )
                    .toLocaleLowerCase(
                        'cs-CZ'
                    )
                    .trim();

            const routeDriverSearchValue = (driver) =>
                normalizeDriverSearchValue(
                    [
                        driver?.name,
                        driver?.external_driver_id,
                    ]
                        .filter(Boolean)
                        .join(' ')
                );

            const routeDriverRecentSort = (
                left,
                right
            ) => {
                const leftDate =
                    String(
                        left?.last_service_date
                        || ''
                    );

                const rightDate =
                    String(
                        right?.last_service_date
                        || ''
                    );

                const dateComparison =
                    rightDate.localeCompare(
                        leftDate
                    );

                if (dateComparison !== 0) {
                    return dateComparison;
                }

                return routeDriverDisplayLabel(
                    left
                ).localeCompare(
                    routeDriverDisplayLabel(
                        right
                    ),
                    'cs-CZ'
                );
            };

            const routeDriverAlphabeticSort = (
                left,
                right
            ) =>
                routeDriverDisplayLabel(
                    left
                ).localeCompare(
                    routeDriverDisplayLabel(
                        right
                    ),
                    'cs-CZ'
                );
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

                const driverButtons =
                    document.getElementById(
                        'routeDriverButtons'
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
                driverButtons.replaceChildren();
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
                            key !== 'yesterday'
                            && Number(period?.total || 0) <= 0
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

                const drivers =
                    Array.isArray(
                        navigation?.drivers
                    )
                        ? navigation.drivers
                        : [];

                const driverSearchInput =
                    document.getElementById(
                        'routeDriverSearch'
                    );

                const driverSearchResults =
                    document.getElementById(
                        'routeDriverSearchResults'
                    );

                const selectedDriver =
                    drivers.find(
                        (driver) =>
                            Number(driver?.id)
                            === routeFilterState.driverId
                    )
                    || null;

                const recentActiveDrivers =
                    drivers
                        .filter(
                            (driver) =>
                                driver?.active
                                !== false
                        )
                        .slice()
                        .sort(
                            routeDriverRecentSort
                        );

                let quickDrivers =
                    recentActiveDrivers.slice(
                        0,
                        routeDriverQuickLimit
                    );

                /*
                 * A driver selected from search remains visible even
                 * when they are not one of the four most recent.
                 */
                if (
                    selectedDriver
                    && !quickDrivers.some(
                        (driver) =>
                            Number(driver?.id)
                            === Number(
                                selectedDriver?.id
                            )
                    )
                ) {
                    quickDrivers = [
                        selectedDriver,
                        ...recentActiveDrivers
                            .filter(
                                (driver) =>
                                    Number(driver?.id)
                                    !== Number(
                                        selectedDriver?.id
                                    )
                            )
                            .slice(
                                0,
                                Math.max(
                                    0,
                                    routeDriverQuickLimit
                                    - 1
                                )
                            ),
                    ];
                }

                driverButtons.appendChild(
                    routeFilterButton(
                        'V\u0161ichni \u0159idi\u010di',
                        routeFilterState.driverId
                            === null,
                        async () => {
                            routeFilterState.driverId =
                                null;

                            if (driverSearchInput) {
                                driverSearchInput.value =
                                    '';
                            }

                            if (driverSearchResults) {
                                driverSearchResults
                                    .classList
                                    .add(
                                        'hidden'
                                    );
                            }

                            await loadReports();
                        }
                    )
                );

                quickDrivers.forEach(
                    (driver) => {
                        const driverId =
                            Number(
                                driver?.id
                            );

                        if (
                            !Number.isInteger(
                                driverId
                            )
                            || driverId <= 0
                        ) {
                            return;
                        }

                        const driverLabel =
                            routeDriverDisplayLabel(
                                driver
                            )
                            || `\u0158idi\u010d ${driverId}`;

                        driverButtons.appendChild(
                            routeFilterButton(
                                driverLabel,
                                routeFilterState.driverId
                                    === driverId,
                                async () => {
                                    routeFilterState.driverId =
                                        driverId;

                                    if (
                                        driverSearchInput
                                    ) {
                                        driverSearchInput.value =
                                            '';
                                    }

                                    if (
                                        driverSearchResults
                                    ) {
                                        driverSearchResults
                                            .classList
                                            .add(
                                                'hidden'
                                            );
                                    }

                                    await loadReports();
                                }
                            )
                        );
                    }
                );

                const renderDriverSearchResults =
                    () => {
                        if (
                            !driverSearchInput
                            || !driverSearchResults
                        ) {
                            return;
                        }

                        const searchValue =
                            normalizeDriverSearchValue(
                                driverSearchInput.value
                            );

                        const matchingDrivers =
                            drivers
                                .filter(
                                    (driver) =>
                                        searchValue === ''
                                        || routeDriverSearchValue(
                                            driver
                                        ).includes(
                                            searchValue
                                        )
                                )
                                .slice()
                                .sort(
                                    routeDriverAlphabeticSort
                                );

                        driverSearchResults
                            .replaceChildren();

                        if (
                            matchingDrivers.length
                            === 0
                        ) {
                            const empty =
                                document.createElement(
                                    'div'
                                );

                            empty.className =
                                'route-driver-search-empty';

                            empty.textContent =
                                'Nenalezen \u017e\u00e1dn\u00fd \u0159idi\u010d.';

                            driverSearchResults
                                .appendChild(
                                    empty
                                );

                            driverSearchResults
                                .classList
                                .remove(
                                    'hidden'
                                );

                            return;
                        }

                        matchingDrivers.forEach(
                            (driver) => {
                                const driverId =
                                    Number(
                                        driver?.id
                                    );

                                if (
                                    !Number.isInteger(
                                        driverId
                                    )
                                    || driverId <= 0
                                ) {
                                    return;
                                }

                                const button =
                                    document.createElement(
                                        'button'
                                    );

                                button.type =
                                    'button';

                                button.className =
                                    'route-driver-search-result';

                                if (
                                    routeFilterState.driverId
                                    === driverId
                                ) {
                                    button.classList.add(
                                        'active'
                                    );
                                }

                                const identity =
                                    document.createElement(
                                        'span'
                                    );

                                identity.className =
                                    'route-driver-search-identity';

                                identity.textContent =
                                    routeDriverDisplayLabel(
                                        driver
                                    )
                                    || `\u0158idi\u010d ${driverId}`;

                                const meta =
                                    document.createElement(
                                        'span'
                                    );

                                meta.className =
                                    'route-driver-search-result-meta';

                                meta.textContent =
                                    driver?.last_service_date
                                        ? `Posledn\u00ed trasa ${formatCzechDate(driver.last_service_date)}`
                                        : '';

                                button.append(
                                    identity,
                                    meta
                                );

                                button.onclick =
                                    async () => {
                                        routeFilterState.driverId =
                                            driverId;

                                        driverSearchInput.value =
                                            '';

                                        driverSearchResults
                                            .classList
                                            .add(
                                                'hidden'
                                            );

                                        await loadReports();
                                    };

                                driverSearchResults
                                    .appendChild(
                                        button
                                    );
                            }
                        );

                        driverSearchResults
                            .classList
                            .remove(
                                'hidden'
                            );
                    };

                if (driverSearchInput) {
                    driverSearchInput.onfocus =
                        () => {
                            renderDriverSearchResults();
                        };

                    driverSearchInput.oninput =
                        () => {
                            renderDriverSearchResults();
                        };

                    driverSearchInput.onkeydown =
                        (event) => {
                            if (
                                event.key
                                === 'Escape'
                            ) {
                                driverSearchResults
                                    ?.classList
                                    .add(
                                        'hidden'
                                    );

                                driverSearchInput
                                    .blur();
                            }
                        };

                    driverSearchInput.onblur =
                        () => {
                            window.setTimeout(
                                () => {
                                    driverSearchResults
                                        ?.classList
                                        .add(
                                            'hidden'
                                        );
                                },
                                150
                            );
                        };
                }
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
                    routeFilterPeriodLabel(navigation);

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
                    `\u0158idi\u010d: ${routeDriverDisplayLabel(selectedDriver) || 'V\u0161ichni \u0159idi\u010di'}`
                );

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
                    routeFilterState.driverId !== null
                ) {
                    params.set(
                        'performed_by_driver_id',
                        String(
                            routeFilterState.driverId
                        )
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
            /*
             * DRAYVIA-24C
             *
             * Each route-history request receives a monotonically
             * increasing sequence number. Only the newest request is
             * allowed to alter the route table or filter UI.
             *
             * This prevents a slower response for an older filter
             * selection from overwriting the current driver selection.
             */
            let routeHistoryLoadSequence = 0;

            const loadReports = async () => {
                const loadSequence =
                    ++routeHistoryLoadSequence;

                /*
                 * Freeze the complete query and driver selection at
                 * the moment this request starts.
                 */
                const query =
                    buildRouteHistoryQuery();

                const requestedDriverId =
                    routeFilterState.driverId;

                reportError.classList.add(
                    'hidden'
                );

                refreshButton.disabled = true;

                const isCurrentLoad = () =>
                    loadSequence
                    === routeHistoryLoadSequence;

                try {
                    await loadPerformancePolicyConfiguration();

                    if (!isCurrentLoad()) {
                        return;
                    }

                    const body = await api(
                        `/api/v1/daily-reports?${query}`
                    );

                    if (!isCurrentLoad()) {
                        return;
                    }

                    const payload =
                        getPayload(body) || {};

                    const navigation =
                        payload.navigation || {};

                    if (
                        initializeRouteHistoryPeriod(
                            navigation
                        )
                    ) {
                        if (isCurrentLoad()) {
                            await loadReports();
                        }

                        return;
                    }

                    const completeHistory =
                        await loadCompleteRouteHistory(
                            payload,
                            query
                        );

                    /*
                     * Another filter click may have happened while
                     * additional API pages were loading.
                     */
                    if (!isCurrentLoad()) {
                        return;
                    }

                    /*
                     * Driver state must still be exactly the state
                     * captured when this request began.
                     */
                    if (
                        routeFilterState.driverId
                        !== requestedDriverId
                    ) {
                        return;
                    }

                    const items =
                        Array.isArray(
                            completeHistory?.items
                        )
                            ? completeHistory.items
                            : [];

                    /*
                     * Hard safety invariant:
                     * a selected driver may NEVER display a route
                     * belonging to another driver.
                     */
                    if (
                        requestedDriverId !== null
                    ) {
                        const foreignRoutes =
                            items.filter(
                                (item) =>
                                    Number(
                                        item?.performed_by_driver_id
                                    )
                                    !== Number(
                                        requestedDriverId
                                    )
                            );

                        if (
                            foreignRoutes.length > 0
                        ) {
                            console.error(
                                'DRAYVIA driver-filter invariant failed.',
                                {
                                    requestedDriverId,
                                    foreignRoutes,
                                }
                            );

                            throw new Error(
                                'Driver filter returned foreign routes.'
                            );
                        }
                    }

                    if (!isCurrentLoad()) {
                        return;
                    }

                    renderReports(
                        items,
                        completeHistory.pagination
                    );

                    renderRouteHistoryFilters(
                        navigation,
                        completeHistory.pagination
                    );
                } catch (error) {
                    /*
                     * An obsolete request must never clear or replace
                     * the result of a newer filter selection.
                     */
                    if (!isCurrentLoad()) {
                        return;
                    }

                    if (error.status === 401) {
                        clearSession();

                        showLogin(
                            'P\u0159ihl\u00e1\u0161en\u00ed vypr\u0161elo. P\u0159ihlaste se znovu.'
                        );

                        return;
                    }

                    reportError.textContent =
                        `Trasy se nepoda\u0159ilo na\u010d\u00edst: ${error.message}`;

                    reportError.classList.remove(
                        'hidden'
                    );

                    renderReports(
                        [],
                        {}
                    );
                } finally {
                    /*
                     * An older request must not re-enable UI controls
                     * while a newer request is still running.
                     */
                    if (isCurrentLoad()) {
                        refreshButton.disabled =
                            false;
                    }
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
                    'Vyberte datum jízdy. DRAYVIA načte formulář platný pro tento den.';
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

<div id="drayviaPreviewLayer" class="drayvia-preview-layer" aria-hidden="true">
    <div class="drayvia-preview-scroll">
        <div id="drayviaPreviewContent" class="drayvia-preview-container"></div>
    </div>
</div>

<script>
(() => {
    const layer = document.getElementById('drayviaPreviewLayer');
    const content = document.getElementById('drayviaPreviewContent');
    const sidebar = document.querySelector('.sidebar');

    if (!layer || !content) {
        return;
    }

    const syncPreviewPosition = () => {
        if (!sidebar) {
            return;
        }

        layer.style.left = `${Math.ceil(sidebar.getBoundingClientRect().width)}px`;
    };

    syncPreviewPosition();
    window.addEventListener('resize', syncPreviewPosition);

    const monthNames = [
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
        'Prosinec'
    ];

    /* DRAYVIA-16G PERIOD SELECTOR */

    let periodMode = 'month';
    let selectedMonth = '2026-07';
    let selectedYear = '2026';

    const currentMonthValue = () => {
        const now = new Date();

        return (
            `${now.getFullYear()}-` +
            `${String(now.getMonth() + 1).padStart(2, '0')}`
        );
    };

    const currentYearValue = () => {
        return String(
            new Date().getFullYear()
        );
    };

    const monthLabelFromValue = (value) => {
        const [year, month] =
            String(value)
                .split('-')
                .map(Number);

        if (
            !year ||
            !month ||
            !monthNames[month - 1]
        ) {
            return '—';
        }

        return `${monthNames[month - 1]} ${year}`;
    };

    const monthLabel = () => {
        if (periodMode === 'current_month') {
            return monthLabelFromValue(
                currentMonthValue()
            );
        }

        if (periodMode === 'month') {
            return monthLabelFromValue(
                selectedMonth
            );
        }

        if (periodMode === 'current_year') {
            return currentYearValue();
        }

        if (periodMode === 'year') {
            return selectedYear;
        }

        if (periodMode === 'all') {
            return 'Vše';
        }

        return monthLabelFromValue(
            selectedMonth
        );
    };

    const periodYearOptions = () => {
        const current =
            Number(currentYearValue());

        const years = [];

        for (
            let year = current + 1;
            year >= 2015;
            year--
        ) {
            years.push(`
                <option
                    value="${year}"
                    ${
                        String(year) === String(selectedYear)
                            ? 'selected'
                            : ''
                    }
                >
                    ${year}
                </option>
            `);
        }

        return years.join('');
    };

    const periodModeOptions = () => {
        const calendarMode =
            currentPage === 'calendar';

        return `
            <option
                value="current_month"
                ${
                    periodMode === 'current_month'
                        ? 'selected'
                        : ''
                }
            >
                Aktuální měsíc
            </option>

            <option
                value="month"
                ${
                    periodMode === 'month'
                        ? 'selected'
                        : ''
                }
            >
                Vybrat měsíc
            </option>

            ${
                calendarMode
                    ? ''
                    : `
                        <option
                            value="current_year"
                            ${
                                periodMode === 'current_year'
                                    ? 'selected'
                                    : ''
                            }
                        >
                            Aktuální rok
                        </option>

                        <option
                            value="year"
                            ${
                                periodMode === 'year'
                                    ? 'selected'
                                    : ''
                            }
                        >
                            Vybrat rok
                        </option>

                        <option
                            value="all"
                            ${
                                periodMode === 'all'
                                    ? 'selected'
                                    : ''
                            }
                        >
                            Vše
                        </option>
                    `
            }
        `;
    };

    const periodDetailControl = () => {
        if (periodMode === 'month') {
            return `
                <input
                    id="drayviaPreviewMonth"
                    type="month"
                    value="${selectedMonth}"
                >
            `;
        }

        if (periodMode === 'year') {
            return `
                <select id="drayviaPreviewYear">
                    ${periodYearOptions()}
                </select>
            `;
        }

        if (periodMode === 'current_month') {
            return `
                <div class="drayvia-period-value">
                    ${monthLabelFromValue(
                        currentMonthValue()
                    )}
                </div>
            `;
        }

        if (periodMode === 'current_year') {
            return `
                <div class="drayvia-period-value">
                    ${currentYearValue()}
                </div>
            `;
        }

        return `
            <div class="drayvia-period-value">
                Všechna období
            </div>
        `;
    };

    const periodControl = () => `
        <div class="drayvia-period-control drayvia-period-control-extended">

            <label>OBDOBÍ</label>

            <select id="drayviaPeriodMode">
                ${periodModeOptions()}
            </select>

            <div class="drayvia-period-detail">
                ${periodDetailControl()}
            </div>

        </div>
    `;
    const header = (title, description) => `
        <div class="drayvia-preview-topbar">
            <div>
                <div class="drayvia-preview-eyebrow">Interní provoz</div>
                <h1 class="drayvia-preview-title">${title}</h1>
                <p class="drayvia-preview-description">${description}</p>
            </div>
            ${periodControl()}
        </div>
    `;

    const overview = () => `
        ${header(
            'Přehled',
            'Stav zvoleného měsíce a vše, co je potřeba dokončit před jeho uzavřením.'
        )}

        <div class="drayvia-overview-module-grid">

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Trasy</div>
                <div class="drayvia-preview-card-value">—</div>
                <div class="drayvia-preview-card-note">
                    Zapsané trasy a položky čekající na kontrolu.
                </div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Řidiči</div>
                <div class="drayvia-preview-card-value">—</div>
                <div class="drayvia-preview-card-note">
                    Dostupnost a stav řidičů ve zvoleném období.
                </div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">PHM</div>
                <div class="drayvia-preview-card-value">— Kč</div>
                <div class="drayvia-preview-card-note">
                    Náklady na PHM a položky čekající na kontrolu.
                </div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Finance</div>
                <div class="drayvia-preview-card-value">— Kč</div>
                <div class="drayvia-preview-card-note">
                    Výpočty, PP, vyúčtování a fakturace.
                </div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Banka</div>
                <div class="drayvia-preview-card-value">—</div>
                <div class="drayvia-preview-card-note">
                    Nepřiřazené nebo nezkontrolované transakce.
                </div>
            </div>

        </div>

        <div class="drayvia-overview-layout">

            <div class="drayvia-preview-panel">
                <div class="drayvia-preview-panel-head">
                    <h2 class="drayvia-preview-panel-title">
                        STAV OBDOBÍ – ${monthLabel()}
                    </h2>
                    <div class="drayvia-preview-panel-subtitle">
                        Měsíc uzavřeme až po dokončení všech provozních a finančních kroků.
                    </div>
                </div>

                <div class="drayvia-preview-panel-body">
                    <div class="drayvia-preview-checklist">

                        <div class="drayvia-preview-check">
                            <span>Trasy zapsány</span>
                            <span class="drayvia-preview-pill">
                                Čeká
                            </span>
                        </div>

                        <div class="drayvia-preview-check">
                            <span>Trasy zkontrolovány</span>
                            <span class="drayvia-preview-pill">
                                Čeká
                            </span>
                        </div>

                        <div class="drayvia-preview-check">
                            <span>PHM importováno a zkontrolováno</span>
                            <span class="drayvia-preview-pill">
                                Čeká
                            </span>
                        </div>

                        <div class="drayvia-preview-check">
                            <span>Vyúčtování řidičů dokončeno</span>
                            <span class="drayvia-preview-pill">
                                Čeká
                            </span>
                        </div>

                        <div class="drayvia-preview-check">
                            <span>Fakturace firmy dokončena</span>
                            <span class="drayvia-preview-pill">
                                Čeká
                            </span>
                        </div>

                        <div class="drayvia-preview-check">
                            <span>Bankovní transakce zpracovány</span>
                            <span class="drayvia-preview-pill">
                                Čeká
                            </span>
                        </div>

                        <div class="drayvia-preview-check drayvia-month-close-row">
                            <strong>OBDOBÍ UZAVŘENO</strong>
                            <span class="drayvia-preview-pill warning">
                                ROZPRACOVÁNO
                            </span>
                        </div>

                    </div>
                </div>
            </div>

            <div class="drayvia-preview-panel">
                <div class="drayvia-preview-panel-head">
                    <h2 class="drayvia-preview-panel-title">
                        VYŽADUJE POZORNOST
                    </h2>
                    <div class="drayvia-preview-panel-subtitle">
                        Pouze věci, které brání dokončení období.
                    </div>
                </div>

                <div class="drayvia-preview-panel-body">

                    <div class="drayvia-attention-item">
                        <div>
                            <strong>Trasy</strong>
                            <span>Bez nevyřešených kontrol</span>
                        </div>
                        <span class="drayvia-preview-pill success">
                            OK
                        </span>
                    </div>

                    <div class="drayvia-attention-item">
                        <div>
                            <strong>PHM</strong>
                            <span>Bez nevyřešených položek</span>
                        </div>
                        <span class="drayvia-preview-pill success">
                            OK
                        </span>
                    </div>

                    <div class="drayvia-attention-item">
                        <div>
                            <strong>Finance</strong>
                            <span>Výpočty a fakturace</span>
                        </div>
                        <span class="drayvia-preview-pill">
                            ČEKÁ
                        </span>
                    </div>

                    <div class="drayvia-attention-item">
                        <div>
                            <strong>Banka</strong>
                            <span>Párování plateb</span>
                        </div>
                        <span class="drayvia-preview-pill">
                            ČEKÁ
                        </span>
                    </div>

                </div>
            </div>

        </div>
    `;
const calendarJuly2026 = {
        workingDays: 22,

        drivers: [
            {
                key: 'vit',
                name: 'Hrůza Vít',
                shortName: 'HRŮZA VÍT',
                working: [1, 2, 7, 8, 9, 13, 14, 15, 16, 17, 28, 29, 30, 31]
            },
            {
                key: 'vojtech',
                name: 'Hrůza Vojtěch',
                shortName: 'HRŮZA VOJTĚCH',
                working: [1, 2, 3, 8, 9, 14, 15, 17, 20, 21, 22, 24, 28, 29, 30, 31]
            },
            {
                key: 'dominik',
                name: 'Kökörčený Dominik',
                shortName: 'KÖKÖRČENÝ DOMINIK',
                working: [1, 2, 3, 7, 8, 10, 15, 20, 21, 22, 23, 24, 27, 28, 29, 31]
            },
            {
                key: 'milos',
                name: 'Kökörčený Miloš',
                shortName: 'KÖKÖRČENÝ MILOŠ',
                working: [1, 2, 3, 7, 8, 9, 10, 13, 15, 16, 17, 20, 21, 22, 23, 24, 27, 30, 31]
            }
        ]
    };

    const calendarDayNames = [
        'NE',
        'PO',
        'ÚT',
        'ST',
        'ČT',
        'PÁ',
        'SO'
    ];

    const calendarDaysInMonth = () => {
        const [year, month] = selectedMonth
            .split('-')
            .map(Number);

        return new Date(
            year,
            month,
            0
        ).getDate();
    };

    const calendarDayMeta = (day) => {
        const [year, month] = selectedMonth
            .split('-')
            .map(Number);

        const date = new Date(
            year,
            month - 1,
            day,
            12,
            0,
            0
        );

        const weekDay = date.getDay();
        const isWeekend =
            weekDay === 0 ||
            weekDay === 6;

        const isJuly2026Holiday =
            selectedMonth === '2026-07' &&
            day === 6;

        let type = 'Pracovní den';
        let css = 'workday';

        if (isWeekend) {
            type = 'Volný den';
            css = 'weekend';
        }
        else if (isJuly2026Holiday) {
            type = 'Státní svátek';
            css = 'holiday';
        }

        return {
            weekDay: calendarDayNames[weekDay],
            type,
            css
        };
    };

    const calendarStatus = (
        driver,
        day
    ) => {
        if (selectedMonth !== '2026-07') {
            return null;
        }

        return driver.working.includes(day)
            ? 'Pracuji'
            : 'Volno';
    };

    const calendarRows = () => {
        const rows = [];
        const days = calendarDaysInMonth();

        for (let day = 1; day <= days; day++) {
            const meta = calendarDayMeta(day);

            const driverCells = calendarJuly2026.drivers
                .map((driver) => {
                    const status = calendarStatus(
                        driver,
                        day
                    );

                    if (!status) {
                        return `
                            <td class="drayvia-calendar-status-cell">
                                <span class="drayvia-calendar-status unset">
                                    —
                                </span>
                            </td>
                        `;
                    }

                    const statusClass =
                        status === 'Pracuji'
                            ? 'working'
                            : 'off';

                    return `
                        <td class="drayvia-calendar-status-cell">
                            <span class="drayvia-calendar-status ${statusClass}">
                                ${status.toUpperCase()}
                            </span>
                        </td>
                    `;
                })
                .join('');

            rows.push(`
                <tr class="drayvia-month-calendar-row ${meta.css}">
                    <td class="drayvia-calendar-date">
                        ${String(day).padStart(2, '0')}.${selectedMonth.slice(5, 7)}.${selectedMonth.slice(0, 4)}
                    </td>

                    <td class="drayvia-calendar-weekday">
                        ${meta.weekDay}
                    </td>

                    <td>
                        <span class="drayvia-calendar-day-type ${meta.css}">
                            ${meta.type}
                        </span>
                    </td>

                    ${driverCells}
                </tr>
            `);
        }

        return rows.join('');
    };

    const calendarSummary = () => {
        if (selectedMonth !== '2026-07') {
            return `
                <div class="drayvia-calendar-summary-grid">
                    <div class="drayvia-preview-card">
                        <div class="drayvia-preview-card-label">
                            Pracovní dny
                        </div>
                        <div class="drayvia-preview-card-value">
                            —
                        </div>
                    </div>

                    ${calendarJuly2026.drivers.map((driver) => `
                        <div class="drayvia-preview-card">
                            <div class="drayvia-preview-card-label">
                                ${driver.name}
                            </div>
                            <div class="drayvia-preview-card-value">
                                —
                            </div>
                            <div class="drayvia-preview-card-note">
                                Pracovní dny řidiče
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        return `
            <div class="drayvia-calendar-summary-grid">

                <div class="drayvia-preview-card">
                    <div class="drayvia-preview-card-label">
                        Pracovní dny
                    </div>
                    <div class="drayvia-preview-card-value">
                        ${calendarJuly2026.workingDays}
                    </div>
                    <div class="drayvia-preview-card-note">
                        Celkem v měsíci
                    </div>
                </div>

                ${calendarJuly2026.drivers.map((driver) => `
                    <div class="drayvia-preview-card">
                        <div class="drayvia-preview-card-label">
                            ${driver.name}
                        </div>
                        <div class="drayvia-preview-card-value">
                            ${driver.working.length}
                        </div>
                        <div class="drayvia-preview-card-note">
                            dnů PRACUJI
                        </div>
                    </div>
                `).join('')}

            </div>
        `;
    };

    const calendarLegend = () => `
        <div class="drayvia-calendar-legend">

            <div class="drayvia-calendar-legend-item">
                <span class="drayvia-calendar-status working">
                    PRACUJI
                </span>
                <span>Řidič je plánován do práce.</span>
            </div>

            <div class="drayvia-calendar-legend-item">
                <span class="drayvia-calendar-status off">
                    VOLNO
                </span>
                <span>Plánované volno.</span>
            </div>

            <div class="drayvia-calendar-legend-item">
                <span class="drayvia-calendar-status vacation">
                    DOVOLENÁ
                </span>
                <span>Řidič čerpá dovolenou.</span>
            </div>

            <div class="drayvia-calendar-legend-item">
                <span class="drayvia-calendar-status sick">
                    NEMOC
                </span>
                <span>Řidič je nemocný.</span>
            </div>

            <div class="drayvia-calendar-legend-item automatic">
                <span class="drayvia-calendar-status unused">
                    NEVYUŽIT
                </span>
                <span>
                    Automaticky po 2 dnech, pokud byl plán PRACUJI,
                    ale řidič nemá žádnou zapsanou trasu.
                </span>
            </div>

        </div>
    `;
    const calendar = () => `
        ${header(
            'Kalendář',
            'Měsíční dostupnost řidičů. Přehled pracovních dnů, volna, víkendů a státních svátků.'
        )}

        ${calendarSummary()}

        ${calendarLegend()}

        <div class="drayvia-preview-panel drayvia-calendar-panel">

            <div class="drayvia-preview-panel-head">
                <h2 class="drayvia-preview-panel-title">
                    DOSTUPNOST ŘIDIČŮ – ${monthLabel()}
                </h2>

                <div class="drayvia-preview-panel-subtitle">
                    Po spuštění bude možné stav PRACUJI / VOLNO měnit přímo v kalendáři.
                </div>
            </div>

            <div class="drayvia-month-calendar-wrap">
                <table class="drayvia-month-calendar">

                    <thead>
                        <tr>
                            <th>DATUM</th>
                            <th>DEN</th>
                            <th>TYP DNE</th>

                            ${calendarJuly2026.drivers.map((driver) => `
                                <th>
                                    ${driver.shortName}
                                </th>
                            `).join('')}
                        </tr>
                    </thead>

                    <tbody>
                        ${calendarRows()}
                    </tbody>

                </table>
            </div>
        </div>
    `;
    const realDriverState = {
        items: []
    };

    const driverText = (value) => {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    /* DRAYVIA-16F4 DATA ITEMS FIX */

    const realDriverItems = (body) => {
        if (Array.isArray(body)) {
            return body;
        }

        if (Array.isArray(body?.data?.items)) {
            return body.data.items;
        }

        if (Array.isArray(body?.data)) {
            return body.data;
        }

        if (Array.isArray(body?.drivers)) {
            return body.drivers;
        }

        if (Array.isArray(body?.data?.drivers)) {
            return body.data.drivers;
        }

        return [];
    };
    const driverStatusLabel = (status) => {
        const labels = {
            active: 'AKTIVNÍ',
            inactive: 'NEAKTIVNÍ',
            suspended: 'POZASTAVEN'
        };

        return labels[status] ||
            String(status || '—').toUpperCase();
    };

    const setRealDriverMessage = (
        message = '',
        type = ''
    ) => {
        const box =
            document.getElementById(
                'drayviaRealDriverMessage'
            );

        if (!box) {
            return;
        }

        box.textContent = message;
        box.className = 'drayvia-real-driver-message';

        if (type) {
            box.classList.add(type);
        }

        box.hidden = !message;
    };

    const driverAssignmentDate = (value) => {
        if (!value) {
            return '—';
        }

        const parts =
            String(value).split('-');

        if (parts.length !== 3) {
            return String(value);
        }

        return `${parts[2]}.${parts[1]}.${parts[0]}`;
    };

    const driverAssignmentValidity = (assignment) => {
        if (!assignment) {
            return '—';
        }

        const from =
            driverAssignmentDate(
                assignment.valid_from
            );

        if (!assignment.valid_until) {
            return `OD ${from}`;
        }

        return `${from} – ${driverAssignmentDate(
            assignment.valid_until
        )}`;
    };

    const driverEmploymentLabel = (
        employmentType
    ) => {
        const labels = {
            employee: 'ZAMĚSTNANEC',
            dpp: 'DPP',
            dpc: 'DPČ',
            other: 'JINÁ',
        };

        return labels[employmentType] ?? '—';
    };

    const driverTodayValue = () => {
        const now =
            new Date();

        return (
            `${now.getFullYear()}-`
            + `${String(
                now.getMonth() + 1
            ).padStart(2, '0')}-`
            + `${String(
                now.getDate()
            ).padStart(2, '0')}`
        );
    };

    const driverMonthEndValue = (
        monthValue
    ) => {
        const [year, month] =
            String(monthValue)
                .split('-')
                .map(Number);

        if (
            !Number.isInteger(year)
            || !Number.isInteger(month)
            || month < 1
            || month > 12
        ) {
            return driverTodayValue();
        }

        const lastDay =
            new Date(
                year,
                month,
                0
            ).getDate();

        return (
            `${year}-`
            + `${String(month).padStart(2, '0')}-`
            + `${String(lastDay).padStart(2, '0')}`
        );
    };

    const driverPeriodReferenceDate = () => {
        const today =
            driverTodayValue();

        if (
            periodMode === 'current_month'
        ) {
            return today;
        }

        if (
            periodMode === 'month'
        ) {
            if (
                selectedMonth ===
                currentMonthValue()
            ) {
                return today;
            }

            return driverMonthEndValue(
                selectedMonth
            );
        }

        if (
            periodMode === 'current_year'
        ) {
            return today;
        }

        if (
            periodMode === 'year'
        ) {
            if (
                selectedYear ===
                currentYearValue()
            ) {
                return today;
            }

            return `${selectedYear}-12-31`;
        }

        return today;
    };

    const driverAssignmentAtDate = (
        driver,
        referenceDate
    ) => {
        const items =
            Array.isArray(
                driver.organization_assignment_items
            )
                ? driver.organization_assignment_items
                : [];

        return items.find(
            (assignment) => {
                if (
                    assignment.valid_from >
                    referenceDate
                ) {
                    return false;
                }

                return (
                    assignment.valid_until === null
                    || assignment.valid_until >=
                        referenceDate
                );
            }
        ) ?? null;
    };

    const driverClosestAssignment = (
        driver,
        referenceDate
    ) => {
        const items =
            Array.isArray(
                driver.organization_assignment_items
            )
                ? [...driver.organization_assignment_items]
                : [];

        if (items.length === 0) {
            return null;
        }

        const past =
            items
                .filter(
                    (assignment) =>
                        assignment.valid_until !== null
                        && assignment.valid_until <
                            referenceDate
                )
                .sort(
                    (left, right) =>
                        String(
                            right.valid_until
                        ).localeCompare(
                            String(
                                left.valid_until
                            )
                        )
                );

        if (past.length > 0) {
            return {
                assignment: past[0],
                relation: 'ended',
            };
        }

        const future =
            items
                .filter(
                    (assignment) =>
                        assignment.valid_from >
                        referenceDate
                )
                .sort(
                    (left, right) =>
                        String(
                            left.valid_from
                        ).localeCompare(
                            String(
                                right.valid_from
                            )
                        )
                );

        if (future.length > 0) {
            return {
                assignment: future[0],
                relation: 'scheduled',
            };
        }

        return null;
    };

    const driverAssignmentPresentation = (
        driver
    ) => {
        const referenceDate =
            driverPeriodReferenceDate();

        const active =
            driverAssignmentAtDate(
                driver,
                referenceDate
            );

        if (active) {
            const isExternal =
                active.organization_type !==
                'master';

            return {
                assignment: active,
                referenceDate,
                type:
                    isExternal
                        ? 'EXTERNÍ'
                        : 'VLASTNÍ',
                status: 'AKTIVNÍ',
                tone: 'active',
                isExternal,
                activeAtReferenceDate: true,
            };
        }

        const closest =
            driverClosestAssignment(
                driver,
                referenceDate
            );

        if (!closest) {
            return {
                assignment: null,
                referenceDate,
                type: 'BEZ PŘIŘAZENÍ',
                status: 'BEZ PŘIŘAZENÍ',
                tone: 'warning',
                isExternal: false,
                activeAtReferenceDate: false,
            };
        }

        const assignment =
            closest.assignment;

        const isExternal =
            assignment.organization_type !==
            'master';

        if (
            closest.relation ===
            'scheduled'
        ) {
            return {
                assignment,
                referenceDate,
                type:
                    isExternal
                        ? 'EXTERNÍ – PLÁNOVÁNO'
                        : 'VLASTNÍ – PLÁNOVÁNO',
                status: 'PLÁNOVÁNO',
                tone: 'warning',
                isExternal,
                activeAtReferenceDate: false,
            };
        }

        return {
            assignment,
            referenceDate,
            type:
                isExternal
                    ? 'EXTERNÍ – UKONČENO'
                    : 'VLASTNÍ – UKONČENO',
            status: 'UKONČENO',
            tone: 'ended',
            isExternal,
            activeAtReferenceDate: false,
        };
    };
    const appendRealDriverTextCell = (
        row,
        value,
        options = {},
    ) => {
        const cell =
            document.createElement('td');

        cell.textContent =
            value === null
            || value === undefined
            || String(value).trim() === ''
                ? '—'
                : String(value);

        if (options.strong) {
            cell.style.fontWeight = '800';
        }

        if (options.muted) {
            cell.style.color = '#667085';
        }

        row.appendChild(cell);

        return cell;
    };

    const appendRealDriverBadgeCell = (
        row,
        text,
        tone = 'neutral',
    ) => {
        const cell =
            document.createElement('td');

        const badge =
            document.createElement('span');

        badge.textContent = text;

        badge.style.display = 'inline-flex';
        badge.style.alignItems = 'center';
        badge.style.padding = '5px 9px';
        badge.style.borderRadius = '999px';
        badge.style.fontSize = '11px';
        badge.style.fontWeight = '900';
        badge.style.letterSpacing = '.02em';
        badge.style.whiteSpace = 'nowrap';

        if (tone === 'active') {
            badge.style.background = '#ecfdf3';
            badge.style.color = '#067647';
        } else if (tone === 'external') {
            badge.style.background = '#eff8ff';
            badge.style.color = '#175cd3';
        } else if (tone === 'warning') {
            badge.style.background = '#fffaeb';
            badge.style.color = '#b54708';
        } else {
            badge.style.background = '#f2f4f7';
            badge.style.color = '#475467';
        }

        cell.appendChild(badge);
        row.appendChild(cell);

        return cell;
    };

    const realDriverFilterState = {
        search: '',
        type: 'all',
        carrier: 'all',
        employment: 'all',
        status: 'all',
    };

    const normalizeDriverSearchValue = (value) =>
        String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('cs')
            .trim();

    const driverPresentationStatusKey = (
        presentation
    ) => {
        if (presentation.activeAtReferenceDate) {
            return 'active';
        }

        if (presentation.status === 'UKONČENO') {
            return 'ended';
        }

        if (presentation.status === 'PLÁNOVÁNO') {
            return 'scheduled';
        }

        return 'unassigned';
    };

    const driverEmploymentKey = (
        assignment
    ) => {
        if (
            !assignment
            || assignment.organization_type !== 'master'
        ) {
            return 'none';
        }

        return assignment.employment_type ?? 'none';
    };

    const driverMatchesFilters = (
        driver,
        presentation
    ) => {
        const assignment =
            presentation.assignment;

        const search =
            normalizeDriverSearchValue(
                realDriverFilterState.search
            );

        if (search !== '') {
            const haystack =
                normalizeDriverSearchValue(
                    [
                        driver.first_name,
                        driver.last_name,
                        driver.full_name,
                        driver.external_driver_id,
                        assignment?.organization_name,
                        driverEmploymentLabel(
                            assignment?.employment_type
                        ),
                        presentation.type,
                        presentation.status,
                    ].join(' ')
                );

            if (!haystack.includes(search)) {
                return false;
            }
        }

        if (
            realDriverFilterState.type !== 'all'
        ) {
            const type =
                assignment
                    ? (
                        presentation.isExternal
                            ? 'external'
                            : 'own'
                    )
                    : 'unassigned';

            if (
                type !==
                realDriverFilterState.type
            ) {
                return false;
            }
        }

        if (
            realDriverFilterState.carrier !== 'all'
            && String(
                assignment?.organization_id ?? ''
            ) !== realDriverFilterState.carrier
        ) {
            return false;
        }

        if (
            realDriverFilterState.employment !== 'all'
            && driverEmploymentKey(
                assignment
            ) !== realDriverFilterState.employment
        ) {
            return false;
        }

        if (
            realDriverFilterState.status !== 'all'
            && driverPresentationStatusKey(
                presentation
            ) !== realDriverFilterState.status
        ) {
            return false;
        }

        return true;
    };

    const syncRealDriverFilterTone = (
        control,
        active
    ) => {
        if (!control) {
            return;
        }

        control.classList.toggle(
            'active',
            Boolean(active)
        );
    };

    const syncRealDriverFilterControls = (
        presentations
    ) => {
        const search =
            document.getElementById(
                'drayviaRealDriverSearch'
            );

        const type =
            document.getElementById(
                'drayviaRealDriverTypeFilter'
            );

        const carrier =
            document.getElementById(
                'drayviaRealDriverCarrierFilter'
            );

        const employment =
            document.getElementById(
                'drayviaRealDriverEmploymentFilter'
            );

        const status =
            document.getElementById(
                'drayviaRealDriverStatusFilter'
            );

        if (search) {
            search.value =
                realDriverFilterState.search;

            syncRealDriverFilterTone(
                search,
                normalizeDriverSearchValue(
                    realDriverFilterState.search
                ) !== ''
            );
        }

        if (type) {
            type.value =
                realDriverFilterState.type;

            syncRealDriverFilterTone(
                type,
                realDriverFilterState.type !==
                    'all'
            );
        }

        if (employment) {
            employment.value =
                realDriverFilterState.employment;

            syncRealDriverFilterTone(
                employment,
                realDriverFilterState.employment !==
                    'all'
            );
        }

        if (status) {
            status.value =
                realDriverFilterState.status;

            syncRealDriverFilterTone(
                status,
                realDriverFilterState.status !==
                    'all'
            );
        }

        if (!carrier) {
            return;
        }

        const organizations =
            new Map();

        presentations.forEach(
            (presentation) => {
                const assignment =
                    presentation.assignment;

                if (!assignment) {
                    return;
                }

                organizations.set(
                    String(
                        assignment.organization_id
                    ),
                    assignment.organization_name
                );
            }
        );

        const selected =
            realDriverFilterState.carrier;

        carrier.replaceChildren();

        const allOption =
            document.createElement(
                'option'
            );

        allOption.value = 'all';
        allOption.textContent =
            'VŠICHNI DOPRAVCI';

        carrier.appendChild(
            allOption
        );

        [...organizations.entries()]
            .sort(
                (left, right) =>
                    String(left[1])
                        .localeCompare(
                            String(right[1]),
                            'cs'
                        )
            )
            .forEach(
                ([id, name]) => {
                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value = id;
                    option.textContent = name;

                    carrier.appendChild(
                        option
                    );
                }
            );

        if (
            selected === 'all'
            || organizations.has(
                selected
            )
        ) {
            carrier.value =
                selected;
        }
        else {
            realDriverFilterState.carrier =
                'all';

            carrier.value =
                'all';
        }

        syncRealDriverFilterTone(
            carrier,
            realDriverFilterState.carrier !==
                'all'
        );
    };
    const renderRealDriverRows = () => {
        const target =
            document.getElementById(
                'drayviaRealDriverRows'
            );

        if (!target) {
            return;
        }

        const drivers =
            realDriverState.items;

        const presentations =
            drivers.map(
                (driver) =>
                    driverAssignmentPresentation(
                        driver
                    )
            );

        syncRealDriverFilterControls(
            presentations
        );

        const total =
            document.getElementById(
                'drayviaRealDriverTotal'
            );

        const own =
            document.getElementById(
                'drayviaRealDriverWithId'
            );

        const external =
            document.getElementById(
                'drayviaRealDriverWithoutId'
            );

        if (total) {
            total.textContent =
                String(drivers.length);
        }

        const ownCount =
            presentations.filter(
                (presentation) =>
                    presentation.activeAtReferenceDate
                    && !presentation.isExternal
            ).length;

        const externalCount =
            presentations.filter(
                (presentation) =>
                    presentation.activeAtReferenceDate
                    && presentation.isExternal
            ).length;

        if (own) {
            own.textContent =
                String(ownCount);
        }

        if (external) {
            external.textContent =
                String(externalCount);
        }

        const rows =
            drivers
                .map(
                    (driver, index) => ({
                        driver,
                        presentation:
                            presentations[index],
                    })
                )
                .filter(
                    ({
                        driver,
                        presentation,
                    }) =>
                        driverMatchesFilters(
                            driver,
                            presentation
                        )
                );

        const filteredCount =
            document.getElementById(
                'drayviaRealDriverFilteredCount'
            );

        if (filteredCount) {
            filteredCount.textContent =
                `ZOBRAZENO ${rows.length} Z ${drivers.length}`;
        }

        target.replaceChildren();

        if (rows.length === 0) {
            const row =
                document.createElement('tr');

            const cell =
                document.createElement('td');

            cell.colSpan = 8;
            cell.className =
                'drayvia-real-driver-empty';

            cell.textContent =
                'FILTRŮM NEODPOVÍDÁ ŽÁDNÝ ŘIDIČ';

            row.appendChild(cell);
            target.appendChild(row);

            return;
        }

        rows.forEach(
            ({
                driver,
                presentation,
            }) => {
                const assignment =
                    presentation.assignment;

                const row =
                    document.createElement('tr');

                const surname =
                    String(
                        driver.last_name ?? ''
                    ).trim();

                const firstName =
                    String(
                        driver.first_name ?? ''
                    ).trim();

                const displayName =
                    `${surname} ${firstName}`.trim()
                    || driver.full_name
                    || `ŘIDIČ ${driver.id}`;

                appendRealDriverTextCell(
                    row,
                    displayName,
                    {
                        strong: true,
                    }
                );

                appendRealDriverTextCell(
                    row,
                    driver.external_driver_id
                    ?? '—',
                    {
                        muted: true,
                    }
                );

                appendRealDriverBadgeCell(
                    row,
                    presentation.type,
                    presentation.isExternal
                        ? (
                            presentation.tone ===
                            'active'
                                ? 'external'
                                : presentation.tone
                        )
                        : presentation.tone
                );

                appendRealDriverTextCell(
                    row,
                    assignment
                        ?.organization_name
                    ?? '—'
                );

                appendRealDriverTextCell(
                    row,
                    assignment
                    && assignment.organization_type ===
                        'master'
                        ? driverEmploymentLabel(
                            assignment.employment_type
                        )
                        : '—'
                );

                appendRealDriverTextCell(
                    row,
                    driverAssignmentValidity(
                        assignment
                    ),
                    {
                        muted: true,
                    }
                );

                appendRealDriverBadgeCell(
                    row,
                    presentation.status,
                    presentation.tone
                );

                const actionCell =
                    document.createElement('td');

                const manageButton =
                    document.createElement('button');

                manageButton.type = 'button';
                manageButton.className =
                    'drayvia-preview-action';

                manageButton.dataset.driverAssignmentManage =
                    String(driver.id);

                manageButton.textContent =
                    'SPRAVOVAT';

                manageButton.style.minHeight =
                    '32px';

                manageButton.style.padding =
                    '6px 10px';

                manageButton.style.fontSize =
                    '11px';

                actionCell.appendChild(
                    manageButton
                );

                row.appendChild(
                    actionCell
                );

                target.appendChild(row);
            }
        );
    };
    const realDriverApi = async (
        path,
        options = {}
    ) => {
        const token =
            sessionStorage.getItem(
                'tms_mvp_token'
            ) || '';

        if (!token) {
            throw new Error(
                'Přihlášení vypršelo. Přihlaste se znovu.'
            );
        }

        const headers = new Headers(
            options.headers || {}
        );

        headers.set(
            'Accept',
            'application/json'
        );

        headers.set(
            'Authorization',
            `Bearer ${token}`
        );

        headers.set(
            'X-Organization-ID',
            '1'
        );

        if (
            options.body &&
            !headers.has('Content-Type')
        ) {
            headers.set(
                'Content-Type',
                'application/json'
            );
        }

        const response = await fetch(
            path,
            {
                ...options,
                headers
            }
        );

        let body = null;

        const contentType =
            response.headers.get(
                'content-type'
            ) || '';

        try {
            if (
                contentType.includes(
                    'application/json'
                )
            ) {
                body = await response.json();
            }
            else {
                const text =
                    await response.text();

                body = text
                    ? { message: text }
                    : null;
            }
        }
        catch (_) {
            body = null;
        }

        if (!response.ok) {
            let validationMessage = '';

            if (
                body?.errors &&
                typeof body.errors === 'object'
            ) {
                validationMessage =
                    Object
                        .values(body.errors)
                        .flat()
                        .join(' ');
            }

            const error = new Error(
                validationMessage ||
                body?.message ||
                `HTTP ${response.status}`
            );

            error.status =
                response.status;

            error.body =
                body;

            throw error;
        }

        return body;
    };
    /* DRAYVIA-25E2B STATISTICS PAGE */
    const driverStatisticsNow = new Date();

    const driverStatisticsState = {
        items: null,
        navigationDrivers: [],
        loading: false,
        mode: 'month',
        year: driverStatisticsNow.getFullYear(),
        month: driverStatisticsNow.getMonth() + 1,
    };

    const driverStatisticsMonths = [
        'Leden',
        '\u00danor',
        'B\u0159ezen',
        'Duben',
        'Kv\u011bten',
        '\u010cerven',
        '\u010cervenec',
        'Srpen',
        'Z\u00e1\u0159\u00ed',
        '\u0158\u00edjen',
        'Listopad',
        'Prosinec',
    ];

    const driverStatisticsNumber = (value) => {
        const number = Number(value);

        return Number.isFinite(number)
            ? number
            : 0;
    };

    const driverStatisticsInteger = (value) =>
        new Intl.NumberFormat(
            'cs-CZ',
            { maximumFractionDigits: 0 }
        ).format(
            driverStatisticsNumber(value)
        );

    const driverStatisticsKm = (value) =>
        new Intl.NumberFormat(
            'cs-CZ',
            { maximumFractionDigits: 1 }
        ).format(
            driverStatisticsNumber(value)
        );

    const driverStatisticsPercent = (value) => {
        if (
            value === null
            || !Number.isFinite(Number(value))
        ) {
            return '\u2014';
        }

        return new Intl.NumberFormat(
            'cs-CZ',
            {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1,
            }
        ).format(Number(value)) + ' %';
    };

    const driverStatisticsResponseItems = (body) => {
        if (Array.isArray(body?.data?.items)) {
            return body.data.items;
        }

        if (Array.isArray(body?.items)) {
            return body.items;
        }

        if (Array.isArray(body?.data)) {
            return body.data;
        }

        return [];
    };

    const driverStatisticsNavigationDrivers = (body) => {
        const candidates = [
            body?.data?.navigation?.drivers,
            body?.navigation?.drivers,
            body?.meta?.navigation?.drivers,
            body?.data?.meta?.navigation?.drivers,
        ];

        return candidates.find(Array.isArray) || [];
    };

    const driverStatisticsDriverIsActive = (driver) => {
        const active = driver?.active;

        if (
            active === true
            || active === 1
            || active === '1'
            || String(active).toLowerCase() === 'true'
        ) {
            return true;
        }

        return String(
            driver?.status ?? ''
        ).toLowerCase() === 'active';
    };

    const fetchAllDriverStatisticsReports = async () => {
        const items = [];
        const perPage = 100;

        driverStatisticsState.navigationDrivers = [];

        for (let page = 1; page <= 100; page += 1) {
            const body = await realDriverApi(
                `/api/v1/daily-reports?per_page=${perPage}&page=${page}`
            );

            const pageItems =
                driverStatisticsResponseItems(body);

            const navigationDrivers =
                driverStatisticsNavigationDrivers(body);

            if (
                driverStatisticsState.navigationDrivers.length === 0
                && navigationDrivers.length > 0
            ) {
                driverStatisticsState.navigationDrivers =
                    navigationDrivers;
            }

            items.push(...pageItems);

            if (pageItems.length < perPage) {
                break;
            }
        }

        return items;
    };

    const driverStatisticsDateParts = (value) => {
        const match = String(value ?? '').match(
            /^(\d{4})-(\d{2})-(\d{2})/
        );

        if (!match) {
            return null;
        }

        return {
            year: Number(match[1]),
            month: Number(match[2]),
        };
    };

    const driverStatisticsYears = () =>
        Array.from(
            new Set(
                (driverStatisticsState.items || [])
                    .map(
                        (item) =>
                            driverStatisticsDateParts(
                                item?.service_date
                            )?.year
                    )
                    .filter(Number.isInteger)
            )
        ).sort(
            (left, right) => right - left
        );

    const driverStatisticsAvailableMonths = (year) =>
        Array.from(
            new Set(
                (driverStatisticsState.items || [])
                    .map(
                        (item) =>
                            driverStatisticsDateParts(
                                item?.service_date
                            )
                    )
                    .filter(
                        (date) => date?.year === year
                    )
                    .map(
                        (date) => date.month
                    )
            )
        ).sort(
            (left, right) => left - right
        );

    const driverStatisticsFilteredItems = () => {
        const items =
            driverStatisticsState.items || [];

        if (driverStatisticsState.mode === 'all') {
            return items;
        }

        return items.filter(
            (item) => {
                const date =
                    driverStatisticsDateParts(
                        item?.service_date
                    );

                if (
                    !date
                    || date.year !== driverStatisticsState.year
                ) {
                    return false;
                }

                if (driverStatisticsState.mode === 'year') {
                    return true;
                }

                return date.month === driverStatisticsState.month;
            }
        );
    };

    const driverStatisticsButton = (
        label,
        active,
        handler
    ) => {
        const button =
            document.createElement('button');

        button.type = 'button';
        button.className =
            'drayvia-driver-stat-button';

        if (active) {
            button.classList.add('active');
        }

        button.textContent = label;

        button.addEventListener(
            'click',
            handler
        );

        return button;
    };

    const ensureDriverStatisticsShell = () => {
        const driverStatsHost =
            document.getElementById(
                'drayviaDriverStatisticsHost'
            );

        if (!driverStatsHost) {
            return null;
        }

        if (
            !driverStatsHost.querySelector(
                '#drayviaDriverStatisticsRows'
            )
        ) {
            driverStatsHost.innerHTML = `
                <div class="drayvia-preview-panel-head">
                    <h2 class="drayvia-preview-panel-title">
                        STATISTIKY &#344;IDI&#268;&#366;
                    </h2>

                    <div class="drayvia-preview-panel-subtitle">
                        Skute&#269;n&#253; provozn&#237; v&#253;kon z tras DRAYVIA.
                    </div>
                </div>

                <div class="drayvia-driver-stat-filters">
                    <div class="drayvia-driver-stat-filter-row">
                        <div class="drayvia-driver-stat-label">ROK</div>
                        <div
                            id="drayviaDriverStatisticsYears"
                            class="drayvia-driver-stat-buttons"
                        ></div>
                    </div>

                    <div class="drayvia-driver-stat-filter-row">
                        <div class="drayvia-driver-stat-label">
                            M&#282;S&#205;C
                        </div>
                        <div
                            id="drayviaDriverStatisticsMonths"
                            class="drayvia-driver-stat-buttons"
                        ></div>
                    </div>

                    <div class="drayvia-driver-stat-filter-row">
                        <div class="drayvia-driver-stat-label">
                            RYCHL&#201; OBDOB&#205;
                        </div>
                        <div
                            id="drayviaDriverStatisticsQuick"
                            class="drayvia-driver-stat-buttons"
                        ></div>
                    </div>

                    <div
                        id="drayviaDriverStatisticsSummary"
                        class="drayvia-driver-stat-summary"
                    >
                        NA&#268;&#205;T&#193;M STATISTIKY...
                    </div>
                </div>

                <div class="drayvia-driver-stat-table-wrap">
                    <table class="drayvia-driver-stat-table">
                        <thead>
                            <tr>
                                <th>P&#344;&#205;JMEN&#205; A JM&#201;NO</th>
                                <th>TRASY</th>
                                <th>DNY S TRASOU</th>
                                <th>NALO&#381;ENO</th>
                                <th>DORU&#268;ENO NA ADRESU</th>
                                <th>V&#221;DEJN&#205; M&#205;STO</th>
                                <th>ODM&#205;TNUTO Z&#193;KAZN&#205;KEM</th>
                                <th>NEDORU&#268;ENO</th>
                                <th>PL&#193;N KM</th>
                                <th>SKUT. KM</th>
                                <th>ROZD&#205;L N&#193;JEZDU</th>
                                <th>D&#205;L&#268;&#205; KVALITA</th>
                            </tr>
                        </thead>

                        <tbody id="drayviaDriverStatisticsRows"></tbody>
                    </table>
                </div>
            `;
        }

        return driverStatsHost;
    };

    const renderDriverStatisticsFilters = () => {
        const yearTarget =
            document.getElementById(
                'drayviaDriverStatisticsYears'
            );

        const monthTarget =
            document.getElementById(
                'drayviaDriverStatisticsMonths'
            );

        const quickTarget =
            document.getElementById(
                'drayviaDriverStatisticsQuick'
            );

        if (
            !yearTarget
            || !monthTarget
            || !quickTarget
        ) {
            return;
        }

        yearTarget.replaceChildren();
        monthTarget.replaceChildren();
        quickTarget.replaceChildren();

        driverStatisticsYears().forEach(
            (year) => {
                yearTarget.appendChild(
                    driverStatisticsButton(
                        String(year),
                        driverStatisticsState.mode !== 'all'
                            && driverStatisticsState.year === year,
                        () => {
                            driverStatisticsState.year = year;
                            driverStatisticsState.mode = 'year';
                            renderDriverStatistics();
                        }
                    )
                );
            }
        );

        driverStatisticsAvailableMonths(
            driverStatisticsState.year
        ).forEach(
            (month) => {
                monthTarget.appendChild(
                    driverStatisticsButton(
                        driverStatisticsMonths[month - 1],
                        driverStatisticsState.mode === 'month'
                            && driverStatisticsState.month === month,
                        () => {
                            driverStatisticsState.month = month;
                            driverStatisticsState.mode = 'month';
                            renderDriverStatistics();
                        }
                    )
                );
            }
        );

        const now = new Date();

        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        const previous = new Date(
            currentYear,
            currentMonth - 2,
            1
        );

        const quick = [
            {
                label: 'Tento m\u011bs\u00edc',
                active:
                    driverStatisticsState.mode === 'month'
                    && driverStatisticsState.year === currentYear
                    && driverStatisticsState.month === currentMonth,
                apply: () => {
                    driverStatisticsState.mode = 'month';
                    driverStatisticsState.year = currentYear;
                    driverStatisticsState.month = currentMonth;
                },
            },
            {
                label: 'Minul\u00fd m\u011bs\u00edc',
                active:
                    driverStatisticsState.mode === 'month'
                    && driverStatisticsState.year
                        === previous.getFullYear()
                    && driverStatisticsState.month
                        === previous.getMonth() + 1,
                apply: () => {
                    driverStatisticsState.mode = 'month';
                    driverStatisticsState.year =
                        previous.getFullYear();
                    driverStatisticsState.month =
                        previous.getMonth() + 1;
                },
            },
            {
                label: 'Tento rok',
                active:
                    driverStatisticsState.mode === 'year'
                    && driverStatisticsState.year === currentYear,
                apply: () => {
                    driverStatisticsState.mode = 'year';
                    driverStatisticsState.year = currentYear;
                },
            },
            {
                label: 'V\u0161e',
                active:
                    driverStatisticsState.mode === 'all',
                apply: () => {
                    driverStatisticsState.mode = 'all';
                },
            },
        ];

        quick.forEach(
            (item) => {
                quickTarget.appendChild(
                    driverStatisticsButton(
                        item.label,
                        item.active,
                        () => {
                            item.apply();
                            renderDriverStatistics();
                        }
                    )
                );
            }
        );
    };

    const driverStatisticsPeriodLabel = () => {
        if (driverStatisticsState.mode === 'all') {
            return 'V\u0161echna data';
        }

        if (driverStatisticsState.mode === 'year') {
            return String(
                driverStatisticsState.year
            );
        }

        return (
            driverStatisticsMonths[
                driverStatisticsState.month - 1
            ]
            + ' '
            + driverStatisticsState.year
        );
    };

    const driverStatisticsActiveDriver = (stat) => {
        return (
            driverStatisticsState.navigationDrivers
            || []
        ).find(
            (driver) => {
                const internalId =
                    Number(
                        driver?.id
                        ?? driver?.driver_id
                        ?? driver?.performed_by_driver_id
                    );

                const externalId =
                    String(
                        driver?.external_driver_id
                        ?? ''
                    ).trim();

                return (
                    (
                        Number.isInteger(internalId)
                        && internalId === stat.driverId
                    )
                    || (
                        externalId !== ''
                        && externalId === stat.externalId
                    )
                );
            }
        );
    };

    const renderDriverStatistics = () => {
        if (!ensureDriverStatisticsShell()) {
            return;
        }

        renderDriverStatisticsFilters();

        const target =
            document.getElementById(
                'drayviaDriverStatisticsRows'
            );

        const summary =
            document.getElementById(
                'drayviaDriverStatisticsSummary'
            );

        const filtered =
            driverStatisticsFilteredItems();

        const statistics = new Map();

        filtered.forEach(
            (item) => {
                const driverId =
                    Number(
                        item?.performed_by_driver_id
                    );

                if (
                    !Number.isInteger(driverId)
                    || driverId <= 0
                ) {
                    return;
                }

                if (!statistics.has(driverId)) {
                    statistics.set(
                        driverId,
                        {
                            driverId,
                            name:
                                String(
                                    item?.performed_by_driver_name
                                    || `\u0158idi\u010d ${driverId}`
                                ),
                            externalId:
                                String(
                                    item?.performed_by_driver_external_id
                                    ?? ''
                                ).trim(),
                            routes: 0,
                            dates: new Set(),
                            loaded: 0,
                            delivered: 0,
                            redirected: 0,
                            rejected: 0,
                            notDelivered: 0,
                            plannedKm: 0,
                            actualKm: 0,
                        }
                    );
                }

                const stat =
                    statistics.get(driverId);

                stat.routes += 1;

                if (item?.service_date) {
                    stat.dates.add(
                        String(item.service_date)
                    );
                }

                const loaded =
                    driverStatisticsNumber(
                        item?.loaded_parcels
                    );

                const delivered =
                    driverStatisticsNumber(
                        item?.delivered_parcels
                    );

                const redirected =
                    driverStatisticsNumber(
                        item?.redirected_parcels
                    );

                const rejected =
                    driverStatisticsNumber(
                        item?.undelivered_parcels
                    );

                stat.loaded += loaded;
                stat.delivered += delivered;
                stat.redirected += redirected;
                stat.rejected += rejected;

                const calculatedNotDelivered =
                    Number(
                        item?.calculated
                            ?.not_delivered_parcels
                    );

                stat.notDelivered +=
                    Number.isFinite(
                        calculatedNotDelivered
                    )
                        ? calculatedNotDelivered
                        : Math.max(
                            loaded
                            - delivered
                            - redirected
                            - rejected,
                            0
                        );

                stat.plannedKm +=
                    driverStatisticsNumber(
                        item?.planned_km
                    );

                stat.actualKm +=
                    driverStatisticsNumber(
                        item?.actual_km
                    );
            }
        );

        const rows =
            Array.from(
                statistics.values()
            )
                .filter(
                    (stat) => {
                        if (stat.routes <= 0) {
                            return false;
                        }

                        const driver =
                            driverStatisticsActiveDriver(
                                stat
                            );

                        return (
                            driver !== undefined
                            && driverStatisticsDriverIsActive(
                                driver
                            )
                        );
                    }
                )
                .sort(
                    (left, right) =>
                        left.name.localeCompare(
                            right.name,
                            'cs-CZ'
                        )
                );

        target.replaceChildren();

        const simpleCell = (
            value,
            className = ''
        ) => {
            const cell =
                document.createElement('td');

            cell.textContent = value;

            if (className) {
                cell.className = className;
            }

            return cell;
        };

        const twoLineCell = (
            primary,
            secondary,
            className = ''
        ) => {
            const cell =
                document.createElement('td');

            if (className) {
                cell.className = className;
            }

            const first =
                document.createElement('span');

            first.className =
                'drayvia-driver-stat-primary';

            first.textContent = primary;

            const second =
                document.createElement('span');

            second.className =
                'drayvia-driver-stat-secondary';

            second.textContent = secondary;

            cell.appendChild(first);
            cell.appendChild(second);

            return cell;
        };

        rows.forEach(
            (stat) => {
                const row =
                    document.createElement('tr');

                const differenceKm =
                    stat.actualKm
                    - stat.plannedKm;

                const differencePercent =
                    stat.plannedKm > 0
                        ? (
                            differenceKm
                            / stat.plannedKm
                        ) * 100
                        : null;

                const pickupPercent =
                    stat.loaded > 0
                        ? (
                            stat.redirected
                            / stat.loaded
                        ) * 100
                        : null;

                const partialQuality =
                    Math.max(
                        stat.loaded
                        - stat.delivered
                        - stat.redirected
                        - stat.rejected,
                        0
                    );

                const kmAlert =
                    differencePercent !== null
                    && Math.abs(
                        differencePercent
                    ) > 10;

                const identityCell =
                    document.createElement('td');

                identityCell.className =
                    'drayvia-driver-stat-identity';

                const name =
                    document.createElement('span');

                name.className =
                    'drayvia-driver-stat-name';

                name.textContent =
                    stat.name;

                const id =
                    document.createElement('span');

                id.className =
                    'drayvia-driver-stat-id';

                id.textContent =
                    `ID: ${stat.externalId || '\u2014'}`;

                identityCell.appendChild(name);
                identityCell.appendChild(id);

                row.appendChild(identityCell);

                row.appendChild(
                    simpleCell(
                        driverStatisticsInteger(
                            stat.routes
                        )
                    )
                );

                row.appendChild(
                    simpleCell(
                        driverStatisticsInteger(
                            stat.dates.size
                        )
                    )
                );

                row.appendChild(
                    simpleCell(
                        driverStatisticsInteger(
                            stat.loaded
                        )
                    )
                );

                row.appendChild(
                    simpleCell(
                        driverStatisticsInteger(
                            stat.delivered
                        )
                    )
                );

                row.appendChild(
                    twoLineCell(
                        driverStatisticsInteger(
                            stat.redirected
                        ),
                        driverStatisticsPercent(
                            pickupPercent
                        )
                    )
                );

                row.appendChild(
                    simpleCell(
                        driverStatisticsInteger(
                            stat.rejected
                        ),
                        stat.rejected > 0
                            ? 'drayvia-driver-stat-warning'
                            : ''
                    )
                );

                row.appendChild(
                    simpleCell(
                        driverStatisticsInteger(
                            stat.notDelivered
                        ),
                        stat.notDelivered === 0
                            ? 'drayvia-driver-stat-quality-good'
                            : 'drayvia-driver-stat-quality-bad'
                    )
                );

                row.appendChild(
                    simpleCell(
                        driverStatisticsKm(
                            stat.plannedKm
                        )
                    )
                );

                row.appendChild(
                    simpleCell(
                        driverStatisticsKm(
                            stat.actualKm
                        )
                    )
                );

                row.appendChild(
                    twoLineCell(
                        (
                            differenceKm > 0
                                ? '+'
                                : ''
                        )
                        + driverStatisticsKm(
                            differenceKm
                        )
                        + ' km',
                        (
                            differencePercent !== null
                            && differencePercent > 0
                                ? '+'
                                : ''
                        )
                        + driverStatisticsPercent(
                            differencePercent
                        ),
                        kmAlert
                            ? 'drayvia-driver-stat-alert'
                            : ''
                    )
                );

                row.appendChild(
                    simpleCell(
                        driverStatisticsInteger(
                            partialQuality
                        ),
                        partialQuality === 0
                            ? 'drayvia-driver-stat-quality-good'
                            : 'drayvia-driver-stat-quality-bad'
                    )
                );

                target.appendChild(row);
            }
        );

        if (rows.length === 0) {
            const row =
                document.createElement('tr');

            const empty =
                document.createElement('td');

            empty.colSpan = 12;
            empty.className =
                'drayvia-driver-stat-empty';

            empty.textContent =
                'V tomto obdob\u00ed nem\u00e1 \u017e\u00e1dn\u00fd aktivn\u00ed \u0159idi\u010d trasu.';

            row.appendChild(empty);
            target.appendChild(row);
        }

        if (summary) {
            summary.textContent =
                `Obdob\u00ed: ${driverStatisticsPeriodLabel()}`
                + ` \u00b7 Aktivn\u00edch \u0159idi\u010d\u016f s trasou: ${rows.length}`
                + ` \u00b7 Tras: ${rows.reduce(
                    (sum, row) => sum + row.routes,
                    0
                )}`;
        }
    };

    const loadDriverStatistics = async (
        force = false
    ) => {
        if (!ensureDriverStatisticsShell()) {
            return;
        }

        if (driverStatisticsState.loading) {
            return;
        }

        if (
            !force
            && Array.isArray(
                driverStatisticsState.items
            )
        ) {
            renderDriverStatistics();
            return;
        }

        driverStatisticsState.loading = true;

        const summary =
            document.getElementById(
                'drayviaDriverStatisticsSummary'
            );

        if (summary) {
            summary.textContent =
                'Na\u010d\u00edt\u00e1m statistiky \u0159idi\u010d\u016f...';
        }

        try {
            driverStatisticsState.items =
                await fetchAllDriverStatisticsReports();

            renderDriverStatistics();
        }
        catch (error) {
            if (summary) {
                summary.textContent =
                    `Chyba: ${error?.message || 'nezn\u00e1m\u00e1 chyba'}`;
            }
        }
        finally {
            driverStatisticsState.loading = false;
        }
    };
    const realDriverAssignmentAdminState = {
        driver: null,
        organizations: [],
    };

    const realDriverCarrierItems = (body) => {
        if (Array.isArray(body?.data?.items)) {
            return body.data.items;
        }

        if (Array.isArray(body?.data)) {
            return body.data;
        }

        if (Array.isArray(body?.items)) {
            return body.items;
        }

        return [];
    };

    const realDriverOpenAssignment = (driver) => {
        const items =
            Array.isArray(
                driver?.organization_assignment_items
            )
                ? driver.organization_assignment_items
                : [];

        return (
            items.find(
                (assignment) =>
                    assignment.valid_until === null
            )
            ?? null
        );
    };

    const realDriverAssignmentDisplayName = (driver) => {
        const surname =
            String(
                driver?.last_name ?? ''
            ).trim();

        const firstName =
            String(
                driver?.first_name ?? ''
            ).trim();

        return (
            `${surname} ${firstName}`.trim()
            || driver?.full_name
            || `ŘIDIČ ${driver?.id ?? ''}`
        );
    };

    const setRealDriverAssignmentMessage = (
        message = '',
        tone = ''
    ) => {
        const target =
            document.getElementById(
                'drayviaRealDriverAssignmentMessage'
            );

        if (!target) {
            return;
        }

        target.textContent =
            String(message ?? '');

        target.hidden =
            target.textContent === '';

        target.className =
            'drayvia-real-driver-message';

        if (tone) {
            target.classList.add(tone);
        }
    };

    const loadRealDriverAssignmentOrganizations =
        async () => {
            const [masterBody, carrierBody] =
                await Promise.all([
                    realDriverApi(
                        '/api/v1/organization-profile'
                    ),
                    realDriverApi(
                        '/api/v1/carriers'
                    ),
                ]);

            const organizations = [];

            const master =
                masterBody?.data ?? null;

            if (
                master
                && Number(master.id) > 0
            ) {
                organizations.push({
                    id: Number(master.id),
                    name: String(
                        master.name
                        ?? 'HLAVNÍ ORGANIZACE'
                    ),
                    type: 'master',
                    status: String(
                        master.status
                        ?? 'active'
                    ),
                });
            }

            realDriverCarrierItems(
                carrierBody
            ).forEach(
                (carrier) => {
                    const id =
                        Number(carrier?.id);

                    if (
                        !Number.isInteger(id)
                        || id < 1
                    ) {
                        return;
                    }

                    organizations.push({
                        id,
                        name: String(
                            carrier?.name
                            ?? `DOPRAVCE ${id}`
                        ),
                        type: String(
                            carrier?.type
                            ?? 'subcontractor'
                        ),
                        status: String(
                            carrier?.status
                            ?? 'active'
                        ),
                    });
                }
            );

            realDriverAssignmentAdminState
                .organizations =
                organizations
                    .filter(
                        (item) =>
                            item.status === 'active'
                    )
                    .sort(
                        (left, right) => {
                            if (
                                left.type === 'master'
                                && right.type !== 'master'
                            ) {
                                return -1;
                            }

                            if (
                                right.type === 'master'
                                && left.type !== 'master'
                            ) {
                                return 1;
                            }

                            return left.name.localeCompare(
                                right.name,
                                'cs'
                            );
                        }
                    );
        };

    const renderRealDriverAssignmentHistory =
        (driver) => {
            const target =
                document.getElementById(
                    'drayviaRealDriverAssignmentHistoryRows'
                );

            if (!target) {
                return;
            }

            target.replaceChildren();

            const items =
                Array.isArray(
                    driver?.organization_assignment_items
                )
                    ? driver.organization_assignment_items
                    : [];

            if (items.length === 0) {
                const row =
                    document.createElement('tr');

                const cell =
                    document.createElement('td');

                cell.colSpan = 6;

                cell.className =
                    'drayvia-real-driver-empty';

                cell.textContent =
                    'ŘIDIČ NEMÁ ULOŽENOU HISTORII PŘIŘAZENÍ';

                row.appendChild(cell);
                target.appendChild(row);

                return;
            }

            items.forEach(
                (assignment) => {
                    const row =
                        document.createElement('tr');

                    appendRealDriverTextCell(
                        row,
                        assignment.organization_name
                        ?? '—',
                        { strong: true }
                    );

                    appendRealDriverTextCell(
                        row,
                        assignment.organization_type ===
                            'master'
                            ? 'VLASTNÍ'
                            : 'EXTERNÍ'
                    );

                    appendRealDriverTextCell(
                        row,
                        assignment.organization_type ===
                            'master'
                            ? driverEmploymentLabel(
                                assignment.employment_type
                            )
                            : '—'
                    );

                    appendRealDriverTextCell(
                        row,
                        driverAssignmentDate(
                            assignment.valid_from
                        )
                    );

                    appendRealDriverTextCell(
                        row,
                        assignment.valid_until
                            ? driverAssignmentDate(
                                assignment.valid_until
                            )
                            : '—'
                    );

                    appendRealDriverBadgeCell(
                        row,
                        assignment.valid_until === null
                            ? 'OTEVŘENO'
                            : 'UKONČENO',
                        assignment.valid_until === null
                            ? 'active'
                            : 'ended'
                    );

                    target.appendChild(row);
                }
            );
        };

    const realDriverAssignmentTarget = () => {
        const select =
            document.getElementById(
                'drayviaRealDriverAssignmentOrganization'
            );

        const id =
            Number(
                select?.value ?? 0
            );

        return (
            realDriverAssignmentAdminState
                .organizations
                .find(
                    (item) =>
                        item.id === id
                )
            ?? null
        );
    };

    const syncRealDriverAssignmentEmployment =
        () => {
            const target =
                realDriverAssignmentTarget();

            const field =
                document.getElementById(
                    'drayviaRealDriverAssignmentEmploymentField'
                );

            const select =
                document.getElementById(
                    'drayviaRealDriverAssignmentEmployment'
                );

            const master =
                target?.type === 'master';

            if (field) {
                field.hidden = !master;

                if (master) {
                    field.style.removeProperty(
                        'display'
                    );
                }
                else {
                    field.style.setProperty(
                        'display',
                        'none',
                        'important'
                    );
                }
            }

            if (select) {
                select.required = master;
                select.disabled = !master;

                if (!master) {
                    select.value = '';
                }
            }
        };

    const realDriverPreviousDay = (value) => {
        if (!value) {
            return null;
        }

        const date =
            new Date(
                `${value}T12:00:00`
            );

        if (
            Number.isNaN(
                date.getTime()
            )
        ) {
            return null;
        }

        date.setDate(
            date.getDate() - 1
        );

        return (
            `${date.getFullYear()}-`
            + `${String(
                date.getMonth() + 1
            ).padStart(2, '0')}-`
            + `${String(
                date.getDate()
            ).padStart(2, '0')}`
        );
    };

    const updateRealDriverAssignmentPreview = () => {
        const preview =
            document.getElementById(
                'drayviaRealDriverAssignmentPreview'
            );

        if (!preview) {
            return;
        }

        const driver =
            realDriverAssignmentAdminState.driver;

        const current =
            realDriverOpenAssignment(
                driver
            );

        const next =
            realDriverAssignmentTarget();

        const validFrom =
            document.getElementById(
                'drayviaRealDriverAssignmentValidFrom'
            )?.value ?? '';

        if (!current) {
            preview.textContent =
                'Řidič nemá otevřené přiřazení, které lze převést.';

            return;
        }

        if (
            !next
            || !validFrom
        ) {
            preview.textContent =
                'Vyberte nového dopravce a datum účinnosti.';

            return;
        }

        const previousUntil =
            realDriverPreviousDay(
                validFrom
            );

        if (!previousUntil) {
            preview.textContent =
                'Datum změny není platné.';

            return;
        }

        preview.textContent =
            `${realDriverAssignmentDisplayName(driver)}: `
            + `${current.organization_name} skončí `
            + `${driverAssignmentDate(previousUntil)}; `
            + `od ${driverAssignmentDate(validFrom)} `
            + `bude přiřazen k ${next.name}.`;
    };

    const populateRealDriverAssignmentOrganizations =
        (driver) => {
            const select =
                document.getElementById(
                    'drayviaRealDriverAssignmentOrganization'
                );

            if (!select) {
                return;
            }

            const current =
                realDriverOpenAssignment(
                    driver
                );

            select.replaceChildren();

            const placeholder =
                document.createElement(
                    'option'
                );

            placeholder.value = '';

            placeholder.textContent =
                'VYBERTE DOPRAVCE';

            select.appendChild(
                placeholder
            );

            realDriverAssignmentAdminState
                .organizations
                .filter(
                    (item) =>
                        !current
                        || item.id !==
                            Number(
                                current.organization_id
                            )
                )
                .forEach(
                    (item) => {
                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            String(item.id);

                        option.textContent =
                            item.type === 'master'
                                ? `${item.name} — VLASTNÍ`
                                : item.name;

                        select.appendChild(
                            option
                        );
                    }
                );

            syncRealDriverAssignmentEmployment();
            updateRealDriverAssignmentPreview();
        };

    const closeRealDriverAssignmentPanel = () => {
        const panel =
            document.getElementById(
                'drayviaRealDriverAssignmentPanel'
            );

        const form =
            document.getElementById(
                'drayviaRealDriverAssignmentTransferForm'
            );

        if (form) {
            form.reset();
        }

        if (panel) {
            panel.hidden = true;
        }

        realDriverAssignmentAdminState.driver =
            null;

        setRealDriverAssignmentMessage();
    };

    const openRealDriverAssignmentPanel =
        async (driver) => {
            const panel =
                document.getElementById(
                    'drayviaRealDriverAssignmentPanel'
                );

            const subtitle =
                document.getElementById(
                    'drayviaRealDriverAssignmentSubtitle'
                );

            const form =
                document.getElementById(
                    'drayviaRealDriverAssignmentTransferForm'
                );

            if (
                !panel
                || !subtitle
                || !form
            ) {
                return;
            }

            realDriverAssignmentAdminState.driver =
                driver;

            form.reset();

            subtitle.textContent =
                realDriverAssignmentDisplayName(
                    driver
                ) +
                (
                    driver.external_driver_id
                        ? ` · ID ${driver.external_driver_id}`
                        : ''
                );

            renderRealDriverAssignmentHistory(
                driver
            );

            setRealDriverAssignmentMessage();

            panel.hidden = false;

            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });

            const current =
                realDriverOpenAssignment(
                    driver
                );

            if (!current) {
                form.hidden = true;

                setRealDriverAssignmentMessage(
                    'Řidič nemá otevřené přiřazení. Historii lze zobrazit, ale převod nyní není dostupný.',
                    'warning'
                );

                return;
            }

            form.hidden = false;

            const submit =
                document.getElementById(
                    'drayviaRealDriverAssignmentTransferSubmit'
                );

            if (submit) {
                submit.disabled = true;
            }

            try {
                await loadRealDriverAssignmentOrganizations();

                populateRealDriverAssignmentOrganizations(
                    driver
                );
            }
            catch (error) {
                form.hidden = true;

                setRealDriverAssignmentMessage(
                    error?.message
                    || 'Seznam dopravců se nepodařilo načíst.',
                    'error'
                );
            }
            finally {
                if (submit) {
                    submit.disabled = false;
                }
            }
        };

    const submitRealDriverAssignmentTransfer =
        async (form) => {
            const driver =
                realDriverAssignmentAdminState.driver;

            const current =
                realDriverOpenAssignment(
                    driver
                );

            const next =
                realDriverAssignmentTarget();

            if (
                !driver
                || !current
                || !next
            ) {
                setRealDriverAssignmentMessage(
                    'Nelze určit aktuální a nové přiřazení.',
                    'error'
                );

                return;
            }

            const data =
                new FormData(form);

            const validFrom =
                String(
                    data.get('valid_from')
                    ?? ''
                ).trim();

            if (!validFrom) {
                setRealDriverAssignmentMessage(
                    'Vyberte datum účinnosti změny.',
                    'error'
                );

                return;
            }

            const employmentType =
                next.type === 'master'
                    ? String(
                        data.get(
                            'employment_type'
                        ) ?? ''
                    ).trim()
                    : '';

            if (
                next.type === 'master'
                && !employmentType
            ) {
                setRealDriverAssignmentMessage(
                    'U vlastního řidiče vyberte pracovní vztah.',
                    'error'
                );

                return;
            }

            const previousUntil =
                realDriverPreviousDay(
                    validFrom
                );

            if (!previousUntil) {
                setRealDriverAssignmentMessage(
                    'Datum změny není platné.',
                    'error'
                );

                return;
            }

            const reason =
                String(
                    data.get('end_reason')
                    ?? ''
                ).trim();

            const confirmation =
                `${realDriverAssignmentDisplayName(driver)}: `
                + `${current.organization_name} skončí `
                + `${driverAssignmentDate(previousUntil)} a `
                + `od ${driverAssignmentDate(validFrom)} `
                + `bude přiřazen k ${next.name}. `
                + `Tato změna upraví historické přiřazení řidiče.`;

            if (
                !window.confirm(
                    confirmation
                )
            ) {
                return;
            }

            const payload = {
                organization_id:
                    next.id,

                valid_from:
                    validFrom,

                employment_type:
                    next.type === 'master'
                        ? employmentType
                        : null,
            };

            if (reason) {
                payload.end_reason =
                    reason;
            }

            const submit =
                document.getElementById(
                    'drayviaRealDriverAssignmentTransferSubmit'
                );

            if (submit) {
                submit.disabled = true;
                submit.textContent =
                    'UKLÁDÁM…';
            }

            try {
                await realDriverApi(
                    `/api/v1/own-drivers/${driver.id}/assignments/${current.id}/transfer`,
                    {
                        method: 'PATCH',
                        body:
                            JSON.stringify(
                                payload
                            ),
                    }
                );

                closeRealDriverAssignmentPanel();

                await loadRealDriverData();

                setRealDriverMessage(
                    'Přiřazení řidiče bylo úspěšně změněno.',
                    'success'
                );
            }
            catch (error) {
                setRealDriverAssignmentMessage(
                    error?.message
                    || 'Přiřazení řidiče se nepodařilo změnit.',
                    'error'
                );
            }
            finally {
                if (submit) {
                    submit.disabled = false;
                    submit.textContent =
                        'ULOŽIT ZMĚNU';
                }
            }
        };
    const loadRealDriverData = async () => {
        const target =
            document.getElementById(
                'drayviaRealDriverRows'
            );

        if (!target) {
            return;
        }

        target.innerHTML = `
            <tr>
                <td colspan="8" class="drayvia-real-driver-empty">
                    NAČÍTÁM…
                </td>
            </tr>
        `;

        setRealDriverMessage();

        try {
            const body =
                await realDriverApi(
                    '/api/v1/own-drivers'
                );

            const drivers =
                realDriverItems(body);

            const enrichedDrivers =
                await Promise.all(
                    drivers.map(
                        async (driver) => {
                            const assignmentBody =
                                await realDriverApi(
                                    `/api/v1/own-drivers/${driver.id}/assignments`
                                );

                            const assignmentData =
                                assignmentBody?.data
                                ?? {};

                            const assignmentItems =
                                Array.isArray(
                                    assignmentData.items
                                )
                                    ? assignmentData.items
                                    : [];

                            return {
                                ...driver,
                                organization_assignment_current:
                                    assignmentData.current
                                    ?? null,
                                organization_assignment_latest:
                                    assignmentData.current
                                    ?? assignmentItems[0]
                                    ?? null,
                                organization_assignment_items:
                                    assignmentItems,
                            };
                        }
                    )
                );

            enrichedDrivers.sort(
                (left, right) => {
                    const leftName =
                        `${left.last_name ?? ''} ${left.first_name ?? ''}`;

                    const rightName =
                        `${right.last_name ?? ''} ${right.first_name ?? ''}`;

                    return leftName.localeCompare(
                        rightName,
                        'cs'
                    );
                }
            );

            realDriverState.items =
                enrichedDrivers;

            renderRealDriverRows();
        }
        catch (error) {
            target.innerHTML = `
                <tr>
                    <td colspan="8" class="drayvia-real-driver-empty">
                        NAČTENÍ SELHALO
                    </td>
                </tr>
            `;

            setRealDriverMessage(
                error?.message ||
                    'Řidiče a jejich organizační přiřazení se nepodařilo načíst.',
                'error'
            );
        }
    };
    const openRealDriverForm = () => {
        const panel =
            document.getElementById(
                'drayviaRealDriverFormPanel'
            );

        const form =
            document.getElementById(
                'drayviaRealDriverForm'
            );

        if (!panel || !form) {
            return;
        }

        form.reset();
        panel.hidden = false;

        setRealDriverMessage();

        form.elements.first_name.focus();
    };

    const closeRealDriverForm = () => {
        const panel =
            document.getElementById(
                'drayviaRealDriverFormPanel'
            );

        const form =
            document.getElementById(
                'drayviaRealDriverForm'
            );

        if (form) {
            form.reset();
        }

        if (panel) {
            panel.hidden = true;
        }
    };

    const submitRealDriver = async (form) => {
        const data =
            new FormData(form);

        const payload = {
            first_name:
                String(
                    data.get('first_name') ?? ''
                ).trim(),

            last_name:
                String(
                    data.get('last_name') ?? ''
                ).trim(),

            external_driver_id:
                String(
                    data.get('external_driver_id') ?? ''
                ).trim() || null,

            email:
                String(
                    data.get('email') ?? ''
                ).trim(),

            phone:
                String(
                    data.get('phone') ?? ''
                ).trim() || null,

            password:
                String(
                    data.get('password') ?? ''
                ),

            password_confirmation:
                String(
                    data.get(
                        'password_confirmation'
                    ) ?? ''
                )
        };

        const submit =
            form.querySelector(
                'button[type="submit"]'
            );

        if (submit) {
            submit.disabled = true;
            submit.textContent = 'UKLÁDÁM…';
        }

        setRealDriverMessage();

        try {
            await realDriverApi(
                '/api/v1/own-drivers',
                {
                    method: 'POST',
                    body: JSON.stringify(payload)
                }
            );

            closeRealDriverForm();

            await loadRealDriverData();

            setRealDriverMessage(
                'Řidič byl úspěšně založen.',
                'success'
            );
        }
        catch (error) {
            setRealDriverMessage(
                error?.message ||
                'Řidiče se nepodařilo uložit.',
                'error'
            );
        }
        finally {
            if (submit) {
                submit.disabled = false;
                submit.textContent =
                    'ULOŽIT ŘIDIČE';
            }
        }
    };

    const drivers = () => `
        ${header(
            'Řidiči',
            'Evidence skutečných řidičů DRAYVIA a jejich přístupových účtů.'
        )}

        <div class="drayvia-preview-actions">
            <button
                id="drayviaRealDriverAdd"
                class="drayvia-preview-action primary"
                type="button"
            >
                PŘIDAT ŘIDIČE
            </button>

            <button
                id="drayviaRealDriverReload"
                class="drayvia-preview-action"
                type="button"
            >
                OBNOVIT
            </button>
        </div>

        <div
            id="drayviaRealDriverMessage"
            class="drayvia-real-driver-message"
            hidden
        ></div>

        <div
            id="drayviaRealDriverFormPanel"
            class="drayvia-real-driver-form-panel"
            hidden
        >
            <div class="drayvia-real-driver-form-head">
                <div>
                    <h2>PŘIDAT ŘIDIČE</h2>
                    <p>
                        Externí nebo náhradní ID bude později použito
                        pro bezpečné párování historických tras.
                    </p>
                </div>

                <button
                    id="drayviaRealDriverClose"
                    type="button"
                    class="drayvia-real-driver-close"
                >
                    ×
                </button>
            </div>

            <form id="drayviaRealDriverForm">

                <div class="drayvia-real-driver-form-grid">

                    <label>
                        <span>JMÉNO *</span>
                        <input
                            name="first_name"
                            maxlength="100"
                            required
                        >
                    </label>

                    <label>
                        <span>PŘÍJMENÍ *</span>
                        <input
                            name="last_name"
                            maxlength="100"
                            required
                        >
                    </label>

                    <label>
                        <span>EXTERNÍ / NÁHRADNÍ ID</span>
                        <input
                            name="external_driver_id"
                            inputmode="numeric"
                            maxlength="32"
                            pattern="[0-9]*"
                        >
                    </label>

                    <label>
                        <span>E-MAIL *</span>
                        <input
                            name="email"
                            type="email"
                            maxlength="255"
                            required
                        >
                    </label>

                    <label>
                        <span>TELEFON</span>
                        <input
                            name="phone"
                            type="tel"
                            maxlength="64"
                        >
                    </label>

                    <div></div>

                    <label>
                        <span>HESLO *</span>
                        <input
                            name="password"
                            type="password"
                            minlength="10"
                            maxlength="128"
                            autocomplete="new-password"
                            required
                        >
                    </label>

                    <label>
                        <span>POTVRZENÍ HESLA *</span>
                        <input
                            name="password_confirmation"
                            type="password"
                            minlength="10"
                            maxlength="128"
                            autocomplete="new-password"
                            required
                        >
                    </label>

                </div>

                <div class="drayvia-real-driver-form-note">
                    Účet se založí nyní, ale přihlašovací údaje
                    zatím řidiči neposílej.
                </div>

                <div class="drayvia-real-driver-form-actions">
                    <button
                        type="submit"
                        class="drayvia-preview-action primary"
                    >
                        ULOŽIT ŘIDIČE
                    </button>

                    <button
                        id="drayviaRealDriverCancel"
                        type="button"
                        class="drayvia-preview-action"
                    >
                        ZRUŠIT
                    </button>
                </div>

            </form>
        </div>

        <div
            class="drayvia-preview-grid"
            style="margin-top:18px;"
        >
            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">
                    ŘIDIČI CELKEM
                </div>
                <div
                    id="drayviaRealDriverTotal"
                    class="drayvia-preview-card-value"
                >
                    —
                </div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">
                    VLASTNÍ
                </div>
                <div
                    id="drayviaRealDriverWithId"
                    class="drayvia-preview-card-value"
                >
                    —
                </div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">
                    EXTERNÍ AKTIVNÍ
                </div>
                <div
                    id="drayviaRealDriverWithoutId"
                    class="drayvia-preview-card-value"
                >
                    —
                </div>
            </div>
        </div>

        <!-- DRAYVIA-26G2 DRIVER ASSIGNMENT MANAGEMENT -->
        <div
            id="drayviaRealDriverAssignmentPanel"
            class="drayvia-real-driver-form-panel"
            hidden
        >
            <div class="drayvia-real-driver-form-head">
                <div>
                    <h2>PŘIŘAZENÍ ŘIDIČE</h2>
                    <p id="drayviaRealDriverAssignmentSubtitle">
                        —
                    </p>
                </div>

                <button
                    id="drayviaRealDriverAssignmentClose"
                    type="button"
                    class="drayvia-real-driver-close"
                    aria-label="Zavřít správu přiřazení"
                >
                    ×
                </button>
            </div>

            <div
                id="drayviaRealDriverAssignmentMessage"
                class="drayvia-real-driver-message"
                hidden
            ></div>

            <div
                style="
                    margin-top:14px;
                    padding:14px;
                    border:1px solid #e4e7ec;
                    border-radius:10px;
                    background:#f8fafc;
                "
            >
                <div
                    style="
                        margin-bottom:10px;
                        font-size:11px;
                        font-weight:900;
                        color:#344054;
                    "
                >
                    HISTORIE PŘIŘAZENÍ
                </div>

                <div class="drayvia-real-driver-table-wrap">
                    <table class="drayvia-real-driver-table">
                        <thead>
                            <tr>
                                <th>DOPRAVCE</th>
                                <th>TYP</th>
                                <th>PRACOVNÍ VZTAH</th>
                                <th>OD</th>
                                <th>DO</th>
                                <th>STAV</th>
                            </tr>
                        </thead>

                        <tbody
                            id="drayviaRealDriverAssignmentHistoryRows"
                        >
                            <tr>
                                <td
                                    colspan="6"
                                    class="drayvia-real-driver-empty"
                                >
                                    —
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <form
                id="drayviaRealDriverAssignmentTransferForm"
                style="
                    margin-top:16px;
                    padding-top:16px;
                    border-top:1px solid #e4e7ec;
                "
            >
                <div
                    style="
                        margin-bottom:12px;
                        font-size:11px;
                        font-weight:900;
                        color:#344054;
                    "
                >
                    ZMĚNIT PŘIŘAZENÍ
                </div>

                <div class="drayvia-real-driver-form-grid">
                    <label>
                        <span>NOVÝ DOPRAVCE *</span>

                        <select
                            id="drayviaRealDriverAssignmentOrganization"
                            name="organization_id"
                            required
                        >
                            <option value="">
                                VYBERTE DOPRAVCE
                            </option>
                        </select>
                    </label>

                    <label>
                        <span>ZMĚNA OD *</span>

                        <input
                            id="drayviaRealDriverAssignmentValidFrom"
                            name="valid_from"
                            type="date"
                            required
                        >
                    </label>

                    <label
                        id="drayviaRealDriverAssignmentEmploymentField"
                        hidden
                    >
                        <span>PRACOVNÍ VZTAH *</span>

                        <select
                            id="drayviaRealDriverAssignmentEmployment"
                            name="employment_type"
                        >
                            <option value="">VYBERTE</option>
                            <option value="employee">ZAMĚSTNANEC</option>
                            <option value="dpp">DPP</option>
                            <option value="dpc">DPČ</option>
                            <option value="other">JINÁ</option>
                        </select>
                    </label>

                    <label>
                        <span>DŮVOD ZMĚNY</span>

                        <input
                            id="drayviaRealDriverAssignmentReason"
                            name="end_reason"
                            type="text"
                            maxlength="1000"
                            placeholder="Volitelné"
                        >
                    </label>
                </div>

                <div
                    id="drayviaRealDriverAssignmentPreview"
                    style="
                        margin-top:14px;
                        padding:12px 14px;
                        border:1px solid #d0d5dd;
                        border-radius:9px;
                        background:#ffffff;
                        color:#344054;
                        font-size:12px;
                        font-weight:700;
                        line-height:1.5;
                    "
                >
                    Vyberte nového dopravce a datum účinnosti.
                </div>

                <div
                    class="drayvia-preview-actions"
                    style="margin-top:14px;"
                >
                    <button
                        id="drayviaRealDriverAssignmentTransferSubmit"
                        class="drayvia-preview-action primary"
                        type="submit"
                    >
                        ULOŽIT ZMĚNU
                    </button>
                </div>
            </form>
        </div>
<div class="drayvia-preview-panel">
            <div class="drayvia-preview-panel-head">
                <h2 class="drayvia-preview-panel-title">
                    SEZNAM ŘIDIČŮ
                </h2>

                <div class="drayvia-preview-panel-subtitle">
                    Skutečná data z databáze DRAYVIA.
                </div>
            </div>

                    <div
            class="drayvia-real-driver-filters"
            style="
                display:flex;
                flex-wrap:wrap;
                align-items:flex-end;
                gap:10px;
                padding:14px 16px;
                border-top:1px solid #e4e7ec;
                border-bottom:1px solid #e4e7ec;
                background:#f8fafc;
            "
        >
            <label style="flex:1 1 260px; min-width:220px;">
                <span style="display:block; margin-bottom:5px; font-size:10px; font-weight:900; color:#667085;">
                    RYCHLÉ HLEDÁNÍ
                </span>
                <input
                    id="drayviaRealDriverSearch" class="drayvia-filter-control drayvia-filter-search"
                    type="search"
                    autocomplete="off"
                    placeholder="Jméno, ID nebo dopravce…"
                    style="width:100%; height:38px; padding:0 12px; border:1px solid #d0d5dd; border-radius:9px; background:#fff; font:inherit;"
                >
            </label>

            <label style="min-width:140px;">
                <span style="display:block; margin-bottom:5px; font-size:10px; font-weight:900; color:#667085;">
                    TYP
                </span>
                <select
                    id="drayviaRealDriverTypeFilter" class="drayvia-filter-control"
                    style="height:38px; padding:0 10px; border:1px solid #d0d5dd; border-radius:9px; background:#fff;"
                >
                    <option value="all">VŠECHNY TYPY</option>
                    <option value="own">VLASTNÍ</option>
                    <option value="external">EXTERNÍ</option>
                    <option value="unassigned">BEZ PŘIŘAZENÍ</option>
                </select>
            </label>

            <label style="min-width:190px;">
                <span style="display:block; margin-bottom:5px; font-size:10px; font-weight:900; color:#667085;">
                    DOPRAVCE
                </span>
                <select
                    id="drayviaRealDriverCarrierFilter" class="drayvia-filter-control"
                    style="height:38px; max-width:220px; padding:0 10px; border:1px solid #d0d5dd; border-radius:9px; background:#fff;"
                >
                    <option value="all">VŠICHNI DOPRAVCI</option>
                </select>
            </label>

            <label style="min-width:170px;">
                <span style="display:block; margin-bottom:5px; font-size:10px; font-weight:900; color:#667085;">
                    PRACOVNÍ VZTAH
                </span>
                <select
                    id="drayviaRealDriverEmploymentFilter" class="drayvia-filter-control"
                    style="height:38px; padding:0 10px; border:1px solid #d0d5dd; border-radius:9px; background:#fff;"
                >
                    <option value="all">VŠECHNY VZTAHY</option>
                    <option value="employee">ZAMĚSTNANEC</option>
                    <option value="dpp">DPP</option>
                    <option value="dpc">DPČ</option>
                    <option value="other">JINÁ</option>
                    <option value="none">BEZ VZTAHU</option>
                </select>
            </label>

            <label style="min-width:145px;">
                <span style="display:block; margin-bottom:5px; font-size:10px; font-weight:900; color:#667085;">
                    STAV
                </span>
                <select
                    id="drayviaRealDriverStatusFilter" class="drayvia-filter-control"
                    style="height:38px; padding:0 10px; border:1px solid #d0d5dd; border-radius:9px; background:#fff;"
                >
                    <option value="all">VŠECHNY STAVY</option>
                    <option value="active">AKTIVNÍ</option>
                    <option value="ended">UKONČENO</option>
                    <option value="scheduled">PLÁNOVÁNO</option>
                    <option value="unassigned">BEZ PŘIŘAZENÍ</option>
                </select>
            </label>

            <button
                id="drayviaRealDriverFilterReset"
                class="drayvia-preview-action drayvia-filter-reset"
                type="button"
                style="height:38px;"
            >
                VYMAZAT FILTRY
            </button>

            <div
                id="drayviaRealDriverFilteredCount"
                style="
                    margin-left:auto;
                    padding:0 4px 10px;
                    white-space:nowrap;
                    font-size:10px;
                    font-weight:900;
                    color:#667085;
                "
            >
                ZOBRAZENO —
            </div>
        </div>
<div class="drayvia-real-driver-table-wrap">
                <table class="drayvia-real-driver-table">
                    <thead>
                        <tr>
                            <th>ŘIDIČ</th>
                            <th>ID</th>
                            <th>TYP</th>
                            <th>DOPRAVCE</th>
                            <th>PRACOVNÍ VZTAH</th>
                            <th>PLATNOST</th>
                            <th>STAV</th>
                            <th>AKCE</th>
                        </tr>
                    </thead>

                    <tbody id="drayviaRealDriverRows">
                        <tr>
                            <td
                                colspan="8"
                                class="drayvia-real-driver-empty"
                            >
                                NAČÍTÁM…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;
    const statistics = () => `
        ${header(
            'Statistiky',
            'Provozn\u00ed statistiky aktivn\u00edch \u0159idi\u010d\u016f podle skute\u010dn\u00fdch tras DRAYVIA.'
        )}

        <div
            id="drayviaDriverStatisticsHost"
            class="drayvia-preview-panel drayvia-driver-statistics"
        ></div>
    `;
const fuel = () => `
        ${header(
            'PHM',
            'Import a kontrola tankování MOL a ORLEN, spotřeby a skutečných nákladů na palivo.'
        )}

        <div class="drayvia-preview-actions">
            <button class="drayvia-preview-action primary" type="button">
                Import MOL
            </button>
            <button class="drayvia-preview-action primary" type="button">
                Import ORLEN
            </button>
            <button class="drayvia-preview-action" type="button">
                Palivové karty
            </button>
            <button class="drayvia-preview-action" type="button">
                Přiřazení karet řidičům
            </button>
        </div>

        <div class="drayvia-preview-grid" style="margin-top:18px;">
            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Tankování</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Litry</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Náklady PHM</div>
                <div class="drayvia-preview-card-value">— Kč</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Ke kontrole</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>
        </div>

        <div class="drayvia-preview-panel">
            <div class="drayvia-preview-panel-head">
                <h2 class="drayvia-preview-panel-title">Tankování</h2>
                <div class="drayvia-preview-panel-subtitle">
                    Datum · řidič · karta · stanice · litry · cena · přiřazení.
                </div>
            </div>

            <div class="drayvia-preview-panel-body">
                Přehled importovaných transakcí bude zde.
            </div>
        </div>
    `;

    const finance = () => `
                ${pageHeader(
                    'Finance',
                    'Odběratelé, ceníky, fakturace, srovnání a ziskovost v jednom finančním prostoru.'
                )}

                <style>
                    .drayvia-finance-shell {
                        display: grid;
                        gap: 18px;
                    }

                    .drayvia-finance-tab-input,
                    .drayvia-price-list-tab-input {
                        position: absolute;
                        opacity: 0;
                        pointer-events: none;
                    }

                    .drayvia-finance-tabs,
                    .drayvia-price-list-tabs {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                    }

                    .drayvia-finance-tab,
                    .drayvia-price-list-tab {
                        cursor: pointer;
                        border: 1px solid #d7dce3;
                        border-radius: 10px;
                        padding: 10px 14px;
                        background: #fff;
                        font-weight: 700;
                    }

                    .drayvia-finance-panel,
                    .drayvia-price-list-panel {
                        display: none;
                    }

                    #finance-tab-customers:checked
                        ~ .drayvia-finance-tabs
                        label[for="finance-tab-customers"],
                    #finance-tab-price-lists:checked
                        ~ .drayvia-finance-tabs
                        label[for="finance-tab-price-lists"],
                    #finance-tab-billing:checked
                        ~ .drayvia-finance-tabs
                        label[for="finance-tab-billing"],
                    #finance-tab-comparison:checked
                        ~ .drayvia-finance-tabs
                        label[for="finance-tab-comparison"],
                    #finance-tab-profitability:checked
                        ~ .drayvia-finance-tabs
                        label[for="finance-tab-profitability"],
                    #price-list-tab-billing:checked
                        ~ .drayvia-price-list-tabs
                        label[for="price-list-tab-billing"],
                    #price-list-tab-drivers:checked
                        ~ .drayvia-price-list-tabs
                        label[for="price-list-tab-drivers"] {
                        border-color: #1f2937;
                        background: #f3f4f6;
                    }

                    #finance-tab-customers:checked
                        ~ .drayvia-finance-panels
                        .drayvia-finance-panel-customers,
                    #finance-tab-price-lists:checked
                        ~ .drayvia-finance-panels
                        .drayvia-finance-panel-price-lists,
                    #finance-tab-billing:checked
                        ~ .drayvia-finance-panels
                        .drayvia-finance-panel-billing,
                    #finance-tab-comparison:checked
                        ~ .drayvia-finance-panels
                        .drayvia-finance-panel-comparison,
                    #finance-tab-profitability:checked
                        ~ .drayvia-finance-panels
                        .drayvia-finance-panel-profitability,
                    #price-list-tab-billing:checked
                        ~ .drayvia-price-list-panels
                        .drayvia-price-list-panel-billing,
                    #price-list-tab-drivers:checked
                        ~ .drayvia-price-list-panels
                        .drayvia-price-list-panel-drivers {
                        display: block;
                    }

                    .drayvia-finance-card {
                        border: 1px solid #e5e7eb;
                        border-radius: 14px;
                        background: #fff;
                        padding: 18px;
                    }

                    .drayvia-finance-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                        gap: 14px;
                    }

                    .drayvia-finance-field {
                        display: grid;
                        gap: 6px;
                    }

                    .drayvia-finance-field label {
                        font-size: 13px;
                        font-weight: 700;
                    }

                    .drayvia-finance-field input,
                    .drayvia-finance-field select {
                        width: 100%;
                        box-sizing: border-box;
                        border: 1px solid #cfd5dd;
                        border-radius: 8px;
                        padding: 10px 11px;
                        background: #fff;
                    }

                    .drayvia-finance-note {
                        border-left: 4px solid #9ca3af;
                        padding: 10px 12px;
                        background: #f8fafc;
                    }

                    .drayvia-finance-status {
                        display: inline-flex;
                        align-items: center;
                        border-radius: 999px;
                        padding: 5px 9px;
                        background: #f3f4f6;
                        font-size: 12px;
                        font-weight: 700;
                    }

                    .drayvia-price-table,
                    .drayvia-customer-table {
                        width: 100%;
                        border-collapse: collapse;
                    }

                    .drayvia-price-table th,
                    .drayvia-price-table td,
                    .drayvia-customer-table th,
                    .drayvia-customer-table td {
                        text-align: left;
                        border-bottom: 1px solid #e5e7eb;
                        padding: 10px 8px;
                        vertical-align: middle;
                    }

                    .drayvia-price-table input {
                        width: 100%;
                        min-width: 110px;
                        box-sizing: border-box;
                        border: 1px solid #cfd5dd;
                        border-radius: 8px;
                        padding: 9px 10px;
                    }
                </style>

                <section class="drayvia-finance-shell" data-finance-root>
                    <input
                        class="drayvia-finance-tab-input"
                        id="finance-tab-customers"
                        name="finance-tab"
                        type="radio"
                        checked
                    >
                    <input
                        class="drayvia-finance-tab-input"
                        id="finance-tab-price-lists"
                        name="finance-tab"
                        type="radio"
                    >
                    <input
                        class="drayvia-finance-tab-input"
                        id="finance-tab-billing"
                        name="finance-tab"
                        type="radio"
                    >
                    <input
                        class="drayvia-finance-tab-input"
                        id="finance-tab-comparison"
                        name="finance-tab"
                        type="radio"
                    >
                    <input
                        class="drayvia-finance-tab-input"
                        id="finance-tab-profitability"
                        name="finance-tab"
                        type="radio"
                    >

                    <nav class="drayvia-finance-tabs" aria-label="Finance">
                        <label class="drayvia-finance-tab" for="finance-tab-customers">
                            Odběratelé
                        </label>
                        <label class="drayvia-finance-tab" for="finance-tab-price-lists">
                            Ceníky
                        </label>
                        <label class="drayvia-finance-tab" for="finance-tab-billing">
                            Fakturace
                        </label>
                        <label class="drayvia-finance-tab" for="finance-tab-comparison">
                            Srovnání
                        </label>
                        <label class="drayvia-finance-tab" for="finance-tab-profitability">
                            Ziskovost
                        </label>
                    </nav>

                    <div class="drayvia-finance-panels">
                        <section
                            class="drayvia-finance-panel drayvia-finance-panel-customers"
                            data-finance-panel="customers"
                            data-customer-index-endpoint="/api/v1/customers"
                        >
                            <div class="drayvia-finance-card">
                                <h3>Odběratelé</h3>
                                <p>
                                    Odběratel je obchodní role existující organizace.
                                </p>

                                <div class="drayvia-finance-note">
                                    Směr vztahu zůstává:
                                    odběratel/customer = source,
                                    DRAYVIA/provider = target.
                                </div>
                                <form
                                    data-customer-create-form
                                    style="margin-top: 18px;"
                                >
                                    <div class="drayvia-finance-grid">
                                        <div class="drayvia-finance-field">
                                            <label for="finance-customer-registration-number">
                                                IČO odběratele
                                            </label>
                                            <input
                                                id="finance-customer-registration-number"
                                                data-customer-registration-number
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]{8}"
                                                maxlength="8"
                                                placeholder="8 číslic"
                                                required
                                            >
                                        </div>

                                        <div class="drayvia-finance-field">
                                            <label for="finance-customer-valid-from">
                                                Platnost vztahu od
                                            </label>
                                            <input
                                                id="finance-customer-valid-from"
                                                data-customer-valid-from
                                                type="date"
                                                required
                                            >
                                        </div>

                                        <div class="drayvia-finance-field">
                                            <label>&nbsp;</label>
                                            <button
                                                type="submit"
                                                data-customer-create-submit
                                            >
                                                Přidat odběratele
                                            </button>
                                        </div>
                                    </div>

                                    <p
                                        data-customer-create-message
                                        class="drayvia-finance-note"
                                        style="margin-top: 12px;"
                                        hidden
                                    ></p>
                                </form>

                                <div style="overflow-x: auto; margin-top: 18px;">
                                    <table class="drayvia-customer-table">
                                        <thead>
                                            <tr>
                                                <th>Odběratel</th>
                                                <th>IČO</th>
                                                <th>Vztah</th>
                                                <th>Fakturační ceníky</th>
                                                <th>Stav</th>
                                            </tr>
                                        </thead>
                                        <tbody data-customer-list>
                                            <tr>
                                                <td colspan="5">
                                                    Datové načtení odběratelů bude
                                                    připojeno k připravenému API
                                                    v navazující jednotce.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div
                                    class="drayvia-finance-card"
                                    style="margin-top: 18px;"
                                    data-customer-detail
                                >
                                    <h4>Detail odběratele</h4>
                                    <p>
                                        Detail zobrazí identitu firmy,
                                        platnost obchodního vztahu a všechny
                                        jeho současné i historické fakturační ceníky.
                                    </p>
                                    <span class="drayvia-finance-status">
                                        GET /api/v1/customers/{relationship}
                                    </span>
                                </div>
                            </div>
                        </section>

                        <section
                            class="drayvia-finance-panel drayvia-finance-panel-price-lists"
                            data-finance-panel="price-lists"
                        >
                            <div class="drayvia-finance-card">
                                <h3>Ceníky</h3>
                                <p>
                                    Fakturační ceníky a ceníky řidičů jsou vedené
                                    jako dva samostatné finanční vztahy.
                                </p>

                                <div class="drayvia-finance-shell">
                                    <input
                                        class="drayvia-price-list-tab-input"
                                        id="price-list-tab-billing"
                                        name="price-list-tab"
                                        type="radio"
                                        checked
                                    >
                                    <input
                                        class="drayvia-price-list-tab-input"
                                        id="price-list-tab-drivers"
                                        name="price-list-tab"
                                        type="radio"
                                    >

                                    <nav class="drayvia-price-list-tabs">
                                        <label
                                            class="drayvia-price-list-tab"
                                            for="price-list-tab-billing"
                                        >
                                            Fakturační ceníky
                                        </label>
                                        <label
                                            class="drayvia-price-list-tab"
                                            for="price-list-tab-drivers"
                                        >
                                            Ceníky řidičů
                                        </label>
                                    </nav>

                                    <div class="drayvia-price-list-panels">
                                        <section
                                            class="drayvia-price-list-panel drayvia-price-list-panel-billing"
                                            data-price-list-panel="billing"
                                            data-provider-managed-price-list-endpoint="/api/v1/customers/{relationship}/price-lists"
                                        >
                                            <div class="drayvia-finance-card">
                                                <h4>Nový fakturační ceník odběratele</h4>

                                                <div class="drayvia-finance-grid">
                                                    <div class="drayvia-finance-field">
                                                        <label for="billing-price-list-customer">
                                                            Odběratel
                                                        </label>
                                                        <select
                                                            id="billing-price-list-customer"
                                                            data-billing-price-list-customer
                                                        >
                                                            <option value="">
                                                                Vyberte odběratele
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <div class="drayvia-finance-field">
                                                        <label for="billing-price-list-name">
                                                            Název ceníku
                                                        </label>
                                                        <input
                                                            id="billing-price-list-name"
                                                            data-billing-price-list-name
                                                            type="text"
                                                            placeholder="Např. Fakturační ceník 2026"
                                                         required>
                                                    </div>

                                                    <div class="drayvia-finance-field">
                                                        <label for="billing-price-list-currency">
                                                            Měna
                                                        </label>
                                                        <select id="billing-price-list-currency"
                                                            data-billing-price-list-currency>
                                                            <option value="CZK">CZK</option>
                                                            <option value="EUR">EUR</option>
                                                        </select>
                                                    </div>

                                                    <div class="drayvia-finance-field">
                                                        <label for="billing-price-list-valid-from">
                                                            Platnost od
                                                        </label>
                                                        <input
                                                            id="billing-price-list-valid-from"
                                                            data-billing-price-list-valid-from
                                                            type="date"
                                                         required>
                                                    </div>

                                                    <div class="drayvia-finance-field">
                                                        <label for="billing-price-list-valid-until">
                                                            Platnost do
                                                        </label>
                                                        <input
                                                            id="billing-price-list-valid-until"
                                                            data-billing-price-list-valid-until
                                                            type="date"
                                                        >
                                                    </div>
                                                </div>

                                                <div style="overflow-x: auto; margin-top: 18px;">
                                                    <table class="drayvia-price-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Položka</th>
                                                                <th>Jednotka</th>
                                                                <th>Sazba</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr data-pricing-code="delivered_parcels">
                                                                <td>Doručená zásilka</td>
                                                                <td>zásilka</td>
                                                                <td>
                                                                    <input
                                                                        data-price-list-rate="delivered_parcels"
                                                                        type="number"
                                                                        min="0"
                                                                        step="0.0001"
                                                                     required>
                                                                </td>
                                                            </tr>
                                                            <tr data-pricing-code="redirected_parcels">
                                                                <td>Přesměrovaná zásilka</td>
                                                                <td>zásilka</td>
                                                                <td>
                                                                    <input
                                                                        data-price-list-rate="redirected_parcels"
                                                                        type="number"
                                                                        min="0"
                                                                        step="0.0001"
                                                                     required>
                                                                </td>
                                                            </tr>
                                                            <tr data-pricing-code="undelivered_parcels">
                                                                <td>Nedoručená zásilka</td>
                                                                <td>zásilka</td>
                                                                <td>
                                                                    <input
                                                                        data-price-list-rate="undelivered_parcels"
                                                                        type="number"
                                                                        min="0"
                                                                        step="0.0001"
                                                                     required>
                                                                </td>
                                                            </tr>
                                                            <tr data-pricing-code="actual_km">
                                                                <td>Skutečný kilometr</td>
                                                                <td>km</td>
                                                                <td>
                                                                    <input
                                                                        data-price-list-rate="actual_km"
                                                                        type="number"
                                                                        min="0"
                                                                        step="0.0001"
                                                                     required>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div
                                                    style="margin-top: 18px;"
                                                >
                                                    <button
                                                        type="button"
                                                        data-billing-price-list-save
                                                    >
                                                        Uložit fakturační ceník
                                                    </button>

                                                    <p
                                                        data-billing-price-list-message
                                                        class="drayvia-finance-note"
                                                        style="margin-top: 12px;"
                                                        hidden
                                                    ></p>

                                                    <div
                                                        class="drayvia-finance-note"
                                                        style="margin-top: 12px;"
                                                    >
                                                        Ceník se uloží jako kompletní
                                                        draft v1. Schválení a aktivace
                                                        zůstávají samostatné kroky.
                                                    </div>
                                                </div>
                                            </div>
                                        </section>

                                        <section
                                            class="drayvia-price-list-panel drayvia-price-list-panel-drivers"
                                            data-price-list-panel="drivers"
                                        >
                                            <div class="drayvia-finance-card">
                                                <h4>Ceníky řidičů</h4>
                                                <p>
                                                    Ceníky řidičů zůstávají samostatným
                                                    driver-specific finančním kontraktem
                                                    a nepoužívají organization Price List
                                                    jen proto, že mají podobné sazby.
                                                </p>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section
                            class="drayvia-finance-panel drayvia-finance-panel-billing"
                            data-finance-panel="billing"
                        >
                            <div class="drayvia-finance-card">
                                <h3>Fakturace</h3>
                                <p>
                                    Přehled odběratelů, období, tras a částek
                                    připravených k vyúčtování.
                                </p>
                            </div>
                        </section>

                        <section
                            class="drayvia-finance-panel drayvia-finance-panel-comparison"
                            data-finance-panel="comparison"
                        >
                            <div class="drayvia-finance-card">
                                <h3>Srovnání</h3>
                                <p>
                                    Fakturace odběrateli versus náklad řidiče,
                                    v korunách i procentech.
                                </p>
                            </div>
                        </section>

                        <section
                            class="drayvia-finance-panel drayvia-finance-panel-profitability"
                            data-finance-panel="profitability"
                        >
                            <div class="drayvia-finance-card">
                                <h3>Ziskovost</h3>
                                <p>
                                    První úroveň je Hrubá marže Kč a Marže %.
                                    Dokud nejsou zahrnuté všechny relevantní
                                    náklady, nebude výsledek označen jako čistý zisk.
                                </p>
                            </div>
                        </section>
                    </div>
                </section>
            `;



    const bank = () => `
        ${header(
            'Banka',
            'Import bankovního výpisu a kontrola skutečně provedených plateb.'
        )}

        <div class="drayvia-preview-actions">
            <button class="drayvia-preview-action primary" type="button">
                Import bankovního výpisu
            </button>
            <button class="drayvia-preview-action" type="button">
                Pravidla párování
            </button>
            <button class="drayvia-preview-action" type="button">
                Kategorie plateb
            </button>
        </div>

        <div class="drayvia-preview-grid" style="margin-top:18px;">
            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Transakce</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Automaticky přiřazeno</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Nepřiřazeno</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Kontrola plateb</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>
        </div>

        <div class="drayvia-preview-panel">
            <div class="drayvia-preview-panel-head">
                <h2 class="drayvia-preview-panel-title">Bankovní transakce</h2>
                <div class="drayvia-preview-panel-subtitle">
                    Platba · protistrana · částka · kategorie · přiřazení.
                </div>
            </div>

            <div class="drayvia-preview-panel-body">
                Zde budeme řešit pouze položky, které systém nedokáže bezpečně přiřadit sám.
            </div>
        </div>
    `;

    const imports = () => `
        ${header(
            'Importy',
            'Centrální historie všech importů provedených v jednotlivých částech DRAYVIA.'
        )}

        <div class="drayvia-preview-grid">
            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Importy celkem</div>
                <div class="drayvia-preview-card-value">—</div>
                <div class="drayvia-preview-card-note">Všechny zdrojové soubory.</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Úspěšné</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">S chybou</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>

            <div class="drayvia-preview-card">
                <div class="drayvia-preview-card-label">Ke kontrole</div>
                <div class="drayvia-preview-card-value">—</div>
            </div>
        </div>

        <div class="drayvia-preview-panel">
            <div class="drayvia-preview-panel-head">
                <h2 class="drayvia-preview-panel-title">Historie importů</h2>
                <div class="drayvia-preview-panel-subtitle">
                    Soubor · období · typ · stav · počet položek · chyby.
                </div>
            </div>

            <div class="drayvia-preview-panel-body">
                Historie všech provedených importů bude zde.
            </div>
        </div>
    `;

    const settings = () => `
        ${header(
            'Nastavení',
            'Zásadní provozní pravidla a globální nastavení celé DRAYVIA.'
        )}

        <div class="drayvia-settings-grid">
            <div class="drayvia-settings-tile">
                <strong>Firma a provoz</strong>
                <span>
                    Identifikační údaje firmy a základní parametry provozu.
                </span>
            </div>

            <div class="drayvia-settings-tile">
                <strong>Provozní pravidla</strong>
                <span>
                    Globální pravidla, která platí napříč celým systémem.
                </span>
            </div>

            <div class="drayvia-settings-tile">
                <strong>Kontroly a tolerance</strong>
                <span>
                    Zásadní limity pro automatické kontroly a upozornění.
                </span>
            </div>

            <div class="drayvia-settings-tile">
                <strong>Uzavírání období</strong>
                <span>
                    Podmínky potřebné pro bezpečné uzavření měsíce.
                </span>
            </div>

            <div class="drayvia-settings-tile">
                <strong>Výchozí hodnoty</strong>
                <span>
                    Společné výchozí chování používané napříč DRAYVIA.
                </span>
            </div>

            <div class="drayvia-settings-tile">
                <strong>Systém</strong>
                <span>
                    Základní technické a provozní informace aplikace.
                </span>
            </div>
        </div>
    `;
const templates = {
        overview,
        calendar,
        drivers,
        statistics,
        fuel,
        finance,
        bank,
        imports,
        settings
    };

    let currentPage = null;

    const setActiveMenu = (page) => {
        document
            .querySelectorAll('[data-drayvia-page]')
            .forEach((button) => {
                button.classList.toggle(
                    'active',
                    button.dataset.drayviaPage === page
                );
            });
    };

    document.addEventListener(
        'input',
        (event) => {
            if (
                event.target?.id !==
                'drayviaRealDriverSearch'
            ) {
                return;
            }

            realDriverFilterState.search =
                event.target.value;

            renderRealDriverRows();
        }
    );

    document.addEventListener(
        'change',
        (event) => {
            const filterMap = {
                drayviaRealDriverTypeFilter:
                    'type',
                drayviaRealDriverCarrierFilter:
                    'carrier',
                drayviaRealDriverEmploymentFilter:
                    'employment',
                drayviaRealDriverStatusFilter:
                    'status',
            };

            const key =
                filterMap[event.target?.id];

            if (!key) {
                return;
            }

            realDriverFilterState[key] =
                event.target.value;

            renderRealDriverRows();
        }
    );

    document.addEventListener(
        'click',
        (event) => {
            if (
                !event.target.closest(
                    '#drayviaRealDriverFilterReset'
                )
            ) {
                return;
            }

            event.preventDefault();

            realDriverFilterState.search = '';
            realDriverFilterState.type = 'all';
            realDriverFilterState.carrier = 'all';
            realDriverFilterState.employment = 'all';
            realDriverFilterState.status = 'all';

            renderRealDriverRows();

            document
                .getElementById(
                    'drayviaRealDriverSearch'
                )
                ?.focus();
        }
    );
    document.addEventListener(
        'click',
        (event) => {
            const manage =
                event.target.closest(
                    '[data-driver-assignment-manage]'
                );

            if (manage) {
                event.preventDefault();

                const driverId =
                    Number(
                        manage.dataset
                            .driverAssignmentManage
                    );

                const driver =
                    realDriverState.items.find(
                        (item) =>
                            Number(item.id) ===
                            driverId
                    );

                if (driver) {
                    openRealDriverAssignmentPanel(
                        driver
                    );
                }

                return;
            }

            if (
                event.target.closest(
                    '#drayviaRealDriverAssignmentClose'
                )
            ) {
                event.preventDefault();

                closeRealDriverAssignmentPanel();
            }
        }
    );

    document.addEventListener(
        'change',
        (event) => {
            if (
                event.target?.id ===
                'drayviaRealDriverAssignmentOrganization'
            ) {
                syncRealDriverAssignmentEmployment();
                updateRealDriverAssignmentPreview();

                return;
            }

            if (
                event.target?.id ===
                'drayviaRealDriverAssignmentValidFrom'
            ) {
                updateRealDriverAssignmentPreview();
            }
        }
    );

    document.addEventListener(
        'input',
        (event) => {
            if (
                event.target?.id ===
                'drayviaRealDriverAssignmentValidFrom'
            ) {
                updateRealDriverAssignmentPreview();
            }
        }
    );

    document.addEventListener(
        'submit',
        (event) => {
            if (
                event.target?.id !==
                'drayviaRealDriverAssignmentTransferForm'
            ) {
                return;
            }

            event.preventDefault();

            submitRealDriverAssignmentTransfer(
                event.target
            );
        }
    );
    const bindPeriodControls = () => {
        const mode =
            document.getElementById(
                'drayviaPeriodMode'
            );

        const month =
            document.getElementById(
                'drayviaPreviewMonth'
            );

        const year =
            document.getElementById(
                'drayviaPreviewYear'
            );

        if (mode) {
            mode.addEventListener(
                'change',
                () => {
                    periodMode =
                        mode.value;

                    if (
                        periodMode ===
                        'current_month'
                    ) {
                        selectedMonth =
                            currentMonthValue();

                        selectedYear =
                            selectedMonth.slice(
                                0,
                                4
                            );
                    }

                    if (
                        periodMode ===
                        'current_year'
                    ) {
                        selectedYear =
                            currentYearValue();
                    }

                    if (
                        currentPage &&
                        templates[currentPage]
                    ) {
                        render(
                            currentPage
                        );
                    }
                }
            );
        }

        if (month) {
            month.addEventListener(
                'change',
                () => {
                    if (!month.value) {
                        return;
                    }

                    selectedMonth =
                        month.value;

                    selectedYear =
                        selectedMonth.slice(
                            0,
                            4
                        );

                    if (
                        currentPage &&
                        templates[currentPage]
                    ) {
                        render(
                            currentPage
                        );
                    }
                }
            );
        }

        if (year) {
            year.addEventListener(
                'change',
                () => {
                    selectedYear =
                        year.value;

                    if (
                        currentPage &&
                        templates[currentPage]
                    ) {
                        render(
                            currentPage
                        );
                    }
                }
            );
        }
    };
            // S021-03E READ-ONLY FINANCE CUSTOMER LOADER
            const financeCustomerDate = (value) => {
                if (!value) {
                    return '\u2014';
                }

                const parts = String(value).slice(0, 10).split('-');

                if (parts.length !== 3) {
                    return String(value);
                }

                return `${parts[2]}.${parts[1]}.${parts[0]}`;
            };

            const financeCustomerStatus = (value) => {
                const statuses = {
                    active: 'Aktivn\u00ed',
                    inactive: 'Neaktivn\u00ed',
                    suspended: 'Pozastaven\u00fd',
                    draft: 'Koncept',
                    approved: 'Schv\u00e1len\u00fd',
                    active_price_list: 'Aktivn\u00ed',
                    replaced: 'Nahrazen\u00fd',
                    expired: 'Ukon\u010den\u00fd',
                    archived: 'Archivovan\u00fd',
                };

                return statuses[value] || value || '\u2014';
            };

            const financeCustomerRelationshipPeriod = (item) => {
                const from =
                    financeCustomerDate(
                        item?.relationship_valid_from
                    );

                const until =
                    financeCustomerDate(
                        item?.relationship_valid_until
                    );

                return `${from} \u2013 ${until}`;
            };

            const financeCustomerPriceListSummary = (priceLists) => {
                const items =
                    Array.isArray(priceLists)
                        ? priceLists
                        : [];

                if (items.length === 0) {
                    return 'Bez faktura\u010dn\u00edho cen\u00edku';
                }

                const current =
                    items.filter(
                        (item) =>
                            item?.status === 'active'
                            || item?.status === 'draft'
                    );

                if (current.length === 0) {
                    return `${items.length} historick\u00fdch`;
                }

                return `${current.length} aktu\u00e1ln\u00ed / ${items.length} celkem`;
            };

            const renderFinanceCustomerDetail = (item) => {
                const root =
                    document.querySelector(
                        '[data-finance-root]'
                    );

                const detail =
                    root?.querySelector(
                        '[data-customer-detail]'
                    );

                if (!detail) {
                    return;
                }

                detail.replaceChildren();

                const title =
                    document.createElement('h4');

                title.textContent =
                    item?.customer?.name
                    || 'Detail odb\u011bratele';

                const identity =
                    document.createElement('div');

                identity.className =
                    'drayvia-finance-grid';

                const identityRows = [
                    [
                        'I\u010cO',
                        item?.customer?.registration_number
                            || '\u2014',
                    ],
                    [
                        'DI\u010c',
                        item?.customer?.vat_number
                            || '\u2014',
                    ],
                    [
                        'Stav firmy',
                        financeCustomerStatus(
                            item?.customer?.status
                        ),
                    ],
                    [
                        'Platnost vztahu',
                        financeCustomerRelationshipPeriod(
                            item
                        ),
                    ],
                ];

                identityRows.forEach(
                    ([label, value]) => {
                        const field =
                            document.createElement('div');

                        field.className =
                            'drayvia-finance-field';

                        const key =
                            document.createElement('label');

                        key.textContent = label;

                        const data =
                            document.createElement('strong');

                        data.textContent =
                            String(value ?? '\u2014');

                        field.append(
                            key,
                            data
                        );

                        identity.appendChild(field);
                    }
                );

                const priceTitle =
                    document.createElement('h4');

                priceTitle.textContent =
                    'Faktura\u010dn\u00ed cen\u00edky';

                priceTitle.style.marginTop =
                    '18px';

                const priceLists =
                    Array.isArray(item?.price_lists)
                        ? item.price_lists
                        : [];

                const priceTable =
                    document.createElement('table');

                priceTable.className =
                    'drayvia-customer-table';

                const priceHead =
                    document.createElement('thead');

                const priceHeadRow =
                    document.createElement('tr');

                [
                    'Cen\u00edk',
                    'Stav',
                    'Verze',
                    'Spr\u00e1va',
                ].forEach((label) => {
                    const cell =
                        document.createElement('th');

                    cell.textContent = label;

                    priceHeadRow.appendChild(cell);
                });

                priceHead.appendChild(priceHeadRow);
                priceTable.appendChild(priceHead);

                const priceBody =
                    document.createElement('tbody');

                if (priceLists.length === 0) {
                    const row =
                        document.createElement('tr');

                    const cell =
                        document.createElement('td');

                    cell.colSpan = 4;
                    cell.textContent =
                        'Odb\u011bratel zat\u00edm nem\u00e1 evidovan\u00fd faktura\u010dn\u00ed cen\u00edk.';

                    row.appendChild(cell);
                    priceBody.appendChild(row);
                }
                else {
                    priceLists.forEach(
                        (priceList) => {
                            const row =
                                document.createElement('tr');

                            const values = [
                                priceList?.name || '\u2014',
                                financeCustomerStatus(
                                    priceList?.status
                                ),
                                priceList?.current_version
                                    ?? '\u2014',
                                priceList?.managed_by_provider
                                    ? 'DRAYVIA'
                                    : 'Odb\u011bratel',
                            ];

                            values.forEach((value) => {
                                const cell =
                                    document.createElement('td');

                                cell.textContent =
                                    String(value);

                                row.appendChild(cell);
                            });

                            priceBody.appendChild(row);
                        }
                    );
                }

                priceTable.appendChild(priceBody);

                detail.append(
                    title,
                    identity,
                    priceTitle,
                    priceTable
                );
            };

            const loadFinanceCustomerDetail = async (
                relationshipId
            ) => {
                if (!relationshipId) {
                    return;
                }

                const root =
                    document.querySelector(
                        '[data-finance-root]'
                    );

                const detail =
                    root?.querySelector(
                        '[data-customer-detail]'
                    );

                const select =
                    root?.querySelector(
                        '[data-billing-price-list-customer]'
                    );

                if (!root || !detail) {
                    return;
                }

                detail.textContent =
                    'Na\u010d\u00edt\u00e1m detail odb\u011bratele\u2026';

                try {
                    const body =
                        await api(
                            `/api/v1/customers/${encodeURIComponent(relationshipId)}`
                        );

                    const item =
                        getPayload(body);

                    if (
                        !item
                        || Number(item.relationship_id)
                            !== Number(relationshipId)
                    ) {
                        throw new Error(
                            'API vr\u00e1tilo neo\u010dek\u00e1van\u00fd detail odb\u011bratele.'
                        );
                    }

                    if (select) {
                        select.value =
                            String(relationshipId);
                    }

                    renderFinanceCustomerDetail(
                        item
                    );
                }
                catch (error) {
                    detail.textContent =
                        `Detail odb\u011bratele se nepoda\u0159ilo na\u010d\u00edst: ${error.message}`;
                }
            };

                        /*
             * S021-03M BROWSER CUSTOMER CREATION
             *
             * Creates only the customer organization/business relationship.
             * Billing-price-list creation remains a separate workflow.
             */
            const bindFinanceCustomerCreate = () => {
                const root =
                    document.querySelector(
                        '[data-finance-root]'
                    );

                const form =
                    root?.querySelector(
                        '[data-customer-create-form]'
                    );

                if (
                    !root
                    || !form
                    || form.dataset.bound === '1'
                ) {
                    return;
                }

                const registrationNumber =
                    form.querySelector(
                        '[data-customer-registration-number]'
                    );

                const validFrom =
                    form.querySelector(
                        '[data-customer-valid-from]'
                    );

                const submit =
                    form.querySelector(
                        '[data-customer-create-submit]'
                    );

                const message =
                    form.querySelector(
                        '[data-customer-create-message]'
                    );

                if (
                    !registrationNumber
                    || !validFrom
                    || !submit
                    || !message
                ) {
                    return;
                }

                form.dataset.bound = '1';

                form.addEventListener(
                    'submit',
                    async (event) => {
                        event.preventDefault();

                        if (!form.reportValidity()) {
                            return;
                        }

                        const ico =
                            registrationNumber.value
                                .trim();

                        if (!/^[0-9]{8}$/.test(ico)) {
                            registrationNumber.setCustomValidity(
                                'IČO musí obsahovat přesně 8 číslic.'
                            );

                            registrationNumber.reportValidity();
                            registrationNumber.setCustomValidity('');
                            return;
                        }

                        submit.disabled = true;
                        message.hidden = false;
                        message.textContent =
                            'Zakládám odběratele a ověřuji IČO v ARES…';

                        try {
                            const body =
                                await api(
                                    '/api/v1/customers',
                                    {
                                        method: 'POST',
                                        body: JSON.stringify({
                                            registration_number:
                                                ico,
                                            relationship_valid_from:
                                                validFrom.value,
                                        }),
                                    }
                                );

                            const created =
                                getPayload(body);

                            const relationshipId =
                                Number(
                                    created?.relationship_id
                                );

                            registrationNumber.value = '';

                            message.textContent =
                                'Odběratel byl úspěšně přidán.';

                            await loadFinanceCustomers();

                            if (
                                Number.isInteger(
                                    relationshipId
                                )
                                && relationshipId > 0
                            ) {
                                await loadFinanceCustomerDetail(
                                    relationshipId
                                );
                            }
                        }
                        catch (error) {
                            message.textContent =
                                `Odběratele se nepodařilo přidat: ${error.message}`;
                        }
                        finally {
                            submit.disabled = false;
                        }
                    }
                );
            };
            /*
             * S021-03N ATOMIC BILLING DRAFT
             *
             * The provider-managed customer endpoint creates the PriceList,
             * draft version 1 and all canonical rate items in one backend
             * transaction. Approval and activation remain separate.
             */
            const bindFinanceBillingPriceListCreate = () => {
                const root =
                    document.querySelector(
                        '[data-finance-root]'
                    );

                const panel =
                    root?.querySelector(
                        '[data-provider-managed-price-list-endpoint]'
                    );

                if (
                    !root
                    || !panel
                    || panel.dataset.billingCreateBound === '1'
                ) {
                    return;
                }

                const customer =
                    panel.querySelector(
                        '[data-billing-price-list-customer]'
                    );

                const name =
                    panel.querySelector(
                        '[data-billing-price-list-name]'
                    );

                const currency =
                    panel.querySelector(
                        '[data-billing-price-list-currency]'
                    );

                const validFrom =
                    panel.querySelector(
                        '[data-billing-price-list-valid-from]'
                    );

                const validUntil =
                    panel.querySelector(
                        '[data-billing-price-list-valid-until]'
                    );

                const save =
                    panel.querySelector(
                        '[data-billing-price-list-save]'
                    );

                const message =
                    panel.querySelector(
                        '[data-billing-price-list-message]'
                    );

                const rateInputs =
                    Array.from(
                        panel.querySelectorAll(
                            '[data-price-list-rate]'
                        )
                    );

                if (
                    !customer
                    || !name
                    || !currency
                    || !validFrom
                    || !validUntil
                    || !save
                    || !message
                    || rateInputs.length !== 4
                ) {
                    return;
                }

                const itemDescriptions = {
                    delivered_parcels:
                        'Doručená zásilka',
                    redirected_parcels:
                        'Přesměrovaná zásilka',
                    undelivered_parcels:
                        'Nedoručená zásilka',
                    actual_km:
                        'Skutečný kilometr',
                };

                const canonicalCodes = [
                    'delivered_parcels',
                    'redirected_parcels',
                    'undelivered_parcels',
                    'actual_km',
                ];

                panel.dataset.billingCreateBound = '1';

                save.addEventListener(
                    'click',
                    async () => {
                        const relationshipId =
                            Number(customer.value);

                        if (
                            !Number.isInteger(
                                relationshipId
                            )
                            || relationshipId < 1
                        ) {
                            message.hidden = false;
                            message.textContent =
                                'Vyberte odběratele.';
                            return;
                        }

                        if (
                            name.value.trim() === ''
                            || validFrom.value === ''
                        ) {
                            message.hidden = false;
                            message.textContent =
                                'Vyplňte název ceníku a platnost od.';
                            return;
                        }

                        if (
                            validUntil.value !== ''
                            && validUntil.value <
                                validFrom.value
                        ) {
                            message.hidden = false;
                            message.textContent =
                                'Platnost do nesmí být před platností od.';
                            return;
                        }

                        const rateMap =
                            new Map(
                                rateInputs.map(
                                    (input) => [
                                        input.dataset
                                            .priceListRate,
                                        input,
                                    ]
                                )
                            );

                        const items =
                            canonicalCodes.map(
                                (code) => {
                                    const input =
                                        rateMap.get(code);

                                    const unitRate =
                                        input?.value
                                            ?.trim()
                                            ?? '';

                                    return {
                                        code,
                                        description:
                                            itemDescriptions[
                                                code
                                            ],
                                        unit_rate:
                                            unitRate,
                                    };
                                }
                            );

                        if (
                            items.some(
                                (item) =>
                                    item.unit_rate === ''
                                    || !Number.isFinite(
                                        Number(
                                            item.unit_rate
                                        )
                                    )
                                    || Number(
                                        item.unit_rate
                                    ) < 0
                            )
                        ) {
                            message.hidden = false;
                            message.textContent =
                                'Vyplňte všechny čtyři nezáporné sazby.';
                            return;
                        }

                        const endpoint =
                            panel.dataset
                                .providerManagedPriceListEndpoint
                                .replace(
                                    '{relationship}',
                                    encodeURIComponent(
                                        String(
                                            relationshipId
                                        )
                                    )
                                );

                        save.disabled = true;
                        message.hidden = false;
                        message.textContent =
                            'Ukládám kompletní draft fakturačního ceníku…';

                        try {
                            await api(
                                endpoint,
                                {
                                    method: 'POST',
                                    body: JSON.stringify({
                                        name:
                                            name.value.trim(),
                                        currency:
                                            currency.value,
                                        valid_from:
                                            validFrom.value,
                                        valid_until:
                                            validUntil.value
                                            || null,
                                        change_reason:
                                            'Založení fakturačního ceníku přes Finance UI.',
                                        items,
                                    }),
                                }
                            );

                            message.textContent =
                                'Fakturační ceník byl uložen jako kompletní draft v1 se čtyřmi sazbami.';

                            name.value = '';

                            rateInputs.forEach(
                                (input) => {
                                    input.value = '';
                                }
                            );

                            await loadFinanceCustomers();

                            await loadFinanceCustomerDetail(
                                relationshipId
                            );
                        }
                        catch (error) {
                            message.textContent =
                                `Fakturační ceník se nepodařilo uložit: ${error.message}`;
                        }
                        finally {
                            save.disabled = false;
                        }
                    }
                );
            };
const loadFinanceCustomers = async () => {
                const root =
                    document.querySelector(
                        '[data-finance-root]'
                    );

                if (!root) {
                    return;
                }

                const customerPanel =
                    root.querySelector(
                        '[data-customer-index-endpoint]'
                    );

                const list =
                    root.querySelector(
                        '[data-customer-list]'
                    );

                const select =
                    root.querySelector(
                        '[data-billing-price-list-customer]'
                    );

                const detail =
                    root.querySelector(
                        '[data-customer-detail]'
                    );

                if (
                    !customerPanel
                    || !list
                    || !select
                    || !detail
                ) {
                    return;
                }

                const customerIndexEndpoint =
                    customerPanel.dataset
                        .customerIndexEndpoint;

                list.replaceChildren();
                select.replaceChildren();
                select.disabled = true;

                const loadingRow =
                    document.createElement('tr');

                const loadingCell =
                    document.createElement('td');

                loadingCell.colSpan = 5;
                loadingCell.textContent =
                    'Na\u010d\u00edt\u00e1m odb\u011bratele\u2026';

                loadingRow.appendChild(loadingCell);
                list.appendChild(loadingRow);

                const emptyOption =
                    document.createElement('option');

                emptyOption.value = '';
                emptyOption.textContent =
                    'Vyberte odb\u011bratele';

                select.appendChild(emptyOption);

                try {
                    const body =
                        await api(
                            customerIndexEndpoint
                        );

                    const payload =
                        getPayload(body);

                    const customers =
                        Array.isArray(payload)
                            ? payload
                            : [];

                    list.replaceChildren();

                    if (customers.length === 0) {
                        const row =
                            document.createElement('tr');

                        const cell =
                            document.createElement('td');

                        cell.colSpan = 5;
                        cell.textContent =
                            'Pro aktu\u00e1ln\u00ed organizaci nen\u00ed evidov\u00e1n \u017e\u00e1dn\u00fd odb\u011bratelsk\u00fd vztah.';

                        row.appendChild(cell);
                        list.appendChild(row);

                        detail.textContent =
                            'Nejprve je pot\u0159eba zalo\u017eit obchodn\u00ed vztah s odb\u011bratelem.';

                        return;
                    }

                    customers.forEach((item) => {
                        const relationshipId =
                            Number(
                                item?.relationship_id
                            );

                        const customer =
                            item?.customer || {};

                        const option =
                            document.createElement('option');

                        option.value =
                            String(relationshipId);

                        option.textContent =
                            customer.registration_number
                                ? `${customer.name} \u00b7 I\u010cO ${customer.registration_number}`
                                : (
                                    customer.name
                                    || `Odb\u011bratel ${relationshipId}`
                                );

                        select.appendChild(option);

                        const row =
                            document.createElement('tr');

                        const values = [
                            customer.name || '\u2014',
                            customer.registration_number
                                || '\u2014',
                            financeCustomerRelationshipPeriod(
                                item
                            ),
                            financeCustomerPriceListSummary(
                                item?.price_lists
                            ),
                        ];

                        values.forEach((value) => {
                            const cell =
                                document.createElement('td');

                            cell.textContent =
                                String(value);

                            row.appendChild(cell);
                        });

                        const actionCell =
                            document.createElement('td');

                        const detailButton =
                            document.createElement('button');

                        detailButton.type = 'button';
                        detailButton.className =
                            'drayvia-finance-tab';
                        detailButton.textContent =
                            'Detail';

                        detailButton.addEventListener(
                            'click',
                            () => {
                                loadFinanceCustomerDetail(
                                    relationshipId
                                );
                            }
                        );

                        actionCell.appendChild(
                            detailButton
                        );

                        row.appendChild(
                            actionCell
                        );

                        list.appendChild(row);
                    });

                    select.disabled = false;

                    if (
                        select.dataset.financeDetailBound
                        !== '1'
                    ) {
                        select.dataset.financeDetailBound =
                            '1';

                        select.addEventListener(
                            'change',
                            () => {
                                if (!select.value) {
                                    return;
                                }

                                loadFinanceCustomerDetail(
                                    Number(select.value)
                                );
                            }
                        );
                    }

                    const firstRelationshipId =
                        Number(
                            customers[0]
                                ?.relationship_id
                        );

                    if (
                        Number.isInteger(
                            firstRelationshipId
                        )
                        && firstRelationshipId > 0
                    ) {
                        select.value =
                            String(
                                firstRelationshipId
                            );

                        await loadFinanceCustomerDetail(
                            firstRelationshipId
                        );
                    }
                }
                catch (error) {
                    list.replaceChildren();

                    const row =
                        document.createElement('tr');

                    const cell =
                        document.createElement('td');

                    cell.colSpan = 5;
                    cell.textContent =
                        `Odb\u011bratele se nepoda\u0159ilo na\u010d\u00edst: ${error.message}`;

                    row.appendChild(cell);
                    list.appendChild(row);

                    detail.textContent =
                        'Detail odb\u011bratele nen\u00ed dostupn\u00fd.';

                    select.disabled = true;
                }
            };

    const render = (page) => {
        const template = templates[page];

        if (!template) {
            return;
        }

        if (
            page === 'calendar' &&
            ![
                'current_month',
                'month'
            ].includes(periodMode)
        ) {
            periodMode = 'month';
        }

        currentPage = page;
        content.innerHTML = template();

        layer.classList.add('is-visible');
        layer.setAttribute('aria-hidden', 'false');

        syncPreviewPosition();
        setActiveMenu(page);
        bindPeriodControls();

        if (page === 'drivers') {
            loadRealDriverData();
        }

        if (page === 'statistics') {
            loadDriverStatistics();
        }

if (page === 'finance') {
            bindFinanceCustomerCreate();
            bindFinanceBillingPriceListCreate();
            loadFinanceCustomers();
        }

        const scroll = layer.querySelector('.drayvia-preview-scroll');

        if (scroll) {
            scroll.scrollTop = 0;
        }
    };

    const showRoutes = () => {
        currentPage = null;

        layer.classList.remove('is-visible');
        layer.setAttribute('aria-hidden', 'true');

        setActiveMenu('routes');
    };

    document.addEventListener(
        'click',
        (event) => {

            if (
                event.target.closest(
                    '#drayviaRealDriverAdd'
                )
            ) {
                event.preventDefault();
                openRealDriverForm();
                return;
            }

            if (
                event.target.closest(
                    '#drayviaRealDriverReload'
                )
            ) {
                event.preventDefault();
                loadRealDriverData();
                return;
            }

            if (
                event.target.closest(
                    '#drayviaRealDriverClose, #drayviaRealDriverCancel'
                )
            ) {
                event.preventDefault();
                closeRealDriverForm();
                return;
            }
        }
    );

    document.addEventListener(
        'submit',
        (event) => {

            if (
                event.target?.id !==
                'drayviaRealDriverForm'
            ) {
                return;
            }

            event.preventDefault();

            submitRealDriver(
                event.target
            );
        }
    );
    document.addEventListener(
        'click',
        (event) => {
            const target = event.target.closest('[data-drayvia-page]');

            if (!target) {
                return;
            }

            const page = target.dataset.drayviaPage;

            event.preventDefault();
            event.stopPropagation();

            if (page === 'routes') {
                showRoutes();
                return;
            }

            if (templates[page]) {
                render(page);
            }
        },
        true
    );
})();
</script>
</body>
</html>
