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

        /* S030-01A DEPOT VERSUS DRIVER RECORD REVIEW UI */
        .sidebar .drayvia-main-nav .drayvia-nav-subitem {
            position: relative;
            width: calc(100% - 18px);
            margin-left: 18px;
            padding-top: 8px;
            padding-bottom: 8px;
            color: #9eabc0;
            font-size: 11px;
            letter-spacing: .035em;
        }

        .sidebar .drayvia-main-nav .drayvia-nav-subitem::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 4px;
            width: 7px;
            height: 1px;
            background: currentColor;
        }

        .sidebar .drayvia-main-nav .drayvia-nav-subitem.active {
            color: #ffffff;
        }

        .drayvia-record-review-topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 22px;
        }

        .drayvia-record-review-topbar .drayvia-preview-actions {
            flex: 0 0 auto;
        }

        .drayvia-record-review-topbar .drayvia-preview-action,
        .drayvia-record-review .drayvia-preview-action {
            cursor: pointer;
        }

        .drayvia-record-review .drayvia-preview-action:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        .drayvia-record-review-batch {
            display: grid;
            grid-template-columns: minmax(280px, 1.5fr) auto;
            gap: 14px;
            align-items: end;
            border: 1px solid #dce3ec;
            border-radius: 14px;
            background: #ffffff;
            padding: 18px 20px;
        }

        .drayvia-record-review-field {
            display: grid;
            gap: 7px;
            color: #34445d;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .025em;
            text-transform: uppercase;
        }

        .drayvia-record-review-field select,
        .drayvia-record-review-field input {
            min-width: 0;
            min-height: 42px;
            box-sizing: border-box;
            border: 1px solid #b9c5d6;
            border-radius: 9px;
            background: #ffffff;
            padding: 8px 10px;
            color: #172033;
            font: inherit;
            font-size: 13px;
            font-weight: 650;
            letter-spacing: normal;
            text-transform: none;
        }

        .drayvia-record-review-batch-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .drayvia-record-review-status {
            min-height: 20px;
            margin-top: 10px;
            color: #667286;
            font-size: 12px;
            line-height: 1.5;
        }

        .drayvia-record-review-status.success {
            color: #28714b;
        }

        .drayvia-record-review-status.error {
            border: 1px solid #efb0aa;
            border-radius: 9px;
            background: #fff3f1;
            padding: 10px 12px;
            color: #8e2a21;
        }

        .drayvia-record-review-readonly {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            border: 1px solid #c9d8ef;
            border-left: 4px solid #365f9c;
            border-radius: 9px;
            background: #f1f6fd;
            padding: 11px 13px;
            color: #29476f;
            font-size: 12px;
            line-height: 1.5;
        }

        .drayvia-record-review-readonly strong {
            white-space: nowrap;
        }

        .drayvia-record-review-summary {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .drayvia-record-review-summary-card {
            min-width: 0;
            border: 1px solid #dfe5ed;
            border-radius: 12px;
            background: #ffffff;
            padding: 14px;
            text-align: left;
            cursor: pointer;
        }

        .drayvia-record-review-summary-card:hover,
        .drayvia-record-review-summary-card.is-active {
            border-color: #607895;
            box-shadow: 0 0 0 2px rgba(52, 77, 110, .08);
        }

        .drayvia-record-review-summary-card.is-active {
            background: #f6f8fb;
        }

        .drayvia-record-review-summary-card span {
            display: block;
            overflow: hidden;
            color: #6e7a8e;
            font-size: 10px;
            font-weight: 850;
            letter-spacing: .045em;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .drayvia-record-review-summary-card strong {
            display: block;
            margin-top: 7px;
            color: #172033;
            font-size: 23px;
            line-height: 1;
        }

        .drayvia-record-review-summary-card[data-record-review-summary-status="matching"] strong {
            color: #28714b;
        }

        .drayvia-record-review-summary-card[data-record-review-summary-status="different"] strong,
        .drayvia-record-review-summary-card[data-record-review-summary-status="driver_mismatch"] strong {
            color: #9c5f05;
        }

        .drayvia-record-review-summary-card[data-record-review-summary-status="missing_driver_record"] strong,
        .drayvia-record-review-summary-card[data-record-review-summary-status="not_comparable"] strong {
            color: #9a3027;
        }

        .drayvia-record-review-filter-panel {
            margin-top: 16px;
            border: 1px solid #dfe5ed;
            border-radius: 14px;
            background: #ffffff;
            padding: 18px 20px;
        }

        .drayvia-record-review-filter-grid {
            display: grid;
            grid-template-columns: minmax(150px, .9fr) minmax(190px, 1.2fr) repeat(2, minmax(140px, .85fr)) minmax(150px, 1fr);
            gap: 10px;
            align-items: end;
        }

        .drayvia-record-review-filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 12px;
        }

        .drayvia-record-review-results {
            margin-top: 16px;
        }

        .drayvia-record-review-results-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 10px;
        }

        .drayvia-record-review-results-head h2 {
            margin: 0;
            color: #172033;
            font-size: 16px;
        }

        .drayvia-record-review-results-head p {
            margin: 4px 0 0;
            color: #6f7b8f;
            font-size: 12px;
        }

        .drayvia-record-review-list {
            display: grid;
            gap: 10px;
        }

        .drayvia-record-review-item {
            overflow: hidden;
            border: 1px solid #dfe5ed;
            border-radius: 13px;
            background: #ffffff;
        }

        .drayvia-record-review-item.is-different,
        .drayvia-record-review-item.is-driver-mismatch {
            border-left: 4px solid #d89020;
        }

        .drayvia-record-review-item.is-missing-driver-record,
        .drayvia-record-review-item.is-not-comparable {
            border-left: 4px solid #bc4a40;
        }

        .drayvia-record-review-item.is-matching {
            border-left: 4px solid #3d9565;
        }

        .drayvia-record-review-item summary {
            display: grid;
            grid-template-columns: minmax(160px, 1fr) minmax(130px, .9fr) minmax(170px, 1.2fr) auto;
            gap: 14px;
            align-items: center;
            padding: 15px 17px;
            cursor: pointer;
            list-style: none;
        }

        .drayvia-record-review-item summary::-webkit-details-marker {
            display: none;
        }

        .drayvia-record-review-item summary::after {
            content: 'Rozbalit';
            color: #617087;
            font-size: 11px;
            font-weight: 800;
        }

        .drayvia-record-review-item[open] summary::after {
            content: 'Sbalit';
        }

        .drayvia-record-review-route strong,
        .drayvia-record-review-driver strong {
            display: block;
            color: #172033;
            font-size: 13px;
        }

        .drayvia-record-review-route span,
        .drayvia-record-review-driver span {
            display: block;
            margin-top: 3px;
            color: #788499;
            font-size: 11px;
        }

        .drayvia-record-review-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border-radius: 999px;
            padding: 6px 10px;
            background: #eef2f7;
            color: #49576c;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .drayvia-record-review-badge.is-matching {
            background: #eaf6ef;
            color: #28714b;
        }

        .drayvia-record-review-badge.is-different,
        .drayvia-record-review-badge.is-driver-mismatch {
            background: #fff3dd;
            color: #8b5b10;
        }

        .drayvia-record-review-badge.is-missing-driver-record,
        .drayvia-record-review-badge.is-not-comparable {
            background: #fff0ee;
            color: #91352d;
        }

        .drayvia-record-review-item-body {
            border-top: 1px solid #edf0f4;
            padding: 0 17px 17px;
        }

        .drayvia-record-review-reason {
            margin: 0 -17px 12px;
            background: #f7f9fb;
            padding: 10px 17px;
            color: #5d6b80;
            font-size: 12px;
            line-height: 1.45;
        }

        .drayvia-record-review-comparison {
            overflow-x: auto;
        }

        .drayvia-record-review-comparison-row {
            display: grid;
            grid-template-columns: minmax(170px, 1fr) repeat(2, minmax(150px, 1.1fr));
            min-width: 580px;
            border-bottom: 1px solid #eef1f5;
        }

        .drayvia-record-review-comparison-row:last-child {
            border-bottom: 0;
        }

        .drayvia-record-review-comparison-row > div {
            min-width: 0;
            padding: 9px 11px;
            color: #34445d;
            font-size: 12px;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .drayvia-record-review-comparison-row > div + div {
            border-left: 1px solid #eef1f5;
        }

        .drayvia-record-review-comparison-head > div {
            background: #f5f7fa;
            color: #677489;
            font-size: 10px;
            font-weight: 850;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .drayvia-record-review-comparison-row.is-different > div {
            background: #fff9ed;
            color: #724b0d;
            font-weight: 750;
        }

        .drayvia-record-review-empty {
            border: 1px dashed #c9d2df;
            border-radius: 12px;
            background: #fafbfd;
            padding: 34px 20px;
            color: #647188;
            text-align: center;
            font-size: 13px;
            line-height: 1.55;
        }

        .drayvia-record-review-pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 9px;
            margin-top: 12px;
        }

        .drayvia-record-review-pagination span {
            color: #657187;
            font-size: 12px;
            font-weight: 700;
        }

        @media (max-width: 1180px) {
            .drayvia-record-review-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .drayvia-record-review-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .drayvia-record-review-topbar,
            .drayvia-record-review-results-head {
                display: grid;
                grid-template-columns: 1fr;
            }

            .drayvia-record-review-batch,
            .drayvia-record-review-filter-grid {
                grid-template-columns: 1fr;
            }

            .drayvia-record-review-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .drayvia-record-review-item summary {
                grid-template-columns: 1fr auto;
            }

            .drayvia-record-review-item summary::after {
                grid-column: 2;
                grid-row: 1 / span 3;
            }

            .drayvia-record-review-readonly {
                display: block;
            }

            .drayvia-record-review-readonly strong {
                display: block;
                margin-bottom: 4px;
            }
        }

        /* S028-01A DEPOT IMPORT READ-ONLY PREVIEW */
        .drayvia-depot-import-form {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) auto;
            gap: 12px;
            align-items: end;
        }

        .drayvia-depot-import-form label,
        .drayvia-depot-alias-field {
            display: grid;
            gap: 7px;
            color: #132038;
            font-size: 13px;
            font-weight: 800;
        }

        .drayvia-depot-import-form input,
        .drayvia-depot-alias-field input {
            min-height: 44px;
            border: 1px solid #b7c4d6;
            border-radius: 9px;
            background: #ffffff;
            color: #132038;
            padding: 9px 12px;
        }

        .drayvia-depot-readonly-note {
            margin-bottom: 16px;
            border-left: 4px solid #1667d9;
            background: #edf6ff;
            color: #0b438f;
            padding: 12px 14px;
            line-height: 1.5;
        }

        .drayvia-depot-import-status {
            margin-top: 14px;
            min-height: 22px;
            color: #52627a;
        }

        .drayvia-depot-import-status.error {
            color: #a11b1b;
        }

        .drayvia-depot-import-status.success {
            color: #08743b;
        }

        .drayvia-depot-preview-table-wrap {
            margin-top: 16px;
            overflow: auto;
            border: 1px solid #d9e1ec;
            border-radius: 10px;
        }

        .drayvia-depot-preview-table {
            width: 100%;
            min-width: 1120px;
            border-collapse: collapse;
            font-size: 12px;
        }

        .drayvia-depot-preview-table th,
        .drayvia-depot-preview-table td {
            border-bottom: 1px solid #e5eaf1;
            padding: 9px 10px;
            text-align: left;
            vertical-align: top;
        }

        .drayvia-depot-preview-table th {
            position: sticky;
            top: 0;
            background: #f5f7fa;
            color: #34445d;
            font-size: 11px;
            text-transform: uppercase;
        }

        .drayvia-depot-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1400;
            display: grid;
            place-items: center;
            padding: 24px;
            background: rgba(11, 22, 41, 0.58);
        }

        .drayvia-depot-modal {
            width: min(660px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(11, 22, 41, 0.3);
            padding: 24px;
        }

        .drayvia-depot-modal h2 {
            margin: 0 0 8px;
            color: #132038;
        }

        .drayvia-depot-modal p {
            margin: 0 0 16px;
            color: #52627a;
            line-height: 1.55;
        }

        .drayvia-depot-carrier-list {
            display: grid;
            gap: 7px;
            margin: 14px 0;
            padding: 12px;
            border: 1px solid #d9e1ec;
            border-radius: 10px;
            background: #f8fafc;
        }

        .drayvia-depot-carrier-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            color: #34445d;
        }

        .drayvia-depot-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* S028-04A DEPOT IMPORT AUDITED DRAFT ADMINISTRATION */
        .drayvia-depot-draft-section {
            margin-top: 18px;
            border: 1px solid #d9e1ec;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }

        .drayvia-depot-draft-section h2,
        .drayvia-depot-draft-section h3 {
            margin: 0 0 8px;
            color: #132038;
        }

        .drayvia-depot-draft-section-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .drayvia-depot-draft-list,
        .drayvia-depot-mapping-grid {
            display: grid;
            gap: 10px;
        }

        .drayvia-depot-draft-list-item,
        .drayvia-depot-mapping-card {
            display: grid;
            gap: 10px;
            border: 1px solid #d9e1ec;
            border-radius: 10px;
            background: #f8fafc;
            padding: 13px;
        }

        .drayvia-depot-draft-list-item {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
        }

        .drayvia-depot-draft-list-item p,
        .drayvia-depot-mapping-card p {
            margin: 4px 0 0;
            color: #52627a;
            font-size: 12px;
            line-height: 1.45;
        }

        .drayvia-depot-mapping-success {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 3px 8px;
            align-items: center;
            width: fit-content;
            color: #087443;
        }

        .drayvia-depot-mapping-success-icon {
            display: inline-grid;
            grid-row: 1 / span 2;
            place-items: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #0b8f52;
            color: #ffffff;
            font-size: 15px;
            font-weight: 900;
        }

        .drayvia-depot-mapping-success strong {
            font-size: 12px;
        }

        .drayvia-depot-mapping-success small {
            color: #397a5d;
            font-size: 11px;
        }

        .drayvia-depot-import-result {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 4px 10px;
            align-items: center;
            margin: 14px 0;
            border: 1px solid;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .drayvia-depot-import-result.success {
            border-color: #8bd1ac;
            background: #ecfbf3;
            color: #087443;
        }

        .drayvia-depot-import-result.cancelled {
            border-color: #efb0aa;
            background: #fff3f1;
            color: #9d281d;
        }

        .drayvia-depot-import-result-icon {
            display: inline-grid;
            grid-row: 1 / span 2;
            place-items: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: #ffffff;
            font-size: 19px;
            font-weight: 900;
        }

        .drayvia-depot-import-result.success
            .drayvia-depot-import-result-icon {
            background: #0b8f52;
        }

        .drayvia-depot-import-result.cancelled
            .drayvia-depot-import-result-icon {
            background: #b63428;
        }

        .drayvia-depot-import-result strong {
            font-size: 15px;
        }

        .drayvia-depot-import-result small {
            color: inherit;
            font-size: 11px;
            line-height: 1.45;
            opacity: 0.82;
        }

        .drayvia-depot-finalize-summary {
            display: grid;
            gap: 8px;
            margin: 18px 0;
            border: 1px solid #d9e1ec;
            border-radius: 10px;
            background: #f8fafc;
            padding: 14px;
        }

        .drayvia-depot-finalize-summary div {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            color: #34445d;
        }

        .drayvia-depot-finalize-summary strong {
            color: #132038;
            text-align: right;
        }

        .drayvia-depot-finalize-warning {
            border-left: 4px solid #d88600;
            border-radius: 8px;
            background: #fff8e8;
            color: #71420b;
            padding: 12px 14px;
            line-height: 1.5;
        }

        .drayvia-depot-cancel-reason {
            display: grid;
            gap: 6px;
            margin-top: 16px;
            color: #34445d;
            font-size: 12px;
            font-weight: 800;
        }

        .drayvia-depot-cancel-reason textarea {
            min-height: 92px;
            resize: vertical;
            border: 1px solid #aebbd0;
            border-radius: 8px;
            background: #ffffff;
            padding: 10px 12px;
            color: #132038;
            font: inherit;
            font-weight: 500;
        }

        .drayvia-depot-cancel-action {
            border-color: #b63428;
            background: #a72c22;
            color: #ffffff;
        }

        .drayvia-depot-cancel-action:hover:not(:disabled) {
            background: #852219;
        }

        .drayvia-depot-cancelled-note {
            margin: 14px 0;
            border: 1px solid #efb0aa;
            border-left: 4px solid #b63428;
            border-radius: 8px;
            background: #fff3f1;
            color: #782118;
            padding: 12px 14px;
            line-height: 1.5;
        }

        .drayvia-depot-locked-note {
            margin: 14px 0;
            border: 1px solid #e7b754;
            border-left: 4px solid #d98b00;
            border-radius: 8px;
            background: #fff8e8;
            color: #71420b;
            padding: 12px 14px;
            line-height: 1.5;
        }

        .drayvia-depot-mapping-card form {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(220px, 1.4fr) auto;
            gap: 8px;
            align-items: end;
        }

        .drayvia-depot-mapping-card label {
            display: grid;
            gap: 5px;
            color: #34445d;
            font-size: 11px;
            font-weight: 800;
        }

        .drayvia-depot-mapping-card select,
        .drayvia-depot-mapping-card input {
            min-height: 38px;
            min-width: 0;
            border: 1px solid #b7c4d6;
            border-radius: 8px;
            background: #ffffff;
            color: #132038;
            padding: 7px 9px;
            font: inherit;
        }

        .drayvia-depot-draft-state {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #e7eef9;
            color: #244a7c;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 800;
        }

        @media (max-width: 760px) {
            .drayvia-depot-import-form {
                grid-template-columns: 1fr;
            }

            .drayvia-depot-modal-actions {
                flex-direction: column-reverse;
            }

            .drayvia-depot-draft-section-head,
            .drayvia-depot-draft-list-item {
                display: grid;
                grid-template-columns: 1fr;
            }

            .drayvia-depot-mapping-card form {
                grid-template-columns: 1fr;
            }
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

        /* objective processed share */
        .drayvia-driver-stat-table th:nth-child(12),
        .drayvia-driver-stat-table td:nth-child(12) {
            background: #e7edf3;
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
    <button class="nav-item drayvia-nav-subitem" type="button" data-drayvia-page="record-review">Kontrola zápisů</button>
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

    /*
     * S022 FINANCE API SCOPE BRIDGE
     *
     * Finance and the preview application live in this second IIFE.
     * Reuse its authenticated API helper rather than reaching into
     * the private login/application IIFE.
     */
    const api = realDriverApi;

    const getPayload = (body) =>
        body
        && Object.prototype.hasOwnProperty.call(
            body,
            'data'
        )
            ? body.data
            : body;

    /* S026-04A FILTERED DRIVER PERFORMANCE OVERVIEW */
    const driverStatisticsState = {
        data: null,
        driverOptions: [],
        carrierOptions: [],
        quickPeriods: [],
        loading: false,
        driverId: '',
        carrierScope: 'all',
        carrierOrganizationId: '',
        period: 'current_month',
        dateFrom: '',
        dateTo: '',
        groupBy: 'month',
    };

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
            {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }
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
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }
        ).format(Number(value)) + ' %';
    };

    const driverStatisticsDate = (value) => {
        const match = String(value ?? '').match(
            /^(\d{4})-(\d{2})-(\d{2})$/
        );

        if (!match) {
            return value || '\u2014';
        }

        return `${match[3]}.${match[2]}.${match[1]}`;
    };

    const driverStatisticsPeriod = (value) => {
        const month = String(value ?? '').match(
            /^(\d{4})-(\d{2})$/
        );

        if (month) {
            const date = new Date(
                Number(month[1]),
                Number(month[2]) - 1,
                1
            );

            return new Intl.DateTimeFormat(
                'cs-CZ',
                {
                    month: 'long',
                    year: 'numeric',
                }
            ).format(date);
        }

        return driverStatisticsDate(value);
    };

    const driverStatisticsQuickPeriodLabel = (key) => ({
        current_month: 'AKTUÁLNÍ MĚSÍC',
        previous_month: 'MINULÝ MĚSÍC',
        current_year: 'AKTUÁLNÍ ROK',
        previous_year: 'MINULÝ ROK',
        last_12_months: 'POSLEDNÍCH 12 MĚSÍCŮ',
        all_history: 'CELÁ HISTORIE',
    }[key] ?? key);

    const driverStatisticsPayload = (body) => {
        const data = getPayload(body);

        return data && typeof data === 'object'
            ? data
            : {};
    };

    const driverStatisticsCell = (
        primary,
        secondary = ''
    ) => {
        const cell = document.createElement('td');
        const first = document.createElement('span');

        first.className = 'drayvia-driver-stat-primary';
        first.textContent = String(primary);
        cell.appendChild(first);

        if (secondary) {
            const second = document.createElement('span');

            second.className =
                'drayvia-driver-stat-secondary';
            second.textContent = String(secondary);
            cell.appendChild(second);
        }

        return cell;
    };

    const driverStatisticsIdentityCell = (driver) => {
        const cell = document.createElement('td');
        const name = document.createElement('span');
        const id = document.createElement('span');

        cell.className = 'drayvia-driver-stat-identity';
        name.className = 'drayvia-driver-stat-name';
        id.className = 'drayvia-driver-stat-id';

        name.textContent = driver?.name || '\u2014';
        id.textContent = driver?.external_id
            ? `ID: ${driver.external_id}`
            : `Intern\u00ed ID: ${driver?.id ?? '\u2014'}`;

        cell.appendChild(name);
        cell.appendChild(id);

        return cell;
    };

    const driverStatisticsCarrierCell = (carriers) => {
        const rows = Array.isArray(carriers)
            ? carriers
            : [];

        return driverStatisticsCell(
            rows.length > 0
                ? rows.map((item) => item?.name || '—').join(', ')
                : 'Bez historicky doloženého dopravce'
        );
    };

    const ensureDriverStatisticsShell = () => {
        const host = document.getElementById(
            'drayviaDriverStatisticsHost'
        );

        if (!host) {
            return null;
        }

        if (
            !host.querySelector(
                '#drayviaDriverStatisticsRows'
            )
        ) {
            host.innerHTML = `
                <div class="drayvia-preview-panel-head">
                    <h2 class="drayvia-preview-panel-title">
                        STATISTIKY &#344;IDI&#268;&#366;
                    </h2>

                    <div class="drayvia-preview-panel-subtitle">
                        Sou&#269;ty a pod&#237;ly ze skute&#269;n&#253;ch tras DRAYVIA.
                    </div>
                </div>

                <form
                    id="drayviaDriverStatisticsFilters"
                    class="drayvia-driver-stat-filters"
                >
                    <div
                        id="drayviaDriverStatisticsQuickPeriods"
                        style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;"
                    ></div>

                    <div class="drayvia-driver-stat-filter-row">
                        <label style="min-width:240px;">
                            <span class="drayvia-driver-stat-label" style="display:block;width:auto;padding:0 0 6px;">
                                &#344;IDI&#268;
                            </span>
                            <select
                                id="drayviaDriverStatisticsDriver"
                                style="width:100%;height:40px;padding:0 10px;border:1px solid #b8c2ce;border-radius:8px;background:#fff;"
                            >
                                <option value="">V&#352;ICHNI &#344;IDI&#268;I</option>
                            </select>
                        </label>

                        <label style="min-width:240px;">
                            <span class="drayvia-driver-stat-label" style="display:block;width:auto;padding:0 0 6px;">
                                DOPRAVCE
                            </span>
                            <select
                                id="drayviaDriverStatisticsCarrier"
                                style="width:100%;height:40px;padding:0 10px;border:1px solid #b8c2ce;border-radius:8px;background:#fff;"
                            >
                                <option value="all">VŠICHNI DOPRAVCI</option>
                            </select>
                        </label>

                        <label style="min-width:170px;">
                            <span class="drayvia-driver-stat-label" style="display:block;width:auto;padding:0 0 6px;">
                                OBDOB&#205; OD
                            </span>
                            <input
                                id="drayviaDriverStatisticsFrom"
                                type="date"
                                style="width:100%;height:40px;padding:0 10px;border:1px solid #b8c2ce;border-radius:8px;background:#fff;"
                            >
                        </label>

                        <label style="min-width:170px;">
                            <span class="drayvia-driver-stat-label" style="display:block;width:auto;padding:0 0 6px;">
                                OBDOB&#205; DO
                            </span>
                            <input
                                id="drayviaDriverStatisticsTo"
                                type="date"
                                style="width:100%;height:40px;padding:0 10px;border:1px solid #b8c2ce;border-radius:8px;background:#fff;"
                            >
                        </label>

                        <label style="min-width:160px;">
                            <span class="drayvia-driver-stat-label" style="display:block;width:auto;padding:0 0 6px;">
                                V&#221;VOJ PO
                            </span>
                            <select
                                id="drayviaDriverStatisticsGroup"
                                style="width:100%;height:40px;padding:0 10px;border:1px solid #b8c2ce;border-radius:8px;background:#fff;"
                            >
                                <option value="month">M&#282;S&#205;C&#205;CH</option>
                                <option value="day">DNECH</option>
                            </select>
                        </label>

                        <button
                            class="drayvia-driver-stat-button active"
                            type="submit"
                            style="align-self:flex-end;height:40px;"
                        >
                            ZOBRAZIT
                        </button>

                        <button
                            id="drayviaDriverStatisticsReset"
                            class="drayvia-driver-stat-button"
                            type="button"
                            style="align-self:flex-end;height:40px;"
                        >
                            AKTU&#193;LN&#205; M&#282;S&#205;C
                        </button>
                    </div>

                    <div
                        id="drayviaDriverStatisticsSummary"
                        class="drayvia-driver-stat-summary"
                    >
                        NA&#268;&#205;T&#193;M STATISTIKY...
                    </div>
                </form>

                <div
                    class="drayvia-real-driver-message"
                    style="display:block;margin-top:14px;"
                >
                    D&#237;l&#269;&#237; kvalita je zde objektivn&#237; pod&#237;l vy&#345;&#237;zen&#253;ch
                    z&#225;silek z nalo&#382;en&#253;ch. Konfigurovateln&#253; profil kvality,
                    finan&#269;n&#237; podm&#237;nky ani bonusy zat&#237;m nejsou pou&#382;ity.
                </div>

                <div class="drayvia-preview-grid" style="margin-top:16px;">
                    <div class="drayvia-preview-card">
                        <div class="drayvia-preview-card-label">TRASY</div>
                        <div id="drayviaDriverStatisticsRouteCount" class="drayvia-preview-card-value">&#8212;</div>
                    </div>
                    <div class="drayvia-preview-card">
                        <div class="drayvia-preview-card-label">PRACOVN&#205; DNY</div>
                        <div id="drayviaDriverStatisticsWorkDayCount" class="drayvia-preview-card-value">&#8212;</div>
                    </div>
                    <div class="drayvia-preview-card">
                        <div class="drayvia-preview-card-label">NALO&#381;ENO</div>
                        <div id="drayviaDriverStatisticsLoaded" class="drayvia-preview-card-value">&#8212;</div>
                    </div>
                    <div class="drayvia-preview-card">
                        <div class="drayvia-preview-card-label">D&#205;L&#268;&#205; KVALITA</div>
                        <div id="drayviaDriverStatisticsProcessedShare" class="drayvia-preview-card-value">&#8212;</div>
                    </div>
                    <div class="drayvia-preview-card">
                        <div class="drayvia-preview-card-label">ROZD&#205;L KM</div>
                        <div id="drayviaDriverStatisticsKmDifference" class="drayvia-preview-card-value">&#8212;</div>
                    </div>
                </div>

                <div class="drayvia-driver-stat-table-wrap">
                    <table class="drayvia-driver-stat-table">
                        <thead>
                            <tr>
                                <th>&#344;IDI&#268;</th>
                                <th>DOPRAVCE</th>
                                <th>TRASY</th>
                                <th>PRACOVN&#205; DNY</th>
                                <th>NALO&#381;ENO</th>
                                <th>DORU&#268;ENO</th>
                                <th>P&#344;ESM&#282;ROV&#193;NO</th>
                                <th>ODM&#205;TNUTO Z&#193;KAZN&#205;KEM</th>
                                <th>Z&#366;STALO NEDORU&#268;ENO</th>
                                <th>PL&#193;N KM</th>
                                <th>SKUT. KM</th>
                                <th>ROZD&#205;L N&#193;JEZDU</th>
                                <th>D&#205;L&#268;&#205; KVALITA</th>
                            </tr>
                        </thead>
                        <tbody id="drayviaDriverStatisticsRows"></tbody>
                    </table>
                </div>

                <div
                    id="drayviaDriverStatisticsCompleteness"
                    class="drayvia-driver-stat-summary"
                ></div>

                <div class="drayvia-driver-stat-table-wrap">
                    <h3 style="margin:24px 0 10px;">V&#221;VOJ V OBDOB&#205;</h3>
                    <table class="drayvia-driver-stat-table">
                        <thead>
                            <tr>
                                <th>OBDOB&#205;</th>
                                <th>TRASY</th>
                                <th>DNY</th>
                                <th>NALO&#381;ENO</th>
                                <th>DORU&#268;ENO</th>
                                <th>P&#344;ESM&#282;ROV&#193;NO</th>
                                <th>ODM&#205;TNUTO</th>
                                <th>NEDORU&#268;ENO</th>
                                <th>D&#205;L&#268;&#205; KVALITA</th>
                                <th>ROZD&#205;L KM</th>
                            </tr>
                        </thead>
                        <tbody id="drayviaDriverStatisticsTimelineRows"></tbody>
                    </table>
                </div>
            `;

            const form = document.getElementById(
                'drayviaDriverStatisticsFilters'
            );

            form?.addEventListener(
                'submit',
                (event) => {
                    event.preventDefault();

                    driverStatisticsState.driverId = String(
                        document.getElementById(
                            'drayviaDriverStatisticsDriver'
                        )?.value ?? ''
                    );

                    const carrierValue = String(
                        document.getElementById(
                            'drayviaDriverStatisticsCarrier'
                        )?.value ?? 'all'
                    );

                    if (carrierValue.startsWith('organization:')) {
                        driverStatisticsState.carrierScope =
                            'external';
                        driverStatisticsState.carrierOrganizationId =
                            carrierValue.split(':')[1] ?? '';
                    }
                    else {
                        driverStatisticsState.carrierScope =
                            carrierValue;
                        driverStatisticsState.carrierOrganizationId = '';
                    }

                    driverStatisticsState.dateFrom = String(
                        document.getElementById(
                            'drayviaDriverStatisticsFrom'
                        )?.value ?? ''
                    );

                    driverStatisticsState.dateTo = String(
                        document.getElementById(
                            'drayviaDriverStatisticsTo'
                        )?.value ?? ''
                    );

                    driverStatisticsState.groupBy = String(
                        document.getElementById(
                            'drayviaDriverStatisticsGroup'
                        )?.value ?? 'month'
                    );

                    driverStatisticsState.period = 'custom';

                    loadDriverStatistics(true);
                }
            );

            document.getElementById(
                'drayviaDriverStatisticsReset'
            )?.addEventListener(
                'click',
                () => {
                    driverStatisticsState.driverId = '';
                    driverStatisticsState.carrierScope = 'all';
                    driverStatisticsState.carrierOrganizationId = '';
                    driverStatisticsState.period = 'current_month';
                    driverStatisticsState.dateFrom = '';
                    driverStatisticsState.dateTo = '';
                    driverStatisticsState.groupBy = 'month';

                    loadDriverStatistics(true);
                }
            );
        }

        return host;
    };

    const driverStatisticsSyncControls = () => {
        const driver = document.getElementById(
            'drayviaDriverStatisticsDriver'
        );

        const carrier = document.getElementById(
            'drayviaDriverStatisticsCarrier'
        );

        const from = document.getElementById(
            'drayviaDriverStatisticsFrom'
        );

        const to = document.getElementById(
            'drayviaDriverStatisticsTo'
        );

        const group = document.getElementById(
            'drayviaDriverStatisticsGroup'
        );

        if (driver) {
            const current = driverStatisticsState.driverId;

            driver.replaceChildren();

            const all = document.createElement('option');

            all.value = '';
            all.textContent = 'V\u0160ICHNI \u0158IDI\u010cI';
            driver.appendChild(all);

            driverStatisticsState.driverOptions.forEach(
                (item) => {
                    const option = document.createElement(
                        'option'
                    );

                    option.value = String(item.id);
                    option.textContent = item.name
                        || `\u0158idi\u010d ${item.id}`;
                    driver.appendChild(option);
                }
            );

            driver.value = current;
        }

        if (carrier) {
            const current =
                driverStatisticsState.carrierScope === 'external'
                    ? `organization:${driverStatisticsState.carrierOrganizationId}`
                    : driverStatisticsState.carrierScope;

            carrier.replaceChildren();

            const all = document.createElement('option');

            all.value = 'all';
            all.textContent = 'VŠICHNI DOPRAVCI';
            carrier.appendChild(all);

            driverStatisticsState.carrierOptions.forEach(
                (item) => {
                    const option = document.createElement(
                        'option'
                    );

                    option.value = String(item.key);
                    option.textContent =
                        `${item.name} (${driverStatisticsInteger(
                            item.route_count
                        )})`;
                    carrier.appendChild(option);
                }
            );

            carrier.value = current;

            if (carrier.value !== current) {
                driverStatisticsState.carrierScope = 'all';
                driverStatisticsState.carrierOrganizationId = '';
                carrier.value = 'all';
            }
        }

        if (from) {
            from.value = driverStatisticsState.dateFrom;
        }

        if (to) {
            to.value = driverStatisticsState.dateTo;
        }

        if (group) {
            group.value = driverStatisticsState.groupBy;
        }

        const quickPeriods = document.getElementById(
            'drayviaDriverStatisticsQuickPeriods'
        );

        if (quickPeriods) {
            quickPeriods.replaceChildren();

            driverStatisticsState.quickPeriods.forEach(
                (item) => {
                    const button = document.createElement(
                        'button'
                    );

                    button.type = 'button';
                    button.className =
                        'drayvia-driver-stat-button';
                    button.textContent =
                        `${driverStatisticsQuickPeriodLabel(
                            item.key
                        )} (${driverStatisticsInteger(
                            item.route_count
                        )})`;

                    if (
                        driverStatisticsState.period === item.key
                    ) {
                        button.classList.add('active');
                    }

                    button.addEventListener(
                        'click',
                        () => {
                            driverStatisticsState.period =
                                String(item.key);
                            driverStatisticsState.dateFrom =
                                String(item.date_from ?? '');
                            driverStatisticsState.dateTo =
                                String(item.date_to ?? '');

                            loadDriverStatistics(true);
                        }
                    );

                    quickPeriods.appendChild(button);
                }
            );
        }
    };

    const driverStatisticsQuery = () => {
        const query = new URLSearchParams();

        query.set(
            'group_by',
            driverStatisticsState.groupBy
        );

        query.set(
            'carrier_scope',
            driverStatisticsState.carrierScope
        );

        if (
            driverStatisticsState.carrierScope === 'external'
            && driverStatisticsState.carrierOrganizationId
        ) {
            query.set(
                'carrier_organization_id',
                driverStatisticsState.carrierOrganizationId
            );
        }

        if (driverStatisticsState.period) {
            query.set(
                'period',
                driverStatisticsState.period
            );
        }

        if (driverStatisticsState.driverId) {
            query.set(
                'performed_by_driver_id',
                driverStatisticsState.driverId
            );
        }

        if (driverStatisticsState.dateFrom) {
            query.set(
                'service_date_from',
                driverStatisticsState.dateFrom
            );
        }

        if (driverStatisticsState.dateTo) {
            query.set(
                'service_date_to',
                driverStatisticsState.dateTo
            );
        }

        return query.toString();
    };

    const driverStatisticsRenderSummary = (data) => {
        const totals = data?.totals ?? {};
        const set = (id, value) => {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = String(value);
            }
        };

        set(
            'drayviaDriverStatisticsRouteCount',
            driverStatisticsInteger(totals.route_count)
        );
        set(
            'drayviaDriverStatisticsWorkDayCount',
            driverStatisticsInteger(totals.work_day_count)
        );
        set(
            'drayviaDriverStatisticsLoaded',
            driverStatisticsInteger(totals.loaded_parcels)
        );
        set(
            'drayviaDriverStatisticsProcessedShare',
            driverStatisticsPercent(
                totals.processed_share_percent
            )
        );
        set(
            'drayviaDriverStatisticsKmDifference',
            `${driverStatisticsKm(totals.difference_km)} km`
        );

        const summary = document.getElementById(
            'drayviaDriverStatisticsSummary'
        );

        if (summary) {
            const first = totals.first_service_date
                ? driverStatisticsDate(
                    totals.first_service_date
                )
                : '\u2014';

            const last = totals.last_service_date
                ? driverStatisticsDate(
                    totals.last_service_date
                )
                : '\u2014';

            summary.textContent =
                `Obdob\u00ed dat: ${first} \u2013 ${last}`
                + ` \u00b7 Tras: ${driverStatisticsInteger(
                    totals.route_count
                )}`
                + ` \u00b7 Pracovn\u00edch dn\u016f: ${driverStatisticsInteger(
                    totals.work_day_count
                )}`;
        }

        const completeness = document.getElementById(
            'drayviaDriverStatisticsCompleteness'
        );

        if (completeness) {
            completeness.textContent =
                `\u00daplnost z\u00e1silkov\u00fdch dat: ${driverStatisticsInteger(
                    totals.parcel_complete_route_count
                )} z ${driverStatisticsInteger(
                    totals.route_count
                )} tras`;

            completeness.textContent +=
                ` \u00b7 \u00daplnost kilometr\u016f: ${driverStatisticsInteger(
                    totals.kilometre_complete_route_count
                )} z ${driverStatisticsInteger(
                    totals.route_count
                )} tras`;

            if (data?.scope?.quality_profile_applied === false) {
                completeness.textContent +=
                    ' \u00b7 Profil kvality nebyl aplikov\u00e1n.';
            }

            const attribution =
                data?.carrier_attribution ?? {};

            completeness.textContent +=
                ` · Historicky doložený dopravce: ${driverStatisticsInteger(
                    attribution.attributed_route_count
                )} tras`;

            if (
                driverStatisticsNumber(
                    attribution.unattributed_route_count
                ) > 0
            ) {
                completeness.textContent +=
                    ` · Bez historicky doloženého dopravce: ${driverStatisticsInteger(
                        attribution.unattributed_route_count
                    )} tras`;
            }

            completeness.textContent +=
                ' · Dílčí kvalita = (doručeno + přesměrováno + odmítnuto zákazníkem) / naloženo.';
        }
    };

    const driverStatisticsRenderDrivers = (data) => {
        const target = document.getElementById(
            'drayviaDriverStatisticsRows'
        );

        if (!target) {
            return;
        }

        target.replaceChildren();

        const rows = Array.isArray(data?.drivers)
            ? data.drivers
            : [];

        rows.forEach(
            (item) => {
                const metrics = item?.metrics ?? {};
                const row = document.createElement('tr');

                row.appendChild(
                    driverStatisticsIdentityCell(
                        item?.driver ?? {}
                    )
                );
                row.appendChild(
                    driverStatisticsCarrierCell(
                        item?.carriers
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.route_count
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.work_day_count
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.loaded_parcels
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.delivered_parcels
                        ),
                        driverStatisticsPercent(
                            metrics.delivered_share_percent
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.redirected_parcels
                        ),
                        driverStatisticsPercent(
                            metrics.redirected_share_percent
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.customer_rejected_parcels
                        ),
                        driverStatisticsPercent(
                            metrics.customer_rejected_share_percent
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.not_delivered_parcels
                        ),
                        driverStatisticsPercent(
                            metrics.not_delivered_share_percent
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        `${driverStatisticsKm(
                            metrics.planned_km
                        )} km`
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        `${driverStatisticsKm(
                            metrics.actual_km
                        )} km`
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        `${driverStatisticsKm(
                            metrics.difference_km
                        )} km`,
                        driverStatisticsPercent(
                            metrics.kilometre_deviation_percent
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsPercent(
                            metrics.processed_share_percent
                        )
                    )
                );

                target.appendChild(row);
            }
        );

        if (rows.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');

            cell.colSpan = 13;
            cell.className = 'drayvia-driver-stat-empty';
            cell.textContent =
                'Ve zvolen\u00e9m obdob\u00ed nejsou ulo\u017een\u00e9 trasy.';
            row.appendChild(cell);
            target.appendChild(row);
        }
    };

    const driverStatisticsRenderTimeline = (data) => {
        const target = document.getElementById(
            'drayviaDriverStatisticsTimelineRows'
        );

        if (!target) {
            return;
        }

        target.replaceChildren();

        const rows = Array.isArray(data?.timeline)
            ? data.timeline
            : [];

        rows.forEach(
            (item) => {
                const metrics = item?.metrics ?? {};
                const row = document.createElement('tr');

                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsPeriod(item?.period)
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.route_count
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.work_day_count
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.loaded_parcels
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.delivered_parcels
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.redirected_parcels
                        ),
                        driverStatisticsPercent(
                            metrics.redirected_share_percent
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.customer_rejected_parcels
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsInteger(
                            metrics.not_delivered_parcels
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        driverStatisticsPercent(
                            metrics.processed_share_percent
                        )
                    )
                );
                row.appendChild(
                    driverStatisticsCell(
                        `${driverStatisticsKm(
                            metrics.difference_km
                        )} km`
                    )
                );

                target.appendChild(row);
            }
        );

        if (rows.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');

            cell.colSpan = 10;
            cell.className = 'drayvia-driver-stat-empty';
            cell.textContent =
                'Pro zvolen\u00e9 obdob\u00ed nen\u00ed dostupn\u00fd v\u00fdvoj.';
            row.appendChild(cell);
            target.appendChild(row);
        }
    };

    const renderDriverStatistics = () => {
        if (!ensureDriverStatisticsShell()) {
            return;
        }

        driverStatisticsSyncControls();

        const data = driverStatisticsState.data ?? {};

        driverStatisticsRenderSummary(data);
        driverStatisticsRenderDrivers(data);
        driverStatisticsRenderTimeline(data);
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

        if (!force && driverStatisticsState.data) {
            renderDriverStatistics();
            return;
        }

        driverStatisticsState.loading = true;

        const summary = document.getElementById(
            'drayviaDriverStatisticsSummary'
        );

        if (summary) {
            summary.textContent =
                'Na\u010d\u00edt\u00e1m statistiky \u0159idi\u010d\u016f...';
        }

        try {
            const query = driverStatisticsQuery();
            const body = await realDriverApi(
                `/api/v1/daily-reports/performance-overview?${query}`
            );
            const data = driverStatisticsPayload(body);

            if (!Array.isArray(data.drivers)) {
                throw new Error(
                    'API nevr\u00e1tilo p\u0159ehled \u0159idi\u010d\u016f.'
                );
            }

            driverStatisticsState.data = data;

            const filterOptions =
                data?.filter_options ?? {};

            driverStatisticsState.driverOptions =
                Array.isArray(filterOptions.drivers)
                    ? filterOptions.drivers
                    : [];
            driverStatisticsState.carrierOptions =
                Array.isArray(filterOptions.carriers)
                    ? filterOptions.carriers
                    : [];
            driverStatisticsState.quickPeriods =
                Array.isArray(filterOptions.quick_periods)
                    ? filterOptions.quick_periods
                    : [];

            driverStatisticsState.period = String(
                data?.filters?.period
                ?? driverStatisticsState.period
            );
            driverStatisticsState.dateFrom = String(
                data?.filters?.service_date_from ?? ''
            );
            driverStatisticsState.dateTo = String(
                data?.filters?.service_date_to ?? ''
            );

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

    /*
     * S027-03A STATISTICS QUALITY PROFILE SETTINGS
     *
     * The browser selects and displays canonical raw metric components.
     * Formula evaluation, effective-scope resolution and optimistic locking
     * remain server-side responsibilities.
     */
    const driverQualitySettingsBaseUrl =
        '/api/v1/daily-reports/quality-profiles';

    const driverQualitySettingsState = {
        profiles: [],
        bindings: [],
        targets: {
            organization: null,
            carrier_relationships: [],
            driver_assignments: [],
        },
        selectedProfileId: '',
        loading: false,
        loaded: false,
        effective: null,
    };

    const driverQualitySourceLabels = {
        delivered_parcels: 'Doručené zásilky',
        redirected_parcels: 'Přesměrované zásilky',
        customer_rejected_parcels:
            'Zásilky odmítnuté zákazníkem',
    };

    const driverQualityStatusLabels = {
        draft: 'Koncept',
        active: 'Aktivní',
        replaced: 'Nahrazená',
        expired: 'Ukončená',
        archived: 'Archivovaný',
    };

    const driverQualityScopeLabels = {
        organization: 'Výchozí nastavení organizace',
        carrier_relationship: 'Dopravce',
        driver_assignment: 'Řidič',
    };

    const driverQualityCurrentMonth = () => {
        const now = new Date();
        const month = String(
            now.getMonth() + 1
        ).padStart(2, '0');

        return String(now.getFullYear()) + '-' + month;
    };

    const driverQualityCurrentDate = () => {
        const now = new Date();
        const month = String(
            now.getMonth() + 1
        ).padStart(2, '0');
        const day = String(
            now.getDate()
        ).padStart(2, '0');

        return String(now.getFullYear())
            + '-' + month
            + '-' + day;
    };

    const driverQualityMonthStart = (value) => {
        const month = String(value ?? '').match(
            /^(\d{4})-(\d{2})$/
        );

        return month
            ? month[1] + '-' + month[2] + '-01'
            : String(value ?? '');
    };

    const driverQualityItems = (body) => {
        const data = getPayload(body);

        return Array.isArray(data?.items)
            ? data.items
            : [];
    };

    const driverQualityMessage = (
        message,
        tone = 'info'
    ) => {
        const target = document.getElementById(
            'drayviaDriverQualityMessage'
        );

        if (!target) {
            return;
        }

        target.textContent = String(message ?? '');
        target.dataset.tone = tone;
        target.style.color =
            tone === 'error'
                ? '#b42318'
                : tone === 'success'
                    ? '#067647'
                    : '#344054';
        target.style.background =
            tone === 'error'
                ? '#fef3f2'
                : tone === 'success'
                    ? '#ecfdf3'
                    : '#f2f4f7';
        target.style.borderColor =
            tone === 'error'
                ? '#fecdca'
                : tone === 'success'
                    ? '#abefc6'
                    : '#d0d5dd';
    };

    const driverQualityCreateOption = (
        value,
        label
    ) => {
        const option = document.createElement('option');

        option.value = String(value ?? '');
        option.textContent = String(label ?? '');

        return option;
    };

    const driverQualityReplaceOptions = (
        select,
        items,
        placeholder,
        value,
        label
    ) => {
        if (!select) {
            return;
        }

        select.replaceChildren();

        if (placeholder !== null) {
            select.appendChild(
                driverQualityCreateOption(
                    '',
                    placeholder
                )
            );
        }

        items.forEach(
            (item) => {
                select.appendChild(
                    driverQualityCreateOption(
                        value(item),
                        label(item)
                    )
                );
            }
        );
    };

    const driverQualityFormulaText = (
        method,
        sources
    ) => {
        if (method === 'disabled') {
            return 'Výpočet dílčí kvality je výslovně vypnutý.';
        }

        const labels = sources.map(
            (source) =>
                driverQualitySourceLabels[source]
                ?? source
        );

        if (labels.length === 0) {
            return 'Vyberte alespoň jednu složku čitatele.';
        }

        return '(' + labels.join(' + ')
            + ') / Naložené zásilky × 100 %';
    };

    const driverQualitySelectedSources = (
        selector
    ) => Array.from(
        document.querySelectorAll(selector)
    )
        .filter((input) => input.checked)
        .map((input) => String(input.value));

    const driverQualityTextCell = (value) => {
        const cell = document.createElement('td');

        cell.textContent = String(value ?? '—');

        return cell;
    };

    const ensureDriverQualitySettingsShell = () => {
        const host = document.getElementById(
            'drayviaDriverQualitySettingsHost'
        );

        if (!host) {
            return null;
        }

        if (
            host.querySelector(
                '#drayviaDriverQualityCreateForm'
            )
        ) {
            return host;
        }

        host.innerHTML = `
            <style>
                .drayvia-driver-quality-settings {
                    padding:0;
                    overflow:hidden;
                }
                .drayvia-quality-head {
                    padding:20px;
                    border-bottom:1px solid #e4e7ec;
                }
                .drayvia-quality-grid {
                    display:grid;
                    grid-template-columns:repeat(2,minmax(0,1fr));
                    gap:16px;
                    padding:16px;
                }
                .drayvia-quality-card {
                    border:1px solid #d0d5dd;
                    border-radius:12px;
                    background:#fff;
                    padding:16px;
                }
                .drayvia-quality-card.wide {
                    grid-column:1 / -1;
                }
                .drayvia-quality-card h3 {
                    margin:0 0 6px;
                    font-size:16px;
                }
                .drayvia-quality-card p {
                    margin:0 0 14px;
                    color:#475467;
                    line-height:1.5;
                }
                .drayvia-quality-fields {
                    display:grid;
                    grid-template-columns:repeat(2,minmax(0,1fr));
                    gap:12px;
                }
                .drayvia-quality-field {
                    display:flex;
                    flex-direction:column;
                    gap:6px;
                    min-width:0;
                    color:#344054;
                    font-size:12px;
                    font-weight:800;
                }
                .drayvia-quality-field.wide {
                    grid-column:1 / -1;
                }
                .drayvia-quality-field input,
                .drayvia-quality-field select,
                .drayvia-quality-field textarea {
                    width:100%;
                    border:1px solid #b8c2ce;
                    border-radius:8px;
                    background:#fff;
                    color:#101828;
                    font:inherit;
                    font-weight:600;
                    padding:9px 10px;
                }
                .drayvia-quality-field textarea {
                    min-height:72px;
                    resize:vertical;
                }
                .drayvia-quality-source-list {
                    display:grid;
                    gap:8px;
                    padding:10px;
                    border:1px solid #e4e7ec;
                    border-radius:8px;
                    background:#f9fafb;
                }
                .drayvia-quality-source-list label {
                    display:flex;
                    gap:8px;
                    align-items:center;
                    font-size:13px;
                    font-weight:700;
                    color:#344054;
                }
                .drayvia-quality-source-list input {
                    width:auto;
                }
                .drayvia-quality-formula {
                    margin-top:10px;
                    padding:10px 12px;
                    border-left:4px solid #175cd3;
                    background:#eff8ff;
                    color:#1849a9;
                    font-size:13px;
                    line-height:1.45;
                }
                .drayvia-quality-actions {
                    display:flex;
                    flex-wrap:wrap;
                    gap:8px;
                    margin-top:14px;
                }
                .drayvia-quality-table-wrap {
                    overflow:auto;
                    margin-top:12px;
                }
                .drayvia-quality-table {
                    width:100%;
                    border-collapse:collapse;
                    font-size:12px;
                }
                .drayvia-quality-table th,
                .drayvia-quality-table td {
                    padding:9px 8px;
                    border-bottom:1px solid #e4e7ec;
                    text-align:left;
                    vertical-align:top;
                }
                .drayvia-quality-table th {
                    color:#475467;
                    background:#f9fafb;
                    font-size:10px;
                    letter-spacing:.04em;
                    text-transform:uppercase;
                }
                @media (max-width:980px) {
                    .drayvia-quality-grid,
                    .drayvia-quality-fields {
                        grid-template-columns:1fr;
                    }
                    .drayvia-quality-card.wide,
                    .drayvia-quality-field.wide {
                        grid-column:auto;
                    }
                }
            </style>

            <div class="drayvia-quality-head">
                <h2 class="drayvia-preview-panel-title">
                    NASTAVENÍ DÍLČÍ KVALITY
                </h2>
                <div class="drayvia-preview-panel-subtitle">
                    Samostatné, verzované nastavení zdrojů výpočtu pro organizaci,
                    dopravce nebo konkrétní zařazení řidiče.
                </div>
            </div>

            <div
                id="drayviaDriverQualityMessage"
                style="margin:16px 16px 0;padding:10px 12px;border:1px solid #d0d5dd;border-radius:8px;"
            >
                Načítám nastavení dílčí kvality…
            </div>

            <div class="drayvia-quality-grid">
                <section class="drayvia-quality-card">
                    <h3>Nový profil</h3>
                    <p>
                        Profil pouze vybírá zdroje čitatele. Jmenovatelem
                        zůstávají naložené zásilky a výpočet provádí server.
                    </p>
                    <form id="drayviaDriverQualityCreateForm">
                        <div class="drayvia-quality-fields">
                            <label class="drayvia-quality-field">
                                Kód
                                <input id="drayviaDriverQualityCreateCode" maxlength="32" required placeholder="STANDARD">
                            </label>
                            <label class="drayvia-quality-field">
                                Název
                                <input id="drayviaDriverQualityCreateName" maxlength="150" required placeholder="Standardní dílčí kvalita">
                            </label>
                            <label class="drayvia-quality-field wide">
                                Popis
                                <textarea id="drayviaDriverQualityCreateDescription" maxlength="5000"></textarea>
                            </label>
                            <label class="drayvia-quality-field">
                                Režim
                                <select id="drayviaDriverQualityCreateMethod">
                                    <option value="processed_share">Vypočítat podíl</option>
                                    <option value="disabled">Výpočet vypnout</option>
                                </select>
                            </label>
                            <label class="drayvia-quality-field">
                                Důvod založení
                                <input id="drayviaDriverQualityCreateReason" maxlength="2000">
                            </label>
                            <div class="drayvia-quality-field wide">
                                Složky čitatele
                                <div class="drayvia-quality-source-list">
                                    <label>
                                        <input type="checkbox" value="delivered_parcels" data-quality-create-source checked>
                                        Doručené zásilky
                                    </label>
                                    <label>
                                        <input type="checkbox" value="redirected_parcels" data-quality-create-source checked>
                                        Přesměrované zásilky
                                    </label>
                                    <label>
                                        <input type="checkbox" value="customer_rejected_parcels" data-quality-create-source checked>
                                        Zásilky odmítnuté zákazníkem
                                    </label>
                                </div>
                                <div id="drayviaDriverQualityCreateFormula" class="drayvia-quality-formula"></div>
                            </div>
                        </div>
                        <div class="drayvia-quality-actions">
                            <button class="drayvia-preview-action primary" type="submit">
                                VYTVOŘIT KONCEPT
                            </button>
                        </div>
                    </form>
                </section>

                <section class="drayvia-quality-card">
                    <h3>Profil a verze</h3>
                    <p>
                        Změna vzorce vzniká jako nová revize. Aktivace i
                        účinnost vazeb začínají vždy prvním dnem měsíce.
                    </p>
                    <label class="drayvia-quality-field">
                        Profil
                        <select id="drayviaDriverQualityProfileSelect"></select>
                    </label>
                    <div class="drayvia-quality-fields" style="margin-top:12px;">
                        <label class="drayvia-quality-field">
                            Důvod nové revize
                            <input id="drayviaDriverQualityNewVersionReason" maxlength="2000">
                        </label>
                        <div class="drayvia-quality-field" style="justify-content:flex-end;">
                            <button id="drayviaDriverQualityNewVersion" class="drayvia-preview-action" type="button">
                                NOVÁ REVIZE
                            </button>
                        </div>
                    </div>
                    <div id="drayviaDriverQualityProfileDetail" style="margin-top:14px;"></div>
                </section>

                <section class="drayvia-quality-card wide">
                    <h3>Platnost nastavení</h3>
                    <p>
                        Nejpřesnější vazba má přednost: řidič, poté dopravce,
                        poté organizace. Ukončením výjimky se zvolený cíl vrátí
                        k děděnému nastavení.
                    </p>
                    <form id="drayviaDriverQualityBindingForm">
                        <div class="drayvia-quality-fields">
                            <label class="drayvia-quality-field">
                                Profil
                                <select id="drayviaDriverQualityBindingProfile" required></select>
                            </label>
                            <label class="drayvia-quality-field">
                                Úroveň
                                <select id="drayviaDriverQualityBindingScope">
                                    <option value="organization">Organizace – výchozí</option>
                                    <option value="carrier_relationship">Dopravce</option>
                                    <option value="driver_assignment">Řidič</option>
                                </select>
                            </label>
                            <label id="drayviaDriverQualityBindingTargetLabel" class="drayvia-quality-field">
                                <span id="drayviaDriverQualityBindingTargetText">Cíl</span>
                                <select id="drayviaDriverQualityBindingTarget"></select>
                            </label>
                            <label class="drayvia-quality-field">
                                Platnost od měsíce
                                <input id="drayviaDriverQualityBindingMonth" type="month" required>
                            </label>
                        </div>
                        <div class="drayvia-quality-actions">
                            <button class="drayvia-preview-action primary" type="submit">
                                ULOŽIT PLATNOST
                            </button>
                            <label class="drayvia-quality-field" style="min-width:190px;">
                                Ukončit od měsíce
                                <input id="drayviaDriverQualityEndMonth" type="month">
                            </label>
                        </div>
                    </form>
                    <div class="drayvia-quality-table-wrap">
                        <table class="drayvia-quality-table">
                            <thead>
                                <tr>
                                    <th>Úroveň</th>
                                    <th>Cíl</th>
                                    <th>Profil</th>
                                    <th>Platnost</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody id="drayviaDriverQualityBindingRows"></tbody>
                        </table>
                    </div>
                </section>

                <section class="drayvia-quality-card wide">
                    <h3>Ověřit účinné nastavení</h3>
                    <p>
                        Náhled pouze ukáže, která historická vazba a verze
                        platí pro zadané datum. Statistiky se zde nepřepočítávají.
                    </p>
                    <form id="drayviaDriverQualityEffectiveForm">
                        <div class="drayvia-quality-fields">
                            <label class="drayvia-quality-field">
                                Datum trasy
                                <input id="drayviaDriverQualityEffectiveDate" type="date" required>
                            </label>
                            <label class="drayvia-quality-field">
                                Dopravce (volitelně)
                                <select id="drayviaDriverQualityEffectiveCarrier"></select>
                            </label>
                            <label class="drayvia-quality-field">
                                Řidič (volitelně)
                                <select id="drayviaDriverQualityEffectiveDriver"></select>
                            </label>
                        </div>
                        <div class="drayvia-quality-actions">
                            <button class="drayvia-preview-action" type="submit">
                                OVĚŘIT NASTAVENÍ
                            </button>
                        </div>
                    </form>
                    <div id="drayviaDriverQualityEffectiveResult" class="drayvia-quality-formula" style="margin-top:14px;">
                        Zvolte datum a případný cíl.
                    </div>
                </section>
            </div>
        `;

        const month = driverQualityCurrentMonth();
        const bindingMonth = document.getElementById(
            'drayviaDriverQualityBindingMonth'
        );
        const endMonth = document.getElementById(
            'drayviaDriverQualityEndMonth'
        );
        const effectiveDate = document.getElementById(
            'drayviaDriverQualityEffectiveDate'
        );

        if (bindingMonth) {
            bindingMonth.value = month;
        }

        if (endMonth) {
            endMonth.value = month;
        }

        if (effectiveDate) {
            effectiveDate.value =
                driverQualityCurrentDate();
        }

        const createMethod = document.getElementById(
            'drayviaDriverQualityCreateMethod'
        );

        createMethod?.addEventListener(
            'change',
            driverQualitySyncCreateFormula
        );

        host.querySelectorAll(
            '[data-quality-create-source]'
        ).forEach(
            (input) => input.addEventListener(
                'change',
                driverQualitySyncCreateFormula
            )
        );

        document.getElementById(
            'drayviaDriverQualityCreateForm'
        )?.addEventListener(
            'submit',
            driverQualityCreateProfile
        );

        document.getElementById(
            'drayviaDriverQualityProfileSelect'
        )?.addEventListener(
            'change',
            (event) => {
                driverQualitySettingsState
                    .selectedProfileId =
                    String(event.target?.value ?? '');
                driverQualityRenderProfileDetail();
            }
        );

        document.getElementById(
            'drayviaDriverQualityNewVersion'
        )?.addEventListener(
            'click',
            driverQualityCreateVersion
        );

        document.getElementById(
            'drayviaDriverQualityBindingScope'
        )?.addEventListener(
            'change',
            driverQualitySyncBindingTargets
        );

        document.getElementById(
            'drayviaDriverQualityBindingForm'
        )?.addEventListener(
            'submit',
            driverQualitySaveBinding
        );

        document.getElementById(
            'drayviaDriverQualityEffectiveForm'
        )?.addEventListener(
            'submit',
            driverQualityLoadEffective
        );

        driverQualitySyncCreateFormula();

        return host;
    };

    function driverQualitySyncCreateFormula() {
        const method = String(
            document.getElementById(
                'drayviaDriverQualityCreateMethod'
            )?.value ?? 'processed_share'
        );
        const inputs = Array.from(
            document.querySelectorAll(
                '[data-quality-create-source]'
            )
        );
        const disabled = method === 'disabled';

        inputs.forEach(
            (input) => {
                input.disabled = disabled;
            }
        );

        const formula = document.getElementById(
            'drayviaDriverQualityCreateFormula'
        );

        if (formula) {
            formula.textContent =
                driverQualityFormulaText(
                    method,
                    disabled
                        ? []
                        : driverQualitySelectedSources(
                            '[data-quality-create-source]'
                        )
                );
        }
    }

    const driverQualitySelectedProfile = () =>
        driverQualitySettingsState.profiles.find(
            (profile) =>
                String(profile?.public_id ?? '')
                === driverQualitySettingsState
                    .selectedProfileId
        ) ?? null;

    const driverQualityRenderProfileOptions = () => {
        const profiles =
            driverQualitySettingsState.profiles;
        const selected =
            driverQualitySettingsState
                .selectedProfileId;
        const profileSelect = document.getElementById(
            'drayviaDriverQualityProfileSelect'
        );
        const bindingSelect = document.getElementById(
            'drayviaDriverQualityBindingProfile'
        );

        driverQualityReplaceOptions(
            profileSelect,
            profiles,
            profiles.length > 0
                ? null
                : 'Zatím nebyl vytvořen žádný profil',
            (profile) => profile.public_id,
            (profile) =>
                profile.code + ' · ' + profile.name
        );

        if (profileSelect && selected) {
            profileSelect.value = selected;
        }

        driverQualityReplaceOptions(
            bindingSelect,
            profiles.filter(
                (profile) =>
                    profile.status === 'active'
            ),
            'Vyberte aktivní profil',
            (profile) => profile.public_id,
            (profile) =>
                profile.code + ' · ' + profile.name
        );
    };

    const driverQualityRenderVersionRows = (
        versions,
        target
    ) => {
        target.replaceChildren();

        versions.forEach(
            (version) => {
                const row = document.createElement('tr');
                const sources = Array.isArray(
                    version?.numerator_sources
                )
                    ? version.numerator_sources
                    : [];

                row.appendChild(
                    driverQualityTextCell(
                        'v' + String(
                            version?.version_number ?? '—'
                        )
                    )
                );
                row.appendChild(
                    driverQualityTextCell(
                        driverQualityStatusLabels[
                            version?.status
                        ] ?? version?.status
                    )
                );
                row.appendChild(
                    driverQualityTextCell(
                        driverQualityFormulaText(
                            version?.calculation_method,
                            sources
                        )
                    )
                );
                row.appendChild(
                    driverQualityTextCell(
                        (version?.valid_from || '—')
                        + ' – '
                        + (version?.valid_until || '—')
                    )
                );
                row.appendChild(
                    driverQualityTextCell(
                        version?.lock_version ?? '—'
                    )
                );
                target.appendChild(row);
            }
        );

        if (versions.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');

            cell.colSpan = 5;
            cell.textContent =
                'Profil zatím nemá žádnou verzi.';
            row.appendChild(cell);
            target.appendChild(row);
        }
    };

    const driverQualityRenderProfileDetail = () => {
        const target = document.getElementById(
            'drayviaDriverQualityProfileDetail'
        );
        const profile = driverQualitySelectedProfile();
        const newVersionButton = document.getElementById(
            'drayviaDriverQualityNewVersion'
        );

        if (!target) {
            return;
        }

        if (!profile) {
            target.textContent =
                'Vytvořte nebo vyberte profil.';

            if (newVersionButton) {
                newVersionButton.disabled = true;
            }

            return;
        }

        const versions = Array.isArray(profile.versions)
            ? profile.versions
            : [];
        const draft = versions.find(
            (version) => version?.status === 'draft'
        ) ?? null;

        if (newVersionButton) {
            newVersionButton.disabled =
                Boolean(draft);
            newVersionButton.title = draft
                ? 'Profil již má otevřený koncept.'
                : '';
        }

        target.innerHTML = `
            <div style="padding:12px;border:1px solid #e4e7ec;border-radius:10px;background:#f9fafb;">
                <strong id="drayviaDriverQualityProfileTitle"></strong>
                <div id="drayviaDriverQualityProfileDescription" style="margin-top:4px;color:#475467;"></div>
            </div>
            <div id="drayviaDriverQualityDraftEditor" style="margin-top:12px;"></div>
            <div class="drayvia-quality-table-wrap">
                <table class="drayvia-quality-table">
                    <thead>
                        <tr>
                            <th>Verze</th>
                            <th>Stav</th>
                            <th>Výpočet</th>
                            <th>Platnost</th>
                            <th>Revize</th>
                        </tr>
                    </thead>
                    <tbody id="drayviaDriverQualityVersionRows"></tbody>
                </table>
            </div>
        `;

        const title = document.getElementById(
            'drayviaDriverQualityProfileTitle'
        );
        const description = document.getElementById(
            'drayviaDriverQualityProfileDescription'
        );

        if (title) {
            title.textContent =
                profile.code + ' · ' + profile.name;
        }

        if (description) {
            description.textContent =
                profile.description
                || 'Bez doplňujícího popisu.';
        }

        const rows = document.getElementById(
            'drayviaDriverQualityVersionRows'
        );

        if (rows) {
            driverQualityRenderVersionRows(
                versions,
                rows
            );
        }

        const editor = document.getElementById(
            'drayviaDriverQualityDraftEditor'
        );

        if (!editor) {
            return;
        }

        if (!draft) {
            editor.textContent =
                'Profil nemá otevřený koncept. Pro změnu vzorce založte novou revizi.';
            return;
        }

        editor.innerHTML = `
            <form id="drayviaDriverQualityDraftForm">
                <div class="drayvia-quality-fields">
                    <label class="drayvia-quality-field">
                        Režim konceptu
                        <select id="drayviaDriverQualityDraftMethod">
                            <option value="processed_share">Vypočítat podíl</option>
                            <option value="disabled">Výpočet vypnout</option>
                        </select>
                    </label>
                    <label class="drayvia-quality-field">
                        Důvod změny
                        <input id="drayviaDriverQualityDraftReason" maxlength="2000">
                    </label>
                    <div class="drayvia-quality-field wide">
                        Složky čitatele
                        <div class="drayvia-quality-source-list">
                            <label>
                                <input type="checkbox" value="delivered_parcels" data-quality-draft-source>
                                Doručené zásilky
                            </label>
                            <label>
                                <input type="checkbox" value="redirected_parcels" data-quality-draft-source>
                                Přesměrované zásilky
                            </label>
                            <label>
                                <input type="checkbox" value="customer_rejected_parcels" data-quality-draft-source>
                                Zásilky odmítnuté zákazníkem
                            </label>
                        </div>
                        <div id="drayviaDriverQualityDraftFormula" class="drayvia-quality-formula"></div>
                    </div>
                    <label class="drayvia-quality-field">
                        Aktivovat od měsíce
                        <input id="drayviaDriverQualityActivationMonth" type="month">
                    </label>
                </div>
                <div class="drayvia-quality-actions">
                    <button class="drayvia-preview-action primary" type="submit">
                        ULOŽIT KONCEPT
                    </button>
                    <button id="drayviaDriverQualityActivateVersion" class="drayvia-preview-action" type="button">
                        AKTIVOVAT OD MĚSÍCE
                    </button>
                </div>
            </form>
        `;

        const method = document.getElementById(
            'drayviaDriverQualityDraftMethod'
        );
        const reason = document.getElementById(
            'drayviaDriverQualityDraftReason'
        );
        const activation = document.getElementById(
            'drayviaDriverQualityActivationMonth'
        );
        const sources = Array.isArray(
            draft.numerator_sources
        )
            ? draft.numerator_sources
            : [];

        if (method) {
            method.value =
                draft.calculation_method;
        }

        if (reason) {
            reason.value =
                draft.change_reason || '';
        }

        if (activation) {
            activation.value =
                driverQualityCurrentMonth();
        }

        editor.querySelectorAll(
            '[data-quality-draft-source]'
        ).forEach(
            (input) => {
                input.checked =
                    sources.includes(input.value);
                input.addEventListener(
                    'change',
                    driverQualitySyncDraftFormula
                );
            }
        );

        method?.addEventListener(
            'change',
            driverQualitySyncDraftFormula
        );

        document.getElementById(
            'drayviaDriverQualityDraftForm'
        )?.addEventListener(
            'submit',
            (event) =>
                driverQualitySaveDraft(
                    event,
                    profile,
                    draft
                )
        );

        document.getElementById(
            'drayviaDriverQualityActivateVersion'
        )?.addEventListener(
            'click',
            () =>
                driverQualityActivateVersion(
                    profile,
                    draft
                )
        );

        driverQualitySyncDraftFormula();
    };

    function driverQualitySyncDraftFormula() {
        const method = String(
            document.getElementById(
                'drayviaDriverQualityDraftMethod'
            )?.value ?? 'processed_share'
        );
        const inputs = Array.from(
            document.querySelectorAll(
                '[data-quality-draft-source]'
            )
        );
        const disabled = method === 'disabled';

        inputs.forEach(
            (input) => {
                input.disabled = disabled;
            }
        );

        const formula = document.getElementById(
            'drayviaDriverQualityDraftFormula'
        );

        if (formula) {
            formula.textContent =
                driverQualityFormulaText(
                    method,
                    disabled
                        ? []
                        : driverQualitySelectedSources(
                            '[data-quality-draft-source]'
                        )
                );
        }
    }

    const driverQualitySyncBindingTargets = () => {
        const scope = String(
            document.getElementById(
                'drayviaDriverQualityBindingScope'
            )?.value ?? 'organization'
        );
        const target = document.getElementById(
            'drayviaDriverQualityBindingTarget'
        );
        const label = document.getElementById(
            'drayviaDriverQualityBindingTargetText'
        );
        const targets =
            driverQualitySettingsState.targets;

        if (!target) {
            return;
        }

        if (scope === 'organization') {
            const organization =
                targets.organization;

            driverQualityReplaceOptions(
                target,
                organization
                    ? [organization]
                    : [],
                null,
                (item) => item.id,
                (item) => item.name
            );
            target.disabled = true;

            if (label) {
                label.textContent = 'Organizace';
            }

            return;
        }

        target.disabled = false;

        if (scope === 'carrier_relationship') {
            driverQualityReplaceOptions(
                target,
                targets.carrier_relationships,
                'Vyberte dopravce',
                (item) => item.relationship_id,
                (item) => item.name
            );

            if (label) {
                label.textContent = 'Dopravce';
            }

            return;
        }

        driverQualityReplaceOptions(
            target,
            targets.driver_assignments,
            'Vyberte řidiče',
            (item) => item.assignment_id,
            (item) =>
                item.driver_name
                + ' · '
                + item.organization_name
        );

        if (label) {
            label.textContent = 'Řidič';
        }
    };

    const driverQualityRenderTargets = () => {
        const targets =
            driverQualitySettingsState.targets;
        const effectiveCarrier =
            document.getElementById(
                'drayviaDriverQualityEffectiveCarrier'
            );
        const effectiveDriver =
            document.getElementById(
                'drayviaDriverQualityEffectiveDriver'
            );

        driverQualityReplaceOptions(
            effectiveCarrier,
            targets.carrier_relationships,
            'Bez konkrétního dopravce',
            (item) => item.relationship_id,
            (item) => item.name
        );
        driverQualityReplaceOptions(
            effectiveDriver,
            targets.driver_assignments,
            'Bez konkrétního řidiče',
            (item) => item.assignment_id,
            (item) =>
                item.driver_name
                + ' · '
                + item.organization_name
        );
        driverQualitySyncBindingTargets();
    };

    const driverQualityBindingPath = (
        binding
    ) => {
        if (
            binding?.scope_type === 'organization'
        ) {
            return driverQualitySettingsBaseUrl
                + '/bindings/organization';
        }

        if (
            binding?.scope_type
            === 'carrier_relationship'
        ) {
            return driverQualitySettingsBaseUrl
                + '/bindings/carrier-relationships/'
                + String(
                    binding.organization_relationship_id
                );
        }

        return driverQualitySettingsBaseUrl
            + '/bindings/driver-assignments/'
            + String(
                binding
                    ?.driver_organization_assignment_id
            );
    };

    const driverQualityRenderBindings = () => {
        const target = document.getElementById(
            'drayviaDriverQualityBindingRows'
        );

        if (!target) {
            return;
        }

        target.replaceChildren();

        driverQualitySettingsState.bindings.forEach(
            (binding) => {
                const row = document.createElement('tr');
                const validity =
                    (binding?.valid_from || '—')
                    + ' – '
                    + (binding?.valid_until || '—');

                row.appendChild(
                    driverQualityTextCell(
                        driverQualityScopeLabels[
                            binding?.scope_type
                        ] ?? binding?.scope_type
                    )
                );
                row.appendChild(
                    driverQualityTextCell(
                        binding?.scope_label || '—'
                    )
                );
                row.appendChild(
                    driverQualityTextCell(
                        binding?.profile
                            ? binding.profile.code
                                + ' · '
                                + binding.profile.name
                            : '—'
                    )
                );
                row.appendChild(
                    driverQualityTextCell(validity)
                );

                const action = document.createElement('td');

                if (!binding?.valid_until) {
                    const button =
                        document.createElement('button');

                    button.type = 'button';
                    button.className =
                        'drayvia-preview-action';
                    button.textContent =
                        'UKONČIT VÝJIMKU';
                    button.addEventListener(
                        'click',
                        () =>
                            driverQualityEndBinding(
                                binding
                            )
                    );
                    action.appendChild(button);
                }
                else {
                    action.textContent =
                        'Historie';
                }

                row.appendChild(action);
                target.appendChild(row);
            }
        );

        if (
            driverQualitySettingsState
                .bindings.length === 0
        ) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');

            cell.colSpan = 5;
            cell.textContent =
                'Zatím není nastavena žádná platnost profilu.';
            row.appendChild(cell);
            target.appendChild(row);
        }
    };

    const driverQualityRender = () => {
        if (!ensureDriverQualitySettingsShell()) {
            return;
        }

        const profiles =
            driverQualitySettingsState.profiles;

        if (
            profiles.length > 0
            && !profiles.some(
                (profile) =>
                    String(profile.public_id)
                    === driverQualitySettingsState
                        .selectedProfileId
            )
        ) {
            driverQualitySettingsState
                .selectedProfileId =
                String(profiles[0].public_id);
        }

        driverQualityRenderProfileOptions();
        driverQualityRenderProfileDetail();
        driverQualityRenderTargets();
        driverQualityRenderBindings();
    };

    const loadDriverQualitySettings = async (
        force = false
    ) => {
        if (!ensureDriverQualitySettingsShell()) {
            return;
        }

        if (driverQualitySettingsState.loading) {
            return;
        }

        if (
            driverQualitySettingsState.loaded
            && !force
        ) {
            driverQualityRender();
            return;
        }

        driverQualitySettingsState.loading = true;
        driverQualityMessage(
            'Načítám profily, platnosti a povolené cíle…'
        );

        try {
            const [
                profilesBody,
                bindingsBody,
                targetsBody,
            ] = await Promise.all([
                realDriverApi(
                    driverQualitySettingsBaseUrl
                ),
                realDriverApi(
                    driverQualitySettingsBaseUrl
                    + '/bindings'
                ),
                realDriverApi(
                    driverQualitySettingsBaseUrl
                    + '/targets'
                ),
            ]);

            driverQualitySettingsState.profiles =
                driverQualityItems(profilesBody);
            driverQualitySettingsState.bindings =
                driverQualityItems(bindingsBody);

            const targets = getPayload(targetsBody);

            driverQualitySettingsState.targets =
                targets
                && typeof targets === 'object'
                    ? {
                        organization:
                            targets.organization ?? null,
                        carrier_relationships:
                            Array.isArray(
                                targets
                                    .carrier_relationships
                            )
                                ? targets
                                    .carrier_relationships
                                : [],
                        driver_assignments:
                            Array.isArray(
                                targets.driver_assignments
                            )
                                ? targets
                                    .driver_assignments
                                : [],
                    }
                    : {
                        organization: null,
                        carrier_relationships: [],
                        driver_assignments: [],
                    };

            driverQualitySettingsState.loaded = true;
            driverQualityRender();
            driverQualityMessage(
                'Nastavení dílčí kvality je aktuální.',
                'success'
            );
        }
        catch (error) {
            driverQualityMessage(
                'Nastavení se nepodařilo načíst: '
                + (error?.message || 'neznámá chyba'),
                'error'
            );
        }
        finally {
            driverQualitySettingsState.loading = false;
        }
    };

    async function driverQualityCreateProfile(
        event
    ) {
        event.preventDefault();

        const method = String(
            document.getElementById(
                'drayviaDriverQualityCreateMethod'
            )?.value ?? 'processed_share'
        );

        try {
            await realDriverApi(
                driverQualitySettingsBaseUrl,
                {
                    method: 'POST',
                    body: JSON.stringify({
                        code: String(
                            document.getElementById(
                                'drayviaDriverQualityCreateCode'
                            )?.value ?? ''
                        ),
                        name: String(
                            document.getElementById(
                                'drayviaDriverQualityCreateName'
                            )?.value ?? ''
                        ),
                        description: String(
                            document.getElementById(
                                'drayviaDriverQualityCreateDescription'
                            )?.value ?? ''
                        ) || null,
                        calculation_method: method,
                        numerator_sources:
                            method === 'disabled'
                                ? []
                                : driverQualitySelectedSources(
                                    '[data-quality-create-source]'
                                ),
                        change_reason: String(
                            document.getElementById(
                                'drayviaDriverQualityCreateReason'
                            )?.value ?? ''
                        ) || null,
                    }),
                }
            );

            event.target.reset();
            driverQualitySyncCreateFormula();
            driverQualitySettingsState.loaded = false;
            await loadDriverQualitySettings(true);
            driverQualityMessage(
                'Koncept profilu byl vytvořen.',
                'success'
            );
        }
        catch (error) {
            driverQualityMessage(
                'Profil se nepodařilo vytvořit: '
                + (error?.message || 'neznámá chyba'),
                'error'
            );
        }
    }

    async function driverQualityCreateVersion() {
        const profile = driverQualitySelectedProfile();

        if (!profile) {
            return;
        }

        try {
            await realDriverApi(
                driverQualitySettingsBaseUrl
                + '/'
                + profile.public_id
                + '/versions',
                {
                    method: 'POST',
                    body: JSON.stringify({
                        change_reason: String(
                            document.getElementById(
                                'drayviaDriverQualityNewVersionReason'
                            )?.value ?? ''
                        ) || null,
                    }),
                }
            );
            driverQualitySettingsState.loaded = false;
            await loadDriverQualitySettings(true);
            driverQualityMessage(
                'Nová revize byla založena.',
                'success'
            );
        }
        catch (error) {
            driverQualityMessage(
                'Revizi se nepodařilo založit: '
                + (error?.message || 'neznámá chyba'),
                'error'
            );
        }
    }

    async function driverQualitySaveDraft(
        event,
        profile,
        draft
    ) {
        event.preventDefault();

        const method = String(
            document.getElementById(
                'drayviaDriverQualityDraftMethod'
            )?.value ?? 'processed_share'
        );

        try {
            await realDriverApi(
                driverQualitySettingsBaseUrl
                + '/'
                + profile.public_id
                + '/versions/'
                + String(draft.version_number),
                {
                    method: 'PUT',
                    body: JSON.stringify({
                        lock_version:
                            Number(draft.lock_version),
                        calculation_method: method,
                        numerator_sources:
                            method === 'disabled'
                                ? []
                                : driverQualitySelectedSources(
                                    '[data-quality-draft-source]'
                                ),
                        change_reason: String(
                            document.getElementById(
                                'drayviaDriverQualityDraftReason'
                            )?.value ?? ''
                        ) || null,
                    }),
                }
            );
            driverQualitySettingsState.loaded = false;
            await loadDriverQualitySettings(true);
            driverQualityMessage(
                'Koncept byl uložen s novou revizí zámku.',
                'success'
            );
        }
        catch (error) {
            driverQualityMessage(
                'Koncept se nepodařilo uložit: '
                + (error?.message || 'neznámá chyba'),
                'error'
            );
        }
    }

    async function driverQualityActivateVersion(
        profile,
        draft
    ) {
        const month = String(
            document.getElementById(
                'drayviaDriverQualityActivationMonth'
            )?.value ?? ''
        );

        if (!month) {
            driverQualityMessage(
                'Vyberte měsíc aktivace.',
                'error'
            );
            return;
        }

        try {
            await realDriverApi(
                driverQualitySettingsBaseUrl
                + '/'
                + profile.public_id
                + '/versions/'
                + String(draft.version_number)
                + '/activate',
                {
                    method: 'POST',
                    body: JSON.stringify({
                        lock_version:
                            Number(draft.lock_version),
                        valid_from:
                            driverQualityMonthStart(month),
                    }),
                }
            );
            driverQualitySettingsState.loaded = false;
            await loadDriverQualitySettings(true);
            driverQualityMessage(
                'Verze byla aktivována od zvoleného měsíce.',
                'success'
            );
        }
        catch (error) {
            driverQualityMessage(
                'Verzi se nepodařilo aktivovat: '
                + (error?.message || 'neznámá chyba'),
                'error'
            );
        }
    }

    async function driverQualitySaveBinding(
        event
    ) {
        event.preventDefault();

        const profileId = String(
            document.getElementById(
                'drayviaDriverQualityBindingProfile'
            )?.value ?? ''
        );
        const scope = String(
            document.getElementById(
                'drayviaDriverQualityBindingScope'
            )?.value ?? 'organization'
        );
        const targetId = String(
            document.getElementById(
                'drayviaDriverQualityBindingTarget'
            )?.value ?? ''
        );
        const month = String(
            document.getElementById(
                'drayviaDriverQualityBindingMonth'
            )?.value ?? ''
        );
        let path = driverQualitySettingsBaseUrl
            + '/bindings/organization';

        if (
            scope === 'carrier_relationship'
        ) {
            path = driverQualitySettingsBaseUrl
                + '/bindings/carrier-relationships/'
                + targetId;
        }
        else if (scope === 'driver_assignment') {
            path = driverQualitySettingsBaseUrl
                + '/bindings/driver-assignments/'
                + targetId;
        }

        try {
            await realDriverApi(
                path,
                {
                    method: 'PUT',
                    body: JSON.stringify({
                        profile_public_id: profileId,
                        valid_from:
                            driverQualityMonthStart(month),
                    }),
                }
            );
            driverQualitySettingsState.loaded = false;
            await loadDriverQualitySettings(true);
            driverQualityMessage(
                'Platnost profilu byla uložena.',
                'success'
            );
        }
        catch (error) {
            driverQualityMessage(
                'Platnost se nepodařilo uložit: '
                + (error?.message || 'neznámá chyba'),
                'error'
            );
        }
    }

    async function driverQualityEndBinding(
        binding
    ) {
        const month = String(
            document.getElementById(
                'drayviaDriverQualityEndMonth'
            )?.value ?? ''
        );

        if (!month) {
            driverQualityMessage(
                'Vyberte měsíc návratu k děděnému nastavení.',
                'error'
            );
            return;
        }

        if (
            !window.confirm(
                'Ukončit tuto výjimku a obnovit děděné nastavení?'
            )
        ) {
            return;
        }

        try {
            await realDriverApi(
                driverQualityBindingPath(binding),
                {
                    method: 'DELETE',
                    body: JSON.stringify({
                        effective_from:
                            driverQualityMonthStart(month),
                    }),
                }
            );
            driverQualitySettingsState.loaded = false;
            await loadDriverQualitySettings(true);
            driverQualityMessage(
                'Výjimka byla ukončena; od zvoleného měsíce se opět dědí.',
                'success'
            );
        }
        catch (error) {
            driverQualityMessage(
                'Výjimku se nepodařilo ukončit: '
                + (error?.message || 'neznámá chyba'),
                'error'
            );
        }
    }

    async function driverQualityLoadEffective(
        event
    ) {
        event.preventDefault();

        const query = new URLSearchParams();
        const serviceDate = String(
            document.getElementById(
                'drayviaDriverQualityEffectiveDate'
            )?.value ?? ''
        );
        const relationshipId = String(
            document.getElementById(
                'drayviaDriverQualityEffectiveCarrier'
            )?.value ?? ''
        );
        const assignmentId = String(
            document.getElementById(
                'drayviaDriverQualityEffectiveDriver'
            )?.value ?? ''
        );

        query.set('service_date', serviceDate);

        if (relationshipId) {
            query.set(
                'organization_relationship_id',
                relationshipId
            );
        }

        if (assignmentId) {
            query.set(
                'driver_organization_assignment_id',
                assignmentId
            );
        }

        try {
            const body = await realDriverApi(
                driverQualitySettingsBaseUrl
                + '/effective?'
                + query.toString()
            );
            const data = getPayload(body) ?? {};
            const target = document.getElementById(
                'drayviaDriverQualityEffectiveResult'
            );
            const reasonLabels = {
                resolved: 'Nastavení bylo nalezeno.',
                unconfigured: 'Pro tento rozsah není nastaven žádný profil.',
                profile_unavailable: 'Vazba odkazuje na nedostupný profil.',
                version_unavailable: 'Profil pro dané datum nemá účinnou verzi.',
            };

            driverQualitySettingsState.effective =
                data;

            if (target) {
                const scope =
                    driverQualityScopeLabels[
                        data.scope_type
                    ] ?? 'Bez vazby';
                const profile = data.profile
                    ? data.profile.code
                        + ' · '
                        + data.profile.name
                    : 'Žádný';
                const version = data.version
                    ? 'v'
                        + String(
                            data.version.version_number
                        )
                        + ' · '
                        + driverQualityFormulaText(
                            data.version
                                .calculation_method,
                            Array.isArray(
                                data.version
                                    .numerator_sources
                            )
                                ? data.version
                                    .numerator_sources
                                : []
                        )
                    : 'Bez účinné verze';

                target.textContent =
                    (reasonLabels[data.reason]
                        ?? data.reason)
                    + ' Úroveň: '
                    + scope
                    + '. Profil: '
                    + profile
                    + '. Verze: '
                    + version
                    + '.';
            }
        }
        catch (error) {
            const target = document.getElementById(
                'drayviaDriverQualityEffectiveResult'
            );

            if (target) {
                target.textContent =
                    'Náhled se nepodařilo načíst: '
                    + (error?.message || 'neznámá chyba');
            }
        }
    }

    const activateDriverStatisticsTab = (
        tab
    ) => {
        const overview = document.getElementById(
            'drayviaDriverStatisticsHost'
        );
        const settings = document.getElementById(
            'drayviaDriverQualitySettingsHost'
        );
        const settingsActive =
            tab === 'quality-settings';

        if (overview) {
            overview.hidden = settingsActive;
        }

        if (settings) {
            settings.hidden = !settingsActive;
        }

        document.querySelectorAll(
            '[data-driver-statistics-tab]'
        ).forEach(
            (button) => {
                const active =
                    button.dataset
                        .driverStatisticsTab === tab;

                button.classList.toggle(
                    'primary',
                    active
                );
                button.setAttribute(
                    'aria-pressed',
                    active ? 'true' : 'false'
                );
            }
        );

        if (settingsActive) {
            loadDriverQualitySettings();
        }
        else {
            loadDriverStatistics();
        }
    };

    const bindDriverStatisticsTabs = () => {
        document.querySelectorAll(
            '[data-driver-statistics-tab]'
        ).forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        activateDriverStatisticsTab(
                            String(
                                button.dataset
                                    .driverStatisticsTab
                                ?? 'overview'
                            )
                        );
                    }
                );
            }
        );

        activateDriverStatisticsTab('overview');
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
            'Provozn\u00ed statistiky \u0159idi\u010d\u016f podle skute\u010dn\u00fdch tras DRAYVIA.'
        )}

        <div
            class="drayvia-preview-actions"
            style="margin:0 0 14px;"
            aria-label="Sekce statistik"
        >
            <button
                class="drayvia-preview-action primary"
                type="button"
                data-driver-statistics-tab="overview"
            >
                PŘEHLED
            </button>
            <button
                class="drayvia-preview-action"
                type="button"
                data-driver-statistics-tab="quality-settings"
            >
                NASTAVENÍ DÍLČÍ KVALITY
            </button>
        </div>

        <div
            id="drayviaDriverStatisticsHost"
            class="drayvia-preview-panel drayvia-driver-statistics"
        ></div>

        <div
            id="drayviaDriverQualitySettingsHost"
            class="drayvia-preview-panel drayvia-driver-quality-settings"
            hidden
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
                ${header(
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
                        label[for="price-list-tab-drivers"],
                    #price-list-tab-external-carriers:checked
                        ~ .drayvia-price-list-tabs
                        label[for="price-list-tab-external-carriers"] {
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
                        .drayvia-price-list-panel-drivers,
                    #price-list-tab-external-carriers:checked
                        ~ .drayvia-price-list-panels
                        .drayvia-price-list-panel-external-carriers {
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
                    /*
                     * S024-02A PRICE-LIST ADMINISTRATION LAYOUT
                     *
                     * Existing price lists are the primary view. Creation is
                     * intentionally kept in a separate secondary workflow.
                     */
                    .drayvia-price-admin {
                        display: grid;
                        gap: 16px;
                    }

                    .drayvia-price-admin-header,
                    .drayvia-price-admin-toolbar,
                    .drayvia-price-admin-actions {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: space-between;
                        gap: 10px;
                    }

                    .drayvia-price-admin-header h4,
                    .drayvia-price-admin-header p {
                        margin: 0;
                    }

                    .drayvia-price-admin-heading {
                        display: grid;
                        gap: 5px;
                    }

                    .drayvia-price-admin-primary,
                    .drayvia-price-admin-secondary,
                    .drayvia-price-admin-filter {
                        border: 1px solid #cbd5e1;
                        border-radius: 9px;
                        padding: 9px 13px;
                        font-weight: 800;
                        cursor: pointer;
                    }

                    .drayvia-price-admin-primary,
                    .drayvia-price-admin-filter.is-active {
                        border-color: #0f172a;
                        color: #fff;
                        background: #0f172a;
                    }

                    .drayvia-price-admin-secondary,
                    .drayvia-price-admin-filter {
                        color: #1f2937;
                        background: #fff;
                    }

                    .drayvia-price-admin-summary {
                        display: grid;
                        grid-template-columns:
                            repeat(auto-fit, minmax(150px, 1fr));
                        gap: 10px;
                    }

                    .drayvia-price-admin-stat {
                        display: grid;
                        gap: 4px;
                        border: 1px solid #e2e8f0;
                        border-radius: 10px;
                        padding: 12px;
                        background: #f8fafc;
                    }

                    .drayvia-price-admin-stat span {
                        color: #64748b;
                        font-size: 12px;
                        font-weight: 700;
                    }

                    .drayvia-price-admin-stat strong {
                        color: #0f172a;
                        font-size: 21px;
                    }

                    .drayvia-price-admin-table-wrap {
                        overflow-x: auto;
                        border: 1px solid #e2e8f0;
                        border-radius: 12px;
                    }

                    .drayvia-price-admin-table {
                        width: 100%;
                        min-width: 760px;
                        border-collapse: collapse;
                    }

                    .drayvia-price-admin-table th,
                    .drayvia-price-admin-table td {
                        padding: 12px;
                        text-align: left;
                        border-bottom: 1px solid #e5e7eb;
                        vertical-align: middle;
                    }

                    .drayvia-price-admin-table th {
                        color: #475569;
                        background: #f8fafc;
                        font-size: 12px;
                    }

                    .drayvia-price-admin-table tbody tr:last-child td {
                        border-bottom: 0;
                    }

                    .drayvia-price-admin-detail {
                        display: grid;
                        gap: 14px;
                        border: 1px solid #cbd5e1;
                        border-radius: 12px;
                        padding: 16px;
                        background: #fff;
                    }

                    .drayvia-price-admin-detail-header {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: flex-start;
                        justify-content: space-between;
                        gap: 12px;
                    }

                    .drayvia-price-admin-detail-header h4,
                    .drayvia-price-admin-detail-header p {
                        margin: 0;
                    }

                    .drayvia-price-admin-detail-heading {
                        display: grid;
                        gap: 6px;
                    }

                    .drayvia-price-admin-detail-actions {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                    }

                    .drayvia-price-admin-editor {
                        display: grid;
                        gap: 16px;
                        border: 0;
                        padding: 0;
                    }

                    .drayvia-price-admin-editor textarea {
                        width: 100%;
                        box-sizing: border-box;
                        border: 1px solid #cfd5dd;
                        border-radius: 8px;
                        padding: 10px 11px;
                        background: #fff;
                    }

                    .drayvia-price-admin-message {
                        border-left: 4px solid #2563eb;
                        padding: 10px 12px;
                        background: #eff6ff;
                    }

                    .drayvia-price-admin-message[data-state="error"] {
                        border-left-color: #dc2626;
                        color: #991b1b;
                        background: #fef2f2;
                    }
                    .drayvia-price-admin-empty {
                        padding: 22px;
                        color: #64748b;
                        text-align: center;
                    }

                    .drayvia-price-create-card[hidden],
                    .drayvia-price-admin[hidden],
                    .drayvia-price-admin-detail[hidden] {
                        display: none;
                    }
                    .drayvia-conditional-rules {
                        margin-top: 20px;
                        border: 1px solid #d7dce3;
                        border-radius: 12px;
                        padding: 16px;
                        background: #f8fafc;
                    }

                    .drayvia-conditional-toolbar,
                    .drayvia-conditional-rule-header,
                    .drayvia-conditional-band-header {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: space-between;
                        gap: 10px;
                    }

                    .drayvia-conditional-toolbar select,
                    .drayvia-conditional-rule input,
                    .drayvia-conditional-rule select,
                    .drayvia-conditional-rule textarea {
                        width: 100%;
                        box-sizing: border-box;
                        border: 1px solid #cfd5dd;
                        border-radius: 8px;
                        padding: 9px 10px;
                        background: #fff;
                    }

                    .drayvia-conditional-toolbar label {
                        flex: 1 1 260px;
                        display: grid;
                        gap: 6px;
                        font-size: 13px;
                        font-weight: 700;
                    }

                    .drayvia-conditional-rule {
                        margin-top: 14px;
                        border: 1px solid #cfd5dd;
                        border-radius: 12px;
                        padding: 14px;
                        background: #fff;
                    }

                    .drayvia-conditional-rule-grid,
                    .drayvia-conditional-band-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
                        gap: 12px;
                        margin-top: 12px;
                    }

                    .drayvia-conditional-rule-field {
                        display: grid;
                        align-content: start;
                        gap: 6px;
                    }

                    .drayvia-conditional-rule-field > span,
                    .drayvia-conditional-rule-field > label {
                        font-size: 13px;
                        font-weight: 700;
                    }

                    .drayvia-conditional-rule-field-wide {
                        grid-column: 1 / -1;
                    }

                    .drayvia-conditional-source-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
                        gap: 8px;
                    }

                    .drayvia-conditional-source-grid label,
                    .drayvia-conditional-band-check {
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        font-size: 13px;
                    }

                    .drayvia-conditional-source-grid input,
                    .drayvia-conditional-band-check input {
                        width: auto;
                        margin: 0;
                    }

                    .drayvia-conditional-bands {
                        margin-top: 14px;
                        border-top: 1px solid #e5e7eb;
                        padding-top: 14px;
                    }

                    .drayvia-conditional-band {
                        margin-top: 10px;
                        border: 1px solid #e5e7eb;
                        border-radius: 10px;
                        padding: 12px;
                    }

                    .drayvia-conditional-danger {
                        border-color: #fecaca;
                        color: #991b1b;
                        background: #fff;
                    }

                    .drayvia-conditional-empty {
                        margin: 14px 0 0;
                        color: #64748b;
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
                                    Fakturační ceníky, ceníky řidičů a ceníky
                                    externích dopravců jsou vedené jako tři
                                    samostatné finanční vztahy.
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

                                    <input
                                        class="drayvia-price-list-tab-input"
                                        id="price-list-tab-external-carriers"
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
                                        <label
                                            class="drayvia-price-list-tab"
                                            for="price-list-tab-external-carriers"
                                        >
                                            Ceníky externích dopravců
                                        </label>
                                    </nav>

                                    <div class="drayvia-price-list-panels">
                                        <section
                                            class="drayvia-price-list-panel drayvia-price-list-panel-billing"
                                            data-price-list-panel="billing"
                                            data-provider-managed-price-list-endpoint="/api/v1/customers/{relationship}/price-lists"
                                        >
                                                                                        <div
                                                class="drayvia-finance-card drayvia-price-admin"
                                                data-billing-price-list-admin
                                                data-unified-price-list-domain="billing"
                                            >
                                                <div class="drayvia-price-admin-header">
                                                    <div class="drayvia-price-admin-heading">
                                                        <h4>Spr&#225;va faktura&#269;n&#237;ch cen&#237;k&#367;</h4>
                                                        <p>
                                                            Aktu&#225;ln&#237;, rozpracovan&#233; a historick&#233;
                                                            cen&#237;ky na jednom m&#237;st&#283;.
                                                        </p>
                                                    </div>

                                                    <button
                                                        class="drayvia-price-admin-primary"
                                                        type="button"
                                                        data-billing-price-list-create-open
                                                    >
                                                        Nov&#253; faktura&#269;n&#237; cen&#237;k
                                                    </button>
                                                </div>

                                                <div class="drayvia-price-admin-summary">
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>V&#353;echny cen&#237;ky</span>
                                                        <strong data-billing-price-list-count="all">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Aktu&#225;ln&#237;</span>
                                                        <strong data-billing-price-list-count="current">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Koncepty</span>
                                                        <strong data-billing-price-list-count="draft">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Historie</span>
                                                        <strong data-billing-price-list-count="history">0</strong>
                                                    </div>
                                                </div>

                                                <div class="drayvia-price-admin-toolbar">
                                                    <div class="drayvia-price-admin-actions">
                                                        <button
                                                            class="drayvia-price-admin-filter is-active"
                                                            type="button"
                                                            data-billing-price-list-filter="all"
                                                            data-unified-price-list-filter="all"
                                                        >
                                                            V&#353;e
                                                        </button>
                                                        <button
                                                            class="drayvia-price-admin-filter"
                                                            type="button"
                                                            data-billing-price-list-filter="current"
                                                            data-unified-price-list-filter="current"
                                                        >
                                                            Aktu&#225;ln&#237;
                                                        </button>
                                                        <button
                                                            class="drayvia-price-admin-filter"
                                                            type="button"
                                                            data-billing-price-list-filter="draft"
                                                            data-unified-price-list-filter="draft"
                                                        >
                                                            Koncepty
                                                        </button>
                                                        <button
                                                            class="drayvia-price-admin-filter"
                                                            type="button"
                                                            data-billing-price-list-filter="history"
                                                            data-unified-price-list-filter="history"
                                                        >
                                                            Historie
                                                        </button>
                                                    </div>

                                                    <button
                                                        class="drayvia-price-admin-secondary"
                                                        type="button"
                                                        data-billing-price-list-reload
                                                        data-unified-price-list-reload
                                                    >
                                                        Obnovit p&#345;ehled
                                                    </button>
                                                </div>

                                                <div class="drayvia-price-admin-table-wrap">
                                                    <table class="drayvia-price-admin-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Odb&#283;ratel</th>
                                                                <th>Cen&#237;k</th>
                                                                <th>Platnost</th>
                                                                <th>Stav</th>
                                                                <th>Verze</th>
                                                                <th>Akce</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody data-billing-price-list-admin-list>
                                                            <tr>
                                                                <td
                                                                    class="drayvia-price-admin-empty"
                                                                    colspan="6"
                                                                >
                                                                    Na&#269;&#237;t&#225;m faktura&#269;n&#237; cen&#237;ky&#8230;
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <section
                                                    class="drayvia-price-admin-detail"
                                                    data-billing-price-list-admin-detail
                                                    hidden
                                                ></section>
                                            </div>

                                            <div
                                                class="drayvia-finance-card drayvia-price-create-card"
                                                data-billing-price-list-create-card
                                                hidden
                                            >
                                                <div class="drayvia-price-admin-toolbar">
                                                    <button
                                                        class="drayvia-price-admin-secondary"
                                                        type="button"
                                                        data-billing-price-list-create-close
                                                    >
                                                        Zp&#283;t na p&#345;ehled
                                                    </button>
                                                </div>
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
                                                                <td>Odm&#237;tnuto z&#225;kazn&#237;kem</td>
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

                                                <section
                                                    class="drayvia-conditional-rules"
                                                    data-conditional-rule-root
                                                >
                                                    <div class="drayvia-conditional-rule-header">
                                                        <div>
                                                            <h5>Faktura&#269;n&#237; p&#345;&#237;platky</h5>
                                                            <p>
                                                                Nastavte libovoln&#253; po&#269;et podm&#237;n&#283;n&#253;ch
                                                                p&#345;&#237;platk&#367;, jejich vzorec, prahy, cenu
                                                                a vyhodnocen&#237; za trasu nebo za m&#283;s&#237;c.
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div class="drayvia-conditional-toolbar">
                                                        <label>
                                                            <span>&#352;ablona nov&#233;ho p&#345;&#237;platku</span>
                                                            <select data-conditional-rule-preset>
                                                                <option value="quality">
                                                                    Kvalita rozvozu
                                                                </option>
                                                                <option value="redirected">
                                                                    Pod&#237;l p&#345;esm&#283;rovan&#253;ch z&#225;silek
                                                                </option>
                                                                <option value="custom">
                                                                    Vlastn&#237; pravidlo
                                                                </option>
                                                            </select>
                                                        </label>

                                                        <button
                                                            type="button"
                                                            data-conditional-rule-add
                                                        >
                                                            P&#345;idat p&#345;&#237;platek
                                                        </button>
                                                    </div>

                                                    <p
                                                        class="drayvia-conditional-empty"
                                                        data-conditional-rule-empty
                                                        hidden
                                                    >
                                                        Nejsou nastaveny &#382;&#225;dn&#233; podm&#237;n&#283;n&#233; p&#345;&#237;platky.
                                                    </p>

                                                    <div data-conditional-rule-list></div>
                                                </section>

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
                                            <div
                                                class="drayvia-finance-card drayvia-price-admin"
                                                data-driver-price-list-root
                                                data-unified-price-list-domain="driver"
                                                data-driver-price-list-index-endpoint="/api/v1/driver-price-lists"
                                            >
                                                <div class="drayvia-price-admin-header">
                                                    <div class="drayvia-price-admin-heading">
                                                        <h4>Správa ceníků řidičů</h4>
                                                        <p>
                                                            Aktuální, rozpracované a historické
                                                            ceníky řidičů na jednom místě.
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="drayvia-price-admin-summary">
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Všechny ceníky</span>
                                                        <strong data-unified-price-list-count="all">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Aktuální</span>
                                                        <strong data-unified-price-list-count="current">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Koncepty</span>
                                                        <strong data-unified-price-list-count="draft">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Historie</span>
                                                        <strong data-unified-price-list-count="history">0</strong>
                                                    </div>
                                                </div>

                                                <div class="drayvia-price-admin-toolbar">
                                                    <div class="drayvia-price-admin-actions">
                                                        <button class="drayvia-price-admin-filter is-active" type="button" data-unified-price-list-filter="all">Vše</button>
                                                        <button class="drayvia-price-admin-filter" type="button" data-unified-price-list-filter="current">Aktuální</button>
                                                        <button class="drayvia-price-admin-filter" type="button" data-unified-price-list-filter="draft">Koncepty</button>
                                                        <button class="drayvia-price-admin-filter" type="button" data-unified-price-list-filter="history">Historie</button>
                                                    </div>
                                                    <button
                                                        class="drayvia-price-admin-secondary"
                                                        type="button"
                                                        data-driver-price-list-reload
                                                        data-unified-price-list-reload
                                                    >
                                                        Obnovit přehled
                                                    </button>
                                                </div>

                                                <h4 style="margin-top: 24px;">Nový ceník řidiče</h4>
                                                <p>
                                                    Nastavte sazby řidiče přímo v TMS.
                                                    Stávající bezpečný postup vytvoření,
                                                    schválení a aktivace zůstává zachovaný.
                                                </p>

                                                <div class="drayvia-finance-grid">
                                                    <div class="drayvia-finance-field">
                                                        <label for="driver-price-list-assignment">
                                                            Řidič
                                                        </label>
                                                        <select
                                                            id="driver-price-list-assignment"
                                                            data-driver-price-list-assignment
                                                            required
                                                            disabled
                                                        >
                                                            <option value="">
                                                                Načítám řidiče…
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <div class="drayvia-finance-field">
                                                        <label for="driver-price-list-name">
                                                            Název ceníku
                                                        </label>
                                                        <input
                                                            id="driver-price-list-name"
                                                            data-driver-price-list-name
                                                            type="text"
                                                            maxlength="150"
                                                            value="Ceník řidiče"
                                                            required
                                                        >
                                                    </div>

                                                    <div class="drayvia-finance-field">
                                                        <label for="driver-price-list-valid-from">
                                                            Platnost od
                                                        </label>
                                                        <input
                                                            id="driver-price-list-valid-from"
                                                            data-driver-price-list-valid-from
                                                            type="date"
                                                            required
                                                        >
                                                    </div>

                                                    <div class="drayvia-finance-field">
                                                        <label for="driver-price-list-valid-until">
                                                            Platnost do
                                                        </label>
                                                        <input
                                                            id="driver-price-list-valid-until"
                                                            data-driver-price-list-valid-until
                                                            type="date"
                                                        >
                                                    </div>

                                                    <div class="drayvia-finance-field">
                                                        <label>Měna</label>
                                                        <strong>CZK</strong>
                                                    </div>
                                                </div>

                                                <div style="overflow-x: auto; margin-top: 18px;">
                                                    <table class="drayvia-price-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Položka</th>
                                                                <th>Jednotka</th>
                                                                <th>Sazba Kč</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>Doručená zásilka</td>
                                                                <td>zásilka</td>
                                                                <td>
                                                                    <input
                                                                        data-driver-price-list-rate="delivered_parcels"
                                                                        type="number"
                                                                        min="0"
                                                                        step="0.0001"
                                                                        required
                                                                    >
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Přesměrovaná zásilka</td>
                                                                <td>zásilka</td>
                                                                <td>
                                                                    <input
                                                                        data-driver-price-list-rate="redirected_parcels"
                                                                        type="number"
                                                                        min="0"
                                                                        step="0.0001"
                                                                        required
                                                                    >
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Nedoručená zásilka</td>
                                                                <td>zásilka</td>
                                                                <td>
                                                                    <input
                                                                        data-driver-price-list-rate="undelivered_parcels"
                                                                        type="number"
                                                                        min="0"
                                                                        step="0.0001"
                                                                        value="0"
                                                                        required
                                                                    >
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Skutečný kilometr</td>
                                                                <td>km</td>
                                                                <td>
                                                                    <input
                                                                        data-driver-price-list-rate="actual_km"
                                                                        type="number"
                                                                        min="0"
                                                                        step="0.0001"
                                                                        required
                                                                    >
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div style="margin-top: 18px;">
                                                    <button
                                                        type="button"
                                                        data-driver-price-list-save
                                                    >
                                                        Uložit a aktivovat ceník
                                                    </button>

                                                    <p
                                                        data-driver-price-list-message
                                                        class="drayvia-finance-note"
                                                        style="margin-top: 12px;"
                                                        hidden
                                                    ></p>
                                                </div>

                                                <div
                                                    class="drayvia-finance-card"
                                                    style="margin-top: 22px;"
                                                >
                                                    <h4>Existující ceníky řidičů</h4>
                                                    <div style="overflow-x: auto;">
                                                        <table class="drayvia-customer-table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Řidič</th>
                                                                    <th>Ceník</th>
                                                                    <th>Stav</th>
                                                                    <th>Verze</th>
                                                                    <th>Měna</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody data-driver-price-list-list>
                                                                <tr>
                                                                    <td colspan="5">
                                                                        Načítám ceníky…
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>

                                        <section
                                            class="drayvia-price-list-panel drayvia-price-list-panel-external-carriers"
                                            data-price-list-panel="external-carriers"
                                        >
                                            <div
                                                class="drayvia-finance-card drayvia-price-admin"
                                                data-external-carrier-price-list-root
                                                data-unified-price-list-domain="external-carrier"
                                                data-external-carrier-index-endpoint="/api/v1/external-carriers"
                                                data-external-carrier-store-endpoint="/api/v1/external-carriers/{relationship}/price-lists"
                                            >
                                                <div class="drayvia-price-admin-header">
                                                    <div class="drayvia-price-admin-heading">
                                                        <h4>Správa ceníků externích dopravců</h4>
                                                        <p>
                                                            Aktuální, rozpracované a historické
                                                            ceníky dodavatelů dopravy na jednom místě.
                                                        </p>
                                                    </div>

                                                    <button
                                                        class="drayvia-price-admin-primary"
                                                        type="button"
                                                        data-external-carrier-price-list-create-open
                                                    >
                                                        Nový ceník externího dopravce
                                                    </button>
                                                </div>

                                                <div class="drayvia-price-admin-summary">
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Všechny ceníky</span>
                                                        <strong data-unified-price-list-count="all">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Aktuální</span>
                                                        <strong data-unified-price-list-count="current">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Koncepty</span>
                                                        <strong data-unified-price-list-count="draft">0</strong>
                                                    </div>
                                                    <div class="drayvia-price-admin-stat">
                                                        <span>Historie</span>
                                                        <strong data-unified-price-list-count="history">0</strong>
                                                    </div>
                                                </div>

                                                <div class="drayvia-price-admin-toolbar">
                                                    <div class="drayvia-price-admin-actions">
                                                        <button class="drayvia-price-admin-filter is-active" type="button" data-unified-price-list-filter="all">Vše</button>
                                                        <button class="drayvia-price-admin-filter" type="button" data-unified-price-list-filter="current">Aktuální</button>
                                                        <button class="drayvia-price-admin-filter" type="button" data-unified-price-list-filter="draft">Koncepty</button>
                                                        <button class="drayvia-price-admin-filter" type="button" data-unified-price-list-filter="history">Historie</button>
                                                    </div>

                                                    <button
                                                        class="drayvia-price-admin-secondary"
                                                        type="button"
                                                        data-external-carrier-price-list-reload
                                                        data-unified-price-list-reload
                                                    >
                                                        Obnovit přehled
                                                    </button>
                                                </div>

                                                <div class="drayvia-price-admin-table-wrap">
                                                    <table class="drayvia-price-admin-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Externí dopravce</th>
                                                                <th>Ceník</th>
                                                                <th>Platnost</th>
                                                                <th>Stav</th>
                                                                <th>Verze</th>
                                                                <th>Akce</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody data-external-carrier-price-list-list>
                                                            <tr>
                                                                <td class="drayvia-price-admin-empty" colspan="6">
                                                                    Načítání bude připojeno v navazující UI jednotce.
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <section
                                                    class="drayvia-price-admin-detail"
                                                    data-external-carrier-price-list-detail
                                                    hidden
                                                ></section>
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

    // S028-01A DEPOT IMPORT READ-ONLY PREVIEW
    const depotImportState = {
        file: null,
        inspection: null,
        preview: null,
        draft: null,
    };

    const depotImportApi = async (
        path,
        {method = 'GET', formData = null, json = null} = {}
    ) => {
        const depotImportToken = sessionStorage.getItem('tms_mvp_token') || '';
        const headers = {
            Accept: 'application/json',
            'X-Organization-ID': '1',
        };

        if (depotImportToken) {
            headers.Authorization = `Bearer ${depotImportToken}`;
        }

        if (json !== null) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(path, {
            method,
            headers,
            body: formData ?? (json === null ? null : JSON.stringify(json)),
        });
        let body = null;

        try {
            body = await response.json();
        } catch {
            body = null;
        }

        if (!response.ok) {
            throw new Error(
                readError(
                    body,
                    `Operace importu skončila chybou HTTP ${response.status}.`
                )
            );
        }

        return getPayload(body);
    };

    const depotImportSetStatus = (message, type = '') => {
        const status = document.getElementById(
            'drayviaDepotImportStatus'
        );

        if (!status) {
            return;
        }

        status.textContent = message;
        status.className =
            `drayvia-depot-import-status ${type}`.trim();
    };

    const depotImportCard = (label, value, note = '') => {
        const card = document.createElement('div');
        card.className = 'drayvia-preview-card';

        const cardLabel = document.createElement('div');
        cardLabel.className = 'drayvia-preview-card-label';
        cardLabel.textContent = label;

        const cardValue = document.createElement('div');
        cardValue.className = 'drayvia-preview-card-value';
        cardValue.textContent = value;

        card.append(cardLabel, cardValue);

        if (note) {
            const cardNote = document.createElement('div');
            cardNote.className = 'drayvia-preview-card-note';
            cardNote.textContent = note;
            card.appendChild(cardNote);
        }

        return card;
    };

    const depotImportFormatDate = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(
            String(value ?? '')
        );

        return match
            ? `${match[3]}.${match[2]}.${match[1]}`
            : (value || '—');
    };

    const depotImportFormatDateTime = (value) => {
        const parsed = new Date(value);

        if (Number.isNaN(parsed.getTime())) {
            return value || '—';
        }

        return new Intl.DateTimeFormat('cs-CZ', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(parsed);
    };

    // S028-04A DEPOT IMPORT AUDITED DRAFT ADMINISTRATION
    const depotImportStatusLabel = (status) => ({
        draft: 'Koncept – čeká na přiřazení',
        ready: 'Připraveno – řidiči přiřazeni',
        imported: 'Importováno',
        cancelled: 'Stornováno',
        no_run: 'Neodjeto',
    })[status] || status;

    const depotImportUniqueDrivers = (drivers) => {
        const unique = new Map();

        (drivers || []).forEach((driver) => {
            if (!unique.has(driver.driver_id)) {
                unique.set(driver.driver_id, driver);
            }
        });

        return [...unique.values()];
    };

    const depotImportAppendDriverOptions = (
        select,
        drivers,
        selectedDriverId = null
    ) => {
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = 'Vyberte oprávněného řidiče';
        select.appendChild(empty);

        depotImportUniqueDrivers(drivers).forEach((driver) => {
            const option = document.createElement('option');
            option.value = String(driver.driver_id);
            option.textContent = driver.external_driver_id
                ? `${driver.driver_name} · ${driver.external_driver_id}`
                : driver.driver_name;
            option.selected = Number(selectedDriverId) === driver.driver_id;
            select.appendChild(option);
        });
    };

    const depotImportRenderDraftList = (drafts) => {
        const host = document.getElementById(
            'drayviaDepotImportDraftListHost'
        );

        if (!host) {
            return;
        }

        host.replaceChildren();

        const section = document.createElement('section');
        section.className = 'drayvia-depot-draft-section';

        const heading = document.createElement('div');
        heading.className = 'drayvia-depot-draft-section-head';

        const copy = document.createElement('div');
        const title = document.createElement('h2');
        title.textContent = 'Importy z depa';
        const description = document.createElement('p');
        description.textContent =
            'Koncept lze znovu otevřít; dokončený import zůstává neměnným zdrojem depa.';
        copy.append(title, description);

        const refresh = document.createElement('button');
        refresh.type = 'button';
        refresh.className = 'drayvia-preview-action';
        refresh.textContent = 'Obnovit seznam';
        refresh.addEventListener('click', () => depotImportLoadDrafts());
        heading.append(copy, refresh);
        section.appendChild(heading);

        const list = document.createElement('div');
        list.className = 'drayvia-depot-draft-list';

        if (!Array.isArray(drafts) || drafts.length === 0) {
            const empty = document.createElement('p');
            empty.textContent = 'Zatím není uložen žádný koncept importu.';
            list.appendChild(empty);
        } else {
            drafts.forEach((draft) => {
                const item = document.createElement('div');
                item.className = 'drayvia-depot-draft-list-item';

                const details = document.createElement('div');
                const name = document.createElement('strong');
                name.textContent =
                    `${draft.source.original_filename} · ${draft.confirmed_alias}`;
                const meta = document.createElement('p');
                meta.textContent =
                    `${depotImportFormatDate(draft.period.from)}–`
                    + `${depotImportFormatDate(draft.period.until)} · `
                    + `${depotImportStatusLabel(draft.status)} · `
                    + `${draft.counts.unassigned_ready} nepřiřazených záznamů`;
                details.append(name, meta);

                const open = document.createElement('button');
                open.type = 'button';
                open.className = 'drayvia-preview-action';
                open.textContent = draft.status === 'imported'
                    ? 'Otevřít import'
                    : (draft.status === 'cancelled'
                        ? 'Otevřít stornovaný import'
                        : 'Otevřít koncept');
                open.addEventListener(
                    'click',
                    () => depotImportLoadDraft(draft.public_id)
                );
                item.append(details, open);
                list.appendChild(item);
            });
        }

        section.appendChild(list);
        host.appendChild(section);
    };

    const depotImportLoadDrafts = async () => {
        try {
            const drafts = await depotImportApi(
                '/api/v1/daily-reports/depot-imports/drafts'
            );
            depotImportRenderDraftList(drafts);
        } catch (error) {
            const host = document.getElementById(
                'drayviaDepotImportDraftListHost'
            );

            if (host) {
                const notice = document.createElement('div');
                notice.className = 'drayvia-depot-import-status error';
                notice.textContent =
                    `Rozpracované importy nelze načíst: ${error.message}`;
                host.replaceChildren(notice);
            }
        }
    };

    const depotImportLoadDraft = async (publicId) => {
        depotImportSetStatus('Načítám ověřený koncept importu…');

        try {
            const draft = await depotImportApi(
                `/api/v1/daily-reports/depot-imports/drafts/${publicId}`
            );
            depotImportState.draft = draft;
            depotImportRenderDraft(draft);
            depotImportSetStatus(
                'Koncept byl načten a jeho chráněné hodnoty byly ověřeny.',
                'success'
            );
        } catch (error) {
            depotImportSetStatus(error.message, 'error');
        }
    };

    const depotImportUpdateDraft = async (
        path,
        payload,
        successMessage
    ) => {
        const currentPublicId = depotImportState.draft?.public_id;

        try {
            const draft = await depotImportApi(path, {
                method: 'PATCH',
                json: payload,
            });
            depotImportState.draft = draft;
            depotImportRenderDraft(draft);
            depotImportSetStatus(successMessage, 'success');
            await depotImportLoadDrafts();
        } catch (error) {
            depotImportSetStatus(error.message, 'error');

            if (currentPublicId) {
                await depotImportLoadDraft(currentPublicId);
            }
        }
    };

    const depotImportConfirmFinalization = (batch) => new Promise((resolve) => {
        document
            .querySelector('.drayvia-depot-modal-backdrop')
            ?.remove();

        const backdrop = document.createElement('div');
        backdrop.className = 'drayvia-depot-modal-backdrop';
        const modal = document.createElement('form');
        modal.className = 'drayvia-depot-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'drayviaDepotFinalizeTitle');

        const title = document.createElement('h2');
        title.id = 'drayviaDepotFinalizeTitle';
        title.textContent = 'Dokončit import zápisů z depa?';
        const description = document.createElement('p');
        description.textContent =
            'Před uzamčením zkontrolujte rozsah importu a přiřazení zdrojových jmen.';
        const sourceNames = new Set(
            batch.rows.map((row) => row.source_driver_name)
        );
        const assignedSourceNames = new Set(
            batch.rows
                .filter((row) => row.assigned_driver?.id)
                .map((row) => row.source_driver_name)
        );
        const totals = batch.source_totals || {};
        const summary = document.createElement('div');
        summary.className = 'drayvia-depot-finalize-summary';

        [
            ['Soubor', batch.source.original_filename],
            ['Alias dopravce', batch.confirmed_alias],
            ['Záznamy depa', String(batch.rows.length)],
            [
                'Přiřazená zdrojová jména',
                `${assignedSourceNames.size} z ${sourceNames.size}`,
            ],
            [
                'Kontrolní součty',
                `naloženo ${totals.loaded_parcels ?? 0}, doručeno `
                + `${totals.delivered_parcels ?? 0}, VM `
                + `${totals.redirected_parcels ?? 0}`,
            ],
        ].forEach(([label, value]) => {
            const row = document.createElement('div');
            const name = document.createElement('span');
            name.textContent = label;
            const content = document.createElement('strong');
            content.textContent = value;
            row.append(name, content);
            summary.appendChild(row);
        });

        const warning = document.createElement('div');
        warning.className = 'drayvia-depot-finalize-warning';
        warning.textContent =
            'Potvrzením se hodnoty depa a přiřazení jmen uzamknou jako '
            + 'samostatný zdroj. Nevzniknou trasy, denní výkazy ani párování.';
        const actions = document.createElement('div');
        actions.className = 'drayvia-depot-modal-actions';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'drayvia-preview-action';
        cancel.textContent = 'Zpět ke kontrole';
        const confirm = document.createElement('button');
        confirm.type = 'submit';
        confirm.className = 'drayvia-preview-action primary';
        confirm.textContent = 'Potvrdit a dokončit import';
        actions.append(cancel, confirm);
        modal.append(title, description, summary, warning, actions);
        backdrop.appendChild(modal);

        let settled = false;
        const close = (confirmed) => {
            if (settled) {
                return;
            }

            settled = true;
            backdrop.remove();
            resolve(confirmed);
        };

        cancel.addEventListener('click', () => close(false));
        modal.addEventListener('submit', (event) => {
            event.preventDefault();
            close(true);
        });
        document.body.appendChild(backdrop);
        confirm.focus();
    });

    const depotImportConfirmCancellation = (batch) => new Promise((resolve) => {
        document
            .querySelector('.drayvia-depot-modal-backdrop')
            ?.remove();

        const backdrop = document.createElement('div');
        backdrop.className = 'drayvia-depot-modal-backdrop';
        const modal = document.createElement('form');
        modal.className = 'drayvia-depot-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'drayviaDepotCancelTitle');

        const title = document.createElement('h2');
        title.id = 'drayviaDepotCancelTitle';
        title.textContent = 'Stornovat import zápisů z depa?';
        const description = document.createElement('p');
        description.textContent =
            'Storno vyřadí tuto importní dávku z dalšího zpracování. '
            + 'Původní zápis depa zůstane zachován pro kontrolu a audit.';
        const totals = batch.source_totals || {};
        const summary = document.createElement('div');
        summary.className = 'drayvia-depot-finalize-summary';

        [
            ['Soubor', batch.source.original_filename],
            ['Alias dopravce', batch.confirmed_alias],
            ['Záznamy depa', String(batch.counts.rows)],
            [
                'Kontrolní součty',
                `naloženo ${totals.loaded_parcels ?? 0}, doručeno `
                + `${totals.delivered_parcels ?? 0}, VM `
                + `${totals.redirected_parcels ?? 0}`,
            ],
        ].forEach(([label, value]) => {
            const row = document.createElement('div');
            const name = document.createElement('span');
            name.textContent = label;
            const content = document.createElement('strong');
            content.textContent = value;
            row.append(name, content);
            summary.appendChild(row);
        });

        const warning = document.createElement('div');
        warning.className = 'drayvia-depot-finalize-warning';
        warning.textContent =
            'Storno nic nemaže: zdrojové hodnoty, přiřazení řidičů, kontrolní '
            + 'součty i auditní historie zůstanou beze změny.';
        const reasonLabel = document.createElement('label');
        reasonLabel.className = 'drayvia-depot-cancel-reason';
        reasonLabel.textContent = 'Důvod storna';
        const reason = document.createElement('textarea');
        reason.required = true;
        reason.minLength = 5;
        reason.maxLength = 2000;
        reason.placeholder = 'Uveďte důvod storna importu…';
        reasonLabel.appendChild(reason);

        const actions = document.createElement('div');
        actions.className = 'drayvia-depot-modal-actions';
        const back = document.createElement('button');
        back.type = 'button';
        back.className = 'drayvia-preview-action';
        back.textContent = 'Zpět';
        const confirm = document.createElement('button');
        confirm.type = 'submit';
        confirm.className =
            'drayvia-preview-action drayvia-depot-cancel-action';
        confirm.textContent = 'Potvrdit storno importu';
        actions.append(back, confirm);
        modal.append(
            title,
            description,
            summary,
            warning,
            reasonLabel,
            actions
        );
        backdrop.appendChild(modal);

        let settled = false;
        const close = (value) => {
            if (settled) {
                return;
            }

            settled = true;
            backdrop.remove();
            resolve(value);
        };

        back.addEventListener('click', () => close(null));
        modal.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!modal.reportValidity()) {
                return;
            }

            close(reason.value.trim());
        });
        document.body.appendChild(backdrop);
        reason.focus();
    });

    const depotImportRenderDraft = (batch) => {
        const host = document.getElementById(
            'drayviaDepotImportDraftHost'
        );

        if (!host) {
            return;
        }

        host.replaceChildren();

        const section = document.createElement('section');
        section.className = 'drayvia-depot-draft-section';
        section.dataset.depotImportDraft = batch.public_id;

        const heading = document.createElement('div');
        heading.className = 'drayvia-depot-draft-section-head';
        const copy = document.createElement('div');
        const title = document.createElement('h2');
        title.textContent = 'Správa konceptu importu';
        const meta = document.createElement('p');
        meta.textContent =
            `${batch.source.original_filename} · alias „${batch.confirmed_alias}“ · `
            + `verze ${batch.lock_version}`;
        copy.append(title, meta);
        const state = document.createElement('span');
        state.className = 'drayvia-depot-draft-state';
        state.textContent = depotImportStatusLabel(batch.status);
        heading.append(copy, state);
        section.appendChild(heading);

        if (batch.status === 'imported') {
            const result = document.createElement('div');
            result.className = 'drayvia-depot-import-result success';
            const icon = document.createElement('span');
            icon.className = 'drayvia-depot-import-result-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = '✓';
            const resultTitle = document.createElement('strong');
            resultTitle.textContent = 'Import úspěšně uložen';
            const detail = document.createElement('small');
            detail.textContent =
                `${batch.counts.rows} záznamů depa bylo uzamčeno jako `
                + 'samostatný neměnný zdroj.';
            result.append(icon, resultTitle, detail);
            section.appendChild(result);
        }

        if (batch.status === 'cancelled') {
            const result = document.createElement('div');
            result.className = 'drayvia-depot-import-result cancelled';
            const icon = document.createElement('span');
            icon.className = 'drayvia-depot-import-result-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = '×';
            const resultTitle = document.createElement('strong');
            resultTitle.textContent = 'Import stornován';
            const detail = document.createElement('small');
            detail.textContent = batch.cancellation
                ? `Storno ${depotImportFormatDateTime(batch.cancellation.created_at)}. `
                    + `Důvod: ${batch.cancellation.reason}`
                : 'Zdrojový zápis zůstal zachován pro kontrolu a audit.';
            result.append(icon, resultTitle, detail);
            section.appendChild(result);
        }

        const cards = document.createElement('div');
        cards.className = 'drayvia-preview-grid';
        cards.append(
            depotImportCard(
                'Záznamy připravené k přiřazení',
                String(batch.counts.ready),
                `${batch.counts.unassigned_ready} ještě bez řidiče`
            ),
            depotImportCard(
                'Neodjeté záznamy',
                String(batch.counts.no_run),
                'Nevytvoří nulovou trasu'
            ),
            depotImportCard(
                'Aktivní oprávnění řidiči',
                String(depotImportUniqueDrivers(batch.eligible_drivers).length),
                'Pouze přiřazení hlavnímu dopravci v období'
            ),
            depotImportCard(
                'Integrita hodnot',
                batch.integrity_verified ? 'Ověřena' : 'Neověřena',
                `Kontrolní otisk ${batch.protected_totals_sha256.slice(0, 12)}…`
            )
        );
        section.appendChild(cards);

        const locked = document.createElement('div');
        locked.className = 'drayvia-depot-locked-note';
        locked.textContent =
            'Naloženo, doručeno, výdejní místa, odmítnuto, nerozvezeno, kilometry '
            + 'a ostatní hodnoty jsou přesným, neměnným zápisem depa. V Importech '
            + 'lze pouze hromadně přiřadit zdrojová jména oprávněným řidičům.';
        section.appendChild(locked);

        const totals = batch.source_totals || {};
        const totalsNote = document.createElement('div');
        totalsNote.className = 'drayvia-depot-readonly-note';
        totalsNote.textContent =
            `Neměnné kontrolní součty: naloženo ${totals.loaded_parcels ?? 0}, `
            + `doručeno ${totals.delivered_parcels ?? 0}, výdejní místo `
            + `${totals.redirected_parcels ?? 0}, odmítnuto `
            + `${totals.customer_rejected_parcels ?? 0}, nerozvezeno `
            + `${totals.computed_not_delivered_parcels ?? 0}.`;
        section.appendChild(totalsNote);

        const mappingTitle = document.createElement('h3');
        mappingTitle.textContent = 'Hromadné přiřazení jmen z depa';
        section.appendChild(mappingTitle);

        const groups = new Map();
        batch.rows.forEach((row) => {
            if (!groups.has(row.source_driver_name)) {
                groups.set(row.source_driver_name, []);
            }

            groups.get(row.source_driver_name).push(row);
        });

        const mappingGrid = document.createElement('div');
        mappingGrid.className = 'drayvia-depot-mapping-grid';

        [...groups.entries()].forEach(([sourceName, rows]) => {
            const card = document.createElement('div');
            card.className = 'drayvia-depot-mapping-card';
            const label = document.createElement('strong');
            label.textContent = `${sourceName} · ${rows.length} záznamů`;
            const assignedNames = [
                ...new Set(
                    rows
                        .map((row) => row.assigned_driver?.name)
                        .filter(Boolean)
                ),
            ];
            const current = document.createElement('p');
            current.textContent = assignedNames.length === 0
                ? 'Zatím bez přiřazení.'
                : `Nyní přiřazeno: ${assignedNames.join(', ')}.`;

            card.append(label, current);

            if (assignedNames.length > 0) {
                const success = document.createElement('div');
                success.className = 'drayvia-depot-mapping-success';
                const icon = document.createElement('span');
                icon.className = 'drayvia-depot-mapping-success-icon';
                icon.setAttribute('aria-hidden', 'true');
                icon.textContent = '✓';
                const heading = document.createElement('strong');
                heading.textContent = 'Přiřazení uloženo';
                const detail = document.createElement('small');
                detail.textContent =
                    'Záznamy z depa byly úspěšně přiřazeny zvolenému řidiči.';
                success.append(icon, heading, detail);
                card.appendChild(success);
            }

            if (['draft', 'ready'].includes(batch.status)) {
                const form = document.createElement('form');
                const driverLabel = document.createElement('label');
                driverLabel.textContent = 'Oprávněný řidič';
                const select = document.createElement('select');
                select.required = true;
                const selectedId = rows.every(
                    (row) => row.assigned_driver?.id === rows[0].assigned_driver?.id
                )
                    ? rows[0].assigned_driver?.id
                    : null;
                depotImportAppendDriverOptions(
                    select,
                    batch.eligible_drivers,
                    selectedId
                );
                driverLabel.appendChild(select);

                const reasonLabel = document.createElement('label');
                reasonLabel.textContent = 'Důvod přiřazení';
                const reason = document.createElement('input');
                reason.required = true;
                reason.maxLength = 2000;
                reason.value = 'Kontrola a přiřazení zdrojového jména dispečerem.';
                reasonLabel.appendChild(reason);

                const submit = document.createElement('button');
                submit.type = 'submit';
                submit.className = 'drayvia-preview-action primary';
                submit.textContent = 'Přiřadit záznamy řidiči';
                form.append(driverLabel, reasonLabel, submit);
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    submit.disabled = true;

                    await depotImportUpdateDraft(
                        `/api/v1/daily-reports/depot-imports/drafts/${batch.public_id}/source-driver`,
                        {
                            source_driver_name: sourceName,
                            driver_id: Number(select.value),
                            expected_lock_version: batch.lock_version,
                            reason: reason.value.trim(),
                        },
                        `Všechny záznamy se jménem „${sourceName}“ byly přiřazeny.`
                    );
                });
                card.appendChild(form);
            }

            mappingGrid.appendChild(card);
        });
        section.appendChild(mappingGrid);

        const rowTitle = document.createElement('h3');
        rowTitle.style.marginTop = '20px';
        rowTitle.textContent = 'Přesný obsah zápisu depa';
        section.appendChild(rowTitle);

        const tableWrap = document.createElement('div');
        tableWrap.className = 'drayvia-depot-preview-table-wrap';
        const table = document.createElement('table');
        table.className = 'drayvia-depot-preview-table';
        table.style.minWidth = '1380px';
        const head = document.createElement('thead');
        const headRow = document.createElement('tr');

        [
            'Řádek',
            'Stav',
            'Datum',
            'Trasa',
            'Řidič v depu',
            'Přiřazený řidič',
            'Naloženo',
            'Adresa',
            'VM',
            'Odmítnuto',
            'Nerozvezeno',
            'Skut. km',
            'Plán km',
        ].forEach((text) => {
            const th = document.createElement('th');
            th.textContent = text;
            headRow.appendChild(th);
        });
        head.appendChild(headRow);
        const body = document.createElement('tbody');

        batch.rows.forEach((row) => {
            const tr = document.createElement('tr');
            const values = row.values || {};
            [
                row.source_row,
                depotImportStatusLabel(row.status),
                row.service_date_display || depotImportFormatDate(row.service_date),
                row.route_number || '—',
                row.source_driver_name || '—',
                row.assigned_driver?.name || 'Nepřiřazeno',
                values.loaded_parcels ?? '—',
                values.delivered_parcels ?? '—',
                values.redirected_parcels ?? '—',
                values.customer_rejected_parcels ?? '—',
                values.computed_not_delivered_parcels ?? '—',
                values.actual_km ?? '—',
                values.planned_km ?? '—',
            ].forEach((value) => {
                const td = document.createElement('td');
                td.textContent = String(value);
                tr.appendChild(td);
            });

            body.appendChild(tr);
        });

        table.append(head, body);
        tableWrap.appendChild(table);
        section.appendChild(tableWrap);

        if (batch.rows_truncated) {
            const warning = document.createElement('div');
            warning.className = 'drayvia-depot-import-status error';
            warning.textContent =
                'Dávka obsahuje více řádků, než lze zobrazit. Mapování nelze uzavřít bez úplné kontroly.';
            section.appendChild(warning);
        }

        const boundary = document.createElement('div');

        if (batch.status === 'cancelled') {
            boundary.className = 'drayvia-depot-cancelled-note';
            boundary.textContent =
                'Tento import je auditně stornován a nebude nabídnut k dalšímu '
                + 'zpracování. Zdrojové záznamy depa, přiřazení a kontrolní součty '
                + 'zůstávají zachovány a uzamčeny.';
            section.appendChild(boundary);
        } else if (batch.status === 'imported') {
            boundary.className = 'drayvia-depot-locked-note';
            boundary.textContent =
                'Import je dokončen a zdrojové hodnoty jsou uzamčeny. Pokud byla '
                + 'naimportována nesprávná dávka, lze ji auditně stornovat bez '
                + 'smazání nebo změny záznamů depa.';
            const cancelImport = document.createElement('button');
            cancelImport.type = 'button';
            cancelImport.className =
                'drayvia-preview-action drayvia-depot-cancel-action';
            cancelImport.disabled = !batch.cancellation_enabled;
            cancelImport.textContent = 'Stornovat import';
            cancelImport.addEventListener('click', async () => {
                if (!batch.cancellation_enabled) {
                    return;
                }

                const reason = await depotImportConfirmCancellation(batch);

                if (!reason) {
                    return;
                }

                cancelImport.disabled = true;
                cancelImport.textContent = 'Stornuji import…';

                try {
                    const cancelled = await depotImportApi(
                        `/api/v1/daily-reports/depot-imports/drafts/${batch.public_id}/cancel`,
                        {
                            method: 'POST',
                            json: {
                                expected_lock_version: batch.lock_version,
                                reason,
                            },
                        }
                    );
                    depotImportState.draft = cancelled;
                    depotImportRenderDraft(cancelled);
                    depotImportSetStatus(
                        'Import byl auditně stornován. Zdrojové hodnoty zůstaly zachovány.',
                        'success'
                    );
                    await depotImportLoadDrafts();
                } catch (error) {
                    depotImportSetStatus(error.message, 'error');
                    await depotImportLoadDraft(batch.public_id);
                }
            });
            boundary.append(document.createElement('br'), cancelImport);
            section.appendChild(boundary);
        } else {
            boundary.className = 'drayvia-depot-locked-note';
            boundary.textContent =
                'Dokončením se uložený zápis depa uzamkne jako samostatný zdroj. '
                + 'Nevytvoří se denní výkazy, párování ani rozdělení tras. Tyto kroky '
                + 'budou později samostatně v Trasy → Kontrola zápisů.';
            const finalize = document.createElement('button');
            finalize.type = 'button';
            finalize.className = 'drayvia-preview-action primary';
            finalize.disabled = !batch.finalization_enabled;
            finalize.textContent = batch.finalization_enabled
                ? 'Dokončit import depa'
                : 'Nejprve přiřaďte všechna jména';
            finalize.addEventListener('click', async () => {
                if (!batch.finalization_enabled) {
                    return;
                }

                const confirmed = await depotImportConfirmFinalization(batch);

                if (!confirmed) {
                    return;
                }

                finalize.disabled = true;
                finalize.textContent = 'Dokončuji import…';

                try {
                    const imported = await depotImportApi(
                        `/api/v1/daily-reports/depot-imports/drafts/${batch.public_id}/finalize`,
                        {
                            method: 'POST',
                            json: {
                                expected_lock_version: batch.lock_version,
                                reason: 'Potvrzení importu depa po hromadném přiřazení zdrojových jmen.',
                            },
                        }
                    );
                    depotImportState.draft = imported;
                    depotImportRenderDraft(imported);
                    depotImportSetStatus(
                        'Import depa byl dokončen. Nebyl vytvořen žádný denní výkaz ani párování.',
                        'success'
                    );
                    await depotImportLoadDrafts();
                } catch (error) {
                    depotImportSetStatus(error.message, 'error');
                    await depotImportLoadDraft(batch.public_id);
                }
            });
            boundary.append(document.createElement('br'), finalize);
            section.appendChild(boundary);
        }
        host.appendChild(section);
    };

    const depotImportRenderPreview = (preview) => {
        const host = document.getElementById(
            'drayviaDepotImportPreviewHost'
        );

        if (!host) {
            return;
        }

        depotImportState.preview = preview;
        host.replaceChildren();

        const note = document.createElement('div');
        note.className = 'drayvia-depot-readonly-note';
        note.textContent =
            `Read-only náhled: alias „${preview.confirmed_alias}“, `
            + `list ${preview.detected.sheet_name}, hlavička řádky `
            + `${preview.detected.header_start_row}–${preview.detected.header_end_row}. `
            + 'Zdrojový soubor nebyl uložen ani změněn.';
        host.appendChild(note);

        if (preview.source.mapped_formula_cell_count > 0) {
            const formulaWarning = document.createElement('div');
            formulaWarning.className = 'drayvia-depot-import-status error';
            formulaWarning.textContent =
                `${preview.source.mapped_formula_cell_count} mapovaných buněk obsahuje vzorec. `
                + 'Jejich hodnoty nejsou pro import povoleny.';
            host.appendChild(formulaWarning);
        }

        const grid = document.createElement('div');
        grid.className = 'drayvia-preview-grid';
        grid.append(
            depotImportCard(
                'Připravené záznamy',
                String(preview.totals.ready_rows),
                `${preview.totals.matched_rows} řádků odpovídá aliasu`
            ),
            depotImportCard(
                'Neodjeté záznamy',
                String(preview.totals.no_run_rows),
                'Nevytvoří nulový výkaz'
            ),
            depotImportCard(
                'Chyby',
                String(preview.totals.invalid_rows),
                'Před importem je nutné vyřešit'
            ),
            depotImportCard(
                'Vyloučení dopravci',
                String(preview.excluded_carrier_row_count),
                'Řádky jiných dopravců'
            ),
            depotImportCard(
                'Zdrojová jména řidičů',
                String(preview.source_driver_values.length),
                'Zatím bez automatického propojení'
            ),
            depotImportCard(
                'Oprávnění řidiči',
                String(preview.eligible_drivers.length),
                'Aktivní přiřazení hlavnímu dopravci'
            )
        );
        host.appendChild(grid);

        const summary = document.createElement('div');
        summary.className = 'drayvia-depot-readonly-note';
        summary.style.marginTop = '16px';
        summary.textContent =
            `Kontrolní součty: naloženo ${preview.totals.loaded_parcels}, `
            + `doručeno ${preview.totals.delivered_parcels}, `
            + `výdejní místo ${preview.totals.redirected_parcels}, `
            + `odmítnuto ${preview.totals.customer_rejected_parcels}, `
            + `nerozvezeno ${preview.totals.computed_not_delivered_parcels}.`;
        host.appendChild(summary);

        const tableWrap = document.createElement('div');
        tableWrap.className = 'drayvia-depot-preview-table-wrap';

        const table = document.createElement('table');
        table.className = 'drayvia-depot-preview-table';
        const head = document.createElement('thead');
        const headRow = document.createElement('tr');

        [
            'Řádek',
            'Stav',
            'Datum',
            'Trasa',
            'Řidič v depu',
            'Naloženo',
            'Adresa',
            'VM',
            'Odmítnuto',
            'Nerozvezeno',
            'Skut. km',
            'Plán km',
            'Kontrola',
        ].forEach((label) => {
            const th = document.createElement('th');
            th.textContent = label;
            headRow.appendChild(th);
        });
        head.appendChild(headRow);

        const body = document.createElement('tbody');

        preview.rows.forEach((row) => {
            const tr = document.createElement('tr');
            const statusLabels = {
                ready: 'Připraveno',
                invalid: 'Chyba',
                no_run: 'Neodjeto',
            };
            const values = [
                row.source_row,
                statusLabels[row.status] || row.status,
                depotImportFormatDate(row.service_date),
                row.route_number || '—',
                row.source_driver_name || '—',
                row.loaded_parcels ?? '—',
                row.delivered_parcels ?? '—',
                row.redirected_parcels ?? '—',
                row.customer_rejected_parcels ?? '—',
                row.computed_not_delivered_parcels ?? '—',
                row.actual_km ?? '—',
                row.planned_km ?? '—',
                [...row.errors, ...row.warnings].join(' ') || 'V pořádku',
            ];

            values.forEach((value) => {
                const td = document.createElement('td');
                td.textContent = String(value);
                tr.appendChild(td);
            });

            body.appendChild(tr);
        });

        table.append(head, body);
        tableWrap.appendChild(table);
        host.appendChild(tableWrap);

        const boundary = document.createElement('div');
        boundary.className = 'drayvia-depot-readonly-note';
        boundary.style.marginTop = '16px';
        boundary.textContent =
            'Náhled sám nic neukládá. Po kontrole můžete vytvořit auditovaný '
            + 'koncept, který uloží pouze ověřené hodnoty a umožní přiřadit '
            + 'řidiče. Zdrojový Excel se neuloží a žádná trasa zatím nevznikne.';
        host.appendChild(boundary);

        const create = document.createElement('button');
        create.type = 'button';
        create.className = 'drayvia-preview-action primary';
        create.textContent = 'Vytvořit auditovaný koncept';
        create.disabled =
            preview.totals.invalid_rows !== 0
            || preview.totals.ready_rows < 1
            || preview.source.mapped_formula_cell_count > 0;
        create.addEventListener('click', async () => {
            if (!(depotImportState.file instanceof File)) {
                depotImportSetStatus(
                    'Zdrojový sešit už není v prohlížeči dostupný. Vyberte jej znovu.',
                    'error'
                );
                return;
            }

            create.disabled = true;
            create.textContent = 'Vytvářím koncept…';

            try {
                const formData = new FormData();
                formData.append(
                    'workbook',
                    depotImportState.file,
                    depotImportState.file.name
                );
                formData.append('carrier_alias', preview.confirmed_alias);
                formData.append('carrier_alias_confirmed', '1');

                const draft = await depotImportApi(
                    '/api/v1/daily-reports/depot-imports/drafts',
                    {method: 'POST', formData}
                );
                depotImportState.draft = draft;
                depotImportRenderDraft(draft);
                await depotImportLoadDrafts();
                depotImportSetStatus(
                    'Auditovaný koncept byl vytvořen. Zdrojový Excel nebyl uložen.',
                    'success'
                );
                document
                    .getElementById('drayviaDepotImportDraftHost')
                    ?.scrollIntoView({behavior: 'smooth', block: 'start'});
            } catch (error) {
                create.disabled = false;
                create.textContent = 'Vytvořit auditovaný koncept';
                depotImportSetStatus(error.message, 'error');
            }
        });
        host.appendChild(create);
    };

    const depotImportOpenAliasConfirmation = (inspection) => {
        document
            .querySelector('.drayvia-depot-modal-backdrop')
            ?.remove();

        const backdrop = document.createElement('div');
        backdrop.className = 'drayvia-depot-modal-backdrop';

        const modal = document.createElement('form');
        modal.className = 'drayvia-depot-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');

        const title = document.createElement('h2');
        title.textContent = 'Potvrdit dopravce před náhledem';

        const description = document.createElement('p');
        description.textContent =
            `Soubor ${inspection.source.original_filename} byl přečten pouze `
            + `pro kontrolu. Systém rozpoznal list ${inspection.detected.sheet_name} `
            + `a navrhuje alias podle názvu hlavní organizace. Alias můžete hned upravit.`;

        const list = document.createElement('div');
        list.className = 'drayvia-depot-carrier-list';

        inspection.carrier_values.forEach((carrier) => {
            const item = document.createElement('div');
            item.className = 'drayvia-depot-carrier-item';

            const name = document.createElement('span');
            name.textContent = carrier.value;

            const count = document.createElement('strong');
            count.textContent = `${carrier.row_count} řádků`;

            item.append(name, count);
            list.appendChild(item);
        });

        const aliasField = document.createElement('label');
        aliasField.className = 'drayvia-depot-alias-field';
        aliasField.textContent = 'Alias hlavního dopravce';

        const aliasInput = document.createElement('input');
        aliasInput.name = 'carrier_alias';
        aliasInput.required = true;
        aliasInput.maxLength = 255;
        aliasInput.value = inspection.suggested_alias;
        aliasField.appendChild(aliasInput);

        const match = document.createElement('p');
        match.textContent =
            `Navržený alias nyní odpovídá ${inspection.suggested_matching_row_count} řádkům. `
            + 'Porovnání ignoruje diakritiku a mezery, nikoli podobnost jmen.';

        const actions = document.createElement('div');
        actions.className = 'drayvia-depot-modal-actions';

        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'drayvia-preview-action';
        cancel.textContent = 'Zrušit';
        cancel.addEventListener('click', () => backdrop.remove());

        const confirm = document.createElement('button');
        confirm.type = 'submit';
        confirm.className = 'drayvia-preview-action primary';
        confirm.textContent = 'Potvrdit a zobrazit náhled';

        actions.append(cancel, confirm);
        modal.append(
            title,
            description,
            list,
            aliasField,
            match,
            actions
        );
        backdrop.appendChild(modal);
        document.body.appendChild(backdrop);
        aliasInput.focus();
        aliasInput.select();

        modal.addEventListener('submit', async (event) => {
            event.preventDefault();
            confirm.disabled = true;
            cancel.disabled = true;
            confirm.textContent = 'Kontroluji…';

            try {
                const formData = new FormData();
                formData.append(
                    'workbook',
                    depotImportState.file,
                    depotImportState.file.name
                );
                formData.append(
                    'carrier_alias',
                    aliasInput.value.trim()
                );
                formData.append(
                    'carrier_alias_confirmed',
                    '1'
                );

                const preview = await depotImportApi(
                    '/api/v1/daily-reports/depot-imports/preview',
                    {method: 'POST', formData}
                );
                backdrop.remove();
                depotImportRenderPreview(preview);
                depotImportSetStatus(
                    'Read-only náhled byl vytvořen. Nic nebylo zapsáno.',
                    'success'
                );
            } catch (error) {
                confirm.disabled = false;
                cancel.disabled = false;
                confirm.textContent = 'Potvrdit a zobrazit náhled';
                depotImportSetStatus(error.message, 'error');
            }
        });
    };

    const bindDepotImportPreview = () => {
        const form = document.getElementById(
            'drayviaDepotImportInspectForm'
        );
        const input = document.getElementById(
            'drayviaDepotImportWorkbook'
        );
        const button = document.getElementById(
            'drayviaDepotImportInspect'
        );

        if (!form || !input || !button) {
            return;
        }

        input.addEventListener('change', () => {
            depotImportState.file = null;
            depotImportState.inspection = null;
            depotImportState.preview = null;
            document
                .getElementById('drayviaDepotImportPreviewHost')
                ?.replaceChildren();
            depotImportSetStatus('');
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const file = input.files?.[0];

            if (!(file instanceof File)) {
                depotImportSetStatus(
                    'Nejprve vyberte sešit XLSX.',
                    'error'
                );
                return;
            }

            depotImportState.file = file;
            button.disabled = true;
            button.textContent = 'Načítám…';
            depotImportSetStatus(
                'Bezpečně čtu hodnoty a hledám importní hlavičku…'
            );

            try {
                const formData = new FormData();
                formData.append('workbook', file, file.name);

                const inspection = await depotImportApi(
                    '/api/v1/daily-reports/depot-imports/inspect',
                    {method: 'POST', formData}
                );
                depotImportState.inspection = inspection;
                depotImportSetStatus(
                    'Struktura byla rozpoznána. Potvrďte alias dopravce.',
                    'success'
                );
                depotImportOpenAliasConfirmation(inspection);
            } catch (error) {
                depotImportSetStatus(error.message, 'error');
            } finally {
                button.disabled = false;
                button.textContent = 'Načíst a zkontrolovat';
            }
        });

        depotImportLoadDrafts();
    };

    const imports = () => `
        ${header(
            'Importy',
            'Bezpečné převzetí měsíčních zápisů depa jako samostatného zdroje.'
        )}

        <div class="drayvia-preview-panel">
            <div class="drayvia-preview-panel-head">
                <h2 class="drayvia-preview-panel-title">Import zápisů z depa</h2>
                <div class="drayvia-preview-panel-subtitle">
                    Rozložení tabulky se rozpoznává podle významu hlaviček, ne podle pevných sloupců.
                </div>
            </div>

            <div class="drayvia-preview-panel-body">
                <div class="drayvia-depot-readonly-note">
                    Sešit se čte pouze jako zdroj hodnot. Neukládá se, neupravuje se
                    a žádný vzorec se nezobrazuje ani nepoužívá pro import.
                </div>

                <form id="drayviaDepotImportInspectForm" class="drayvia-depot-import-form">
                    <label>
                        Měsíční sešit XLSX
                        <input
                            id="drayviaDepotImportWorkbook"
                            name="workbook"
                            type="file"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required
                        >
                    </label>

                    <button
                        id="drayviaDepotImportInspect"
                        class="drayvia-preview-action primary"
                        type="submit"
                    >
                        Načíst a zkontrolovat
                    </button>
                </form>

                <div
                    id="drayviaDepotImportStatus"
                    class="drayvia-depot-import-status"
                    role="status"
                    aria-live="polite"
                ></div>
            </div>
        </div>

        <div id="drayviaDepotImportDraftListHost"></div>
        <div id="drayviaDepotImportPreviewHost"></div>
        <div id="drayviaDepotImportDraftHost"></div>
    `;

    // S030-01A DEPOT VERSUS DRIVER RECORD REVIEW UI
    const depotDriverReviewStatuses = {
        matching: 'Shoda',
        different: 'Rozdíl',
        missing_driver_record: 'Chybí zápis řidiče',
        driver_mismatch: 'Jiný řidič',
        not_comparable: 'Nelze porovnat',
    };

    const depotDriverReviewReasons = {
        all_comparable_values_match:
            'Všechny porovnatelné hodnoty depa a řidiče se shodují.',
        comparable_values_differ:
            'Jedna nebo více porovnatelných hodnot se liší.',
        driver_record_missing:
            'Pro datum a trasu nebyl nalezen samostatný zápis řidiče.',
        assigned_driver_differs:
            'Zápis trasy existuje, ale je vedený na jiného řidiče.',
        depot_no_run:
            'Depo označilo řádek jako neodjetou trasu.',
        depot_driver_unassigned:
            'K řádku depa není přiřazený oprávněný řidič.',
        multiple_driver_records:
            'Pro datum a trasu existuje více zápisů řidiče; automatické porovnání není jednoznačné.',
    };

    const depotDriverReviewFields = [
        ['departure_time', 'Čas odjezdu'],
        ['arrival_time', 'Čas příjezdu'],
        ['loaded_parcels', 'Naloženo'],
        ['delivered_parcels', 'Doručeno na adresu'],
        ['redirected_parcels', 'Doručeno na výdejní místo'],
        ['customer_rejected_parcels', 'Odmítnuto zákazníkem'],
        ['computed_not_delivered_parcels', 'Nedoručeno'],
        ['actual_km', 'Skutečné kilometry'],
        ['planned_km', 'Plánované kilometry'],
        ['surcharge_amount', 'Příplatek'],
        ['operational_notes', 'Provozní poznámka'],
    ];

    const depotDriverReviewState = {
        batches: [],
        selectedBatch: '',
        data: null,
        loading: false,
        page: 1,
        perPage: 25,
        filters: {
            comparisonStatus: '',
            driverId: '',
            dateFrom: '',
            dateTo: '',
            routeNumber: '',
        },
    };

    const depotDriverReviewEscape = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const depotDriverReviewInteger = (value) => {
        const numeric = Number(value);

        return Number.isFinite(numeric)
            ? new Intl.NumberFormat('cs-CZ', {
                maximumFractionDigits: 0,
            }).format(numeric)
            : '—';
    };

    const depotDriverReviewWholeKm = (value) => {
        const numeric = Number(value);

        return Number.isFinite(numeric)
            ? `${new Intl.NumberFormat('cs-CZ', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(numeric)} km`
            : '—';
    };

    const depotDriverReviewMoney = (value) => {
        const numeric = Number(value);

        return Number.isFinite(numeric)
            ? `${new Intl.NumberFormat('cs-CZ', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(numeric)} Kč`
            : '—';
    };

    const depotDriverReviewValue = (field, value) => {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        if (field === 'actual_km' || field === 'planned_km') {
            return depotDriverReviewWholeKm(value);
        }

        if (field === 'surcharge_amount') {
            return depotDriverReviewMoney(value);
        }

        if ([
            'loaded_parcels',
            'delivered_parcels',
            'redirected_parcels',
            'customer_rejected_parcels',
            'computed_not_delivered_parcels',
        ].includes(field)) {
            return depotDriverReviewInteger(value);
        }

        return String(value);
    };

    const depotDriverReviewDate = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(
            String(value ?? '')
        );

        return match
            ? `${match[3]}.${match[2]}.${match[1]}`
            : (value || '—');
    };

    const depotDriverReviewSetStatus = (message, tone = '') => {
        const target = document.getElementById(
            'drayviaDepotDriverReviewStatus'
        );

        if (!target) {
            return;
        }

        target.textContent = message;
        target.className =
            `drayvia-record-review-status ${tone}`.trim();
    };

    const depotDriverReviewResetFilters = () => {
        depotDriverReviewState.page = 1;
        depotDriverReviewState.filters = {
            comparisonStatus: '',
            driverId: '',
            dateFrom: '',
            dateTo: '',
            routeNumber: '',
        };
    };

    const depotDriverReviewQuery = () => {
        const query = new URLSearchParams();
        const filters = depotDriverReviewState.filters;

        if (filters.comparisonStatus) {
            query.set('comparison_status', filters.comparisonStatus);
        }

        if (filters.driverId) {
            query.set('performed_by_driver_id', filters.driverId);
        }

        if (filters.dateFrom) {
            query.set('service_date_from', filters.dateFrom);
        }

        if (filters.dateTo) {
            query.set('service_date_to', filters.dateTo);
        }

        if (filters.routeNumber) {
            query.set('route_number', filters.routeNumber);
        }

        query.set('page', String(depotDriverReviewState.page));
        query.set('per_page', String(depotDriverReviewState.perPage));

        return query.toString();
    };

    const depotDriverReviewRenderBatches = () => {
        const select = document.getElementById(
            'drayviaDepotDriverReviewBatch'
        );

        if (!select) {
            return;
        }

        select.replaceChildren();

        if (depotDriverReviewState.batches.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Není dostupná žádná importovaná dávka';
            select.appendChild(option);
            select.disabled = true;
            return;
        }

        depotDriverReviewState.batches.forEach((batch) => {
            const option = document.createElement('option');
            const period = [
                depotDriverReviewDate(batch.period?.from),
                depotDriverReviewDate(batch.period?.until),
            ].join('–');

            option.value = batch.public_id;
            option.textContent =
                `${batch.source?.original_filename || 'Import depa'} · `
                + `${batch.confirmed_alias || 'bez aliasu'} · ${period}`;
            option.selected = batch.public_id
                === depotDriverReviewState.selectedBatch;
            select.appendChild(option);
        });

        select.disabled = depotDriverReviewState.loading;
    };

    const depotDriverReviewRenderFilterOptions = () => {
        const data = depotDriverReviewState.data;
        const driver = document.getElementById(
            'drayviaDepotDriverReviewDriver'
        );
        const status = document.getElementById(
            'drayviaDepotDriverReviewComparisonStatus'
        );

        if (status) {
            status.value =
                depotDriverReviewState.filters.comparisonStatus;
        }

        if (!driver) {
            return;
        }

        driver.replaceChildren();

        const all = document.createElement('option');
        all.value = '';
        all.textContent = 'Všichni přiřazení řidiči';
        driver.appendChild(all);

        const drivers = Array.isArray(data?.filter_options?.drivers)
            ? data.filter_options.drivers
            : [];

        drivers.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.name || `Řidič ${item.id}`;
            option.selected = option.value
                === depotDriverReviewState.filters.driverId;
            driver.appendChild(option);
        });

        driver.value = depotDriverReviewState.filters.driverId;

        const dateFrom = document.getElementById(
            'drayviaDepotDriverReviewDateFrom'
        );
        const dateTo = document.getElementById(
            'drayviaDepotDriverReviewDateTo'
        );
        const route = document.getElementById(
            'drayviaDepotDriverReviewRoute'
        );

        if (dateFrom) {
            dateFrom.value = depotDriverReviewState.filters.dateFrom;
        }

        if (dateTo) {
            dateTo.value = depotDriverReviewState.filters.dateTo;
        }

        if (route) {
            route.value = depotDriverReviewState.filters.routeNumber;
        }
    };

    const depotDriverReviewSummaryButton = (
        status,
        label,
        value
    ) => {
        const active = depotDriverReviewState.filters.comparisonStatus
            === status;

        return `
            <button
                class="drayvia-record-review-summary-card${active ? ' is-active' : ''}"
                type="button"
                data-record-review-summary-status="${depotDriverReviewEscape(status)}"
                aria-pressed="${active ? 'true' : 'false'}"
            >
                <span>${depotDriverReviewEscape(label)}</span>
                <strong>${depotDriverReviewEscape(depotDriverReviewInteger(value))}</strong>
            </button>
        `;
    };

    const depotDriverReviewRenderSummary = () => {
        const host = document.getElementById(
            'drayviaDepotDriverReviewSummary'
        );
        const summary = depotDriverReviewState.data?.summary;

        if (!host) {
            return;
        }

        if (!summary) {
            host.replaceChildren();
            return;
        }

        host.innerHTML = [
            depotDriverReviewSummaryButton(
                '',
                'Všechny záznamy',
                summary.source_records
            ),
            depotDriverReviewSummaryButton(
                'matching',
                'Shoda',
                summary.matching
            ),
            depotDriverReviewSummaryButton(
                'different',
                'Rozdíl hodnot',
                summary.different
            ),
            depotDriverReviewSummaryButton(
                'missing_driver_record',
                'Chybí zápis',
                summary.missing_driver_record
            ),
            depotDriverReviewSummaryButton(
                'driver_mismatch',
                'Jiný řidič',
                summary.driver_mismatch
            ),
            depotDriverReviewSummaryButton(
                'not_comparable',
                'Nelze porovnat',
                summary.not_comparable
            ),
        ].join('');

        host.querySelectorAll(
            '[data-record-review-summary-status]'
        ).forEach((button) => {
            button.addEventListener('click', () => {
                depotDriverReviewState.filters.comparisonStatus =
                    button.dataset.recordReviewSummaryStatus || '';
                depotDriverReviewState.page = 1;

                const select = document.getElementById(
                    'drayviaDepotDriverReviewComparisonStatus'
                );

                if (select) {
                    select.value =
                        depotDriverReviewState.filters.comparisonStatus;
                }

                depotDriverReviewLoad();
            });
        });
    };

    const depotDriverReviewComparisonRows = (item) => {
        const depot = item?.depot_record || {};
        const driver = item?.driver_record || null;
        const depotValues = depot.values || {};
        const driverValues = driver?.values || {};
        const differenceFields = new Set(
            Array.isArray(item?.differences)
                ? item.differences.map((difference) => difference.field)
                : []
        );
        const assignedDriver = depot.assigned_driver?.name || '—';
        const performedDriver =
            driver?.performed_by_driver?.name || '—';
        const driverDiffers = differenceFields.has(
            'performed_by_driver_id'
        );
        const rows = [
            `
                <div class="drayvia-record-review-comparison-row${driverDiffers ? ' is-different' : ''}">
                    <div>Přiřazený řidič</div>
                    <div>${depotDriverReviewEscape(assignedDriver)}</div>
                    <div>${depotDriverReviewEscape(performedDriver)}</div>
                </div>
            `,
        ];

        depotDriverReviewFields.forEach(([field, label]) => {
            const differs = differenceFields.has(field);

            rows.push(`
                <div class="drayvia-record-review-comparison-row${differs ? ' is-different' : ''}">
                    <div>${depotDriverReviewEscape(label)}</div>
                    <div>${depotDriverReviewEscape(
                        depotDriverReviewValue(field, depotValues[field])
                    )}</div>
                    <div>${depotDriverReviewEscape(
                        depotDriverReviewValue(field, driverValues[field])
                    )}</div>
                </div>
            `);
        });

        return `
            <div class="drayvia-record-review-comparison">
                <div class="drayvia-record-review-comparison-row drayvia-record-review-comparison-head">
                    <div>Kontrolované pole</div>
                    <div>Depo</div>
                    <div>Řidič</div>
                </div>
                ${rows.join('')}
            </div>
        `;
    };

    const depotDriverReviewItem = (item) => {
        const depot = item?.depot_record || {};
        const driver = item?.driver_record || null;
        const status = Object.prototype.hasOwnProperty.call(
            depotDriverReviewStatuses,
            item?.comparison_status
        )
            ? item.comparison_status
            : 'not_comparable';
        const statusLabel = depotDriverReviewStatuses[status];
        const reason = depotDriverReviewReasons[item?.comparison_reason]
            || 'Výsledek porovnání není blíže popsán.';
        const differenceCount = depotDriverReviewInteger(
            item?.difference_count || 0
        );
        const assignedDriver = depot.assigned_driver?.name
            || depot.source_driver_name
            || 'Nepřiřazený řidič';
        const driverName = driver?.performed_by_driver?.name
            || 'Bez zápisu řidiče';
        const open = status === 'matching' ? '' : ' open';

        return `
            <details class="drayvia-record-review-item is-${status}"${open}>
                <summary>
                    <div class="drayvia-record-review-route">
                        <strong>Trasa ${depotDriverReviewEscape(depot.route_number || '—')}</strong>
                        <span>${depotDriverReviewEscape(
                            depot.service_date_display
                            || depotDriverReviewDate(depot.service_date)
                        )} · řádek depa ${depotDriverReviewEscape(depot.source_row || '—')}</span>
                    </div>
                    <div>
                        <span class="drayvia-record-review-badge is-${status}">
                            ${depotDriverReviewEscape(statusLabel)}
                        </span>
                    </div>
                    <div class="drayvia-record-review-driver">
                        <strong>${depotDriverReviewEscape(assignedDriver)}</strong>
                        <span>Řidičův zápis: ${depotDriverReviewEscape(driverName)} · rozdíly ${depotDriverReviewEscape(differenceCount)}</span>
                    </div>
                </summary>
                <div class="drayvia-record-review-item-body">
                    <p class="drayvia-record-review-reason">
                        ${depotDriverReviewEscape(reason)}
                    </p>
                    ${depotDriverReviewComparisonRows(item)}
                </div>
            </details>
        `;
    };

    const depotDriverReviewRenderResults = () => {
        const host = document.getElementById(
            'drayviaDepotDriverReviewList'
        );
        const count = document.getElementById(
            'drayviaDepotDriverReviewResultCount'
        );
        const paginationHost = document.getElementById(
            'drayviaDepotDriverReviewPagination'
        );
        const data = depotDriverReviewState.data;
        const pagination = data?.pagination;
        const items = Array.isArray(data?.items) ? data.items : [];

        if (!host || !paginationHost) {
            return;
        }

        if (count) {
            count.textContent = pagination?.total
                ? `Zobrazeno ${pagination.from}–${pagination.to} z ${pagination.total}`
                : 'Žádný záznam neodpovídá zvoleným filtrům.';
        }

        if (items.length === 0) {
            host.innerHTML = `
                <div class="drayvia-record-review-empty">
                    Pro tuto dávku a zvolené filtry není co zobrazit.<br>
                    Změňte stav nebo vymažte filtry.
                </div>
            `;
        } else {
            host.innerHTML = items
                .map((item) => depotDriverReviewItem(item))
                .join('');
        }

        paginationHost.replaceChildren();

        if (!pagination || pagination.last_page <= 1) {
            return;
        }

        const previous = document.createElement('button');
        previous.type = 'button';
        previous.className = 'drayvia-preview-action';
        previous.textContent = 'Předchozí';
        previous.disabled = pagination.current_page <= 1;
        previous.addEventListener('click', () => {
            depotDriverReviewState.page = Math.max(
                1,
                pagination.current_page - 1
            );
            depotDriverReviewLoad();
        });

        const label = document.createElement('span');
        label.textContent =
            `Strana ${pagination.current_page} z ${pagination.last_page}`;

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'drayvia-preview-action';
        next.textContent = 'Další';
        next.disabled = pagination.current_page >= pagination.last_page;
        next.addEventListener('click', () => {
            depotDriverReviewState.page = Math.min(
                pagination.last_page,
                pagination.current_page + 1
            );
            depotDriverReviewLoad();
        });

        paginationHost.append(previous, label, next);
    };

    const depotDriverReviewRenderData = () => {
        depotDriverReviewRenderBatches();
        depotDriverReviewRenderFilterOptions();
        depotDriverReviewRenderSummary();
        depotDriverReviewRenderResults();
    };

    const depotDriverReviewLoad = async () => {
        if (
            !depotDriverReviewState.selectedBatch
            || depotDriverReviewState.loading
        ) {
            return;
        }

        depotDriverReviewState.loading = true;
        depotDriverReviewRenderBatches();
        depotDriverReviewSetStatus(
            'Načítám a ověřuji chráněné hodnoty depa…'
        );

        try {
            const batch = encodeURIComponent(
                depotDriverReviewState.selectedBatch
            );
            const body = await api(
                `/api/v1/daily-reports/record-review/depot-driver/${batch}?${depotDriverReviewQuery()}`
            );
            const data = getPayload(body);

            if (data?.workspace !== 'depot_driver_record_review') {
                throw new Error(
                    'API vrátilo neočekávaný formát porovnání.'
                );
            }

            depotDriverReviewState.data = data;
            depotDriverReviewState.page =
                Number(data.pagination?.current_page) || 1;
            depotDriverReviewRenderData();
            depotDriverReviewSetStatus(
                'Porovnání je načtené. Zdroj depa byl ověřen a zůstává beze změny.',
                'success'
            );
        } catch (error) {
            depotDriverReviewState.data = null;
            depotDriverReviewRenderData();
            depotDriverReviewSetStatus(
                `Porovnání nelze načíst: ${error.message}`,
                'error'
            );
        } finally {
            depotDriverReviewState.loading = false;
            depotDriverReviewRenderBatches();
        }
    };

    const depotDriverReviewLoadBatches = async () => {
        if (depotDriverReviewState.loading) {
            return;
        }

        depotDriverReviewState.loading = true;
        depotDriverReviewRenderBatches();
        depotDriverReviewSetStatus('Načítám importované dávky depa…');

        try {
            const body = await api(
                '/api/v1/daily-reports/depot-imports/drafts'
            );
            const batches = getPayload(body);

            depotDriverReviewState.batches = Array.isArray(batches)
                ? batches.filter((batch) => batch.status === 'imported')
                : [];

            const selectedStillExists = depotDriverReviewState.batches
                .some(
                    (batch) => batch.public_id
                        === depotDriverReviewState.selectedBatch
                );

            if (!selectedStillExists) {
                depotDriverReviewState.selectedBatch =
                    depotDriverReviewState.batches[0]?.public_id || '';
                depotDriverReviewResetFilters();
            }

            depotDriverReviewRenderBatches();

            if (!depotDriverReviewState.selectedBatch) {
                depotDriverReviewState.data = null;
                depotDriverReviewRenderData();
                depotDriverReviewSetStatus(
                    'Nejprve dokončete alespoň jeden import zápisů z depa.',
                    'error'
                );
                return;
            }
        } catch (error) {
            depotDriverReviewState.batches = [];
            depotDriverReviewState.data = null;
            depotDriverReviewRenderData();
            depotDriverReviewSetStatus(
                `Importované dávky nelze načíst: ${error.message}`,
                'error'
            );
            return;
        } finally {
            depotDriverReviewState.loading = false;
            depotDriverReviewRenderBatches();
        }

        await depotDriverReviewLoad();
    };

    const depotDriverReviewReadFilters = () => {
        const comparisonStatus = document.getElementById(
            'drayviaDepotDriverReviewComparisonStatus'
        )?.value || '';
        const driverId = document.getElementById(
            'drayviaDepotDriverReviewDriver'
        )?.value || '';
        const dateFrom = document.getElementById(
            'drayviaDepotDriverReviewDateFrom'
        )?.value || '';
        const dateTo = document.getElementById(
            'drayviaDepotDriverReviewDateTo'
        )?.value || '';
        const routeNumber = document.getElementById(
            'drayviaDepotDriverReviewRoute'
        )?.value.trim() || '';

        if (dateFrom && dateTo && dateFrom > dateTo) {
            throw new Error(
                'Datum „Od“ nesmí být pozdější než datum „Do“.'
            );
        }

        depotDriverReviewState.filters = {
            comparisonStatus,
            driverId,
            dateFrom,
            dateTo,
            routeNumber,
        };
        depotDriverReviewState.page = 1;
    };

    const bindDepotDriverRecordReview = () => {
        const batch = document.getElementById(
            'drayviaDepotDriverReviewBatch'
        );
        const refresh = document.getElementById(
            'drayviaDepotDriverReviewRefresh'
        );
        const form = document.getElementById(
            'drayviaDepotDriverReviewFilters'
        );
        const reset = document.getElementById(
            'drayviaDepotDriverReviewReset'
        );

        batch?.addEventListener('change', () => {
            depotDriverReviewState.selectedBatch = batch.value;
            depotDriverReviewState.data = null;
            depotDriverReviewResetFilters();
            depotDriverReviewLoad();
        });

        refresh?.addEventListener('click', () => {
            depotDriverReviewLoadBatches();
        });

        form?.addEventListener('submit', (event) => {
            event.preventDefault();

            try {
                depotDriverReviewReadFilters();
                depotDriverReviewLoad();
            } catch (error) {
                depotDriverReviewSetStatus(error.message, 'error');
            }
        });

        reset?.addEventListener('click', () => {
            depotDriverReviewResetFilters();
            depotDriverReviewRenderFilterOptions();
            depotDriverReviewLoad();
        });

        depotDriverReviewLoadBatches();
    };

    const recordReview = () => `
        <section class="drayvia-record-review">
            <div class="drayvia-record-review-topbar">
                <div>
                    <div class="drayvia-preview-eyebrow">Trasy / provozní kontrola</div>
                    <h1 class="drayvia-preview-title">Kontrola zápisů</h1>
                    <p class="drayvia-preview-description">
                        Fyzické porovnání neměnného zápisu depa se samostatným zápisem řidiče.
                    </p>
                </div>
                <div class="drayvia-preview-actions">
                    <button
                        class="drayvia-preview-action"
                        type="button"
                        data-drayvia-page="routes"
                    >
                        Zpět na trasy
                    </button>
                </div>
            </div>

            <div class="drayvia-record-review-batch">
                <label class="drayvia-record-review-field">
                    Importovaná dávka depa
                    <select id="drayviaDepotDriverReviewBatch" disabled>
                        <option>Načítám importované dávky…</option>
                    </select>
                </label>
                <div class="drayvia-record-review-batch-actions">
                    <button
                        id="drayviaDepotDriverReviewRefresh"
                        class="drayvia-preview-action"
                        type="button"
                    >
                        Obnovit data
                    </button>
                </div>
            </div>

            <div
                id="drayviaDepotDriverReviewStatus"
                class="drayvia-record-review-status"
                role="status"
                aria-live="polite"
            ></div>

            <div class="drayvia-record-review-readonly">
                <strong>Pouze ke čtení.</strong>
                <span>
                    Obrazovka nic nepřijímá, neopravuje ani nerozděluje.
                    Přesné hodnoty zůstávají v API; kilometry se zde zobrazují jako celá čísla.
                </span>
            </div>

            <div
                id="drayviaDepotDriverReviewSummary"
                class="drayvia-record-review-summary"
                aria-label="Souhrn výsledků porovnání"
            ></div>

            <form
                id="drayviaDepotDriverReviewFilters"
                class="drayvia-record-review-filter-panel"
            >
                <div class="drayvia-record-review-filter-grid">
                    <label class="drayvia-record-review-field">
                        Stav
                        <select id="drayviaDepotDriverReviewComparisonStatus">
                            <option value="">Všechny stavy</option>
                            <option value="matching">Shoda</option>
                            <option value="different">Rozdíl hodnot</option>
                            <option value="missing_driver_record">Chybí zápis řidiče</option>
                            <option value="driver_mismatch">Jiný řidič</option>
                            <option value="not_comparable">Nelze porovnat</option>
                        </select>
                    </label>

                    <label class="drayvia-record-review-field">
                        Přiřazený řidič
                        <select id="drayviaDepotDriverReviewDriver">
                            <option value="">Všichni přiřazení řidiči</option>
                        </select>
                    </label>

                    <label class="drayvia-record-review-field">
                        Datum od
                        <input id="drayviaDepotDriverReviewDateFrom" type="date">
                    </label>

                    <label class="drayvia-record-review-field">
                        Datum do
                        <input id="drayviaDepotDriverReviewDateTo" type="date">
                    </label>

                    <label class="drayvia-record-review-field">
                        Trasa obsahuje
                        <input
                            id="drayviaDepotDriverReviewRoute"
                            type="search"
                            placeholder="např. 36"
                            autocomplete="off"
                        >
                    </label>
                </div>

                <div class="drayvia-record-review-filter-actions">
                    <button
                        id="drayviaDepotDriverReviewReset"
                        class="drayvia-preview-action"
                        type="button"
                    >
                        Vymazat filtry
                    </button>
                    <button
                        class="drayvia-preview-action primary"
                        type="submit"
                    >
                        Použít filtry
                    </button>
                </div>
            </form>

            <section class="drayvia-record-review-results">
                <div class="drayvia-record-review-results-head">
                    <div>
                        <h2>Depo × Řidič</h2>
                        <p id="drayviaDepotDriverReviewResultCount">
                            Vyberte importovanou dávku.
                        </p>
                    </div>
                </div>
                <div
                    id="drayviaDepotDriverReviewList"
                    class="drayvia-record-review-list"
                >
                    <div class="drayvia-record-review-empty">
                        Načítám porovnání…
                    </div>
                </div>
                <div
                    id="drayviaDepotDriverReviewPagination"
                    class="drayvia-record-review-pagination"
                ></div>
            </section>
        </section>
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
        'record-review': recordReview,
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
             * S023-04B UNLIMITED CONDITIONAL SURCHARGE UI
             *
             * The provider-managed endpoint creates the price list, draft
             * version, canonical items and the complete conditional-rule tree
             * in one transaction. Rule and band counts are intentionally
             * unlimited in the browser.
             */
            const financeConditionalMetricSources = [
                { value: 'loaded_parcels', label: 'Nalo\u017eeno' },
                { value: 'delivered_parcels', label: 'Doru\u010deno' },
                { value: 'redirected_parcels', label: 'P\u0159esm\u011brov\u00e1no' },
                {
                    value: 'customer_rejected_parcels',
                    label: 'Odm\u00edtnuto z\u00e1kazn\u00edkem',
                },
                { value: 'not_delivered_parcels', label: 'Nerozvezeno' },
                {
                    value: 'processed_parcels',
                    label: 'Zpracov\u00e1no celkem',
                },
                { value: 'actual_km', label: 'Skute\u010dn\u00e9 km' },
                { value: 'planned_km', label: 'Pl\u00e1novan\u00e9 km' },
            ];

            const financeConditionalBaseItems = [
                { value: 'delivered_parcels', label: 'Doru\u010den\u00e9 z\u00e1silky' },
                { value: 'redirected_parcels', label: 'P\u0159esm\u011brovan\u00e9 z\u00e1silky' },
                {
                    value: 'undelivered_parcels',
                    label: 'Odm\u00edtnuto z\u00e1kazn\u00edkem',
                },
                { value: 'actual_km', label: 'Skute\u010dn\u00e9 km' },
            ];

            const financeConditionalRulePresets = {
                quality: {
                    code: 'delivery_quality',
                    name: 'P\u0159\u00edplatek za kvalitu rozvozu',
                    metricType: 'ratio_percentage',
                    numeratorSources: [
                        'delivered_parcels',
                        'redirected_parcels',
                        'customer_rejected_parcels',
                    ],
                    denominatorSources: ['loaded_parcels'],
                    scope: 'per_route',
                    rewardMethod: 'fixed_amount',
                    minimumValue: '95',
                },
                redirected: {
                    code: 'redirected_share',
                    name: 'P\u0159\u00edplatek za pod\u00edl p\u0159esm\u011brovan\u00fdch z\u00e1silek',
                    metricType: 'ratio_percentage',
                    numeratorSources: ['redirected_parcels'],
                    denominatorSources: ['loaded_parcels'],
                    scope: 'per_route',
                    rewardMethod: 'fixed_amount',
                    minimumValue: '5',
                },
                custom: {
                    code: 'conditional_surcharge',
                    name: '',
                    metricType: 'ratio_percentage',
                    numeratorSources: ['delivered_parcels'],
                    denominatorSources: ['loaded_parcels'],
                    scope: 'per_route',
                    rewardMethod: 'fixed_amount',
                    minimumValue: '',
                },
            };

            const financeConditionalOptions = (
                options,
                includeEmpty = false
            ) => {
                const empty = includeEmpty
                    ? '<option value="">Vyberte polo\u017eku</option>'
                    : '';

                return empty + options.map(
                    (option) =>
                        `<option value="${option.value}">${option.label}</option>`
                ).join('');
            };

            const financeConditionalSourceCheckboxes = (role) =>
                financeConditionalMetricSources.map(
                    (source) => `
                        <label>
                            <input
                                type="checkbox"
                                value="${source.value}"
                                data-conditional-${role}-source
                            >
                            <span>${source.label}</span>
                        </label>
                    `
                ).join('');

            const financeConditionalUniqueCode = (
                panel,
                requestedCode
            ) => {
                const used = new Set(
                    Array.from(
                        panel.querySelectorAll(
                            '[data-conditional-rule-code]'
                        )
                    ).map((input) => input.value.trim())
                );

                let code = requestedCode;
                let suffix = 2;

                while (used.has(code)) {
                    code = `${requestedCode}_${suffix}`;
                    suffix += 1;
                }

                return code;
            };

            const updateFinanceConditionalEmptyState = (panel) => {
                const empty = panel.querySelector(
                    '[data-conditional-rule-empty]'
                );
                const count = panel.querySelectorAll(
                    '[data-conditional-rule]'
                ).length;

                if (empty) {
                    empty.hidden = count !== 0;
                }
            };

            const addFinanceConditionalBand = (
                rule,
                values = {}
            ) => {
                const list = rule.querySelector(
                    '[data-conditional-band-list]'
                );

                if (!list) {
                    return;
                }

                const band = document.createElement('div');
                band.className = 'drayvia-conditional-band';
                band.dataset.conditionalBand = '1';
                band.innerHTML = `
                    <div class="drayvia-conditional-band-grid">
                        <label class="drayvia-conditional-rule-field">
                            <span>Od hodnoty</span>
                            <input
                                type="number"
                                min="0"
                                step="0.0001"
                                data-conditional-band-minimum
                            >
                        </label>
                        <label class="drayvia-conditional-rule-field">
                            <span>Do hodnoty</span>
                            <input
                                type="number"
                                min="0"
                                step="0.0001"
                                data-conditional-band-maximum
                            >
                        </label>
                        <label class="drayvia-conditional-rule-field">
                            <span>Hodnota p\u0159\u00edplatku</span>
                            <input
                                type="number"
                                min="0"
                                step="0.0001"
                                data-conditional-band-adjustment
                                required
                            >
                        </label>
                        <div class="drayvia-conditional-rule-field">
                            <span>Hranice</span>
                            <label class="drayvia-conditional-band-check">
                                <input
                                    type="checkbox"
                                    data-conditional-band-minimum-inclusive
                                    checked
                                >
                                v\u010detn\u011b minima
                            </label>
                            <label class="drayvia-conditional-band-check">
                                <input
                                    type="checkbox"
                                    data-conditional-band-maximum-inclusive
                                >
                                v\u010detn\u011b maxima
                            </label>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="drayvia-conditional-danger"
                        data-conditional-band-remove
                    >
                        Odebrat p\u00e1smo
                    </button>
                `;

                band.querySelector(
                    '[data-conditional-band-minimum]'
                ).value = values.minimumValue ?? '';
                band.querySelector(
                    '[data-conditional-band-maximum]'
                ).value = values.maximumValue ?? '';
                band.querySelector(
                    '[data-conditional-band-adjustment]'
                ).value = values.adjustmentValue ?? '';

                band.querySelector(
                    '[data-conditional-band-remove]'
                ).addEventListener(
                    'click',
                    () => band.remove()
                );

                list.appendChild(band);
            };

            const syncFinanceConditionalRuleFields = (rule) => {
                const metricType = rule.querySelector(
                    '[data-conditional-rule-metric-type]'
                )?.value;
                const rewardMethod = rule.querySelector(
                    '[data-conditional-rule-reward-method]'
                )?.value;
                const denominatorInputs = Array.from(
                    rule.querySelectorAll(
                        '[data-conditional-denominator-source]'
                    )
                );
                const quantitySource = rule.querySelector(
                    '[data-conditional-rule-reward-quantity]'
                );
                const targetItem = rule.querySelector(
                    '[data-conditional-rule-reward-target]'
                );

                denominatorInputs.forEach((input) => {
                    input.disabled = metricType === 'quantity';

                    if (input.disabled) {
                        input.checked = false;
                    }
                });

                if (quantitySource) {
                    quantitySource.disabled =
                        rewardMethod !== 'amount_per_unit';

                    if (quantitySource.disabled) {
                        quantitySource.value = '';
                    }
                }

                if (targetItem) {
                    targetItem.disabled =
                        rewardMethod !== 'percentage_of_item';

                    if (targetItem.disabled) {
                        targetItem.value = '';
                    }
                }
            };

            const addFinanceConditionalRule = (
                panel,
                presetName = 'custom'
            ) => {
                const list = panel.querySelector(
                    '[data-conditional-rule-list]'
                );
                const preset = financeConditionalRulePresets[presetName]
                    ?? financeConditionalRulePresets.custom;

                if (!list) {
                    return;
                }

                const rule = document.createElement('article');
                rule.className = 'drayvia-conditional-rule';
                rule.dataset.conditionalRule = '1';
                rule.innerHTML = `
                    <div class="drayvia-conditional-rule-header">
                        <strong data-conditional-rule-title>
                            Podm\u00edn\u011bn\u00fd p\u0159\u00edplatek
                        </strong>
                        <button
                            type="button"
                            class="drayvia-conditional-danger"
                            data-conditional-rule-remove
                        >
                            Odebrat p\u0159\u00edplatek
                        </button>
                    </div>

                    <div class="drayvia-conditional-rule-grid">
                        <label class="drayvia-conditional-rule-field">
                            <span>Technick\u00fd k\u00f3d</span>
                            <input
                                type="text"
                                pattern="[a-z][a-z0-9_]{0,63}"
                                maxlength="64"
                                data-conditional-rule-code
                                required
                            >
                        </label>
                        <label class="drayvia-conditional-rule-field">
                            <span>N\u00e1zev p\u0159\u00edplatku</span>
                            <input
                                type="text"
                                maxlength="150"
                                data-conditional-rule-name
                                required
                            >
                        </label>
                        <label class="drayvia-conditional-rule-field">
                            <span>Typ metriky</span>
                            <select data-conditional-rule-metric-type>
                                <option value="ratio_percentage">
                                    Procento
                                </option>
                                <option value="quantity">
                                    Mno\u017estv\u00ed
                                </option>
                            </select>
                        </label>
                        <label class="drayvia-conditional-rule-field">
                            <span>Vyhodnocen\u00ed</span>
                            <select data-conditional-rule-scope>
                                <option value="per_route">
                                    Za danou trasu
                                </option>
                                <option value="monthly_price_list">
                                    M\u011bs\u00ed\u010dn\u011b za faktura\u010dn\u00ed cen\u00edk
                                </option>
                                <option value="monthly_driver">
                                    M\u011bs\u00ed\u010dn\u011b za \u0159idi\u010de (historick\u00e9)
                                </option>
                            </select>
                        </label>
                        <label class="drayvia-conditional-rule-field">
                            <span>Zp\u016fsob p\u0159\u00edplatku</span>
                            <select data-conditional-rule-reward-method>
                                <option value="fixed_amount">
                                    Pevn\u00e1 \u010d\u00e1stka za rozsah
                                </option>
                                <option value="amount_per_unit">
                                    \u010c\u00e1stka \u00d7 vybran\u00e9 mno\u017estv\u00ed
                                </option>
                                <option value="percentage_of_item">
                                    Procento ze z\u00e1kladn\u00ed polo\u017eky
                                </option>
                            </select>
                        </label>
                        <label class="drayvia-conditional-rule-field">
                            <span>N\u00e1sobit mno\u017estv\u00edm</span>
                            <select data-conditional-rule-reward-quantity>
                                ${financeConditionalOptions(
                                    financeConditionalMetricSources,
                                    true
                                )}
                            </select>
                        </label>
                        <label class="drayvia-conditional-rule-field">
                            <span>Procento z polo\u017eky</span>
                            <select data-conditional-rule-reward-target>
                                ${financeConditionalOptions(
                                    financeConditionalBaseItems,
                                    true
                                )}
                            </select>
                        </label>
                        <label class="drayvia-conditional-rule-field drayvia-conditional-rule-field-wide">
                            <span>Popis</span>
                            <textarea
                                rows="2"
                                maxlength="5000"
                                data-conditional-rule-description
                            ></textarea>
                        </label>
                    </div>

                    <div class="drayvia-conditional-rule-grid">
                        <fieldset class="drayvia-conditional-rule-field">
                            <legend>Co vstupuje do \u010ditatele</legend>
                            <div class="drayvia-conditional-source-grid">
                                ${financeConditionalSourceCheckboxes(
                                    'numerator'
                                )}
                            </div>
                        </fieldset>
                        <fieldset class="drayvia-conditional-rule-field">
                            <legend>Co vstupuje do jmenovatele</legend>
                            <div class="drayvia-conditional-source-grid">
                                ${financeConditionalSourceCheckboxes(
                                    'denominator'
                                )}
                            </div>
                        </fieldset>
                    </div>

                    <section class="drayvia-conditional-bands">
                        <div class="drayvia-conditional-band-header">
                            <div>
                                <strong>Prahy a ceny p\u0159\u00edplatku</strong>
                                <div>
                                    Pro procentn\u00ed metriku zad\u00e1vejte hranice v %.
                                </div>
                            </div>
                            <button
                                type="button"
                                data-conditional-band-add
                            >
                                P\u0159idat dal\u0161\u00ed p\u00e1smo
                            </button>
                        </div>
                        <div data-conditional-band-list></div>
                    </section>
                `;

                const code = financeConditionalUniqueCode(
                    panel,
                    preset.code
                );
                const codeInput = rule.querySelector(
                    '[data-conditional-rule-code]'
                );
                const nameInput = rule.querySelector(
                    '[data-conditional-rule-name]'
                );
                const title = rule.querySelector(
                    '[data-conditional-rule-title]'
                );
                const metricType = rule.querySelector(
                    '[data-conditional-rule-metric-type]'
                );
                const scope = rule.querySelector(
                    '[data-conditional-rule-scope]'
                );
                const rewardMethod = rule.querySelector(
                    '[data-conditional-rule-reward-method]'
                );

                codeInput.value = code;
                nameInput.value = preset.name;
                title.textContent = preset.name
                    || 'Vlastn\u00ed podm\u00edn\u011bn\u00fd p\u0159\u00edplatek';
                metricType.value = preset.metricType;
                scope.value = preset.scope;
                rewardMethod.value = preset.rewardMethod;

                rule.querySelectorAll(
                    '[data-conditional-numerator-source]'
                ).forEach((input) => {
                    input.checked = preset.numeratorSources.includes(
                        input.value
                    );
                });
                rule.querySelectorAll(
                    '[data-conditional-denominator-source]'
                ).forEach((input) => {
                    input.checked = preset.denominatorSources.includes(
                        input.value
                    );
                });

                nameInput.addEventListener('input', () => {
                    title.textContent = nameInput.value.trim()
                        || 'Vlastn\u00ed podm\u00edn\u011bn\u00fd p\u0159\u00edplatek';
                });
                metricType.addEventListener(
                    'change',
                    () => syncFinanceConditionalRuleFields(rule)
                );
                rewardMethod.addEventListener(
                    'change',
                    () => syncFinanceConditionalRuleFields(rule)
                );
                rule.querySelector(
                    '[data-conditional-rule-remove]'
                ).addEventListener('click', () => {
                    rule.remove();
                    updateFinanceConditionalEmptyState(panel);
                });
                rule.querySelector(
                    '[data-conditional-band-add]'
                ).addEventListener(
                    'click',
                    () => addFinanceConditionalBand(rule)
                );

                list.appendChild(rule);
                addFinanceConditionalBand(rule, {
                    minimumValue: preset.minimumValue,
                });
                syncFinanceConditionalRuleFields(rule);
                updateFinanceConditionalEmptyState(panel);
            };

            const resetFinanceConditionalRules = (panel) => {
                const list = panel.querySelector(
                    '[data-conditional-rule-list]'
                );

                if (!list) {
                    return;
                }

                list.replaceChildren();
                addFinanceConditionalRule(panel, 'quality');
                addFinanceConditionalRule(panel, 'redirected');
            };

            const collectFinanceConditionalRules = (panel) => {
                const codes = new Set();

                return Array.from(
                    panel.querySelectorAll('[data-conditional-rule]')
                ).map((rule, ruleIndex) => {
                    const number = ruleIndex + 1;
                    const code = rule.querySelector(
                        '[data-conditional-rule-code]'
                    ).value.trim();
                    const name = rule.querySelector(
                        '[data-conditional-rule-name]'
                    ).value.trim();
                    const description = rule.querySelector(
                        '[data-conditional-rule-description]'
                    ).value.trim();
                    const metricType = rule.querySelector(
                        '[data-conditional-rule-metric-type]'
                    ).value;
                    const evaluationScope = rule.querySelector(
                        '[data-conditional-rule-scope]'
                    ).value;
                    const rewardMethod = rule.querySelector(
                        '[data-conditional-rule-reward-method]'
                    ).value;
                    const rewardQuantitySource = rule.querySelector(
                        '[data-conditional-rule-reward-quantity]'
                    ).value || null;
                    const rewardTargetItemCode = rule.querySelector(
                        '[data-conditional-rule-reward-target]'
                    ).value || null;
                    const numeratorSources = Array.from(
                        rule.querySelectorAll(
                            '[data-conditional-numerator-source]:checked'
                        )
                    ).map((input) => input.value);
                    const denominatorSources = Array.from(
                        rule.querySelectorAll(
                            '[data-conditional-denominator-source]:checked'
                        )
                    ).map((input) => input.value);

                    if (!/^[a-z][a-z0-9_]{0,63}$/.test(code)) {
                        throw new Error(
                            `P\u0159\u00edplatek ${number}: technick\u00fd k\u00f3d nen\u00ed platn\u00fd.`
                        );
                    }

                    if (codes.has(code)) {
                        throw new Error(
                            `P\u0159\u00edplatek ${number}: technick\u00fd k\u00f3d se opakuje.`
                        );
                    }

                    codes.add(code);

                    if (name === '') {
                        throw new Error(
                            `P\u0159\u00edplatek ${number}: dopl\u0148te n\u00e1zev.`
                        );
                    }

                    if (numeratorSources.length === 0) {
                        throw new Error(
                            `P\u0159\u00edplatek ${number}: vyberte alespo\u0148 jednu polo\u017eku \u010ditatele.`
                        );
                    }

                    if (
                        metricType === 'ratio_percentage'
                        && denominatorSources.length === 0
                    ) {
                        throw new Error(
                            `P\u0159\u00edplatek ${number}: procento vy\u017eaduje alespo\u0148 jednu polo\u017eku jmenovatele.`
                        );
                    }

                    if (
                        rewardMethod === 'amount_per_unit'
                        && rewardQuantitySource === null
                    ) {
                        throw new Error(
                            `P\u0159\u00edplatek ${number}: vyberte n\u00e1soben\u00e9 mno\u017estv\u00ed.`
                        );
                    }

                    if (
                        rewardMethod === 'percentage_of_item'
                        && rewardTargetItemCode === null
                    ) {
                        throw new Error(
                            `P\u0159\u00edplatek ${number}: vyberte z\u00e1kladn\u00ed polo\u017eku.`
                        );
                    }

                    const bands = Array.from(
                        rule.querySelectorAll('[data-conditional-band]')
                    ).map((band, bandIndex) => {
                        const minimum = band.querySelector(
                            '[data-conditional-band-minimum]'
                        ).value.trim();
                        const maximum = band.querySelector(
                            '[data-conditional-band-maximum]'
                        ).value.trim();
                        const adjustment = band.querySelector(
                            '[data-conditional-band-adjustment]'
                        ).value.trim();

                        if (minimum === '' && maximum === '') {
                            throw new Error(
                                `P\u0159\u00edplatek ${number}, p\u00e1smo ${bandIndex + 1}: zadejte alespo\u0148 jednu hranici.`
                            );
                        }

                        if (
                            [minimum, maximum].some(
                                (boundary) =>
                                    boundary !== ''
                                    && (
                                        !Number.isFinite(Number(boundary))
                                        || Number(boundary) < 0
                                    )
                            )
                        ) {
                            throw new Error(
                                `P\u0159\u00edplatek ${number}, p\u00e1smo ${bandIndex + 1}: hranice mus\u00ed b\u00fdt nez\u00e1porn\u00e1 \u010d\u00edsla.`
                            );
                        }

                        if (
                            adjustment === ''
                            || !Number.isFinite(Number(adjustment))
                            || Number(adjustment) < 0
                        ) {
                            throw new Error(
                                `P\u0159\u00edplatek ${number}, p\u00e1smo ${bandIndex + 1}: hodnota mus\u00ed b\u00fdt nez\u00e1porn\u00e1.`
                            );
                        }

                        if (
                            minimum !== ''
                            && maximum !== ''
                            && Number(minimum) > Number(maximum)
                        ) {
                            throw new Error(
                                `P\u0159\u00edplatek ${number}, p\u00e1smo ${bandIndex + 1}: minimum je vy\u0161\u0161\u00ed ne\u017e maximum.`
                            );
                        }

                        return {
                            minimum_value: minimum || null,
                            maximum_value: maximum || null,
                            minimum_inclusive: band.querySelector(
                                '[data-conditional-band-minimum-inclusive]'
                            ).checked,
                            maximum_inclusive: band.querySelector(
                                '[data-conditional-band-maximum-inclusive]'
                            ).checked,
                            adjustment_value: adjustment,
                        };
                    });

                    if (bands.length === 0) {
                        throw new Error(
                            `P\u0159\u00edplatek ${number}: p\u0159idejte alespo\u0148 jedno p\u00e1smo.`
                        );
                    }

                    return {
                        code,
                        name,
                        description: description || null,
                        metric_type: metricType,
                        metric_numerator_sources: numeratorSources,
                        metric_denominator_sources: denominatorSources,
                        evaluation_scope: evaluationScope,
                        reward_method: rewardMethod,
                        reward_quantity_source:
                            rewardMethod === 'amount_per_unit'
                                ? rewardQuantitySource
                                : null,
                        reward_target_item_code:
                            rewardMethod === 'percentage_of_item'
                                ? rewardTargetItemCode
                                : null,
                        rounding_scale: 2,
                        bands,
                    };
                });
            };

            /*
             * S024-03A BILLING PRICE-LIST ADMINISTRATION DATA
             *
             * Customer relationships remain the source of customer identity.
             * Public price-list UUIDs remain the only identifiers used by UI.
             */
            let financeBillingPriceLists = [];
            let financeBillingPriceListFilter = 'all';

            const financeBillingPriceListCategory = (priceList) => {
                if (priceList?.status === 'active') {
                    return 'current';
                }

                if (
                    priceList?.status === 'draft'
                    || priceList?.status === 'approved'
                ) {
                    return 'draft';
                }

                return 'history';
            };

            const financeBillingPriceListPeriod = (version) => {
                if (!version) {
                    return '\u2014';
                }

                return `${
                    financeCustomerDate(version.valid_from)
                } \u2013 ${
                    financeCustomerDate(version.valid_until)
                }`;
            };

            const financeBillingPriceListCell = (value) => {
                const cell = document.createElement('td');

                cell.textContent = String(value ?? '\u2014');

                return cell;
            };

            const financeBillingCurrentVersion = (record) => {
                const versions = Array.isArray(record?.versions)
                    ? record.versions
                    : [];

                return versions.find(
                    (version) =>
                        Number(version?.version_number)
                        === Number(record?.current_version)
                ) || versions[0] || null;
            };

            const renderFinanceBillingPriceListIndex = () => {
                const root = document.querySelector(
                    '[data-finance-root]'
                );

                const list = root?.querySelector(
                    '[data-billing-price-list-admin-list]'
                );

                if (!root || !list) {
                    return;
                }

                const counts = {
                    all: financeBillingPriceLists.length,
                    current: 0,
                    draft: 0,
                    history: 0,
                };

                financeBillingPriceLists.forEach((record) => {
                    counts[
                        financeBillingPriceListCategory(record)
                    ] += 1;
                });

                Object.entries(counts).forEach(
                    ([category, count]) => {
                        const output = root.querySelector(
                            `[data-billing-price-list-count="${category}"]`
                        );

                        if (output) {
                            output.textContent = String(count);
                        }
                    }
                );

                root.querySelectorAll(
                    '[data-billing-price-list-filter]'
                ).forEach((button) => {
                    button.classList.toggle(
                        'is-active',
                        button.dataset.billingPriceListFilter
                            === financeBillingPriceListFilter
                    );
                });

                const records = financeBillingPriceLists.filter(
                    (record) =>
                        financeBillingPriceListFilter === 'all'
                        || financeBillingPriceListCategory(record)
                            === financeBillingPriceListFilter
                );

                list.replaceChildren();

                if (records.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');

                    cell.colSpan = 6;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent =
                        financeBillingPriceLists.length === 0
                            ? 'Zat\u00edm nen\u00ed evidov\u00e1n \u017e\u00e1dn\u00fd faktura\u010dn\u00ed cen\u00edk.'
                            : 'Vybran\u00e9mu filtru neodpov\u00edd\u00e1 \u017e\u00e1dn\u00fd cen\u00edk.';

                    row.appendChild(cell);
                    list.appendChild(row);

                    return;
                }

                records.forEach((record) => {
                    const version =
                        financeBillingCurrentVersion(record);

                    const row = document.createElement('tr');

                    row.append(
                        financeBillingPriceListCell(
                            record.customer_name
                        ),
                        financeBillingPriceListCell(
                            record.name
                        ),
                        financeBillingPriceListCell(
                            financeBillingPriceListPeriod(version)
                        ),
                        financeBillingPriceListCell(
                            financeCustomerStatus(record.status)
                        ),
                        financeBillingPriceListCell(
                            record.current_version
                        )
                    );

                    const actionCell =
                        document.createElement('td');

                    const detailButton =
                        document.createElement('button');

                    detailButton.type = 'button';
                    detailButton.className =
                        'drayvia-price-admin-secondary';
                    detailButton.textContent = 'Otev\u0159\u00edt';

                    detailButton.addEventListener(
                        'click',
                        () => {
                            renderFinanceBillingPriceListDetail(
                                record
                            );
                        }
                    );

                    actionCell.appendChild(detailButton);
                    row.appendChild(actionCell);
                    list.appendChild(row);
                });
            };

            const financeBillingDetailTable = (
                titleText,
                headers,
                rows
            ) => {
                const section = document.createElement('section');
                const title = document.createElement('h5');
                const wrapper = document.createElement('div');
                const table = document.createElement('table');
                const head = document.createElement('thead');
                const headRow = document.createElement('tr');
                const body = document.createElement('tbody');

                title.textContent = titleText;
                wrapper.className =
                    'drayvia-price-admin-table-wrap';
                table.className =
                    'drayvia-price-admin-table';

                headers.forEach((header) => {
                    const cell = document.createElement('th');

                    cell.textContent = header;
                    headRow.appendChild(cell);
                });

                head.appendChild(headRow);
                table.appendChild(head);

                if (rows.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');

                    cell.colSpan = headers.length;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent = '\u017d\u00e1dn\u00e9 z\u00e1znamy.';

                    row.appendChild(cell);
                    body.appendChild(row);
                }
                else {
                    rows.forEach((values) => {
                        const row = document.createElement('tr');

                        values.forEach((value) => {
                            row.appendChild(
                                financeBillingPriceListCell(value)
                            );
                        });

                        body.appendChild(row);
                    });
                }

                table.appendChild(body);
                wrapper.appendChild(table);
                section.append(title, wrapper);

                return section;
            };

            /*
             * S024-04B BILLING DRAFT EDITOR
             *
             * Draft editing sends the complete version tree through one
             * optimistic-locking PUT request. Immutable versions remain
             * read-only.
             */
            const financeBillingMoney = (
                value,
                currency = 'CZK'
            ) => {
                const amount = Number(value);

                if (!Number.isFinite(amount)) {
                    return '\u2014';
                }

                return new Intl.NumberFormat('cs-CZ', {
                    style: 'currency',
                    currency: currency || 'CZK',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                }).format(amount);
            };

            const financeBillingNumber = (value) => {
                const number = Number(value);

                if (!Number.isFinite(number)) {
                    return '\u2014';
                }

                return new Intl.NumberFormat('cs-CZ', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 4,
                }).format(number);
            };

            const financeBillingItemLabel = (code) => ({
                delivered_parcels:
                    'Doru\u010den\u00e1 z\u00e1silka',
                redirected_parcels:
                    'P\u0159esm\u011brovan\u00e1 z\u00e1silka',
                undelivered_parcels:
                    'Odm\u00edtnuto z\u00e1kazn\u00edkem',
                actual_km:
                    'Skute\u010dn\u00fd kilometr',
            }[code] || code || '\u2014');

            const financeBillingUnitLabel = (unit) => ({
                parcel: 'z\u00e1silka',
                km: 'km',
            }[unit] || unit || '\u2014');

            const financeBillingMetricSourceLabel = (source) => {
                const option = financeConditionalMetricSources.find(
                    (item) => item.value === source
                );

                return option?.label || source || '\u2014';
            };

            const financeBillingEvaluationScopeLabel = (scope) => ({
                per_route:
                    'Za danou trasu',
                monthly_price_list:
                    'M\u011bs\u00ed\u010dn\u011b za faktura\u010dn\u00ed cen\u00edk',
                monthly_driver:
                    'M\u011bs\u00ed\u010dn\u011b za \u0159idi\u010de',
            }[scope] || scope || '\u2014');

            const financeBillingRewardLabel = (rule) => {
                if (rule?.reward_method === 'fixed_amount') {
                    return 'Pevn\u00e1 \u010d\u00e1stka za spln\u011bn\u00e9 p\u00e1smo';
                }

                if (rule?.reward_method === 'amount_per_unit') {
                    return `\u010c\u00e1stka \u00d7 ${
                        financeBillingMetricSourceLabel(
                            rule?.reward_quantity_source
                        )
                    }`;
                }

                if (rule?.reward_method === 'percentage_of_item') {
                    return `Procento z ${
                        financeBillingItemLabel(
                            rule?.reward_target_item_code
                        )
                    }`;
                }

                return rule?.reward_method || '\u2014';
            };

            const financeBillingRuleFormula = (rule) => {
                const numerator = Array.isArray(
                    rule?.metric_numerator_sources
                )
                    ? rule.metric_numerator_sources.map(
                        financeBillingMetricSourceLabel
                    )
                    : [];

                const denominator = Array.isArray(
                    rule?.metric_denominator_sources
                )
                    ? rule.metric_denominator_sources.map(
                        financeBillingMetricSourceLabel
                    )
                    : [];

                const numeratorText =
                    numerator.join(' + ') || '\u2014';

                if (rule?.metric_type === 'quantity') {
                    return numeratorText;
                }

                return `(${numeratorText}) / (${
                    denominator.join(' + ') || '\u2014'
                }) \u00d7 100 %`;
            };

            const financeBillingBandLabel = (
                rule,
                band,
                currency
            ) => {
                const percentage =
                    rule?.metric_type === 'ratio_percentage';

                const boundary = (
                    value,
                    inclusive,
                    emptyText
                ) => {
                    if (
                        value === null
                        || value === undefined
                        || value === ''
                    ) {
                        return emptyText;
                    }

                    return `${
                        financeBillingNumber(value)
                    }${percentage ? ' %' : ''}${
                        inclusive ? ' v\u010detn\u011b' : ''
                    }`;
                };

                const adjustment =
                    rule?.reward_method === 'percentage_of_item'
                        ? `${financeBillingNumber(
                            band?.adjustment_value
                        )} %`
                        : financeBillingMoney(
                            band?.adjustment_value,
                            currency
                        );

                return `${
                    boundary(
                        band?.minimum_value,
                        band?.minimum_inclusive,
                        'bez minima'
                    )
                } a\u017e ${
                    boundary(
                        band?.maximum_value,
                        band?.maximum_inclusive,
                        'bez maxima'
                    )
                } \u2192 ${adjustment}`;
            };

            const financeBillingAdminNotice = (
                detail,
                text,
                state = 'success'
            ) => {
                const notice = document.createElement('p');

                notice.className = 'drayvia-price-admin-message';
                notice.dataset.state = state;
                notice.textContent = text;

                detail.prepend(notice);
            };

            const hydrateFinanceConditionalRule = (
                panel,
                value
            ) => {
                addFinanceConditionalRule(panel, 'custom');

                const rules = panel.querySelectorAll(
                    '[data-conditional-rule]'
                );

                const rule = rules.item(rules.length - 1);

                if (!rule) {
                    return;
                }

                const setValue = (selector, fieldValue) => {
                    const input = rule.querySelector(selector);

                    if (input) {
                        input.value = fieldValue ?? '';
                    }
                };

                setValue(
                    '[data-conditional-rule-code]',
                    value?.code
                );

                setValue(
                    '[data-conditional-rule-name]',
                    value?.name
                );

                setValue(
                    '[data-conditional-rule-description]',
                    value?.description
                );

                setValue(
                    '[data-conditional-rule-metric-type]',
                    value?.metric_type
                );

                setValue(
                    '[data-conditional-rule-scope]',
                    value?.evaluation_scope
                );

                setValue(
                    '[data-conditional-rule-reward-method]',
                    value?.reward_method
                );

                syncFinanceConditionalRuleFields(rule);

                setValue(
                    '[data-conditional-rule-reward-quantity]',
                    value?.reward_quantity_source
                );

                setValue(
                    '[data-conditional-rule-reward-target]',
                    value?.reward_target_item_code
                );

                const numerator = Array.isArray(
                    value?.metric_numerator_sources
                )
                    ? value.metric_numerator_sources
                    : [];

                const denominator = Array.isArray(
                    value?.metric_denominator_sources
                )
                    ? value.metric_denominator_sources
                    : [];

                rule.querySelectorAll(
                    '[data-conditional-numerator-source]'
                ).forEach((input) => {
                    input.checked = numerator.includes(input.value);
                });

                rule.querySelectorAll(
                    '[data-conditional-denominator-source]'
                ).forEach((input) => {
                    input.checked = denominator.includes(input.value);
                });

                const title = rule.querySelector(
                    '[data-conditional-rule-title]'
                );

                if (title) {
                    title.textContent =
                        value?.name
                        || 'Vlastn\u00ed podm\u00edn\u011bn\u00fd p\u0159\u00edplatek';
                }

                const bandList = rule.querySelector(
                    '[data-conditional-band-list]'
                );

                bandList?.replaceChildren();

                const bands = Array.isArray(value?.bands)
                    ? value.bands
                    : [];

                bands.forEach((bandValue) => {
                    addFinanceConditionalBand(rule, {
                        minimumValue:
                            bandValue?.minimum_value ?? '',
                        maximumValue:
                            bandValue?.maximum_value ?? '',
                        adjustmentValue:
                            bandValue?.adjustment_value ?? '',
                    });

                    const bandElements = rule.querySelectorAll(
                        '[data-conditional-band]'
                    );

                    const band = bandElements.item(
                        bandElements.length - 1
                    );

                    const minimumInclusive = band?.querySelector(
                        '[data-conditional-band-minimum-inclusive]'
                    );

                    const maximumInclusive = band?.querySelector(
                        '[data-conditional-band-maximum-inclusive]'
                    );

                    if (minimumInclusive) {
                        minimumInclusive.checked =
                            Boolean(
                                bandValue?.minimum_inclusive
                            );
                    }

                    if (maximumInclusive) {
                        maximumInclusive.checked =
                            Boolean(
                                bandValue?.maximum_inclusive
                            );
                    }
                });

                if (bands.length === 0) {
                    addFinanceConditionalBand(rule);
                }

                syncFinanceConditionalRuleFields(rule);
                updateFinanceConditionalEmptyState(panel);
            };

            const renderFinanceBillingPriceListDetail = (record) => {
                const root = document.querySelector(
                    '[data-finance-root]'
                );

                const detail = root?.querySelector(
                    '[data-billing-price-list-admin-detail]'
                );

                if (!detail) {
                    return;
                }

                const current =
                    financeBillingCurrentVersion(record);

                const header = document.createElement('div');
                const heading = document.createElement('div');
                const title = document.createElement('h4');
                const meta = document.createElement('p');
                const actions = document.createElement('div');
                const close = document.createElement('button');

                header.className =
                    'drayvia-price-admin-detail-header';

                heading.className =
                    'drayvia-price-admin-detail-heading';

                actions.className =
                    'drayvia-price-admin-detail-actions';

                title.textContent =
                    record?.name || 'Detail cen\u00edku';

                meta.textContent = [
                    record?.customer_name || '\u2014',
                    record?.code || '\u2014',
                    financeCustomerStatus(record?.status),
                    financeBillingPriceListPeriod(current),
                ].join(' \u00b7 ');

                close.type = 'button';
                close.className =
                    'drayvia-price-admin-secondary';
                close.textContent = 'Zav\u0159\u00edt detail';

                close.addEventListener('click', () => {
                    detail.hidden = true;
                    detail.replaceChildren();
                });

                if (
                    record?.status === 'draft'
                    && current?.status === 'draft'
                ) {
                    const edit = document.createElement('button');

                    edit.type = 'button';
                    edit.className =
                        'drayvia-price-admin-primary';
                    edit.textContent = 'Upravit koncept';

                    edit.addEventListener('click', () => {
                        renderFinanceBillingPriceListEditor(
                            record
                        );
                    });

                    actions.appendChild(edit);
                }

                actions.appendChild(close);
                heading.append(title, meta);
                header.append(heading, actions);

                const items = Array.isArray(current?.items)
                    ? current.items
                    : [];

                const rules = Array.isArray(
                    current?.conditional_rules
                )
                    ? current.conditional_rules
                    : [];

                const versions = Array.isArray(record?.versions)
                    ? record.versions
                    : [];

                const itemRows = items.map((item) => [
                    item?.description
                        || financeBillingItemLabel(item?.code),
                    financeBillingUnitLabel(item?.unit),
                    financeBillingMoney(
                        item?.unit_rate,
                        item?.currency || record?.currency
                    ),
                ]);

                const ruleRows = rules.map((rule) => [
                    rule?.name || rule?.code,
                    financeBillingRuleFormula(rule),
                    financeBillingEvaluationScopeLabel(
                        rule?.evaluation_scope
                    ),
                    financeBillingRewardLabel(rule),
                    (
                        Array.isArray(rule?.bands)
                            ? rule.bands
                            : []
                    ).map(
                        (band) => financeBillingBandLabel(
                            rule,
                            band,
                            record?.currency
                        )
                    ).join('; ') || '\u2014',
                ]);

                const versionRows = versions.map((version) => [
                    version?.version_number,
                    financeCustomerStatus(version?.status),
                    financeBillingPriceListPeriod(version),
                    version?.lock_version,
                ]);

                const children = [header];

                if (record?.description) {
                    const description =
                        document.createElement('p');

                    description.className =
                        'drayvia-finance-note';

                    description.textContent =
                        record.description;

                    children.push(description);
                }

                if (record?.version_error) {
                    const error = document.createElement('p');

                    error.className =
                        'drayvia-price-admin-message';

                    error.dataset.state = 'error';
                    error.textContent = record.version_error;
                    children.push(error);
                }

                children.push(
                    financeBillingDetailTable(
                        'Z\u00e1kladn\u00ed sazby',
                        [
                            'Polo\u017eka',
                            'Jednotka',
                            'Sazba',
                        ],
                        itemRows
                    ),
                    financeBillingDetailTable(
                        'Podm\u00edn\u011bn\u00e9 p\u0159\u00edplatky',
                        [
                            'P\u0159\u00edplatek',
                            'Vzorec',
                            'Vyhodnocen\u00ed',
                            'Zp\u016fsob',
                            'P\u00e1sma a ceny',
                        ],
                        ruleRows
                    ),
                    financeBillingDetailTable(
                        'Historie verz\u00ed',
                        [
                            'Verze',
                            'Stav',
                            'Platnost',
                            'Revize',
                        ],
                        versionRows
                    )
                );

                detail.replaceChildren(...children);
                detail.hidden = false;

                detail.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            };

            const renderFinanceBillingPriceListEditor = (record) => {
                const root = document.querySelector(
                    '[data-finance-root]'
                );

                const detail = root?.querySelector(
                    '[data-billing-price-list-admin-detail]'
                );

                const template = root?.querySelector(
                    '[data-billing-price-list-create-card]'
                );

                const current =
                    financeBillingCurrentVersion(record);

                if (
                    !root
                    || !detail
                    || !template
                    || record?.status !== 'draft'
                    || current?.status !== 'draft'
                ) {
                    return;
                }

                const editor = template.cloneNode(true);

                editor.hidden = false;
                editor.classList.add(
                    'drayvia-price-admin-editor'
                );

                editor.removeAttribute(
                    'data-billing-price-list-create-card'
                );

                editor.removeAttribute(
                    'data-provider-managed-price-list-endpoint'
                );

                editor.dataset.billingPriceListEditor = '1';

                const title = editor.querySelector('h4');
                const customer = editor.querySelector(
                    '[data-billing-price-list-customer]'
                );
                const name = editor.querySelector(
                    '[data-billing-price-list-name]'
                );
                const currency = editor.querySelector(
                    '[data-billing-price-list-currency]'
                );
                const validFrom = editor.querySelector(
                    '[data-billing-price-list-valid-from]'
                );
                const validUntil = editor.querySelector(
                    '[data-billing-price-list-valid-until]'
                );
                const save = editor.querySelector(
                    '[data-billing-price-list-save]'
                );
                const cancel = editor.querySelector(
                    '[data-billing-price-list-create-close]'
                );
                const message = editor.querySelector(
                    '[data-billing-price-list-message]'
                );
                const addRule = editor.querySelector(
                    '[data-conditional-rule-add]'
                );
                const preset = editor.querySelector(
                    '[data-conditional-rule-preset]'
                );
                const conditionalList = editor.querySelector(
                    '[data-conditional-rule-list]'
                );
                const rateInputs = Array.from(
                    editor.querySelectorAll(
                        '[data-price-list-rate]'
                    )
                );

                if (
                    !name
                    || !currency
                    || !validFrom
                    || !validUntil
                    || !save
                    || !cancel
                    || !message
                    || !addRule
                    || !preset
                    || !conditionalList
                    || rateInputs.length !== 4
                ) {
                    renderFinanceBillingPriceListDetail(record);
                    financeBillingAdminNotice(
                        detail,
                        'Editor konceptu se nepoda\u0159ilo p\u0159ipravit.',
                        'error'
                    );
                    return;
                }

                if (title) {
                    title.textContent =
                        `Upravit koncept: ${record?.name || ''}`;
                }

                const customerField = customer?.closest(
                    '.drayvia-finance-field'
                );

                if (customerField) {
                    customerField.hidden = true;
                }

                if (customer) {
                    customer.disabled = true;
                }

                name.value = record?.name || '';
                currency.value = record?.currency || 'CZK';
                currency.disabled = true;
                validFrom.value = current?.valid_from || '';
                validUntil.value = current?.valid_until || '';

                save.textContent = 'Ulo\u017eit zm\u011bny konceptu';
                cancel.textContent = 'Zru\u0161it \u00fapravy';

                const notes = editor.querySelectorAll(
                    '.drayvia-finance-note'
                );

                const note = notes.item(notes.length - 1);

                if (note) {
                    note.textContent =
                        'Ulo\u017een\u00ed atomicky nahrad\u00ed cel\u00fd koncept v\u010detn\u011b sazeb a p\u0159\u00edplatk\u016f. Aktivn\u00ed ani historick\u00e9 verze nelze upravovat.';
                }

                const items = Array.isArray(current?.items)
                    ? current.items
                    : [];

                const itemMap = new Map(
                    items.map((item) => [item?.code, item])
                );

                rateInputs.forEach((input) => {
                    const item = itemMap.get(
                        input.dataset.priceListRate
                    );

                    input.value = item?.unit_rate ?? '';
                });

                conditionalList.replaceChildren();

                const rules = Array.isArray(
                    current?.conditional_rules
                )
                    ? current.conditional_rules
                    : [];

                rules.forEach((rule) => {
                    hydrateFinanceConditionalRule(
                        editor,
                        rule
                    );
                });

                updateFinanceConditionalEmptyState(editor);

                addRule.addEventListener('click', () => {
                    addFinanceConditionalRule(
                        editor,
                        preset.value
                    );
                });

                cancel.addEventListener('click', () => {
                    renderFinanceBillingPriceListDetail(record);
                });

                save.addEventListener('click', async () => {
                    message.hidden = true;
                    message.dataset.state = '';

                    const normalizedName = name.value.trim();

                    if (normalizedName === '') {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Dopl\u0148te n\u00e1zev cen\u00edku.';
                        return;
                    }

                    if (validFrom.value === '') {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Dopl\u0148te datum platnosti od.';
                        return;
                    }

                    if (
                        validUntil.value !== ''
                        && validUntil.value < validFrom.value
                    ) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Platnost do nesm\u00ed b\u00fdt p\u0159ed platnost\u00ed od.';
                        return;
                    }

                    const itemDescriptions = {
                        delivered_parcels:
                            'Doru\u010den\u00e1 z\u00e1silka',
                        redirected_parcels:
                            'P\u0159esm\u011brovan\u00e1 z\u00e1silka',
                        undelivered_parcels:
                            'Odm\u00edtnuto z\u00e1kazn\u00edkem',
                        actual_km:
                            'Skute\u010dn\u00fd kilometr',
                    };

                    const canonicalCodes = [
                        'delivered_parcels',
                        'redirected_parcels',
                        'undelivered_parcels',
                        'actual_km',
                    ];

                    const rateMap = new Map(
                        rateInputs.map((input) => [
                            input.dataset.priceListRate,
                            input,
                        ])
                    );

                    const updatedItems = canonicalCodes.map(
                        (code) => {
                            const input = rateMap.get(code);
                            const existing = itemMap.get(code);

                            return {
                                code,
                                description:
                                    existing?.description
                                    || itemDescriptions[code],
                                unit_rate:
                                    input?.value?.trim() || '',
                            };
                        }
                    );

                    if (
                        updatedItems.some(
                            (item) =>
                                item.unit_rate === ''
                                || !Number.isFinite(
                                    Number(item.unit_rate)
                                )
                                || Number(item.unit_rate) < 0
                        )
                    ) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Vypl\u0148te v\u0161echny \u010dty\u0159i nez\u00e1porn\u00e9 sazby.';
                        return;
                    }

                    let conditionalRules;

                    try {
                        conditionalRules =
                            collectFinanceConditionalRules(
                                editor
                            );
                    }
                    catch (error) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent = error.message;
                        return;
                    }

                    const endpoint =
                        `/api/v1/price-lists/${
                            encodeURIComponent(
                                record.public_id
                            )
                        }/versions/${
                            encodeURIComponent(
                                String(current.version_number)
                            )
                        }`;

                    save.disabled = true;
                    cancel.disabled = true;
                    message.hidden = false;
                    message.dataset.state = '';
                    message.textContent =
                        'Ukl\u00e1d\u00e1m cel\u00fd koncept\u2026';

                    try {
                        await api(endpoint, {
                            method: 'PUT',
                            body: JSON.stringify({
                                name: normalizedName,
                                description:
                                    record?.description || null,
                                expected_lock_version:
                                    Number(current.lock_version),
                                valid_from: validFrom.value,
                                valid_until:
                                    validUntil.value || null,
                                change_reason:
                                    'Ru\u010dn\u00ed \u00faprava faktura\u010dn\u00edho cen\u00edku p\u0159es Finance UI.',
                                items: updatedItems,
                                conditional_rules:
                                    conditionalRules,
                            }),
                        });

                        const nextRevision =
                            Number(current.lock_version) + 1;

                        await loadFinanceCustomers();

                        const refreshed =
                            financeBillingPriceLists.find(
                                (priceList) =>
                                    priceList.public_id
                                    === record.public_id
                            );

                        if (refreshed) {
                            renderFinanceBillingPriceListDetail(
                                refreshed
                            );

                            financeBillingAdminNotice(
                                detail,
                                `Koncept byl ulo\u017een. Aktu\u00e1ln\u00ed revize: ${nextRevision}.`
                            );
                        }
                    }
                    catch (error) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            `Koncept se nepoda\u0159ilo ulo\u017eit: ${error.message}`;
                    }
                    finally {
                        save.disabled = false;
                        cancel.disabled = false;
                    }
                });

                detail.replaceChildren(editor);
                detail.hidden = false;

                detail.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            };
            const loadFinanceBillingPriceLists = async (
                customers
            ) => {
                const root = document.querySelector(
                    '[data-finance-root]'
                );

                const list = root?.querySelector(
                    '[data-billing-price-list-admin-list]'
                );

                if (!root || !list) {
                    return;
                }

                const loadingRow =
                    document.createElement('tr');

                const loadingCell =
                    document.createElement('td');

                loadingCell.colSpan = 6;
                loadingCell.className =
                    'drayvia-price-admin-empty';
                loadingCell.textContent =
                    'Na\u010d\u00edt\u00e1m faktura\u010dn\u00ed cen\u00edky\u2026';

                loadingRow.appendChild(loadingCell);
                list.replaceChildren(loadingRow);

                const records = [];

                customers.forEach((customerRecord) => {
                    const customer =
                        customerRecord?.customer || {};

                    const priceLists = Array.isArray(
                        customerRecord?.price_lists
                    )
                        ? customerRecord.price_lists
                        : [];

                    priceLists.forEach((priceList) => {
                        records.push({
                            ...priceList,
                            customer_name:
                                customer.name || '\u2014',
                            relationship_id:
                                customerRecord.relationship_id,
                        });
                    });
                });

                financeBillingPriceLists =
                    await Promise.all(
                        records.map(async (record) => {
                            if (!record?.public_id) {
                                return {
                                    ...record,
                                    versions: [],
                                    version_error:
                                        'Chyb\u00ed ve\u0159ejn\u00fd identifik\u00e1tor.',
                                };
                            }

                            try {
                                const body = await api(
                                    `/api/v1/price-lists/${
                                        encodeURIComponent(
                                            record.public_id
                                        )
                                    }/versions`
                                );

                                const payload =
                                    getPayload(body);

                                const versions =
                                    Array.isArray(payload?.items)
                                        ? payload.items
                                        : (
                                            Array.isArray(payload)
                                                ? payload
                                                : []
                                        );

                                return {
                                    ...record,
                                    versions,
                                    version_error: null,
                                };
                            }
                            catch (error) {
                                return {
                                    ...record,
                                    versions: [],
                                    version_error: error.message,
                                };
                            }
                        })
                    );

                financeBillingPriceLists.sort(
                    (left, right) => {
                        const leftVersion =
                            financeBillingCurrentVersion(left);

                        const rightVersion =
                            financeBillingCurrentVersion(right);

                        return String(
                            rightVersion?.valid_from || ''
                        ).localeCompare(
                            String(
                                leftVersion?.valid_from || ''
                            )
                        );
                    }
                );

                renderFinanceBillingPriceListIndex();
            };

            const bindFinanceBillingPriceListAdministration = () => {
                const root = document.querySelector(
                    '[data-finance-root]'
                );

                const panel = root?.querySelector(
                    '[data-price-list-panel="billing"]'
                );

                const administration = panel?.querySelector(
                    '[data-billing-price-list-admin]'
                );

                const createCard = panel?.querySelector(
                    '[data-billing-price-list-create-card]'
                );

                if (
                    !panel
                    || !administration
                    || !createCard
                    || panel.dataset.billingAdministrationBound
                        === '1'
                ) {
                    return;
                }

                panel.dataset.billingAdministrationBound = '1';

                panel.querySelector(
                    '[data-billing-price-list-create-open]'
                )?.addEventListener('click', () => {
                    administration.hidden = true;
                    createCard.hidden = false;
                });

                panel.querySelector(
                    '[data-billing-price-list-create-close]'
                )?.addEventListener('click', () => {
                    createCard.hidden = true;
                    administration.hidden = false;
                });

                panel.querySelectorAll(
                    '[data-billing-price-list-filter]'
                ).forEach((button) => {
                    button.addEventListener('click', () => {
                        financeBillingPriceListFilter =
                            button.dataset
                                .billingPriceListFilter
                            || 'all';

                        renderFinanceBillingPriceListIndex();
                    });
                });

                panel.querySelector(
                    '[data-billing-price-list-reload]'
                )?.addEventListener('click', async (event) => {
                    const button = event.currentTarget;

                    button.disabled = true;

                    try {
                        await loadFinanceCustomers();
                    }
                    finally {
                        button.disabled = false;
                    }
                });
            };

            const bindFinanceBillingPriceListCreate = () => {
                const root = document.querySelector(
                    '[data-finance-root]'
                );
                const panel = root?.querySelector(
                    '[data-provider-managed-price-list-endpoint]'
                );

                if (
                    !root
                    || !panel
                    || panel.dataset.billingCreateBound === '1'
                ) {
                    return;
                }

                const customer = panel.querySelector(
                    '[data-billing-price-list-customer]'
                );
                const name = panel.querySelector(
                    '[data-billing-price-list-name]'
                );
                const currency = panel.querySelector(
                    '[data-billing-price-list-currency]'
                );
                const validFrom = panel.querySelector(
                    '[data-billing-price-list-valid-from]'
                );
                const validUntil = panel.querySelector(
                    '[data-billing-price-list-valid-until]'
                );
                const save = panel.querySelector(
                    '[data-billing-price-list-save]'
                );
                const message = panel.querySelector(
                    '[data-billing-price-list-message]'
                );
                const conditionalRoot = panel.querySelector(
                    '[data-conditional-rule-root]'
                );
                const addRule = panel.querySelector(
                    '[data-conditional-rule-add]'
                );
                const preset = panel.querySelector(
                    '[data-conditional-rule-preset]'
                );
                const rateInputs = Array.from(
                    panel.querySelectorAll('[data-price-list-rate]')
                );

                if (
                    !customer
                    || !name
                    || !currency
                    || !validFrom
                    || !validUntil
                    || !save
                    || !message
                    || !conditionalRoot
                    || !addRule
                    || !preset
                    || rateInputs.length !== 4
                ) {
                    return;
                }

                const itemDescriptions = {
                    delivered_parcels: 'Doru\u010den\u00e1 z\u00e1silka',
                    redirected_parcels: 'P\u0159esm\u011brovan\u00e1 z\u00e1silka',
                    undelivered_parcels: 'Odm\u00edtnuto z\u00e1kazn\u00edkem',
                    actual_km: 'Skute\u010dn\u00fd kilometr',
                };
                const canonicalCodes = [
                    'delivered_parcels',
                    'redirected_parcels',
                    'undelivered_parcels',
                    'actual_km',
                ];

                panel.dataset.billingCreateBound = '1';
                resetFinanceConditionalRules(panel);

                addRule.addEventListener(
                    'click',
                    () => addFinanceConditionalRule(
                        panel,
                        preset.value
                    )
                );

                save.addEventListener('click', async () => {
                    const relationshipId = Number(customer.value);

                    if (
                        !Number.isInteger(relationshipId)
                        || relationshipId < 1
                    ) {
                        message.hidden = false;
                        message.textContent = 'Vyberte odb\u011bratele.';
                        return;
                    }

                    if (
                        name.value.trim() === ''
                        || validFrom.value === ''
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Vypl\u0148te n\u00e1zev cen\u00edku a platnost od.';
                        return;
                    }

                    if (
                        validUntil.value !== ''
                        && validUntil.value < validFrom.value
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Platnost do nesm\u00ed b\u00fdt p\u0159ed platnost\u00ed od.';
                        return;
                    }

                    const rateMap = new Map(
                        rateInputs.map((input) => [
                            input.dataset.priceListRate,
                            input,
                        ])
                    );
                    const items = canonicalCodes.map((code) => {
                        const input = rateMap.get(code);
                        const unitRate = input?.value?.trim() ?? '';

                        return {
                            code,
                            description: itemDescriptions[code],
                            unit_rate: unitRate,
                        };
                    });

                    if (
                        items.some(
                            (item) =>
                                item.unit_rate === ''
                                || !Number.isFinite(
                                    Number(item.unit_rate)
                                )
                                || Number(item.unit_rate) < 0
                        )
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Vypl\u0148te v\u0161echny \u010dty\u0159i nez\u00e1porn\u00e9 sazby.';
                        return;
                    }

                    let conditionalRules;

                    try {
                        conditionalRules =
                            collectFinanceConditionalRules(panel);
                    }
                    catch (error) {
                        message.hidden = false;
                        message.textContent = error.message;
                        return;
                    }

                    const endpoint = panel.dataset
                        .providerManagedPriceListEndpoint
                        .replace(
                            '{relationship}',
                            encodeURIComponent(String(relationshipId))
                        );

                    save.disabled = true;
                    message.hidden = false;
                    message.textContent =
                        'Ukl\u00e1d\u00e1m cen\u00edk, sazby a podm\u00edn\u011bn\u00e9 p\u0159\u00edplatky\u2026';

                    try {
                        await api(endpoint, {
                            method: 'POST',
                            body: JSON.stringify({
                                name: name.value.trim(),
                                currency: currency.value,
                                valid_from: validFrom.value,
                                valid_until:
                                    validUntil.value || null,
                                change_reason:
                                    'Zalo\u017een\u00ed faktura\u010dn\u00edho cen\u00edku p\u0159es Finance UI.',
                                items,
                                conditional_rules:
                                    conditionalRules,
                            }),
                        });

                        message.textContent =
                            `Faktura\u010dn\u00ed cen\u00edk byl ulo\u017een jako draft v1 s ${conditionalRules.length} podm\u00edn\u011bn\u00fdmi p\u0159\u00edplatky.`;
                        name.value = '';
                        rateInputs.forEach((input) => {
                            input.value = '';
                        });
                        resetFinanceConditionalRules(panel);
                        await loadFinanceCustomers();
                        await loadFinanceCustomerDetail(
                            relationshipId
                        );
                    }
                    catch (error) {
                        message.textContent =
                            `Faktura\u010dn\u00ed cen\u00edk se nepoda\u0159ilo ulo\u017eit: ${error.message}`;
                    }
                    finally {
                        save.disabled = false;
                    }
                });
            };
            /*
             * S022-MVP-01 DRIVER PRICE LIST WEB UI
             *
             * This browser workflow deliberately uses the existing authenticated
             * API helper and verified organization context. It creates the complete
             * driver compensation draft with rates and conditional surcharge rules.
             * Approval and activation remain explicit lifecycle actions.
             */
            let financeDriverAssignments = new Map();

            const financeDriverPriceListArray = (body) => {
                const payload = getPayload(body) || {};
                const items =
                    payload?.items?.data
                    ?? payload?.items
                    ?? [];

                return Array.isArray(items)
                    ? items
                    : [];
            };

            const financeDriverLabel = (
                driver,
                assignment
            ) => {
                const name =
                    driver?.full_name
                    || `${driver?.first_name ?? ''} ${driver?.last_name ?? ''}`.trim()
                    || `Řidič ${driver?.id ?? ''}`;

                const organization =
                    assignment?.organization_name
                    || assignment?.organization?.name
                    || '';

                return organization
                    ? `${name} · ${organization}`
                    : name;
            };

            let financeDriverPriceLists = [];

            const financeUnifiedPriceListCategory = (priceList) => {
                if (priceList?.status === 'active') {
                    return 'current';
                }

                if (
                    priceList?.status === 'draft'
                    || priceList?.status === 'approved'
                ) {
                    return 'draft';
                }

                return 'history';
            };

            const financeUnifiedPriceListCurrentVersion = (priceList) => {
                const versions = Array.isArray(priceList?.versions)
                    ? priceList.versions
                    : [];

                return versions.find(
                    (version) => Number(version?.version_number)
                        === Number(priceList?.current_version)
                ) || versions[0] || null;
            };

            const financeUnifiedPriceListPeriod = (priceList) => {
                const version = financeUnifiedPriceListCurrentVersion(
                    priceList
                );

                if (!version) {
                    return '\u2014';
                }

                return `${financeCustomerDate(version.valid_from)} \u2013 ${financeCustomerDate(version.valid_until)}`;
            };

            const updateFinanceUnifiedPriceListCounts = (
                root,
                records
            ) => {
                const counts = {
                    all: records.length,
                    current: 0,
                    draft: 0,
                    history: 0,
                };

                records.forEach((record) => {
                    counts[financeUnifiedPriceListCategory(record)] += 1;
                });

                Object.entries(counts).forEach(([category, count]) => {
                    const output = root.querySelector(
                        `[data-unified-price-list-count="${category}"]`
                    );

                    if (output) {
                        output.textContent = String(count);
                    }
                });
            };

            const ensureFinanceDriverPriceListDetail = () => {
                const root = document.querySelector(
                    '[data-driver-price-list-root]'
                );

                if (!root) {
                    return null;
                }

                const existing = root.querySelector(
                    '[data-driver-price-list-detail]'
                );

                if (existing) {
                    return existing;
                }

                const detail = document.createElement('section');

                detail.className = 'drayvia-price-admin-detail';
                detail.setAttribute(
                    'data-driver-price-list-detail',
                    ''
                );
                detail.hidden = true;
                root.appendChild(detail);

                return detail;
            };

            const renderFinanceDriverPriceListEditor = (
                record,
                createVersion = false
            ) => {
                const detail = ensureFinanceDriverPriceListDetail();
                const template =
                    ensureFinanceDriverPriceListCreateCard();
                const current =
                    financeUnifiedPriceListCurrentVersion(record);

                if (
                    !detail
                    || !template
                    || (
                        !createVersion
                        && current?.status !== 'draft'
                    )
                    || (
                        createVersion
                        && current?.status === 'draft'
                    )
                ) {
                    return;
                }

                const editor = template.cloneNode(true);

                editor.hidden = false;
                editor.classList.add('drayvia-price-admin-editor');
                editor.removeAttribute(
                    'data-driver-price-list-create-card'
                );
                delete editor.dataset.driverDraftCreateBound;
                editor.dataset.driverPriceListEditor = '1';
                editor.dataset.driverPriceListEditorMode =
                    createVersion ? 'create-version' : 'update';

                Array.from(editor.querySelectorAll('[id]')).forEach(
                    (element) => {
                        const originalId = element.id;
                        const editorId = originalId.replace(
                            'driver-draft-price-list',
                            'driver-edit-price-list'
                        );
                        const label = editor.querySelector(
                            `label[for="${originalId}"]`
                        );

                        element.id = editorId;

                        if (label) {
                            label.htmlFor = editorId;
                        }
                    }
                );

                const title = editor.querySelector('h4');
                const assignment = editor.querySelector(
                    '[data-driver-draft-price-list-assignment]'
                );
                const name = editor.querySelector(
                    '[data-driver-draft-price-list-name]'
                );
                const currency = editor.querySelector(
                    '[data-driver-draft-price-list-currency]'
                );
                const validFrom = editor.querySelector(
                    '[data-driver-draft-price-list-valid-from]'
                );
                const validUntil = editor.querySelector(
                    '[data-driver-draft-price-list-valid-until]'
                );
                const save = editor.querySelector(
                    '[data-driver-draft-price-list-save]'
                );
                const cancel = editor.querySelector(
                    '[data-driver-price-list-create-close]'
                );
                const message = editor.querySelector(
                    '[data-driver-draft-price-list-message]'
                );
                const addRule = editor.querySelector(
                    '[data-conditional-rule-add]'
                );
                const preset = editor.querySelector(
                    '[data-conditional-rule-preset]'
                );
                const conditionalList = editor.querySelector(
                    '[data-conditional-rule-list]'
                );
                const rateInputs = Array.from(
                    editor.querySelectorAll('[data-price-list-rate]')
                );

                if (
                    !name
                    || !currency
                    || !validFrom
                    || !validUntil
                    || !save
                    || !cancel
                    || !message
                    || !addRule
                    || !preset
                    || !conditionalList
                    || rateInputs.length !== 4
                ) {
                    renderFinanceDriverPriceListDetail(record);
                    financeBillingAdminNotice(
                        detail,
                        'Editor konceptu se nepoda\u0159ilo p\u0159ipravit.',
                        'error'
                    );
                    return;
                }

                if (title) {
                    title.textContent = createVersion
                        ? `Nov\u00e1 verze: ${record?.name || ''}`
                        : `Upravit koncept: ${record?.name || ''}`;
                }

                const assignmentField = assignment?.closest(
                    '.drayvia-finance-field'
                );

                if (assignmentField) {
                    assignmentField.hidden = true;
                }

                name.value = record?.name || '';
                name.disabled = createVersion;
                currency.value = record?.currency || 'CZK';
                currency.disabled = true;
                validFrom.value = createVersion
                    ? ''
                    : current?.valid_from || '';
                validUntil.value = createVersion
                    ? ''
                    : current?.valid_until || '';
                save.textContent = createVersion
                    ? 'Vytvo\u0159it koncept nov\u00e9 verze'
                    : 'Ulo\u017eit zm\u011bny konceptu';
                cancel.textContent = 'Zru\u0161it \u00fapravy';

                const notes = editor.querySelectorAll(
                    '.drayvia-finance-note'
                );
                const note = notes.item(notes.length - 1);

                if (note) {
                    note.textContent = createVersion
                        ? 'Nov\u00e1 verze vznikne jako koncept s kompletn\u00ed kopi\u00ed sazeb a p\u0159\u00edplatk\u016f. Schv\u00e1len\u00ed a aktivace z\u016fstanou samostatn\u00e9.'
                        : 'Ulo\u017een\u00ed atomicky nahrad\u00ed cel\u00fd koncept v\u010detn\u011b sazeb a p\u0159\u00edplatk\u016f. Aktivn\u00ed ani historick\u00e9 verze nelze upravovat.';
                }

                const items = Array.isArray(current?.items)
                    ? current.items
                    : [];
                const itemMap = new Map(
                    items.map((item) => [item?.code, item])
                );

                rateInputs.forEach((input) => {
                    const item = itemMap.get(
                        input.dataset.priceListRate
                    );

                    input.value = item?.unit_rate ?? '';
                });

                conditionalList.replaceChildren();

                const rules = Array.isArray(
                    current?.conditional_rules
                )
                    ? current.conditional_rules
                    : [];

                rules.forEach((rule) => {
                    hydrateFinanceConditionalRule(editor, rule);
                });

                updateFinanceConditionalEmptyState(editor);

                addRule.addEventListener('click', () => {
                    addFinanceConditionalRule(
                        editor,
                        preset.value
                    );
                });

                cancel.addEventListener('click', () => {
                    renderFinanceDriverPriceListDetail(record);
                });

                save.addEventListener('click', async () => {
                    message.hidden = true;
                    message.dataset.state = '';

                    const normalizedName = name.value.trim();

                    if (normalizedName === '') {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Dopl\u0148te n\u00e1zev cen\u00edku.';
                        return;
                    }

                    if (validFrom.value === '') {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Dopl\u0148te datum platnosti od.';
                        return;
                    }

                    if (
                        validUntil.value !== ''
                        && validUntil.value < validFrom.value
                    ) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Platnost do nesm\u00ed b\u00fdt p\u0159ed platnost\u00ed od.';
                        return;
                    }

                    const itemDescriptions = {
                        delivered_parcels:
                            'Doru\u010den\u00e1 z\u00e1silka',
                        redirected_parcels:
                            'P\u0159esm\u011brovan\u00e1 z\u00e1silka',
                        undelivered_parcels:
                            'Odm\u00edtnuto z\u00e1kazn\u00edkem',
                        actual_km:
                            'Skute\u010dn\u00fd kilometr',
                    };
                    const canonicalCodes = [
                        'delivered_parcels',
                        'redirected_parcels',
                        'undelivered_parcels',
                        'actual_km',
                    ];
                    const rateMap = new Map(
                        rateInputs.map((input) => [
                            input.dataset.priceListRate,
                            input,
                        ])
                    );
                    const updatedItems = canonicalCodes.map((code) => {
                        const input = rateMap.get(code);
                        const existing = itemMap.get(code);

                        return {
                            code,
                            description:
                                existing?.description
                                || itemDescriptions[code],
                            unit_rate:
                                input?.value?.trim() || '',
                        };
                    });

                    if (
                        updatedItems.some(
                            (item) =>
                                item.unit_rate === ''
                                || !Number.isFinite(
                                    Number(item.unit_rate)
                                )
                                || Number(item.unit_rate) < 0
                        )
                    ) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Vypl\u0148te v\u0161echny \u010dty\u0159i nez\u00e1porn\u00e9 sazby.';
                        return;
                    }

                    let conditionalRules;

                    try {
                        conditionalRules =
                            collectFinanceConditionalRules(editor);
                    }
                    catch (error) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent = error.message;
                        return;
                    }

                    const endpoint = createVersion
                        ? `/api/v1/driver-price-lists/${
                            encodeURIComponent(record.public_id)
                        }/versions`
                        : `/api/v1/driver-price-lists/${
                            encodeURIComponent(record.public_id)
                        }/versions/${
                            encodeURIComponent(
                                String(current.version_number)
                            )
                        }`;
                    const payload = createVersion
                        ? {
                            expected_current_version:
                                Number(current.version_number),
                            valid_from: validFrom.value,
                            valid_until:
                                validUntil.value || null,
                            change_reason:
                                'Complete driver draft version created through Finance UI.',
                            items: updatedItems,
                            conditional_rules:
                                conditionalRules,
                        }
                        : {
                            name: normalizedName,
                            description:
                                record?.description || null,
                            expected_lock_version:
                                Number(current.lock_version),
                            valid_from: validFrom.value,
                            valid_until:
                                validUntil.value || null,
                            change_reason:
                                'Complete driver draft updated through Finance UI.',
                            items: updatedItems,
                            conditional_rules:
                                conditionalRules,
                        };

                    save.disabled = true;
                    cancel.disabled = true;
                    message.hidden = false;
                    message.dataset.state = '';
                    message.textContent = createVersion
                        ? 'Vytv\u00e1\u0159\u00edm koncept nov\u00e9 verze\u2026'
                        : 'Ukl\u00e1d\u00e1m cel\u00fd koncept\u2026';

                    try {
                        await api(endpoint, {
                            method: createVersion ? 'POST' : 'PUT',
                            body: JSON.stringify(payload),
                        });

                        const nextRevision =
                            Number(current.lock_version) + 1;

                        await loadFinanceDriverPriceLists();

                        const updatedRecord =
                            financeDriverPriceLists.find(
                                (priceList) =>
                                    priceList?.public_id
                                    === record?.public_id
                            ) || {
                                ...record,
                                name: normalizedName,
                            };

                        await loadFinanceDriverPriceListDetail(
                            updatedRecord
                        );
                        financeBillingAdminNotice(
                            detail,
                            createVersion
                                ? 'Koncept nov\u00e9 verze byl vytvo\u0159en.'
                                : `Koncept byl ulo\u017een. Aktu\u00e1ln\u00ed revize: ${nextRevision}.`
                        );
                    }
                    catch (error) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            `Koncept se nepoda\u0159ilo ulo\u017eit: ${error.message}`;
                    }
                    finally {
                        save.disabled = false;
                        cancel.disabled = false;
                    }
                });

                detail.replaceChildren(editor);
                detail.hidden = false;
                detail.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            };
            const runFinanceDriverPriceListLifecycle = async (
                record,
                current,
                action,
                validUntil = null
            ) => {
                const detail = ensureFinanceDriverPriceListDetail();
                const publicId = String(record?.public_id || '');
                const versionNumber = Number(
                    current?.version_number
                );
                const lockVersion = Number(current?.lock_version);
                const contracts = {
                    approve: {
                        requiredStatus: 'draft',
                        path: 'approve',
                        question:
                            'Opravdu chcete schv\u00e1lit tento koncept? Po schv\u00e1len\u00ed ji\u017e nep\u016fjde upravovat.',
                        pending: 'Schvaluji koncept\u2026',
                        success: 'Koncept byl schv\u00e1len.',
                    },
                    activate: {
                        requiredStatus: 'approved',
                        path: 'activate',
                        question:
                            'Opravdu chcete tuto verzi aktivovat? P\u0159\u00edpadn\u00e1 p\u0159edchoz\u00ed aktivn\u00ed verze bude dom\u00e9novou slu\u017ebou nahrazena.',
                        pending: 'Aktivuji schv\u00e1lenou verzi\u2026',
                        success: 'Verze byla aktivov\u00e1na.',
                    },
                    expire: {
                        requiredStatus: 'active',
                        path: 'expire',
                        question:
                            'Opravdu chcete ukon\u010dit platnost aktivn\u00ed verze k vybran\u00e9mu datu?',
                        pending: 'Ukon\u010duji platnost verze\u2026',
                        success: 'Platnost verze byla ukon\u010dena.',
                    },
                };
                const contract = contracts[action];

                if (
                    !detail
                    || publicId === ''
                    || !Number.isInteger(versionNumber)
                    || versionNumber < 1
                    || !Number.isInteger(lockVersion)
                    || lockVersion < 1
                    || !contract
                    || current?.status !== contract.requiredStatus
                ) {
                    return;
                }

                if (
                    action === 'expire'
                    && (
                        typeof validUntil !== 'string'
                        || validUntil === ''
                    )
                ) {
                    financeBillingAdminNotice(
                        detail,
                        'Vyberte datum ukon\u010den\u00ed platnosti.',
                        'error'
                    );
                    return;
                }

                if (!window.confirm(contract.question)) {
                    return;
                }

                const payload = {
                    expected_lock_version: lockVersion,
                };

                if (action === 'expire') {
                    payload.valid_until = validUntil;
                }

                financeBillingAdminNotice(
                    detail,
                    contract.pending
                );

                try {
                    await api(
                        `/api/v1/driver-price-lists/${
                            encodeURIComponent(publicId)
                        }/versions/${
                            encodeURIComponent(
                                String(versionNumber)
                            )
                        }/${contract.path}`,
                        {
                            method: 'POST',
                            body: JSON.stringify(payload),
                        }
                    );

                    await loadFinanceDriverPriceLists();

                    const updatedRecord =
                        financeDriverPriceLists.find(
                            (priceList) =>
                                priceList?.public_id === publicId
                        ) || record;

                    await loadFinanceDriverPriceListDetail(
                        updatedRecord
                    );
                    financeBillingAdminNotice(
                        detail,
                        contract.success
                    );
                }
                catch (error) {
                    financeBillingAdminNotice(
                        detail,
                        `Zm\u011bna stavu se nepoda\u0159ila: ${error.message}`,
                        'error'
                    );
                }
            };
            const renderFinanceDriverPriceListDetail = (record) => {
                const detail = ensureFinanceDriverPriceListDetail();

                if (!detail) {
                    return;
                }

                const current =
                    financeUnifiedPriceListCurrentVersion(record);
                const assignmentKey = Number(
                    record?.driver_organization_assignment_id
                );
                const driverInfo = financeDriverAssignments.get(
                    assignmentKey
                );
                const items = Array.isArray(current?.items)
                    ? current.items
                    : [];
                const rules = Array.isArray(
                    current?.conditional_rules
                )
                    ? current.conditional_rules
                    : [];
                const versions = Array.isArray(record?.versions)
                    ? record.versions
                    : [];

                const header = document.createElement('div');
                const heading = document.createElement('div');
                const title = document.createElement('h4');
                const meta = document.createElement('p');
                const actions = document.createElement('div');
                const close = document.createElement('button');

                header.className =
                    'drayvia-price-admin-detail-header';
                heading.className =
                    'drayvia-price-admin-detail-heading';
                actions.className =
                    'drayvia-price-admin-detail-actions';
                title.textContent =
                    record?.name || 'Detail cen\u00edku \u0159idi\u010de';
                meta.textContent = [
                    driverInfo?.label
                        || `P\u0159i\u0159azen\u00ed ${assignmentKey}`,
                    record?.code || '\u2014',
                    financeCustomerStatus(record?.status),
                    financeBillingPriceListPeriod(current),
                ].join(' \u00b7 ');

                close.type = 'button';
                close.className =
                    'drayvia-price-admin-secondary';
                close.textContent = 'Zav\u0159\u00edt detail';
                close.addEventListener('click', () => {
                    detail.hidden = true;
                    detail.replaceChildren();
                });

                if (current?.status === 'draft') {
                    const edit = document.createElement('button');
                    const approve = document.createElement('button');

                    edit.type = 'button';
                    edit.className =
                        'drayvia-price-admin-primary';
                    edit.textContent = 'Upravit koncept';
                    edit.addEventListener('click', () => {
                        renderFinanceDriverPriceListEditor(record);
                    });

                    approve.type = 'button';
                    approve.className =
                        'drayvia-price-admin-secondary';
                    approve.textContent = 'Schv\u00e1lit';
                    approve.addEventListener('click', () => {
                        runFinanceDriverPriceListLifecycle(
                            record,
                            current,
                            'approve'
                        );
                    });

                    actions.append(edit, approve);
                }
                else if (current?.status === 'approved') {
                    const activate = document.createElement('button');

                    activate.type = 'button';
                    activate.className =
                        'drayvia-price-admin-primary';
                    activate.textContent = 'Aktivovat';
                    activate.addEventListener('click', () => {
                        runFinanceDriverPriceListLifecycle(
                            record,
                            current,
                            'activate'
                        );
                    });

                    actions.appendChild(activate);
                }
                else if (current?.status === 'active') {
                    const expiration = document.createElement('input');
                    const expire = document.createElement('button');
                    const now = new Date();
                    const today = new Date(
                        now.getTime()
                        - now.getTimezoneOffset() * 60000
                    )
                        .toISOString()
                        .slice(0, 10);

                    expiration.type = 'date';
                    expiration.className = 'drayvia-finance-input';
                    expiration.max = today;
                    expiration.value = today;
                    expiration.setAttribute(
                        'aria-label',
                        'Datum ukon\u010den\u00ed platnosti'
                    );
                    expiration.title =
                        'Datum ukon\u010den\u00ed platnosti';

                    expire.type = 'button';
                    expire.className =
                        'drayvia-price-admin-secondary';
                    expire.textContent = 'Ukon\u010dit platnost';
                    expire.addEventListener('click', () => {
                        runFinanceDriverPriceListLifecycle(
                            record,
                            current,
                            'expire',
                            expiration.value
                        );
                    });

                    actions.append(expiration, expire);
                }

                if (
                    current?.status === 'active'
                    || current?.status === 'expired'
                ) {
                    const newVersion =
                        document.createElement('button');

                    newVersion.type = 'button';
                    newVersion.className =
                        'drayvia-price-admin-primary';
                    newVersion.textContent = 'Nov\u00e1 verze';
                    newVersion.addEventListener('click', () => {
                        renderFinanceDriverPriceListEditor(
                            record,
                            true
                        );
                    });
                    actions.appendChild(newVersion);
                }

                actions.appendChild(close);
                heading.append(title, meta);
                header.append(heading, actions);

                const itemRows = items.map((item) => [
                    item?.description
                        || financeBillingItemLabel(item?.code),
                    financeBillingUnitLabel(item?.unit),
                    financeBillingMoney(
                        item?.unit_rate,
                        item?.currency || record?.currency
                    ),
                ]);

                const ruleRows = rules.map((rule) => [
                    rule?.name || rule?.code,
                    financeBillingRuleFormula(rule),
                    financeBillingEvaluationScopeLabel(
                        rule?.evaluation_scope
                    ),
                    financeBillingRewardLabel(rule),
                    (
                        Array.isArray(rule?.bands)
                            ? rule.bands
                            : []
                    ).map(
                        (band) => financeBillingBandLabel(
                            rule,
                            band,
                            record?.currency
                        )
                    ).join('; ') || '\u2014',
                ]);

                const versionRows = versions.map((version) => [
                    version?.version_number,
                    financeCustomerStatus(version?.status),
                    financeBillingPriceListPeriod(version),
                    version?.lock_version,
                ]);

                detail.replaceChildren(
                    header,
                    financeBillingDetailTable(
                        'Z\u00e1kladn\u00ed sazby',
                        [
                            'Polo\u017eka',
                            'Jednotka',
                            'Sazba',
                        ],
                        itemRows
                    ),
                    financeBillingDetailTable(
                        'Podm\u00edn\u011bn\u00e9 p\u0159\u00edplatky',
                        [
                            'P\u0159\u00edplatek',
                            'Vzorec',
                            'Vyhodnocen\u00ed',
                            'Zp\u016fsob',
                            'P\u00e1sma a ceny',
                        ],
                        ruleRows
                    ),
                    financeBillingDetailTable(
                        'Historie verz\u00ed',
                        [
                            'Verze',
                            'Stav',
                            'Platnost',
                            'Revize',
                        ],
                        versionRows
                    )
                );
                detail.hidden = false;
                detail.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            };

            const loadFinanceDriverPriceListDetail = async (priceList) => {
                const detail = ensureFinanceDriverPriceListDetail();
                const publicId = String(
                    priceList?.public_id || ''
                );

                if (!detail || publicId === '') {
                    return;
                }

                detail.hidden = false;
                detail.textContent =
                    'Na\u010d\u00edt\u00e1m detail cen\u00edku \u0159idi\u010de\u2026';

                try {
                    const body = await api(
                        `/api/v1/driver-price-lists/${encodeURIComponent(publicId)}/versions`
                    );
                    const payload = getPayload(body) || {};
                    const versionCollection =
                        payload?.items?.data
                        ?? payload?.items
                        ?? [];
                    const versions = Array.isArray(versionCollection)
                        ? versionCollection
                        : [];

                    renderFinanceDriverPriceListDetail({
                        ...priceList,
                        versions,
                    });
                }
                catch (error) {
                    detail.replaceChildren();
                    detail.hidden = false;

                    const message = document.createElement('p');
                    const close = document.createElement('button');

                    message.className =
                        'drayvia-price-admin-message';
                    message.dataset.state = 'error';
                    message.textContent =
                        `Detail cen\u00edku \u0159idi\u010de se nepoda\u0159ilo na\u010d\u00edst: ${error.message}`;
                    close.type = 'button';
                    close.className =
                        'drayvia-price-admin-secondary';
                    close.textContent = 'Zav\u0159\u00edt detail';
                    close.addEventListener('click', () => {
                        detail.hidden = true;
                        detail.replaceChildren();
                    });
                    detail.append(message, close);
                }
            };

            const renderFinanceDriverPriceListIndex = () => {
                const root = document.querySelector(
                    '[data-driver-price-list-root]'
                );
                const list = root?.querySelector(
                    '[data-driver-price-list-list]'
                );

                if (!root || !list) {
                    return;
                }

                const headerRow = list.closest('table')
                    ?.querySelector('thead tr');

                if (headerRow && headerRow.children.length === 5) {
                    const actionHeader = document.createElement('th');

                    actionHeader.textContent = 'Akce';
                    headerRow.appendChild(actionHeader);
                }

                updateFinanceUnifiedPriceListCounts(
                    root,
                    financeDriverPriceLists
                );

                const selectedFilter =
                    root.dataset.unifiedPriceListFilter || 'all';
                const records = financeDriverPriceLists.filter(
                    (record) => selectedFilter === 'all'
                        || financeUnifiedPriceListCategory(record)
                            === selectedFilter
                );

                list.replaceChildren();

                if (records.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');

                    cell.colSpan = 6;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent = financeDriverPriceLists.length === 0
                        ? '\u017d\u00e1dn\u00fd cen\u00edk \u0159idi\u010de zat\u00edm nen\u00ed evidov\u00e1n.'
                        : 'Vybran\u00e9mu filtru neodpov\u00edd\u00e1 \u017e\u00e1dn\u00fd cen\u00edk.';

                    row.appendChild(cell);
                    list.appendChild(row);
                    return;
                }

                records.forEach((priceList) => {
                    const row = document.createElement('tr');
                    const assignmentKey = Number(
                        priceList?.driver_organization_assignment_id
                    );
                    const driverInfo = financeDriverAssignments.get(
                        assignmentKey
                    );
                    const values = [
                        driverInfo?.label || `P\u0159i\u0159azen\u00ed ${assignmentKey}`,
                        priceList?.name || '\u2014',
                        financeCustomerStatus(priceList?.status),
                        priceList?.current_version ?? '\u2014',
                        priceList?.currency || '\u2014',
                    ];

                    values.forEach((value) => {
                        const cell = document.createElement('td');
                        cell.textContent = String(value);
                        row.appendChild(cell);
                    });

                    const actionCell = document.createElement('td');
                    const detailButton = document.createElement('button');

                    detailButton.type = 'button';
                    detailButton.className =
                        'drayvia-price-admin-secondary';
                    detailButton.textContent = 'Otev\u0159\u00edt';
                    detailButton.addEventListener('click', () => {
                        loadFinanceDriverPriceListDetail(priceList);
                    });

                    actionCell.appendChild(detailButton);
                    row.appendChild(actionCell);
                    list.appendChild(row);
                });
            };
            const loadFinanceDriverPriceLists = async () => {
                const root = document.querySelector(
                    '[data-driver-price-list-root]'
                );
                const list = root?.querySelector(
                    '[data-driver-price-list-list]'
                );

                if (!root || !list) {
                    return;
                }

                list.replaceChildren();

                const loadingRow = document.createElement('tr');
                const loadingCell = document.createElement('td');

                loadingCell.colSpan = 5;
                loadingCell.textContent =
                    'Na\u010d\u00edt\u00e1m cen\u00edky \u0159idi\u010d\u016f\u2026';
                loadingRow.appendChild(loadingCell);
                list.appendChild(loadingRow);

                try {
                    const params = new URLSearchParams();

                    params.set('per_page', '100');
                    params.set('sort_by', 'name');
                    params.set('sort_dir', 'asc');

                    const body = await api(
                        `/api/v1/driver-price-lists?${params.toString()}`
                    );

                    financeDriverPriceLists =
                        financeDriverPriceListArray(body);

                    renderFinanceDriverPriceListIndex();
                }
                catch (error) {
                    financeDriverPriceLists = [];
                    updateFinanceUnifiedPriceListCounts(root, []);
                    list.replaceChildren();

                    const row = document.createElement('tr');
                    const cell = document.createElement('td');

                    cell.colSpan = 5;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent =
                        `Cen\u00edky \u0159idi\u010d\u016f se nepoda\u0159ilo na\u010d\u00edst: ${error.message}`;

                    row.appendChild(cell);
                    list.appendChild(row);
                }
            };

            const bindFinanceDriverPriceListAdministration = () => {
                const root = document.querySelector(
                    '[data-driver-price-list-root]'
                );

                if (
                    !root
                    || root.dataset.driverPriceListAdministrationBound === '1'
                ) {
                    return;
                }

                root.dataset.driverPriceListAdministrationBound = '1';

                root.querySelectorAll(
                    '[data-unified-price-list-filter]'
                ).forEach((button) => {
                    button.addEventListener('click', () => {
                        root.dataset.unifiedPriceListFilter =
                            button.dataset.unifiedPriceListFilter || 'all';
                        renderFinanceDriverPriceListIndex();
                    });
                });

                root.querySelector(
                    '[data-driver-price-list-reload]'
                )?.addEventListener('click', () => {
                    loadFinanceDriverPriceLists();
                });
            };
            const loadFinanceDriverAssignments = async () => {
                const root =
                    document.querySelector(
                        '[data-driver-price-list-root]'
                    );

                const select =
                    root?.querySelector(
                        '[data-driver-price-list-assignment]'
                    );

                const message =
                    root?.querySelector(
                        '[data-driver-price-list-message]'
                    );

                if (!root || !select) {
                    return;
                }

                select.disabled = true;
                select.replaceChildren();

                const loading =
                    document.createElement('option');

                loading.value = '';
                loading.textContent =
                    'Načítám řidiče…';

                select.appendChild(
                    loading
                );

                financeDriverAssignments =
                    new Map();

                try {
                    const body =
                        await api(
                            '/api/v1/own-drivers'
                        );

                    const payload =
                        getPayload(body) || {};

                    const drivers =
                        Array.isArray(payload?.items)
                            ? payload.items
                            : (
                                Array.isArray(payload)
                                    ? payload
                                    : []
                            );

                    const enriched =
                        await Promise.all(
                            drivers.map(
                                async (driver) => {
                                    try {
                                        const assignmentBody =
                                            await api(
                                                `/api/v1/own-drivers/${encodeURIComponent(String(driver.id))}/assignments`
                                            );

                                        const assignmentData =
                                            getPayload(
                                                assignmentBody
                                            ) || {};

                                        const assignment =
                                            assignmentData?.current
                                            ?? null;

                                        if (
                                            !assignment
                                            || !Number.isInteger(
                                                Number(
                                                    assignment.id
                                                )
                                            )
                                        ) {
                                            return null;
                                        }

                                        return {
                                            driver,
                                            assignment,
                                            label:
                                                financeDriverLabel(
                                                    driver,
                                                    assignment
                                                ),
                                        };
                                    }
                                    catch {
                                        return null;
                                    }
                                }
                            )
                        );

                    const usable =
                        enriched
                            .filter(Boolean)
                            .sort(
                                (left, right) =>
                                    left.label.localeCompare(
                                        right.label,
                                        'cs'
                                    )
                            );

                    select.replaceChildren();

                    const empty =
                        document.createElement('option');

                    empty.value = '';
                    empty.textContent =
                        usable.length > 0
                            ? 'Vyberte řidiče'
                            : 'Žádný řidič s aktuálním přiřazením';

                    select.appendChild(
                        empty
                    );

                    usable.forEach(
                        (item) => {
                            const assignmentId =
                                Number(
                                    item.assignment.id
                                );

                            financeDriverAssignments.set(
                                assignmentId,
                                item
                            );

                            const option =
                                document.createElement('option');

                            option.value =
                                String(assignmentId);

                            option.textContent =
                                item.label;

                            select.appendChild(
                                option
                            );
                        }
                    );

                    select.disabled =
                        usable.length === 0;

                    if (usable.length > 0) {
                        select.value =
                            String(
                                usable[0]
                                    .assignment
                                    .id
                            );
                    }

                    await loadFinanceDriverPriceLists();
                }
                catch (error) {
                    select.replaceChildren();

                    const failed =
                        document.createElement('option');

                    failed.value = '';
                    failed.textContent =
                        'Řidiče se nepodařilo načíst';

                    select.appendChild(
                        failed
                    );

                    if (message) {
                        message.hidden = false;
                        message.textContent =
                            `Řidiče pro ceník se nepodařilo načíst: ${error.message}`;
                    }
                }
            };

            const ensureFinanceDriverPriceListCreateCard = () => {
                const root = document.querySelector(
                    '[data-driver-price-list-root]'
                );
                const panel = root?.closest(
                    '[data-price-list-panel="drivers"]'
                );

                if (!root || !panel) {
                    return null;
                }

                const existing = panel.querySelector(
                    '[data-driver-price-list-create-card]'
                );

                if (existing) {
                    return existing;
                }

                const template = document.querySelector(
                    '[data-billing-price-list-create-card]'
                );

                if (!template) {
                    return null;
                }

                const driverCreateCard = template.cloneNode(true);
                const attributes = {
                    'data-billing-price-list-create-close':
                        'data-driver-price-list-create-close',
                    'data-billing-price-list-customer':
                        'data-driver-draft-price-list-assignment',
                    'data-billing-price-list-name':
                        'data-driver-draft-price-list-name',
                    'data-billing-price-list-currency':
                        'data-driver-draft-price-list-currency',
                    'data-billing-price-list-valid-from':
                        'data-driver-draft-price-list-valid-from',
                    'data-billing-price-list-valid-until':
                        'data-driver-draft-price-list-valid-until',
                    'data-billing-price-list-save':
                        'data-driver-draft-price-list-save',
                    'data-billing-price-list-message':
                        'data-driver-draft-price-list-message',
                };

                driverCreateCard.removeAttribute(
                    'data-billing-price-list-create-card'
                );
                driverCreateCard.setAttribute(
                    'data-driver-price-list-create-card',
                    ''
                );
                driverCreateCard.hidden = true;

                Object.entries(attributes).forEach(([
                    source,
                    target,
                ]) => {
                    const element = driverCreateCard.querySelector(
                        `[${source}]`
                    );

                    if (element) {
                        element.removeAttribute(source);
                        element.setAttribute(target, '');
                    }
                });

                Array.from(
                    driverCreateCard.querySelectorAll('[id]')
                ).forEach((element) => {
                    const originalId = element.id;
                    const driverId = originalId.replace(
                        'billing-price-list',
                        'driver-draft-price-list'
                    );
                    const label = driverCreateCard.querySelector(
                        `label[for="${originalId}"]`
                    );

                    element.id = driverId;

                    if (label) {
                        label.htmlFor = driverId;
                    }
                });

                const header = root.querySelector(
                    '.drayvia-price-admin-header'
                );
                const open = document.createElement('button');

                open.type = 'button';
                open.className = 'drayvia-price-admin-primary';
                open.setAttribute(
                    'data-driver-price-list-create-open',
                    ''
                );
                open.textContent = 'Nov\u00fd cen\u00edk \u0159idi\u010de';
                header?.appendChild(open);

                const legacyAssignment = root.querySelector(
                    '[data-driver-price-list-assignment]'
                );
                const legacyGrid = legacyAssignment?.closest(
                    '.drayvia-finance-grid'
                );
                const legacyDescription = legacyGrid
                    ?.previousElementSibling;
                const legacyHeading = legacyDescription
                    ?.previousElementSibling;
                const legacyRate = root.querySelector(
                    '[data-driver-price-list-rate]'
                );
                const legacyRateWrapper = legacyRate?.closest(
                    'div[style*="overflow-x"]'
                );
                const legacySave = root.querySelector(
                    '[data-driver-price-list-save]'
                );
                const legacySaveWrapper = legacySave?.closest(
                    'div[style*="margin-top"]'
                );

                [
                    legacyHeading,
                    legacyDescription,
                    legacyGrid,
                    legacyRateWrapper,
                    legacySaveWrapper,
                ].forEach((element) => {
                    if (element) {
                        element.hidden = true;
                    }
                });

                const heading = driverCreateCard.querySelector('h4');
                const assignment = driverCreateCard.querySelector(
                    '[data-driver-draft-price-list-assignment]'
                );
                const assignmentLabel = assignment
                    ?.closest('.drayvia-finance-field')
                    ?.querySelector('label');
                const conditionalHeading = driverCreateCard.querySelector(
                    '[data-conditional-rule-root] h5'
                );
                const save = driverCreateCard.querySelector(
                    '[data-driver-draft-price-list-save]'
                );
                const note = driverCreateCard.querySelector(
                    '.drayvia-finance-note:not([data-driver-draft-price-list-message])'
                );

                if (heading) {
                    heading.textContent = 'Nov\u00fd cen\u00edk \u0159idi\u010de';
                }

                if (assignmentLabel) {
                    assignmentLabel.textContent = '\u0158idi\u010d';
                }

                if (conditionalHeading) {
                    conditionalHeading.textContent =
                        'Podm\u00edn\u011bn\u00e9 p\u0159\u00edplatky \u0159idi\u010de';
                }

                if (save) {
                    save.textContent = 'Ulo\u017eit koncept cen\u00edku';
                }

                if (note) {
                    note.textContent =
                        'Cen\u00edk se ulo\u017e\u00ed jako kompletn\u00ed draft v1. Schv\u00e1len\u00ed a aktivace z\u016fst\u00e1vaj\u00ed samostatn\u00e9 kroky.';
                }

                root.insertAdjacentElement(
                    'afterend',
                    driverCreateCard
                );

                return driverCreateCard;
            };

            const populateFinanceDriverPriceListSelect = () => {
                const createCard =
                    ensureFinanceDriverPriceListCreateCard();
                const select = createCard?.querySelector(
                    '[data-driver-draft-price-list-assignment]'
                );

                if (!select) {
                    return;
                }

                const selected = select.value;
                const placeholder = document.createElement('option');

                placeholder.value = '';
                placeholder.textContent = 'Vyberte \u0159idi\u010de';
                select.replaceChildren(placeholder);

                Array.from(financeDriverAssignments.entries())
                    .sort((left, right) => String(
                        left[1]?.label || ''
                    ).localeCompare(
                        String(right[1]?.label || ''),
                        'cs'
                    ))
                    .forEach(([assignmentId, item]) => {
                        if (!Number.isInteger(Number(assignmentId))) {
                            return;
                        }

                        const option = document.createElement('option');

                        option.value = String(assignmentId);
                        option.textContent = item?.label
                            || `\u0158idi\u010d ${assignmentId}`;
                        select.appendChild(option);
                    });

                if (
                    Array.from(select.options).some(
                        (option) => option.value === selected
                    )
                ) {
                    select.value = selected;
                }
            };

            const bindFinanceDriverPriceListCreate = () => {
                const root = document.querySelector(
                    '[data-driver-price-list-root]'
                );
                const createCard =
                    ensureFinanceDriverPriceListCreateCard();
                const assignment = createCard?.querySelector(
                    '[data-driver-draft-price-list-assignment]'
                );
                const name = createCard?.querySelector(
                    '[data-driver-draft-price-list-name]'
                );
                const currency = createCard?.querySelector(
                    '[data-driver-draft-price-list-currency]'
                );
                const validFrom = createCard?.querySelector(
                    '[data-driver-draft-price-list-valid-from]'
                );
                const validUntil = createCard?.querySelector(
                    '[data-driver-draft-price-list-valid-until]'
                );
                const save = createCard?.querySelector(
                    '[data-driver-draft-price-list-save]'
                );
                const message = createCard?.querySelector(
                    '[data-driver-draft-price-list-message]'
                );
                const addRule = createCard?.querySelector(
                    '[data-conditional-rule-add]'
                );
                const preset = createCard?.querySelector(
                    '[data-conditional-rule-preset]'
                );
                const rateInputs = createCard
                    ? Array.from(
                        createCard.querySelectorAll(
                            '[data-price-list-rate]'
                        )
                    )
                    : [];

                if (
                    !root
                    || !createCard
                    || !assignment
                    || !name
                    || !currency
                    || !validFrom
                    || !validUntil
                    || !save
                    || !message
                    || !addRule
                    || !preset
                    || rateInputs.length !== 4
                    || createCard.dataset.driverDraftCreateBound === '1'
                ) {
                    return;
                }

                const itemDescriptions = {
                    delivered_parcels:
                        'Doru\u010den\u00e1 z\u00e1silka',
                    redirected_parcels:
                        'P\u0159esm\u011brovan\u00e1 z\u00e1silka',
                    undelivered_parcels:
                        'Odm\u00edtnuto z\u00e1kazn\u00edkem',
                    actual_km:
                        'Skute\u010dn\u00fd kilometr',
                };
                const canonicalCodes = [
                    'delivered_parcels',
                    'redirected_parcels',
                    'undelivered_parcels',
                    'actual_km',
                ];

                createCard.dataset.driverDraftCreateBound = '1';
                resetFinanceConditionalRules(createCard);

                assignment.addEventListener('focus', () => {
                    populateFinanceDriverPriceListSelect();
                });

                addRule.addEventListener('click', () => {
                    addFinanceConditionalRule(
                        createCard,
                        preset.value
                    );
                });

                root.querySelector(
                    '[data-driver-price-list-create-open]'
                )?.addEventListener('click', () => {
                    populateFinanceDriverPriceListSelect();
                    root.hidden = true;
                    createCard.hidden = false;
                });

                createCard.querySelector(
                    '[data-driver-price-list-create-close]'
                )?.addEventListener('click', () => {
                    createCard.hidden = true;
                    root.hidden = false;
                });

                save.addEventListener('click', async () => {
                    const assignmentId = Number(assignment.value);

                    if (
                        !Number.isInteger(assignmentId)
                        || assignmentId < 1
                    ) {
                        message.hidden = false;
                        message.textContent = 'Vyberte \u0159idi\u010de.';
                        return;
                    }

                    if (
                        name.value.trim() === ''
                        || validFrom.value === ''
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Vypl\u0148te n\u00e1zev cen\u00edku a platnost od.';
                        return;
                    }

                    if (
                        validUntil.value !== ''
                        && validUntil.value < validFrom.value
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Platnost do nesm\u00ed b\u00fdt p\u0159ed platnost\u00ed od.';
                        return;
                    }

                    const rateMap = new Map(
                        rateInputs.map((input) => [
                            input.dataset.priceListRate,
                            input,
                        ])
                    );
                    const items = canonicalCodes.map((code) => {
                        const input = rateMap.get(code);
                        const unitRate = input?.value?.trim() ?? '';

                        return {
                            code,
                            description: itemDescriptions[code],
                            unit_rate: unitRate,
                        };
                    });

                    if (
                        items.some(
                            (item) =>
                                item.unit_rate === ''
                                || !Number.isFinite(
                                    Number(item.unit_rate)
                                )
                                || Number(item.unit_rate) < 0
                        )
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Vypl\u0148te v\u0161echny \u010dty\u0159i nez\u00e1porn\u00e9 sazby.';
                        return;
                    }

                    let conditionalRules;

                    try {
                        conditionalRules =
                            collectFinanceConditionalRules(createCard);
                    }
                    catch (error) {
                        message.hidden = false;
                        message.textContent = error.message;
                        return;
                    }

                    const code = [
                        'DPL',
                        String(assignmentId).slice(-8),
                        Date.now()
                            .toString(36)
                            .slice(-8)
                            .toUpperCase(),
                    ].join('-');

                    save.disabled = true;
                    message.hidden = false;
                    message.textContent =
                        'Ukl\u00e1d\u00e1m koncept, sazby a podm\u00edn\u011bn\u00e9 p\u0159\u00edplatky\u2026';

                    try {
                        const body = await api(
                            '/api/v1/driver-price-lists',
                            {
                                method: 'POST',
                                body: JSON.stringify({
                                    driver_organization_assignment_id:
                                        assignmentId,
                                    code,
                                    name: name.value.trim(),
                                    description: null,
                                    currency: currency.value,
                                    valid_from: validFrom.value,
                                    valid_until:
                                        validUntil.value || null,
                                    change_reason:
                                        'Complete driver price-list draft created through Finance UI.',
                                    items,
                                    conditional_rules:
                                        conditionalRules,
                                }),
                            }
                        );
                        const created = getPayload(body) || {};
                        const identifier = created?.code || code;

                        message.textContent =
                            `Koncept ${identifier} byl ulo\u017een jako draft v1 s ${conditionalRules.length} podm\u00edn\u011bn\u00fdmi p\u0159\u00edplatky. Schv\u00e1len\u00ed a aktivace nebyly provedeny.`;
                        name.value = '';
                        rateInputs.forEach((input) => {
                            input.value = '';
                        });
                        resetFinanceConditionalRules(createCard);
                        await loadFinanceDriverPriceLists();
                    }
                    catch (error) {
                        message.textContent =
                            `Koncept cen\u00edku \u0159idi\u010de se nepoda\u0159ilo ulo\u017eit: ${error.message}`;
                    }
                    finally {
                        save.disabled = false;
                    }
                });
            };
            let financeExternalCarrierRelationships = [];
            let financeExternalCarrierPriceLists = [];

            const renderFinanceExternalCarrierPriceListEditor = (
                record,
                createVersion = false
            ) => {
                const root = document.querySelector(
                    '[data-external-carrier-price-list-root]'
                );
                const detail = root?.querySelector(
                    '[data-external-carrier-price-list-detail]'
                );
                const template =
                    ensureFinanceExternalCarrierPriceListCreateCard();
                const current =
                    financeUnifiedPriceListCurrentVersion(record);
                const relationshipId = Number(
                    record?.relationship_id
                );
                const publicId = String(record?.public_id || '');

                if (
                    !root
                    || !detail
                    || !template
                    || !Number.isInteger(relationshipId)
                    || relationshipId < 1
                    || publicId === ''
                    || !current
                    || (
                        !createVersion
                        && current?.status !== 'draft'
                    )
                    || (
                        createVersion
                        && current?.status === 'draft'
                    )
                ) {
                    return;
                }

                const editor = template.cloneNode(true);

                editor.hidden = false;
                editor.classList.add('drayvia-price-admin-editor');
                editor.removeAttribute(
                    'data-external-carrier-price-list-create-card'
                );
                delete editor.dataset.externalCarrierCreateBound;
                editor.dataset.externalCarrierPriceListEditor = '1';
                editor.dataset.externalCarrierPriceListEditorMode =
                    createVersion ? 'create-version' : 'update';

                Array.from(editor.querySelectorAll('[id]')).forEach(
                    (element) => {
                        const originalId = element.id;
                        const editorId = originalId.replace(
                            'external-carrier-price-list',
                            'external-carrier-edit-price-list'
                        );
                        const label = editor.querySelector(
                            `label[for="${originalId}"]`
                        );

                        element.id = editorId;

                        if (label) {
                            label.htmlFor = editorId;
                        }
                    }
                );

                const title = editor.querySelector('h4');
                const relationship = editor.querySelector(
                    '[data-external-carrier-price-list-relationship]'
                );
                const name = editor.querySelector(
                    '[data-external-carrier-price-list-name]'
                );
                const currency = editor.querySelector(
                    '[data-external-carrier-price-list-currency]'
                );
                const validFrom = editor.querySelector(
                    '[data-external-carrier-price-list-valid-from]'
                );
                const validUntil = editor.querySelector(
                    '[data-external-carrier-price-list-valid-until]'
                );
                const save = editor.querySelector(
                    '[data-external-carrier-price-list-save]'
                );
                const cancel = editor.querySelector(
                    '[data-external-carrier-price-list-create-close]'
                );
                const message = editor.querySelector(
                    '[data-external-carrier-price-list-message]'
                );
                const addRule = editor.querySelector(
                    '[data-conditional-rule-add]'
                );
                const preset = editor.querySelector(
                    '[data-conditional-rule-preset]'
                );
                const conditionalList = editor.querySelector(
                    '[data-conditional-rule-list]'
                );
                const rateInputs = Array.from(
                    editor.querySelectorAll('[data-price-list-rate]')
                );

                if (
                    !relationship
                    || !name
                    || !currency
                    || !validFrom
                    || !validUntil
                    || !save
                    || !cancel
                    || !message
                    || !addRule
                    || !preset
                    || !conditionalList
                    || rateInputs.length !== 4
                ) {
                    renderFinanceExternalCarrierPriceListDetail(record);
                    financeBillingAdminNotice(
                        detail,
                        'Editor cenĂ­ku externĂ­ho dopravce se nepodaĹ™ilo pĹ™ipravit.',
                        'error'
                    );
                    return;
                }

                const relationshipField = relationship.closest(
                    '.drayvia-finance-field'
                );

                if (relationshipField) {
                    relationshipField.hidden = true;
                }

                relationship.value = String(relationshipId);
                relationship.disabled = true;
                name.value = record?.name || '';
                name.disabled = createVersion;
                currency.value = record?.currency || 'CZK';
                currency.disabled = true;
                validFrom.value = createVersion
                    ? ''
                    : current?.valid_from || '';
                validUntil.value = createVersion
                    ? ''
                    : current?.valid_until || '';
                save.textContent = createVersion
                    ? 'VytvoĹ™it koncept novĂ© verze'
                    : 'UloĹľit zmÄ›ny konceptu';
                cancel.textContent = 'ZruĹˇit Ăşpravy';

                if (title) {
                    title.textContent = createVersion
                        ? `NovĂˇ verze: ${record?.name || ''}`
                        : `Upravit koncept: ${record?.name || ''}`;
                }

                const notes = editor.querySelectorAll(
                    '.drayvia-finance-note'
                );
                const note = notes.item(notes.length - 1);

                if (note) {
                    note.textContent = createVersion
                        ? 'NovĂˇ verze vznikne jako koncept s kompletnĂ­ kopiĂ­ sazeb a pĹ™Ă­platkĹŻ. SchvĂˇlenĂ­ a aktivace zĹŻstanou samostatnĂ©.'
                        : 'UloĹľenĂ­ atomicky nahradĂ­ celĂ˝ koncept vÄŤetnÄ› sazeb a pĹ™Ă­platkĹŻ. AktivnĂ­ ani historickĂ© verze nelze upravovat.';
                }

                const items = Array.isArray(current?.items)
                    ? current.items
                    : [];
                const itemMap = new Map(
                    items.map((item) => [item?.code, item])
                );

                rateInputs.forEach((input) => {
                    const item = itemMap.get(
                        input.dataset.priceListRate
                    );

                    input.value = item?.unit_rate ?? '';
                });

                conditionalList.replaceChildren();

                const rules = Array.isArray(
                    current?.conditional_rules
                )
                    ? current.conditional_rules
                    : [];

                rules.forEach((rule) => {
                    hydrateFinanceConditionalRule(editor, rule);
                });

                updateFinanceConditionalEmptyState(editor);

                addRule.addEventListener('click', () => {
                    addFinanceConditionalRule(editor, preset.value);
                });

                cancel.addEventListener('click', () => {
                    renderFinanceExternalCarrierPriceListDetail(record);
                });

                save.addEventListener('click', async () => {
                    message.hidden = true;
                    message.dataset.state = '';

                    const normalizedName = name.value.trim();

                    if (normalizedName === '') {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'DoplĹte nĂˇzev cenĂ­ku.';
                        return;
                    }

                    if (validFrom.value === '') {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'DoplĹte datum platnosti od.';
                        return;
                    }

                    if (
                        validUntil.value !== ''
                        && validUntil.value < validFrom.value
                    ) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'Platnost do nesmĂ­ bĂ˝t pĹ™ed platnostĂ­ od.';
                        return;
                    }

                    const itemDescriptions = {
                        delivered_parcels:
                            'DoruÄŤenĂˇ zĂˇsilka',
                        redirected_parcels:
                            'PĹ™esmÄ›rovanĂˇ zĂˇsilka',
                        undelivered_parcels:
                            'OdmĂ­tnuto zĂˇkaznĂ­kem',
                        actual_km:
                            'SkuteÄŤnĂ˝ kilometr',
                    };
                    const canonicalCodes = [
                        'delivered_parcels',
                        'redirected_parcels',
                        'undelivered_parcels',
                        'actual_km',
                    ];
                    const rateMap = new Map(
                        rateInputs.map((input) => [
                            input.dataset.priceListRate,
                            input,
                        ])
                    );
                    const updatedItems = canonicalCodes.map((code) => {
                        const input = rateMap.get(code);
                        const existing = itemMap.get(code);

                        return {
                            code,
                            description:
                                existing?.description
                                || itemDescriptions[code],
                            unit_rate:
                                input?.value?.trim() || '',
                        };
                    });

                    if (
                        updatedItems.some(
                            (item) =>
                                item.unit_rate === ''
                                || !Number.isFinite(
                                    Number(item.unit_rate)
                                )
                                || Number(item.unit_rate) < 0
                        )
                    ) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            'VyplĹte vĹˇechny ÄŤtyĹ™i nezĂˇpornĂ© sazby.';
                        return;
                    }

                    let conditionalRules;

                    try {
                        conditionalRules =
                            collectFinanceConditionalRules(editor);
                    }
                    catch (error) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent = error.message;
                        return;
                    }

                    const versionBase =
                        `/api/v1/external-carriers/${
                            encodeURIComponent(
                                String(relationshipId)
                            )
                        }/price-lists/${
                            encodeURIComponent(publicId)
                        }/versions`;
                    const endpoint = createVersion
                        ? versionBase
                        : `${versionBase}/${
                            encodeURIComponent(
                                String(current.version_number)
                            )
                        }`;
                    const payload = createVersion
                        ? {
                            name: normalizedName,
                            description:
                                record?.description || null,
                            currency: record?.currency || 'CZK',
                            expected_current_version:
                                Number(current.version_number),
                            valid_from: validFrom.value,
                            valid_until:
                                validUntil.value || null,
                            change_reason:
                                'Complete external-carrier draft version created through Finance UI.',
                            items: updatedItems,
                            conditional_rules:
                                conditionalRules,
                        }
                        : {
                            name: normalizedName,
                            description:
                                record?.description || null,
                            expected_lock_version:
                                Number(current.lock_version),
                            valid_from: validFrom.value,
                            valid_until:
                                validUntil.value || null,
                            change_reason:
                                'Complete external-carrier draft updated through Finance UI.',
                            items: updatedItems,
                            conditional_rules:
                                conditionalRules,
                        };

                    save.disabled = true;
                    cancel.disabled = true;
                    message.hidden = false;
                    message.dataset.state = '';
                    message.textContent = createVersion
                        ? 'VytvĂˇĹ™Ă­m koncept novĂ© verzeâ€¦'
                        : 'UklĂˇdĂˇm celĂ˝ konceptâ€¦';

                    try {
                        await api(endpoint, {
                            method: createVersion ? 'POST' : 'PUT',
                            body: JSON.stringify(payload),
                        });

                        const nextRevision =
                            Number(current.lock_version) + 1;

                        await loadFinanceExternalCarrierPriceLists();

                        const updatedRecord =
                            financeExternalCarrierPriceLists.find(
                                (priceList) =>
                                    priceList?.public_id === publicId
                                    && Number(
                                        priceList?.relationship_id
                                    ) === relationshipId
                            ) || {
                                ...record,
                                name: normalizedName,
                            };

                        renderFinanceExternalCarrierPriceListDetail(
                            updatedRecord
                        );
                        financeBillingAdminNotice(
                            detail,
                            createVersion
                                ? 'Koncept novĂ© verze byl vytvoĹ™en.'
                                : `Koncept byl uloĹľen. AktuĂˇlnĂ­ revize: ${nextRevision}.`
                        );
                    }
                    catch (error) {
                        message.hidden = false;
                        message.dataset.state = 'error';
                        message.textContent =
                            `Koncept se nepodaĹ™ilo uloĹľit: ${error.message}`;
                    }
                    finally {
                        save.disabled = false;
                        cancel.disabled = false;
                    }
                });

                detail.replaceChildren(editor);
                detail.hidden = false;
                detail.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            };

            const runFinanceExternalCarrierPriceListLifecycle = async (
                record,
                current,
                action,
                validUntil = null
            ) => {
                const root = document.querySelector(
                    '[data-external-carrier-price-list-root]'
                );
                const detail = root?.querySelector(
                    '[data-external-carrier-price-list-detail]'
                );
                const relationshipId = Number(
                    record?.relationship_id
                );
                const publicId = String(record?.public_id || '');
                const versionNumber = Number(
                    current?.version_number
                );
                const lockVersion = Number(current?.lock_version);
                const contracts = {
                    approve: {
                        requiredStatus: 'draft',
                        path: 'approve',
                        question:
                            'Opravdu chcete schvĂˇlit tento koncept? Po schvĂˇlenĂ­ jiĹľ nepĹŻjde upravovat.',
                        pending: 'Schvaluji konceptâ€¦',
                        success: 'Koncept byl schvĂˇlen.',
                    },
                    activate: {
                        requiredStatus: 'approved',
                        path: 'activate',
                        question:
                            'Opravdu chcete tuto verzi aktivovat? PĹ™Ă­padnĂˇ pĹ™edchozĂ­ aktivnĂ­ verze bude nahrazena.',
                        pending: 'Aktivuji schvĂˇlenou verziâ€¦',
                        success: 'Verze byla aktivovĂˇna.',
                    },
                    expire: {
                        requiredStatus: 'active',
                        path: 'expire',
                        question:
                            'Opravdu chcete ukonÄŤit platnost aktivnĂ­ verze k vybranĂ©mu datu?',
                        pending: 'UkonÄŤuji platnost verzeâ€¦',
                        success: 'Platnost verze byla ukonÄŤena.',
                    },
                };
                const contract = contracts[action];

                if (
                    !detail
                    || !Number.isInteger(relationshipId)
                    || relationshipId < 1
                    || publicId === ''
                    || !Number.isInteger(versionNumber)
                    || versionNumber < 1
                    || !Number.isInteger(lockVersion)
                    || lockVersion < 1
                    || !contract
                    || current?.status !== contract.requiredStatus
                ) {
                    return;
                }

                if (
                    action === 'expire'
                    && (
                        typeof validUntil !== 'string'
                        || validUntil === ''
                    )
                ) {
                    financeBillingAdminNotice(
                        detail,
                        'Vyberte datum ukonÄŤenĂ­ platnosti.',
                        'error'
                    );
                    return;
                }

                if (!window.confirm(contract.question)) {
                    return;
                }

                const payload = {
                    expected_lock_version: lockVersion,
                };

                if (action === 'expire') {
                    payload.valid_until = validUntil;
                }

                financeBillingAdminNotice(detail, contract.pending);

                try {
                    await api(
                        `/api/v1/external-carriers/${
                            encodeURIComponent(
                                String(relationshipId)
                            )
                        }/price-lists/${
                            encodeURIComponent(publicId)
                        }/versions/${
                            encodeURIComponent(
                                String(versionNumber)
                            )
                        }/${contract.path}`,
                        {
                            method: 'POST',
                            body: JSON.stringify(payload),
                        }
                    );

                    await loadFinanceExternalCarrierPriceLists();

                    const updatedRecord =
                        financeExternalCarrierPriceLists.find(
                            (priceList) =>
                                priceList?.public_id === publicId
                                && Number(
                                    priceList?.relationship_id
                                ) === relationshipId
                        ) || record;

                    renderFinanceExternalCarrierPriceListDetail(
                        updatedRecord
                    );
                    financeBillingAdminNotice(
                        detail,
                        contract.success
                    );
                }
                catch (error) {
                    financeBillingAdminNotice(
                        detail,
                        `ZmÄ›na stavu se nepodaĹ™ila: ${error.message}`,
                        'error'
                    );
                }
            };

            const renderFinanceExternalCarrierPriceListDetail = (record) => {
                const root = document.querySelector(
                    '[data-external-carrier-price-list-root]'
                );
                const detail = root?.querySelector(
                    '[data-external-carrier-price-list-detail]'
                );

                if (!detail) {
                    return;
                }

                const current =
                    financeUnifiedPriceListCurrentVersion(record);
                const versions = Array.isArray(record?.versions)
                    ? record.versions
                    : [];
                const items = Array.isArray(current?.items)
                    ? current.items
                    : [];
                const rules = Array.isArray(
                    current?.conditional_rules
                )
                    ? current.conditional_rules
                    : [];
                const itemLabels = {
                    delivered_parcels: 'DoruÄŤenĂˇ zĂˇsilka',
                    redirected_parcels: 'PĹ™esmÄ›rovanĂˇ zĂˇsilka',
                    undelivered_parcels: 'OdmĂ­tnuto zĂˇkaznĂ­kem',
                    actual_km: 'SkuteÄŤnĂ˝ kilometr',
                };
                const sourceLabels = {
                    delivered_parcels: 'DoruÄŤeno',
                    redirected_parcels: 'PĹ™esmÄ›rovĂˇno',
                    customer_rejected_parcels:
                        'OdmĂ­tnuto zĂˇkaznĂ­kem',
                    loaded_parcels: 'NaloĹľeno',
                    actual_km: 'SkuteÄŤnĂ© km',
                };

                detail.replaceChildren();
                detail.hidden = false;

                const header = document.createElement('div');
                header.className = 'drayvia-price-admin-detail-header';

                const heading = document.createElement('div');
                const title = document.createElement('h4');
                const meta = document.createElement('p');
                const actions = document.createElement('div');
                const close = document.createElement('button');

                actions.className = 'drayvia-price-admin-actions';
                title.textContent = record?.name || 'Detail cenĂ­ku';
                meta.textContent = [
                    record?.external_carrier?.name || 'â€”',
                    record?.code || 'â€”',
                    financeCustomerStatus(current?.status || record?.status),
                    financeBillingPriceListPeriod(current),
                    `Revize ${current?.lock_version ?? 'â€”'}`,
                ].join(' Â· ');

                close.type = 'button';
                close.className = 'drayvia-price-admin-secondary';
                close.textContent = 'ZavĹ™Ă­t detail';
                close.addEventListener('click', () => {
                    detail.hidden = true;
                    detail.replaceChildren();
                });

                if (current?.status === 'draft') {
                    const edit = document.createElement('button');
                    const approve = document.createElement('button');

                    edit.type = 'button';
                    edit.className = 'drayvia-price-admin-primary';
                    edit.textContent = 'Upravit koncept';
                    edit.addEventListener('click', () => {
                        renderFinanceExternalCarrierPriceListEditor(
                            record
                        );
                    });

                    approve.type = 'button';
                    approve.className =
                        'drayvia-price-admin-secondary';
                    approve.textContent = 'SchvĂˇlit';
                    approve.addEventListener('click', () => {
                        runFinanceExternalCarrierPriceListLifecycle(
                            record,
                            current,
                            'approve'
                        );
                    });

                    actions.append(edit, approve);
                }
                else if (current?.status === 'approved') {
                    const activate = document.createElement('button');

                    activate.type = 'button';
                    activate.className = 'drayvia-price-admin-primary';
                    activate.textContent = 'Aktivovat';
                    activate.addEventListener('click', () => {
                        runFinanceExternalCarrierPriceListLifecycle(
                            record,
                            current,
                            'activate'
                        );
                    });

                    actions.appendChild(activate);
                }
                else if (current?.status === 'active') {
                    const expiration = document.createElement('input');
                    const expire = document.createElement('button');
                    const now = new Date();
                    const today = new Date(
                        now.getTime()
                        - now.getTimezoneOffset() * 60000
                    )
                        .toISOString()
                        .slice(0, 10);

                    expiration.type = 'date';
                    expiration.className = 'drayvia-finance-input';
                    expiration.max = today;
                    expiration.value = today;
                    expiration.setAttribute(
                        'aria-label',
                        'Datum ukonÄŤenĂ­ platnosti'
                    );
                    expiration.title =
                        'Datum ukonÄŤenĂ­ platnosti';

                    expire.type = 'button';
                    expire.className =
                        'drayvia-price-admin-secondary';
                    expire.textContent = 'UkonÄŤit platnost';
                    expire.addEventListener('click', () => {
                        runFinanceExternalCarrierPriceListLifecycle(
                            record,
                            current,
                            'expire',
                            expiration.value
                        );
                    });

                    actions.append(expiration, expire);
                }

                if (
                    current?.status === 'active'
                    || current?.status === 'expired'
                ) {
                    const newVersion = document.createElement('button');

                    newVersion.type = 'button';
                    newVersion.className = 'drayvia-price-admin-primary';
                    newVersion.textContent = 'NovĂˇ verze';
                    newVersion.addEventListener('click', () => {
                        renderFinanceExternalCarrierPriceListEditor(
                            record,
                            true
                        );
                    });
                    actions.appendChild(newVersion);
                }

                actions.appendChild(close);
                heading.append(title, meta);
                header.append(heading, actions);

                const ratesTitle = document.createElement('h4');
                ratesTitle.textContent = 'ZĂˇkladnĂ­ sazby';

                const ratesTable = document.createElement('table');
                ratesTable.className = 'drayvia-price-admin-table';
                const ratesHead = document.createElement('thead');
                const ratesHeadRow = document.createElement('tr');

                ['PoloĹľka', 'Jednotka', 'Sazba'].forEach((label) => {
                    const cell = document.createElement('th');
                    cell.textContent = label;
                    ratesHeadRow.appendChild(cell);
                });
                ratesHead.appendChild(ratesHeadRow);

                const ratesBody = document.createElement('tbody');

                if (items.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 3;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent = 'Verze nemĂˇ evidovanĂ© sazby.';
                    row.appendChild(cell);
                    ratesBody.appendChild(row);
                }
                else {
                    items.forEach((item) => {
                        const row = document.createElement('tr');
                        const amount = Number(item?.unit_rate);
                        const unit = item?.unit
                            || (item?.code === 'actual_km'
                                ? 'km'
                                : 'zĂˇsilka');
                        const rate = Number.isFinite(amount)
                            ? `${amount.toLocaleString('cs-CZ', {
                                maximumFractionDigits: 4,
                            })} ${record?.currency || 'CZK'}`
                            : 'â€”';

                        [
                            itemLabels[item?.code]
                                || item?.description
                                || item?.code
                                || 'â€”',
                            unit,
                            rate,
                        ].forEach((value) => {
                            const cell = document.createElement('td');
                            cell.textContent = String(value);
                            row.appendChild(cell);
                        });

                        ratesBody.appendChild(row);
                    });
                }

                ratesTable.append(ratesHead, ratesBody);

                const rulesTitle = document.createElement('h4');
                rulesTitle.textContent = 'PodmĂ­nÄ›nĂ© pĹ™Ă­platky';

                const rulesTable = document.createElement('table');
                rulesTable.className = 'drayvia-price-admin-table';
                const rulesHead = document.createElement('thead');
                const rulesHeadRow = document.createElement('tr');

                [
                    'PĹ™Ă­platek',
                    'Vzorec',
                    'VyhodnocenĂ­',
                    'ZpĹŻsob',
                    'PĂˇsma a ceny',
                ].forEach((label) => {
                    const cell = document.createElement('th');
                    cell.textContent = label;
                    rulesHeadRow.appendChild(cell);
                });
                rulesHead.appendChild(rulesHeadRow);

                const rulesBody = document.createElement('tbody');

                if (rules.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 5;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent =
                        'Verze nemĂˇ podmĂ­nÄ›nĂ© pĹ™Ă­platky.';
                    row.appendChild(cell);
                    rulesBody.appendChild(row);
                }
                else {
                    rules.forEach((rule) => {
                        const row = document.createElement('tr');
                        const numerators = Array.isArray(
                            rule?.metric_numerator_sources
                        )
                            ? rule.metric_numerator_sources
                            : [];
                        const denominators = Array.isArray(
                            rule?.metric_denominator_sources
                        )
                            ? rule.metric_denominator_sources
                            : [];
                        const formula = `(${numerators.map(
                            (source) => sourceLabels[source] || source
                        ).join(' + ') || 'â€”'}) / (${denominators.map(
                            (source) => sourceLabels[source] || source
                        ).join(' + ') || 'â€”'}) Ă— 100 %`;
                        const bands = Array.isArray(rule?.bands)
                            ? rule.bands
                            : [];
                        const bandsText = bands.length === 0
                            ? 'â€”'
                            : bands.map((band) => {
                                const minimum =
                                    band?.minimum_value ?? 'â’âž';
                                const maximum =
                                    band?.maximum_value ?? '+âž';
                                const left = band?.minimum_inclusive
                                    ? 'âź¨'
                                    : '(';
                                const right = band?.maximum_inclusive
                                    ? 'âź©'
                                    : ')';

                                return `${left}${minimum}; ${maximum}${right} â†’ ${band?.adjustment_value ?? 'â€”'} ${record?.currency || 'CZK'}`;
                            }).join('; ');

                        [
                            rule?.name || rule?.code || 'â€”',
                            formula,
                            rule?.evaluation_scope || 'â€”',
                            rule?.reward_method || 'â€”',
                            bandsText,
                        ].forEach((value) => {
                            const cell = document.createElement('td');
                            cell.textContent = String(value);
                            row.appendChild(cell);
                        });

                        rulesBody.appendChild(row);
                    });
                }

                rulesTable.append(rulesHead, rulesBody);

                const versionsTitle = document.createElement('h4');
                versionsTitle.textContent = 'Historie verzĂ­';

                const versionsTable = document.createElement('table');
                versionsTable.className = 'drayvia-price-admin-table';
                const versionsHead = document.createElement('thead');
                const versionsHeadRow = document.createElement('tr');

                ['Verze', 'Stav', 'Platnost', 'Revize'].forEach(
                    (label) => {
                        const cell = document.createElement('th');
                        cell.textContent = label;
                        versionsHeadRow.appendChild(cell);
                    }
                );
                versionsHead.appendChild(versionsHeadRow);

                const versionsBody = document.createElement('tbody');

                if (versions.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 4;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent =
                        'CenĂ­k nemĂˇ evidovanou verzi.';
                    row.appendChild(cell);
                    versionsBody.appendChild(row);
                }
                else {
                    versions.forEach((version) => {
                        const row = document.createElement('tr');
                        const period = `${financeCustomerDate(
                            version?.valid_from
                        )} â€“ ${financeCustomerDate(
                            version?.valid_until
                        )}`;

                        [
                            version?.version_number ?? 'â€”',
                            financeCustomerStatus(version?.status),
                            period,
                            version?.lock_version ?? 'â€”',
                        ].forEach((value) => {
                            const cell = document.createElement('td');
                            cell.textContent = String(value);
                            row.appendChild(cell);
                        });

                        versionsBody.appendChild(row);
                    });
                }

                versionsTable.append(versionsHead, versionsBody);
                detail.append(
                    header,
                    ratesTitle,
                    ratesTable,
                    rulesTitle,
                    rulesTable,
                    versionsTitle,
                    versionsTable
                );
            };

            const renderFinanceExternalCarrierPriceListIndex = () => {
                const root = document.querySelector(
                    '[data-external-carrier-price-list-root]'
                );
                const list = root?.querySelector(
                    '[data-external-carrier-price-list-list]'
                );

                if (!root || !list) {
                    return;
                }

                updateFinanceUnifiedPriceListCounts(
                    root,
                    financeExternalCarrierPriceLists
                );

                const selectedFilter =
                    root.dataset.unifiedPriceListFilter || 'all';
                const records = financeExternalCarrierPriceLists.filter(
                    (record) => selectedFilter === 'all'
                        || financeUnifiedPriceListCategory(record)
                            === selectedFilter
                );

                list.replaceChildren();

                if (records.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');

                    cell.colSpan = 6;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent =
                        financeExternalCarrierPriceLists.length === 0
                            ? 'Pro extern\u00ed dopravce zat\u00edm nen\u00ed evidov\u00e1n \u017e\u00e1dn\u00fd cen\u00edk.'
                            : 'Vybran\u00e9mu filtru neodpov\u00edd\u00e1 \u017e\u00e1dn\u00fd cen\u00edk.';

                    row.appendChild(cell);
                    list.appendChild(row);
                    return;
                }

                records.forEach((record) => {
                    const row = document.createElement('tr');

                    [
                        record?.external_carrier?.name || '\u2014',
                        record?.name || '\u2014',
                        financeUnifiedPriceListPeriod(record),
                        financeCustomerStatus(record?.status),
                        record?.current_version ?? '\u2014',
                    ].forEach((value) => {
                        const cell = document.createElement('td');
                        cell.textContent = String(value);
                        row.appendChild(cell);
                    });

                    const actionCell = document.createElement('td');
                    const detailButton = document.createElement('button');

                    detailButton.type = 'button';
                    detailButton.className = 'drayvia-price-admin-secondary';
                    detailButton.textContent = 'Otev\u0159\u00edt';
                    detailButton.addEventListener('click', () => {
                        renderFinanceExternalCarrierPriceListDetail(record);
                    });

                    actionCell.appendChild(detailButton);
                    row.appendChild(actionCell);
                    list.appendChild(row);
                });
            };

            const loadFinanceExternalCarrierPriceLists = async () => {
                const root = document.querySelector(
                    '[data-external-carrier-price-list-root]'
                );
                const list = root?.querySelector(
                    '[data-external-carrier-price-list-list]'
                );
                const endpoint = root?.dataset
                    .externalCarrierIndexEndpoint;

                if (!root || !list || !endpoint) {
                    return;
                }

                list.replaceChildren();

                const loadingRow = document.createElement('tr');
                const loadingCell = document.createElement('td');

                loadingCell.colSpan = 6;
                loadingCell.textContent =
                    'Na\u010d\u00edt\u00e1m cen\u00edky extern\u00edch dopravc\u016f\u2026';
                loadingRow.appendChild(loadingCell);
                list.appendChild(loadingRow);

                try {
                    const body = await api(endpoint);
                    const relationships = getPayload(body);
                    const records = Array.isArray(relationships)
                        ? relationships
                        : [];

                    financeExternalCarrierRelationships = records;
                    populateFinanceExternalCarrierPriceListSelect();

                    financeExternalCarrierPriceLists = records.flatMap(
                        (relationship) => {
                            const priceLists = Array.isArray(
                                relationship?.price_lists
                            )
                                ? relationship.price_lists
                                : [];

                            return priceLists.map((priceList) => ({
                                ...priceList,
                                relationship_id:
                                    relationship?.relationship_id,
                                external_carrier:
                                    relationship?.external_carrier || null,
                            }));
                        }
                    );

                    financeExternalCarrierPriceLists.sort(
                        (left, right) => String(
                            left?.external_carrier?.name || ''
                        ).localeCompare(
                            String(right?.external_carrier?.name || ''),
                            'cs'
                        ) || String(left?.name || '').localeCompare(
                            String(right?.name || ''),
                            'cs'
                        )
                    );

                    renderFinanceExternalCarrierPriceListIndex();
                }
                catch (error) {
                    financeExternalCarrierRelationships = [];
                    financeExternalCarrierPriceLists = [];
                    populateFinanceExternalCarrierPriceListSelect();
                    updateFinanceUnifiedPriceListCounts(root, []);
                    list.replaceChildren();

                    const row = document.createElement('tr');
                    const cell = document.createElement('td');

                    cell.colSpan = 6;
                    cell.className = 'drayvia-price-admin-empty';
                    cell.textContent =
                        `Cen\u00edky extern\u00edch dopravc\u016f se nepoda\u0159ilo na\u010d\u00edst: ${error.message}`;

                    row.appendChild(cell);
                    list.appendChild(row);
                }
            };

            const ensureFinanceExternalCarrierPriceListCreateCard = () => {
                const root = document.querySelector(
                    '[data-external-carrier-price-list-root]'
                );
                const panel = root?.closest(
                    '[data-price-list-panel="external-carriers"]'
                );

                if (!root || !panel) {
                    return null;
                }

                const existing = panel.querySelector(
                    '[data-external-carrier-price-list-create-card]'
                );

                if (existing) {
                    return existing;
                }

                const template = document.querySelector(
                    '[data-billing-price-list-create-card]'
                );

                if (!template) {
                    return null;
                }

                const createCard = template.cloneNode(true);
                const attributes = {
                    'data-billing-price-list-create-close':
                        'data-external-carrier-price-list-create-close',
                    'data-billing-price-list-customer':
                        'data-external-carrier-price-list-relationship',
                    'data-billing-price-list-name':
                        'data-external-carrier-price-list-name',
                    'data-billing-price-list-currency':
                        'data-external-carrier-price-list-currency',
                    'data-billing-price-list-valid-from':
                        'data-external-carrier-price-list-valid-from',
                    'data-billing-price-list-valid-until':
                        'data-external-carrier-price-list-valid-until',
                    'data-billing-price-list-save':
                        'data-external-carrier-price-list-save',
                    'data-billing-price-list-message':
                        'data-external-carrier-price-list-message',
                };

                createCard.removeAttribute(
                    'data-billing-price-list-create-card'
                );
                createCard.setAttribute(
                    'data-external-carrier-price-list-create-card',
                    ''
                );
                createCard.hidden = true;

                Object.entries(attributes).forEach(([
                    source,
                    target,
                ]) => {
                    const element = createCard.querySelector(
                        `[${source}]`
                    );

                    if (element) {
                        element.removeAttribute(source);
                        element.setAttribute(target, '');
                    }
                });

                Array.from(
                    createCard.querySelectorAll('[id]')
                ).forEach((element) => {
                    const originalId = element.id;
                    const externalId = originalId.replace(
                        'billing-price-list',
                        'external-carrier-price-list'
                    );
                    const label = createCard.querySelector(
                        `label[for="${originalId}"]`
                    );

                    element.id = externalId;

                    if (label) {
                        label.htmlFor = externalId;
                    }
                });

                const heading = createCard.querySelector('h4');
                const carrierSelect = createCard.querySelector(
                    '[data-external-carrier-price-list-relationship]'
                );
                const carrierLabel = carrierSelect
                    ?.closest('.drayvia-finance-field')
                    ?.querySelector('label');
                const conditionalHeading = createCard.querySelector(
                    '[data-conditional-rule-root] h5'
                );
                const save = createCard.querySelector(
                    '[data-external-carrier-price-list-save]'
                );
                const note = createCard.querySelector(
                    '.drayvia-finance-note:not([data-external-carrier-price-list-message])'
                );

                if (heading) {
                    heading.textContent =
                        'Nov\u00fd cen\u00edk extern\u00edho dopravce';
                }

                if (carrierLabel) {
                    carrierLabel.textContent = 'Extern\u00ed dopravce';
                }

                if (conditionalHeading) {
                    conditionalHeading.textContent =
                        'P\u0159\u00edplatky extern\u00edho dopravce';
                }

                if (save) {
                    save.textContent =
                        'Ulo\u017eit cen\u00edk extern\u00edho dopravce';
                }

                if (note) {
                    note.textContent =
                        'Cen\u00edk se ulo\u017e\u00ed jako kompletn\u00ed draft v1. Schv\u00e1len\u00ed a aktivace z\u016fst\u00e1vaj\u00ed samostatn\u00e9 kroky.';
                }

                root.insertAdjacentElement('afterend', createCard);

                return createCard;
            };

            const populateFinanceExternalCarrierPriceListSelect = () => {
                const createCard =
                    ensureFinanceExternalCarrierPriceListCreateCard();
                const select = createCard?.querySelector(
                    '[data-external-carrier-price-list-relationship]'
                );

                if (!select) {
                    return;
                }

                const selected = select.value;
                const placeholder = document.createElement('option');

                placeholder.value = '';
                placeholder.textContent =
                    'Vyberte extern\u00edho dopravce';
                select.replaceChildren(placeholder);

                financeExternalCarrierRelationships
                    .slice()
                    .sort((left, right) => String(
                        left?.external_carrier?.name || ''
                    ).localeCompare(
                        String(right?.external_carrier?.name || ''),
                        'cs'
                    ))
                    .forEach((relationship) => {
                        const relationshipId = Number(
                            relationship?.relationship_id
                        );

                        if (!Number.isInteger(relationshipId)) {
                            return;
                        }

                        const option = document.createElement('option');

                        option.value = String(relationshipId);
                        option.textContent =
                            relationship?.external_carrier?.name
                            || `Extern\u00ed dopravce ${relationshipId}`;
                        select.appendChild(option);
                    });

                if (
                    Array.from(select.options).some(
                        (option) => option.value === selected
                    )
                ) {
                    select.value = selected;
                }
            };

            const bindFinanceExternalCarrierPriceListCreate = () => {
                const root = document.querySelector(
                    '[data-external-carrier-price-list-root]'
                );
                const createCard =
                    ensureFinanceExternalCarrierPriceListCreateCard();
                const relationship = createCard?.querySelector(
                    '[data-external-carrier-price-list-relationship]'
                );
                const name = createCard?.querySelector(
                    '[data-external-carrier-price-list-name]'
                );
                const currency = createCard?.querySelector(
                    '[data-external-carrier-price-list-currency]'
                );
                const validFrom = createCard?.querySelector(
                    '[data-external-carrier-price-list-valid-from]'
                );
                const validUntil = createCard?.querySelector(
                    '[data-external-carrier-price-list-valid-until]'
                );
                const save = createCard?.querySelector(
                    '[data-external-carrier-price-list-save]'
                );
                const message = createCard?.querySelector(
                    '[data-external-carrier-price-list-message]'
                );
                const addRule = createCard?.querySelector(
                    '[data-conditional-rule-add]'
                );
                const preset = createCard?.querySelector(
                    '[data-conditional-rule-preset]'
                );
                const rateInputs = createCard
                    ? Array.from(
                        createCard.querySelectorAll(
                            '[data-price-list-rate]'
                        )
                    )
                    : [];

                if (
                    !root
                    || !createCard
                    || !relationship
                    || !name
                    || !currency
                    || !validFrom
                    || !validUntil
                    || !save
                    || !message
                    || !addRule
                    || !preset
                    || rateInputs.length !== 4
                    || createCard.dataset.externalCarrierCreateBound
                        === '1'
                ) {
                    return;
                }

                const itemDescriptions = {
                    delivered_parcels:
                        'Doru\u010den\u00e1 z\u00e1silka',
                    redirected_parcels:
                        'P\u0159esm\u011brovan\u00e1 z\u00e1silka',
                    undelivered_parcels:
                        'Odm\u00edtnuto z\u00e1kazn\u00edkem',
                    actual_km:
                        'Skute\u010dn\u00fd kilometr',
                };
                const canonicalCodes = [
                    'delivered_parcels',
                    'redirected_parcels',
                    'undelivered_parcels',
                    'actual_km',
                ];

                createCard.dataset.externalCarrierCreateBound = '1';
                resetFinanceConditionalRules(createCard);
                populateFinanceExternalCarrierPriceListSelect();

                addRule.addEventListener('click', () => {
                    addFinanceConditionalRule(
                        createCard,
                        preset.value
                    );
                });

                root.querySelector(
                    '[data-external-carrier-price-list-create-open]'
                )?.addEventListener('click', () => {
                    populateFinanceExternalCarrierPriceListSelect();
                    root.hidden = true;
                    createCard.hidden = false;
                });

                createCard.querySelector(
                    '[data-external-carrier-price-list-create-close]'
                )?.addEventListener('click', () => {
                    createCard.hidden = true;
                    root.hidden = false;
                });

                save.addEventListener('click', async () => {
                    const relationshipId = Number(relationship.value);

                    if (
                        !Number.isInteger(relationshipId)
                        || relationshipId < 1
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Vyberte extern\u00edho dopravce.';
                        return;
                    }

                    if (
                        name.value.trim() === ''
                        || validFrom.value === ''
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Vypl\u0148te n\u00e1zev cen\u00edku a platnost od.';
                        return;
                    }

                    if (
                        validUntil.value !== ''
                        && validUntil.value < validFrom.value
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Platnost do nesm\u00ed b\u00fdt p\u0159ed platnost\u00ed od.';
                        return;
                    }

                    const rateMap = new Map(
                        rateInputs.map((input) => [
                            input.dataset.priceListRate,
                            input,
                        ])
                    );
                    const items = canonicalCodes.map((code) => {
                        const input = rateMap.get(code);
                        const unitRate = input?.value?.trim() ?? '';

                        return {
                            code,
                            description: itemDescriptions[code],
                            unit_rate: unitRate,
                        };
                    });

                    if (
                        items.some(
                            (item) =>
                                item.unit_rate === ''
                                || !Number.isFinite(
                                    Number(item.unit_rate)
                                )
                                || Number(item.unit_rate) < 0
                        )
                    ) {
                        message.hidden = false;
                        message.textContent =
                            'Vypl\u0148te v\u0161echny \u010dty\u0159i nez\u00e1porn\u00e9 sazby.';
                        return;
                    }

                    let conditionalRules;

                    try {
                        conditionalRules =
                            collectFinanceConditionalRules(createCard);
                    }
                    catch (error) {
                        message.hidden = false;
                        message.textContent = error.message;
                        return;
                    }

                    const endpoint = root.dataset
                        .externalCarrierStoreEndpoint
                        .replace(
                            '{relationship}',
                            encodeURIComponent(String(relationshipId))
                        );

                    save.disabled = true;
                    message.hidden = false;
                    message.textContent =
                        'Ukl\u00e1d\u00e1m cen\u00edk, sazby a podm\u00edn\u011bn\u00e9 p\u0159\u00edplatky\u2026';

                    try {
                        await api(endpoint, {
                            method: 'POST',
                            body: JSON.stringify({
                                name: name.value.trim(),
                                currency: currency.value,
                                valid_from: validFrom.value,
                                valid_until:
                                    validUntil.value || null,
                                change_reason:
                                    'Customer-managed external-carrier price list created through Finance UI.',
                                items,
                                conditional_rules:
                                    conditionalRules,
                            }),
                        });

                        message.textContent =
                            `Cen\u00edk extern\u00edho dopravce byl ulo\u017een jako draft v1 s ${conditionalRules.length} podm\u00edn\u011bn\u00fdmi p\u0159\u00edplatky.`;
                        name.value = '';
                        rateInputs.forEach((input) => {
                            input.value = '';
                        });
                        resetFinanceConditionalRules(createCard);
                        await loadFinanceExternalCarrierPriceLists();
                    }
                    catch (error) {
                        message.textContent =
                            `Cen\u00edk extern\u00edho dopravce se nepoda\u0159ilo ulo\u017eit: ${error.message}`;
                    }
                    finally {
                        save.disabled = false;
                    }
                });
            };

            const bindFinanceExternalCarrierPriceListAdministration = () => {
                const root = document.querySelector(
                    '[data-external-carrier-price-list-root]'
                );

                if (
                    !root
                    || root.dataset.externalCarrierPriceListBound === '1'
                ) {
                    return;
                }

                root.dataset.externalCarrierPriceListBound = '1';

                root.querySelectorAll(
                    '[data-unified-price-list-filter]'
                ).forEach((button) => {
                    button.addEventListener('click', () => {
                        root.dataset.unifiedPriceListFilter =
                            button.dataset.unifiedPriceListFilter || 'all';
                        renderFinanceExternalCarrierPriceListIndex();
                    });
                });

                root.querySelector(
                    '[data-external-carrier-price-list-reload]'
                )?.addEventListener('click', () => {
                    loadFinanceExternalCarrierPriceLists();
                });

                bindFinanceExternalCarrierPriceListCreate();
            };
            const bindFinanceUnifiedPriceListAdministration = () => {
                document
                    .querySelectorAll('[data-unified-price-list-domain]')
                    .forEach((admin) => {
                        if (admin.dataset.unifiedPriceListBound === '1') {
                            return;
                        }

                        admin.dataset.unifiedPriceListBound = '1';

                        const filters = Array.from(
                            admin.querySelectorAll(
                                '[data-unified-price-list-filter]'
                            )
                        );

                        filters.forEach((filter) => {
                            filter.addEventListener('click', () => {
                                filters.forEach((candidate) => {
                                    candidate.classList.toggle(
                                        'is-active',
                                        candidate === filter
                                    );
                                });

                                admin.dataset.unifiedPriceListFilter =
                                    filter.dataset.unifiedPriceListFilter
                                    || 'all';
                            });
                        });
                    });
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

                    await loadFinanceBillingPriceLists(
                        customers
                    );

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
            bindDriverStatisticsTabs();
        }

        if (page === 'imports') {
            bindDepotImportPreview();
        }

        if (page === 'record-review') {
            bindDepotDriverRecordReview();
        }

if (page === 'finance') {
            bindFinanceCustomerCreate();
            bindFinanceUnifiedPriceListAdministration();
            bindFinanceDriverPriceListAdministration();
            bindFinanceExternalCarrierPriceListAdministration();
            bindFinanceBillingPriceListAdministration();
            bindFinanceBillingPriceListCreate();
            bindFinanceDriverPriceListCreate();
            loadFinanceDriverAssignments();
            loadFinanceDriverPriceLists();
            loadFinanceExternalCarrierPriceLists();
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
