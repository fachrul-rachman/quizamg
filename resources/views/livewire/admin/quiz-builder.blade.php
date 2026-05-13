@php
    $optionKeys = ['A', 'B', 'C', 'D', 'E'];
    $difficultyOptions = \App\Support\QuestionDifficulty::options();
@endphp

<div class="qb">
    <style>
        @verbatim
        /* ─── RESET & TOKENS ─── */
        .qb *, .qb *::before, .qb *::after { box-sizing: border-box; }
        .qb {
            --bg: #F5F4F1;
            --surface: #FFFFFF;
            --surface-2: #F9F8F6;
            --border: #E4E2DC;
            --border-focus: #2D6BE4;
            --text-1: #1A1917;
            --text-2: #6B6860;
            --text-3: #A8A59E;
            --blue: #2D6BE4;
            --blue-light: #EBF1FD;
            --blue-mid: #C5D8FA;
            --amber: #D97706;
            --amber-light: #FEF3C7;
            --teal: #0D9488;
            --teal-light: #CCFBF1;
            --coral: #DC4E2A;
            --coral-light: #FEE8E2;
            --green: #16A34A;
            --green-light: #DCFCE7;
            --red: #DC2626;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-1);
            -webkit-font-smoothing: antialiased;
        }

        /* ─── LAYOUT ─── */
        .qb .main {
            max-width: 760px;
            margin: 0 auto;
            padding: 24px 16px 170px;
        }
        @media (min-width: 600px) {
            .qb .main { padding: 28px 24px 170px; }
        }

        /* ─── ALERTS ─── */
        .qb .success {
            margin-bottom: 14px;
            border: 1px solid #BBF7D0;
            background: #ECFDF5;
            color: #065F46;
            padding: 10px 14px;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 500;
        }
        .qb .danger {
            margin-bottom: 14px;
            border: 1px solid #FECACA;
            background: #FEF2F2;
            color: #7F1D1D;
            padding: 10px 14px;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 500;
        }
        .qb .error {
            margin-top: 6px;
            font-size: 11.5px;
            color: var(--red);
            font-weight: 600;
        }
        .qb .qb-spinner {
            animation: qbSpin 0.9s linear infinite;
        }
        @keyframes qbSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .qb .toast {
            position: fixed;
            right: 16px;
            top: 16px;
            z-index: 9999;
            background: #111827;
            color: #fff;
            border-radius: 12px;
            padding: 12px 14px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.18);
            font-size: 13px;
            line-height: 1.35;
            max-width: min(360px, calc(100vw - 32px));
            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
            transition: opacity 0.16s ease, transform 0.16s ease;
        }
        .qb .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .qb .toast-title {
            font-weight: 700;
            margin-bottom: 2px;
        }
        .qb .toast-desc {
            color: rgba(255,255,255,0.85);
        }
        .qb .hint {
            font-size: 11.5px;
            color: var(--text-3);
            margin-top: 5px;
            line-height: 1.5;
        }

        /* ─── SECTION ─── */
        .qb .section {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-bottom: 14px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        .qb .section:focus-within { box-shadow: var(--shadow-md); }

        .qb .section-header {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
        }
        .qb .section-head-right {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .qb .section-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .qb .icon-blue   { background: var(--blue-light); }
        .qb .icon-amber  { background: var(--amber-light); }
        .qb .icon-teal   { background: var(--teal-light); }
        .qb .icon-coral  { background: var(--coral-light); }
        .qb .section-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-1);
            letter-spacing: 0.01em;
        }
        .qb .section-body { padding: 18px; }

        /* ─── FIELDS ─── */
        .qb .field { margin-bottom: 16px; }
        .qb .field:last-child { margin-bottom: 0; }

        .qb .label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-2);
            margin-bottom: 6px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .qb .label .optional {
            font-weight: 400;
            color: var(--text-3);
            text-transform: none;
            letter-spacing: 0;
            margin-left: 4px;
        }

        /* ─── INPUTS ─── */
        .qb input[type=text],
        .qb textarea,
        .qb select {
            width: 100%;
            padding: 10px 13px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            font-family: inherit;
            font-size: 14px;
            color: var(--text-1);
            outline: none;
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
            appearance: none;
            -webkit-appearance: none;
        }
        .qb input[type=text]:hover,
        .qb textarea:hover,
        .qb select:hover { border-color: #C8C5BE; }
        .qb input[type=text]:focus,
        .qb textarea:focus,
        .qb select:focus {
            border-color: var(--blue);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(45,107,228,0.1);
        }
        .qb input[type=text]::placeholder,
        .qb textarea::placeholder { color: var(--text-3); }

        .qb textarea {
            resize: vertical;
            min-height: 80px;
            line-height: 1.6;
        }

        .qb select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cpath d='M3 5l4 4 4-4' stroke='%23888' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 11px center;
            padding-right: 34px;
            cursor: pointer;
        }

        .qb input[type=file] {
            width: 100%;
            font-size: 13px;
            color: var(--text-2);
            padding: 8px 0;
        }

        /* ─── FIELD ROW (2-col) ─── */
        .qb .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 520px) {
            .qb .field-row { grid-template-columns: 1fr; }
        }

        /* ─── DURATION INPUT ─── */
        .qb .dur-wrap {
            display: flex;
            align-items: center;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            overflow: hidden;
            transition: border-color 0.15s, box-shadow 0.15s;
            width: fit-content;
        }
        .qb .dur-wrap:focus-within {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(45,107,228,0.1);
        }
        .qb .dur-wrap input[type=text] {
            width: 68px;
            border: none;
            border-radius: 0;
            background: transparent;
            text-align: center;
            font-family: 'DM Mono', monospace;
            font-size: 15px;
            font-weight: 500;
            padding: 9px 12px;
            box-shadow: none !important;
        }
        .qb .dur-sep { width: 1px; background: var(--border); align-self: stretch; }
        .qb .dur-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-2);
            padding: 0 13px;
            white-space: nowrap;
        }

        /* ─── TOGGLE ROWS ─── */
        .qb .toggle-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .qb .toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
        .qb .toggle-row:first-child { padding-top: 0; }
        .qb .toggle-info { flex: 1; }
        .qb .toggle-label { font-size: 14px; font-weight: 500; color: var(--text-1); }
        .qb .toggle-desc  { font-size: 12px; color: var(--text-3); margin-top: 3px; line-height: 1.5; }

        /* Custom toggle switch */
        .qb .toggle {
            position: relative;
            width: 40px;
            height: 22px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .qb .toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
        .qb .toggle .slider {
            position: absolute;
            inset: 0;
            border-radius: 99px;
            background: var(--border);
            cursor: pointer;
            transition: background 0.2s;
        }
        .qb .toggle .slider::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            left: 3px;
            top: 3px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: transform 0.2s cubic-bezier(.4,0,.2,1);
        }
        .qb .toggle input:checked + .slider { background: var(--blue); }
        .qb .toggle input:checked + .slider::before { transform: translateX(18px); }

        /* ─── IMPORT BOX ─── */
        .qb .import-box {
            border: 2px dashed var(--border);
            border-radius: var(--radius-md);
            padding: 24px 16px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            background: var(--surface-2);
        }
        .qb .import-box:hover { border-color: var(--blue); background: var(--blue-light); }
        .qb .import-icon {
            width: 44px;
            height: 44px;
            background: var(--blue-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }
        .qb .import-box:hover .import-icon { background: var(--blue-mid); }
        .qb .import-text { font-size: 13.5px; color: var(--text-2); line-height: 1.5; }
        .qb .import-text b { color: var(--blue); font-weight: 600; }
        .qb .import-hint { font-size: 11.5px; color: var(--text-3); margin-top: 5px; line-height: 1.5; }

        /* ─── IMPORT FILE ROW ─── */
        .qb .import-file-row {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            margin-top: 14px;
        }
        .qb .import-file-row > div:first-child { flex: 1; }

        /* ─── BUTTONS ─── */
        .qb .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
            background: var(--surface);
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text-1);
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, box-shadow 0.15s, transform 0.1s;
            text-decoration: none;
            white-space: nowrap;
            user-select: none;
        }
        .qb .btn:hover { background: var(--surface-2); border-color: #C8C5BE; }
        .qb .btn:active { transform: scale(0.98); }

        .qb .btn-primary {
            background: var(--blue);
            border-color: var(--blue);
            color: #fff;
            font-weight: 600;
        }
        .qb .btn-primary:hover { background: #1A52C2; border-color: #1A52C2; box-shadow: 0 4px 12px rgba(45,107,228,0.3); }

        .qb .btn-soft {
            background: var(--blue-light);
            border-color: rgba(45,107,228,0.25);
            color: var(--blue);
            font-weight: 500;
        }
        .qb .btn-soft:hover { background: var(--blue-mid); border-color: rgba(45,107,228,0.35); }

        .qb .btn-danger {
            background: var(--coral-light);
            border-color: rgba(220,78,42,0.25);
            color: var(--coral);
            font-weight: 500;
        }
        .qb .btn-danger:hover { background: #fdd9cf; border-color: rgba(220,78,42,0.35); }

        .qb .btn-sm { padding: 6px 11px; font-size: 12px; border-radius: 7px; }

        /* ─── SOAL SECTION COUNT ─── */
        .qb .soal-count-num {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-3);
            font-family: 'DM Mono', monospace;
        }

        /* ─── SOAL CARDS ─── */
        .qb .soal-card {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            margin-top: 12px;
            overflow: hidden;
            background: var(--surface);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .qb .soal-card:focus-within {
            border-color: var(--blue-mid);
            box-shadow: var(--shadow-sm);
        }

        .qb .soal-head {
            padding: 10px 14px;
            background: var(--surface-2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            gap: 10px;
        }
        .qb .soal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-2);
        }
        .qb .soal-num-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--blue-light);
            color: var(--blue);
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(45,107,228,0.2);
        }
        .qb .difficulty-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 10.5px;
            font-weight: 700;
            border: 1px solid transparent;
            line-height: 1;
        }
        .qb .difficulty-mudah {
            background: #DCFCE7;
            color: #166534;
            border-color: #BBF7D0;
        }
        .qb .difficulty-sedang {
            background: #E0F2FE;
            color: #075985;
            border-color: #BAE6FD;
        }
        .qb .difficulty-sulit {
            background: #FEF3C7;
            color: #92400E;
            border-color: #FDE68A;
        }
        .qb .difficulty-sangat_sulit {
            background: #FFE4E6;
            color: #9F1239;
            border-color: #FECDD3;
        }
        .qb .soal-actions { display: flex; align-items: center; gap: 6px; }

        .qb .del-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 500;
            color: var(--coral);
            cursor: pointer;
            background: none;
            border: none;
            font-family: inherit;
            padding: 5px 8px;
            border-radius: 6px;
            transition: background 0.15s;
        }
        .qb .del-btn:hover { background: var(--coral-light); }

        .qb .soal-body { padding: 16px; }
        .qb .soal-field { margin-bottom: 14px; }
        .qb .soal-field:last-child { margin-bottom: 0; }

        /* ─── JENIS ROW ─── */
        .qb .jenis-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .qb .jenis-row > div:first-child { flex: 1 1 180px; }
        .qb .jenis-row > .jenis-level { flex: 1 1 180px; }
        .qb .jenis-row > .jenis-status { flex: 0 0 auto; }

        /* ─── STATUS BADGE ─── */
        .qb .badge-aktif {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            background: var(--green-light);
            color: var(--green);
            border: 1px solid rgba(22,163,74,0.2);
        }
        .qb .badge-nonaktif {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            background: #F3F4F6;
            color: #6B7280;
            border: 1px solid rgba(107,114,128,0.2);
        }

        /* ─── IMG UPLOAD ─── */
        .qb .img-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 13px;
            border: 1.5px dashed var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            font-size: 12.5px;
            color: var(--text-2);
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s, color 0.15s;
        }
        .qb .img-upload-btn:hover { border-color: var(--blue); background: var(--blue-light); color: var(--blue); }

        .qb .preview-img {
            margin-top: 10px;
            max-height: 220px;
            width: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: var(--surface-2);
            object-fit: contain;
            display: block;
        }
        .qb .img-preview-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        /* ─── OPTIONS (Multiple Choice) ─── */
        .qb .opsi-list { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }

        .qb .opsi-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
        }
        .qb .opsi-item.correct {
            border-color: var(--green);
            background: var(--green-light);
            box-shadow: 0 0 0 3px rgba(22,163,74,0.1);
        }

        .qb .opsi-key {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: #EEF2FF;
            border: 1px solid rgba(45,107,228,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #1F57C9;
            flex-shrink: 0;
            font-family: 'DM Mono', monospace;
        }
        .qb .opsi-item.correct .opsi-key {
            background: var(--green-light);
            border-color: rgba(22,163,74,0.3);
            color: var(--green);
        }

        .qb .opsi-content { flex: 1; min-width: 0; }
        .qb .opsi-input {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--text-1);
            outline: none;
            padding: 0;
        }
        .qb .opsi-input::placeholder { color: var(--text-3); }
        .qb .opsi-item.correct .opsi-input { font-weight: 500; }

        .qb .correct-tag {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 10.5px;
            font-weight: 600;
            color: var(--green);
            background: #fff;
            border: 1px solid rgba(22,163,74,0.3);
            border-radius: 4px;
            padding: 2px 7px;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .qb .opsi-actions { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }

        .qb .btn-mark-correct {
            font-size: 11px;
            font-weight: 500;
            color: var(--green);
            background: var(--green-light);
            border: 1px solid rgba(22,163,74,0.25);
            padding: 4px 9px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s;
            font-family: inherit;
            white-space: nowrap;
        }
        .qb .btn-mark-correct:hover { background: #CFFAE0; }

        .qb .opsi-del {
            font-size: 15px;
            line-height: 1;
            color: var(--text-3);
            cursor: pointer;
            flex-shrink: 0;
            padding: 3px 5px;
            border-radius: 5px;
            transition: color 0.15s, background 0.15s;
            border: none;
            background: none;
            font-family: inherit;
        }
        .qb .opsi-del:hover { color: var(--coral); background: var(--coral-light); }

        /* Opsi image preview */
        .qb .opsi-img-wrap { margin-top: 8px; }
        .qb .opsi-preview-img {
            max-height: 120px;
            width: auto;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface-2);
            object-fit: contain;
            display: block;
            margin-bottom: 6px;
        }

        /* Add opsi button */
        .qb .add-opsi-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--blue);
            cursor: pointer;
            padding: 7px 2px;
            border: none;
            background: none;
            font-family: inherit;
            transition: opacity 0.15s;
            margin-top: 2px;
        }
        .qb .add-opsi-btn:hover { opacity: 0.7; }

        /* ─── SHORT ANSWER ─── */
        .qb .jawaban-box {
            border: 1.5px solid var(--green);
            border-radius: var(--radius-sm);
            background: var(--green-light);
            overflow: hidden;
        }
        .qb .jawaban-box-header {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-bottom: 1px solid #BBF7D0;
            background: #D1FAE5;
        }
        .qb .jawaban-box-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--green);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .qb .jawaban-input-wrap { padding: 10px 12px; }
        .qb .jawaban-input-wrap input {
            border: none;
            background: transparent;
            box-shadow: none !important;
            padding: 0;
            font-size: 14px;
            color: var(--text-1);
            font-weight: 500;
            width: 100%;
            outline: none;
            font-family: inherit;
        }
        .qb .jawaban-input-wrap input::placeholder { color: #6EE7B7; font-weight: 400; }

        /* ─── FOOTER ─── */
        .qb .footer {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            position: sticky;
            bottom: 14px;
            z-index: 20;
            padding: 10px;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(228,226,220,0.9);
            background: rgba(255,255,255,0.86);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: var(--shadow-md);
        }
        @media (max-width: 640px) {
            .qb .footer { justify-content: stretch; }
            .qb .footer .btn { flex: 1; justify-content: center; }
        }

        /* ─── SOAL HINT ─── */
        .qb .soal-section-hint {
            font-size: 12px;
            color: var(--text-3);
            margin-bottom: 4px;
        }
        @endverbatim
    </style>

    <div class="main">
        <div id="qb-toast" class="toast" role="status" aria-live="polite">
            <div class="toast-title">Periksa lagi ya</div>
            <div class="toast-desc">Ada input yang belum lengkap.</div>
        </div>

        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        @error('questions')
            <div class="danger">{{ $message }}</div>
        @enderror

        {{-- ─── INFORMASI QUIZ ─── --}}
        <div class="section" id="qb-section-info">
            <div class="section-header">
                <div class="section-icon icon-blue">
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><rect x="1.5" y="1.5" width="12" height="12" rx="2" stroke="#2D6BE4" stroke-width="1.3"/><path d="M4 5h7M4 7.5h7M4 10h4.5" stroke="#2D6BE4" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <span class="section-title">Informasi Quiz</span>
            </div>
            <div class="section-body">
                <div class="field" style="margin-bottom:16px">
                    <label class="label">Nama Quiz</label>
                    <input type="text" wire:model.defer="title" placeholder="Contoh: UTS Matematika Kelas 10" />
                    @error('title') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label class="label">Deskripsi <span class="optional">(opsional)</span></label>
                    <textarea wire:model.defer="description" placeholder="Deskripsi singkat tentang quiz ini..."></textarea>
                    @error('description') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- ─── PENGATURAN ─── --}}
        <div class="section" id="qb-section-settings">
            <div class="section-header">
                <div class="section-icon icon-amber">
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1.5a1.5 1.5 0 010 3 1.5 1.5 0 010-3zM2.5 6.5h10M4 10.5a1.5 1.5 0 010 3 1.5 1.5 0 010-3zM11 10.5a1.5 1.5 0 010 3 1.5 1.5 0 010-3z" stroke="#D97706" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <span class="section-title">Pengaturan</span>
            </div>
            <div class="section-body">
                <div class="field-row" style="margin-bottom:16px">
                    <div>
                        <label class="label">Durasi</label>
                        <div class="dur-wrap">
                            <input type="text" wire:model.defer="durationMinutes" inputmode="numeric" />
                            <div class="dur-sep"></div>
                            <span class="dur-label">menit</span>
                        </div>
                        <p class="hint">0 = tidak ada batas waktu</p>
                        @error('durationMinutes') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="toggle-row" style="padding-top:0">
                    <div class="toggle-info">
                        <div class="toggle-label">Shuffle Soal</div>
                        <div class="toggle-desc">Acak urutan soal untuk setiap peserta</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" wire:model.defer="shuffleQuestions" />
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Kesulitan Bertingkat</div>
                        <div class="toggle-desc">Kelompokkan soal dari mudah sampai sangat sulit</div>
                        @error('difficultyLevelsEnabled') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <label class="toggle">
                        <input type="checkbox" wire:model.live="difficultyLevelsEnabled" />
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Shuffle Opsi Jawaban</div>
                        <div class="toggle-desc">Acak urutan pilihan jawaban</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" wire:model.defer="shuffleOptions" />
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Tampilkan Jawaban Benar</div>
                        <div class="toggle-desc">Peserta langsung melihat benar/salah setelah memilih</div>
                        @error('instantFeedbackEnabled') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <label class="toggle">
                        <input type="checkbox" wire:model.defer="instantFeedbackEnabled" />
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Status Aktif</div>
                        <div class="toggle-desc">Quiz dapat diakses oleh peserta</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" wire:model.defer="isActive" />
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        {{-- ─── IMPORT SOAL ─── --}}
        <div class="section" id="qb-section-import">
            <div class="section-header">
                <div class="section-icon icon-teal">
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1v9M4.5 7l3 3 3-3" stroke="#0D9488" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.5 11v2h10v-2" stroke="#0D9488" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <span class="section-title">Import Soal</span>
                <div class="section-head-right">
                    <a href="{{ url('/admin/quizzes/template') }}" class="btn btn-sm btn-soft" target="_blank" rel="noreferrer">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 1v7M3.5 5.5L6 8l2.5-2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M1.5 10h9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                        Download Template
                    </a>
                </div>
            </div>
            <div class="section-body">
                <div class="import-box">
                    <div class="import-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="2" width="16" height="16" rx="3" stroke="#2D6BE4" stroke-width="1.4"/><path d="M7 10h6M10 7v6" stroke="#2D6BE4" stroke-width="1.4" stroke-linecap="round"/></svg>
                    </div>
                    <div class="import-text"><b>Pilih file .xlsx</b> atau seret ke sini</div>
                    <div class="import-hint">Kolom: Soal, Jenis Jawaban, Opsi A–E, Jawaban Benar, Short Answer, Tingkat Kesulitan</div>
                </div>

                <div class="import-file-row">
                    <div>
                        <label class="label">File (.xlsx)</label>
                        <input type="file" wire:model="importFile" accept=".xlsx" />
                        @error('importFile') <div class="error">{{ $message }}</div> @enderror
                        <p class="hint">Import akan menambahkan soal baru ke bawah daftar soal yang ada.</p>
                    </div>
                    <div>
                        <button type="button" wire:click="importFromXlsx" class="btn btn-primary">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v8M4 6l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 11h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            Import Soal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── SOAL ─── --}}
        <div class="section" id="qb-section-questions">
            <div class="section-header">
                <div class="section-icon icon-coral">
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><circle cx="7.5" cy="7.5" r="6" stroke="#DC4E2A" stroke-width="1.3"/><path d="M7.5 4.5v3.5" stroke="#DC4E2A" stroke-width="1.5" stroke-linecap="round"/><circle cx="7.5" cy="10" r="0.75" fill="#DC4E2A"/></svg>
                </div>
                <span class="section-title">Soal</span>
                <div class="section-head-right">
                    <span class="soal-count-num">{{ count($questions) }} soal</span>
                    <button type="button" wire:click="addQuestion" class="btn btn-sm btn-soft">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        Tambah Soal
                    </button>
                </div>
            </div>
            <div class="section-body">
                <p class="soal-section-hint">Pastikan setiap soal pilihan ganda memiliki 1 jawaban benar yang jelas.</p>

                @foreach ($questions as $qi => $q)
                    <div class="soal-card" id="qb-question-{{ $qi }}">
                        {{-- Soal Header --}}
                        <div class="soal-head">
                            <div class="soal-badge">
                                <div class="soal-num-dot">{{ $qi + 1 }}</div>
                                <span>Soal {{ $qi + 1 }}</span>
                                @if ($difficultyLevelsEnabled)
                                    @php
                                        $difficultyLevel = $q['difficulty_level'] ?? \App\Support\QuestionDifficulty::DEFAULT;
                                    @endphp
                                    <span class="difficulty-pill difficulty-{{ $difficultyLevel }}">
                                        {{ \App\Support\QuestionDifficulty::label($difficultyLevel) }}
                                    </span>
                                @endif
                            </div>
                            <div class="soal-actions">
                                <button type="button" wire:click="removeQuestion({{ $qi }})" class="del-btn">
                                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M2 3.5h9M5 3.5V2h3v1.5M5.5 10V5.5M7.5 10V5.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><path d="M3 3.5l.5 7h6l.5-7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                                    Hapus
                                </button>
                            </div>
                        </div>

                        {{-- Soal Body --}}
                        <div class="soal-body">

                            {{-- Pertanyaan --}}
                            <div class="soal-field">
                                <label class="label">Pertanyaan</label>
                                <textarea wire:model.defer="questions.{{ $qi }}.question_text" placeholder="Tulis pertanyaan di sini..."></textarea>
                                @error('questions.'.$qi.'.question_text') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            {{-- Gambar Soal --}}
                            <div class="soal-field">
                                <label class="label">Gambar Soal <span class="optional">(opsional)</span></label>
                                <input type="file" wire:model="questions.{{ $qi }}.question_image_upload" accept="image/*" />
                                @error('questions.'.$qi.'.question_image_upload') <div class="error">{{ $message }}</div> @enderror

                                @php
                                    $previewUrl = null;
                                    if (!empty($q['question_image_upload'])) {
                                        $previewUrl = $q['question_image_upload']->temporaryUrl();
                                    } elseif (!empty($q['question_image_path']) && empty($q['remove_question_image'])) {
                                        $previewUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($q['question_image_path']);
                                    }
                                @endphp

                                @if ($previewUrl)
                                    <div class="img-preview-row">
                                        <img src="{{ $previewUrl }}" alt="Preview soal {{ $qi + 1 }}" class="preview-img" />
                                        <button type="button" wire:click="removeQuestionImage({{ $qi }})" class="btn btn-sm btn-danger">Hapus Gambar</button>
                                    </div>
                                @endif
                                <p class="hint">Teks atau gambar — boleh salah satu atau keduanya.</p>
                            </div>

                            {{-- Jenis Jawaban + Status --}}
                            <div class="soal-field">
                                <div class="jenis-row">
                                    <div>
                                        <label class="label">Jenis Jawaban</label>
                                        <select wire:model.live="questions.{{ $qi }}.question_type">
                                            <option value="multiple_choice">Multiple Choice</option>
                                            <option value="short_answer">Short Answer</option>
                                        </select>
                                        @error('questions.'.$qi.'.question_type') <div class="error">{{ $message }}</div> @enderror
                                    </div>
                                    @if ($difficultyLevelsEnabled)
                                        <div class="jenis-level">
                                            <label class="label">Tingkat Kesulitan</label>
                                            <select wire:model.live="questions.{{ $qi }}.difficulty_level">
                                                @foreach ($difficultyOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('questions.'.$qi.'.difficulty_level') <div class="error">{{ $message }}</div> @enderror
                                        </div>
                                    @endif
                                    <div class="jenis-status">
                                        @if (!empty($q['is_active']))
                                            <span class="badge-aktif">
                                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M1.5 5l2.5 2.5 4.5-4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="badge-nonaktif">Nonaktif</span>
                                        @endif
                                    </div>
                                </div>
                                {{-- Hidden status toggle tetap ada untuk wire binding --}}
                                <div style="margin-top:8px">
                                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-2);cursor:pointer;">
                                        <label class="toggle" style="margin:0">
                                            <input type="checkbox" wire:model.defer="questions.{{ $qi }}.is_active" />
                                            <span class="slider"></span>
                                        </label>
                                        Status Soal Aktif
                                    </label>
                                </div>
                            </div>

                            {{-- Multiple Choice --}}
                            @if (($q['question_type'] ?? 'multiple_choice') === 'multiple_choice')
                                <div class="soal-field">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                        <label class="label" style="margin:0">Opsi Jawaban <span class="optional">— klik "Jadikan benar" untuk tandai</span></label>
                                        <button type="button" wire:click="addOption({{ $qi }})" class="add-opsi-btn">
                                            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6.5 1v11M1 6.5h11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            Tambah Opsi
                                        </button>
                                    </div>
                                    @error('questions.'.$qi.'.options') <div class="error">{{ $message }}</div> @enderror

                                    <div class="opsi-list">
                                        @foreach (($q['options'] ?? []) as $oi => $opt)
                                            @php $isCorrect = !empty($opt['is_correct']); @endphp
                                            <div class="opsi-item {{ $isCorrect ? 'correct' : '' }}">
                                                <div class="opsi-key">{{ $optionKeys[$oi] ?? ($oi + 1) }}</div>

                                                <div class="opsi-content">
                                                    <input
                                                        class="opsi-input"
                                                        type="text"
                                                        wire:model.defer="questions.{{ $qi }}.options.{{ $oi }}.option_text"
                                                        placeholder="Tulis opsi {{ $optionKeys[$oi] ?? ($oi + 1) }}..."
                                                    />
                                                    @error('questions.'.$qi.'.options.'.$oi.'.option_text') <div class="error">{{ $message }}</div> @enderror

                                                    {{-- Gambar opsi --}}
                                                    @php
                                                        $optionPreviewUrl = null;
                                                        if (!empty($opt['option_image_upload'])) {
                                                            $optionPreviewUrl = $opt['option_image_upload']->temporaryUrl();
                                                        } elseif (!empty($opt['option_image_path']) && empty($opt['remove_option_image'])) {
                                                            $optionPreviewUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($opt['option_image_path']);
                                                        }
                                                    @endphp

                                                    <div class="opsi-img-wrap">
                                                        @if ($optionPreviewUrl)
                                                            <img src="{{ $optionPreviewUrl }}" alt="Gambar opsi {{ $optionKeys[$oi] ?? ($oi + 1) }}" class="opsi-preview-img" />
                                                            <button type="button" wire:click="removeOptionImage({{ $qi }}, {{ $oi }})" class="btn btn-sm btn-danger" style="margin-bottom:6px">Hapus Gambar</button>
                                                        @endif
                                                        <div>
                                                            <input type="file" wire:model="questions.{{ $qi }}.options.{{ $oi }}.option_image_upload" accept="image/*" />
                                                            @error('questions.'.$qi.'.options.'.$oi.'.option_image_upload') <div class="error">{{ $message }}</div> @enderror
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="opsi-actions">
                                                    @if ($isCorrect)
                                                        <span class="correct-tag">
                                                            <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            Benar
                                                        </span>
                                                    @else
                                                        <button type="button" wire:click="markCorrect({{ $qi }}, {{ $oi }})" class="btn-mark-correct">Jadikan benar</button>
                                                    @endif
                                                    <button type="button" wire:click="removeOption({{ $qi }}, {{ $oi }})" class="opsi-del">×</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            {{-- Short Answer --}}
                            @else
                                <div class="soal-field">
                                    <label class="label">Jawaban yang Diterima</label>
                                    <div class="jawaban-box">
                                        <div class="jawaban-box-header">
                                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1.5 6l3 3 6-6" stroke="#16A34A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <span class="jawaban-box-label">Jawaban Utama</span>
                                        </div>
                                        <div class="jawaban-input-wrap">
                                            <input
                                                type="text"
                                                wire:model.defer="questions.{{ $qi }}.short_answers"
                                                placeholder="Contoh: Jakarta|DKI Jakarta"
                                            />
                                        </div>
                                    </div>
                                    @error('questions.'.$qi.'.short_answers') <div class="error">{{ $message }}</div> @enderror
                                    <p class="hint">Pisahkan beberapa jawaban dengan tanda |. Pencocokan tidak memperhatikan huruf kapital.</p>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach

            </div>
        </div>

        {{-- ─── FOOTER ─── --}}
        <div class="footer">
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                class="btn btn-primary"
            >
                <span wire:loading.remove.flex wire:target="save" style="align-items:center;gap:8px">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5 6.5-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Simpan Quiz
                </span>
                <span wire:loading.flex wire:target="save" style="align-items:center;gap:8px">
                    <svg class="qb-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 2a10 10 0 1 0 10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Menyimpan...
                </span>
            </button>
            <a href="{{ url('/admin/quizzes') }}" class="btn">Kembali</a>
        </div>

    </div>
</div>

<script>
    (function () {
        function showToast(message) {
            const el = document.getElementById('qb-toast');
            if (!el) return;
            const desc = el.querySelector('.toast-desc');
            if (desc) desc.textContent = message || 'Ada input yang belum lengkap.';
            el.classList.add('show');
            window.clearTimeout(window.__qbToastTimer);
            window.__qbToastTimer = window.setTimeout(() => el.classList.remove('show'), 3200);
        }

        function scrollToTarget(targetId) {
            const el = document.getElementById(targetId);
            if (!el) return false;
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return true;
        }

        window.addEventListener('qb:validation-failed', function (e) {
            let detail = (e && e.detail) ? e.detail : {};
            if (Array.isArray(detail) && detail[0] && typeof detail[0] === 'object') {
                detail = detail[0];
            }

            const firstErrorKey = detail.firstErrorKey || null;
            showToast(detail.message || 'Ada yang perlu diperbaiki. Kamu akan diarahkan ke bagian yang bermasalah.');

            if (typeof firstErrorKey === 'string' && firstErrorKey.startsWith('questions.')) {
                const match = firstErrorKey.match(/^questions\.(\d+)\./);
                if (match) {
                    scrollToTarget('qb-question-' + match[1]);
                    return;
                }
                scrollToTarget('qb-section-questions');
                return;
            }

            if (firstErrorKey === 'title' || firstErrorKey === 'description') {
                scrollToTarget('qb-section-info');
                return;
            }

            if (firstErrorKey === 'durationMinutes' || firstErrorKey === 'shuffleQuestions' || firstErrorKey === 'shuffleOptions' || firstErrorKey === 'instantFeedbackEnabled' || firstErrorKey === 'isActive') {
                scrollToTarget('qb-section-settings');
                return;
            }

            scrollToTarget('qb-section-info');
        });
    })();
</script>
