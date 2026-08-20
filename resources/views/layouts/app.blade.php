<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --jeny-primary: #0B2545;
            --jeny-secondary: #13355F;
            --jeny-accent: #C9A24B;
            --text-primary: #10151F;
            --text-secondary: #667085;
            --bg: #FFFFFF;
            --bg-secondary: #F7F5F0;
            --border: #E4EAF2;
            --success: #0F8B5E;
            --warning: #C9A24B;
            --danger: #D92D20;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 10px 30px -12px rgba(11, 37, 69, 0.18);
            --shadow-md: 0 20px 40px -15px rgba(11, 37, 69, 0.28);
            --sidebar-w: 260px;
        }
        * { box-sizing: border-box; margin: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        :focus-visible { outline: 3px solid var(--jeny-accent); outline-offset: 2px; }

        /* ========== SIDEBAR ========== */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w); z-index: 100;
            background: var(--jeny-primary);
            display: flex; flex-direction: column;
            transition: transform .25s ease;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: .75rem;
            padding: 1.25rem 1.25rem 1rem;
            text-decoration: none;
        }
        .sidebar-brand img { height: 32px; width: auto; }
        .sidebar-brand span { font-weight: 700; font-size: .95rem; color: white; }

        .sidebar-nav {
            flex: 1; overflow-y: auto; padding: .5rem .75rem;
            display: flex; flex-direction: column; gap: 2px;
        }
        .sidebar-nav::-webkit-scrollbar { width: 0; }

        .sidebar-link {
            display: flex; align-items: center; gap: .65rem;
            padding: .55rem .75rem; border-radius: var(--radius-sm);
            color: rgba(255,255,255,.85); text-decoration: none;
            font-size: .82rem; font-weight: 500; white-space: nowrap;
            transition: background .15s, color .15s;
        }
        .sidebar-link:hover { background: rgba(255,255,255,.1); color: white; }
        .sidebar-link.active { background: rgba(255,255,255,.18); color: white; font-weight: 600; }
        .sidebar-link.active i { color: var(--jeny-accent); }
        .sidebar-link i { font-size: 1.05rem; width: 20px; text-align: center; flex-shrink: 0; }

        .sidebar-section {
            font-size: .65rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .1em; color: rgba(255,255,255,.35);
            padding: .75rem .75rem .35rem;
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,.1);
            padding: .75rem;
        }
        .sidebar-user {
            display: flex; align-items: center; gap: .6rem;
            padding: .5rem .6rem; border-radius: var(--radius-sm);
        }
        .sidebar-avatar {
            width: 34px; height: 34px; border-radius: var(--radius-sm);
            background: var(--jeny-accent); color: var(--jeny-primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .8rem; flex-shrink: 0;
        }
        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-name { font-weight: 600; font-size: .82rem; color: white; line-height: 1.1; display: block; }
        .sidebar-user-role { font-size: .65rem; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; display: block; }
        .sidebar-logout {
            display: flex; align-items: center; gap: .5rem;
            width: 100%; padding: .45rem .6rem; margin-top: .35rem;
            border: 0; border-radius: var(--radius-sm);
            background: rgba(255,255,255,.08); color: rgba(255,255,255,.6);
            font-family: inherit; font-size: .75rem; font-weight: 500;
            cursor: pointer; transition: background .15s, color .15s;
        }
        .sidebar-logout:hover { background: rgba(255,255,255,.15); color: white; }
        .sidebar-logout svg { width: 16px; height: 16px; }

        /* Mobile toggle */
        .sidebar-toggle {
            display: none; position: fixed; top: .75rem; left: .75rem; z-index: 110;
            width: 40px; height: 40px; border-radius: var(--radius-sm);
            background: var(--jeny-primary); border: 0; color: white;
            cursor: pointer; align-items: center; justify-content: center;
        }
        .sidebar-toggle svg { width: 20px; height: 20px; }
        .sidebar-overlay {
            display: none; position: fixed; inset: 0; z-index: 90;
            background: rgba(0,0,0,.4);
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        .topbar {
            position: sticky; top: 0; z-index: 50;
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(11, 37, 69, 0.06);
            padding: 0 2rem;
            height: 56px; display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-title { font-size: .85rem; font-weight: 600; color: var(--text-primary); }
        .topbar-right { display: flex; align-items: center; gap: .75rem; }

        .page { width: 100%; max-width: 1200px; margin: 0 auto; padding: 1.5rem 2rem 3rem; flex: 1; }

        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: .5rem;
            font-size: .7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .12em; color: var(--jeny-accent);
        }
        .page-head h1 { margin: .4rem 0 0; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }
        .page-head .page-sub { margin: .35rem 0 0; font-size: .85rem; color: var(--text-secondary); }

        /* ---------- Cards ---------- */
        .card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }
        .card h2 { margin: 0 0 1rem; font-size: .9rem; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: .5rem; }
        .card h2 .ic { color: var(--jeny-primary); }
        .card-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .card-head h2 { margin: 0; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .stat {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 1rem 1.25rem;
            position: relative;
        }
        .stat .value { font-size: 1.75rem; font-weight: 700; color: var(--text-primary); letter-spacing: -.01em; margin-top: .35rem; }
        .stat .value.accent { color: var(--jeny-accent); }
        .stat .value.green { color: var(--success); }
        .stat .label { color: var(--text-secondary); font-size: .72rem; font-weight: 500; text-transform: uppercase; letter-spacing: .08em; }
        .stat .sub { color: var(--text-secondary); font-size: .78rem; margin-top: .3rem; }

        /* ---------- Tables ---------- */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .72rem .85rem; border-bottom: 1px solid var(--border); font-size: .83rem; color: var(--text-primary); }
        th { color: var(--text-secondary); font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; background: var(--bg-secondary); }
        tr:last-child td { border-bottom: 0; }
        tbody tr:hover td { background: var(--bg-secondary); }

        /* ---------- Badges charte ---------- */
        .badge {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .25rem .65rem; border-radius: 9999px;
            font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em;
        }
        .badge::before { content: ''; width: 5px; height: 5px; border-radius: 9999px; background: currentColor; }
        .badge.active, .badge.successful, .badge.paid, .badge.synced, .badge.verified { background: #ECFDF3; color: #027A48; }
        .badge.grace_period, .badge.pending, .badge.processing, .badge.issued, .badge.partially_paid { background: #FFFAEB; color: #B54708; }
        .badge.suspended, .badge.blocked, .badge.overdue, .badge.terminated, .badge.failed, .badge.expired, .badge.refused { background: #FEF3F2; color: #B42318; }
        .badge.cancelled, .badge.refunded, .badge.draft, .badge.prospect, .badge.pending_approval { background: #F2F4F7; color: #475467; }

        /* ---------- Boutons charte ---------- */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .55rem 1.1rem; border-radius: var(--radius-sm);
            border: 0; cursor: pointer; text-decoration: none;
            font-family: inherit; font-size: .8rem; font-weight: 600;
            transition: background .15s, color .15s, border-color .15s;
        }
        .btn.primary { background: var(--jeny-primary); color: white; }
        .btn.primary:hover { background: var(--jeny-secondary); }
        .btn.outline { background: transparent; color: var(--jeny-primary); border: 1px solid var(--jeny-primary); }
        .btn.outline:hover { background: var(--jeny-primary); color: white; }
        .btn.accent { background: var(--jeny-accent); color: white; }
        .btn.accent:hover { background: #B8923F; }
        .btn.danger { background: var(--danger); color: white; }
        .btn.danger:hover { background: #B42318; }
        .btn.sm { padding: .35rem .8rem; font-size: .72rem; }

        /* ---------- Misc ---------- */
        .muted { color: var(--text-secondary); }
        .amount { font-weight: 600; color: var(--text-primary); }
        .amount.accent { color: var(--jeny-accent); }
        .dl { display: grid; grid-template-columns: 200px 1fr; gap: .45rem 1rem; margin: 0; }
        .dl dt { color: var(--text-secondary); font-weight: 500; font-size: .78rem; }
        .dl dd { margin: 0; font-weight: 500; font-size: .85rem; color: var(--text-primary); }

        /* ---------- Auth ---------- */
        .auth-page { background: var(--bg-secondary); min-height: 100vh; }
        .auth-wrap { width: 100%; }
        .auth-card {
            display: flex; min-height: 100vh;
        }
        .auth-card-left {
            flex: 0 0 42%; max-width: 42%;
            background: var(--jeny-primary);
            display: flex; flex-direction: column;
            justify-content: space-between;
            padding: 2.5rem;
            position: relative; overflow: hidden;
        }
        .auth-card-left::before {
            content: ''; position: absolute; top: -30%; right: -20%;
            width: 500px; height: 500px; border-radius: 50%;
            background: rgba(201, 162, 75, 0.08);
        }
        .auth-card-left::after {
            content: ''; position: absolute; bottom: -20%; left: -15%;
            width: 400px; height: 400px; border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
        }
        .auth-card-left-content { position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .auth-logo { height: 42px; width: auto; margin-bottom: 2rem; filter: brightness(0) invert(1); }
        .auth-card-left h2 { font-size: 1.75rem; font-weight: 700; color: white; line-height: 1.25; margin-bottom: .75rem; }
        .auth-card-left p { font-size: .88rem; color: rgba(255,255,255,.65); line-height: 1.6; max-width: 320px; }
        .auth-features { display: flex; flex-direction: column; gap: .65rem; margin-top: 2rem; }
        .auth-feature {
            display: flex; align-items: center; gap: .6rem;
            font-size: .82rem; color: rgba(255,255,255,.8); font-weight: 500;
        }
        .auth-feature i {
            width: 32px; height: 32px; border-radius: 8px;
            background: rgba(255,255,255,.1);
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem; color: var(--jeny-accent);
        }
        .auth-card-left-footer {
            position: relative; z-index: 1;
            font-size: .72rem; color: rgba(255,255,255,.35);
        }
        .auth-card-right {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 2rem;
            background: var(--bg);
        }
        .auth-form-wrap { width: 100%; max-width: 400px; }
        .auth-form-header { text-align: center; margin-bottom: 2rem; }
        .auth-form-icon {
            font-size: 2.5rem; color: var(--jeny-primary);
            margin-bottom: .75rem; display: block;
        }
        .auth-form-header h1 { font-size: 1.4rem; font-weight: 700; color: var(--text-primary); margin: 0 0 .35rem; }
        .auth-form-header p { font-size: .85rem; color: var(--text-secondary); margin: 0; }
        .auth-form { display: flex; flex-direction: column; gap: 1rem; }
        .auth-field label {
            display: flex; align-items: center; gap: .4rem;
            font-size: .78rem; font-weight: 600; color: var(--text-primary);
            margin-bottom: .35rem;
        }
        .auth-field label i { color: var(--jeny-primary); font-size: .85rem; }
        .auth-field input {
            width: 100%; padding: .65rem .85rem;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-size: .88rem; font-family: inherit; color: var(--text-primary);
            background: white; transition: border-color .15s, box-shadow .15s;
        }
        .auth-field input:focus {
            outline: none; border-color: var(--jeny-primary);
            box-shadow: 0 0 0 3px rgba(11, 37, 69, 0.08);
        }
        .auth-field input::placeholder { color: #B0B8C9; }
        .auth-field-error { font-size: .72rem; color: var(--danger); margin-top: .25rem; display: block; }
        .auth-field-row {
            display: flex; align-items: center; justify-content: space-between;
            font-size: .8rem;
        }
        .auth-checkbox {
            display: flex; align-items: center; gap: .4rem;
            cursor: pointer; color: var(--text-secondary); font-size: .8rem;
        }
        .auth-checkbox input[type=checkbox] {
            width: auto; accent-color: var(--jeny-primary);
        }
        .auth-link {
            color: var(--jeny-primary); font-weight: 600; text-decoration: none;
            font-size: .8rem;
        }
        .auth-link:hover { color: var(--jeny-secondary); text-decoration: underline; }
        .auth-btn {
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            width: 100%; padding: .7rem 1.5rem; margin-top: .5rem;
            background: var(--jeny-primary); color: white; border: 0;
            border-radius: var(--radius-sm); font-family: inherit;
            font-size: .88rem; font-weight: 600; cursor: pointer;
            transition: background .15s, transform .1s;
        }
        .auth-btn:hover { background: var(--jeny-secondary); transform: translateY(-1px); }
        .auth-btn:active { transform: translateY(0); }
        .auth-form-footer {
            text-align: center; margin-top: 1.5rem; padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        .auth-form-footer a {
            display: inline-flex; align-items: center; gap: .35rem;
            color: var(--text-secondary); font-size: .8rem; font-weight: 500;
            text-decoration: none; transition: color .15s;
        }
        .auth-form-footer a:hover { color: var(--jeny-primary); }
        .error, .success {
            border-radius: var(--radius-sm); padding: .75rem 1rem; margin-bottom: 1rem;
            font-size: .85rem; font-weight: 500; display: flex; align-items: center; gap: .5rem;
        }
        .error { background: #FEF3F2; color: #B42318; border: 1px solid #FECDCA; }
        .success { background: #ECFDF3; color: #027A48; border: 1px solid #A6F4C5; }
        @media (max-width: 768px) {
            .auth-card { flex-direction: column; }
            .auth-card-left { flex: none; max-width: 100%; padding: 2rem 1.5rem; }
            .auth-card-left h2 { font-size: 1.35rem; }
            .auth-features { display: none; }
            .auth-card-left-footer { display: none; }
            .auth-card-right { padding: 1.5rem; }
        }

        /* ---------- Hero (client dashboard) ---------- */
        .hero {
            border-radius: var(--radius-md);
            background: var(--jeny-primary); color: white;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            padding: 1.5rem 1.75rem;
        }
        .hero h1 { margin: .35rem 0 0; font-size: 1.4rem; font-weight: 700; color: white; }
        .hero .hero-sub { margin: .4rem 0 0; color: rgba(255,255,255,.75); font-size: .85rem; line-height: 1.5; }
        .hero .hero-metrics { display: flex; gap: 2rem; margin-top: 1rem; flex-wrap: wrap; }
        .hero .hero-metric { text-align: center; }
        .hero .hero-metric .hnum { font-size: 1.5rem; font-weight: 700; color: white; tabular-nums: true; }
        .hero .hero-metric .hlab { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.65); font-weight: 500; margin-top: .15rem; }

        /* ---------- Evolution badges ---------- */
        .evolution {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .15rem .55rem; border-radius: 9999px;
            font-size: .7rem; font-weight: 600;
        }
        .evolution.up { background: #ECFDF3; color: #027A48; }
        .evolution.down { background: #FEF3F2; color: #B42318; }
        .evolution.neutral { background: #F2F4F7; color: #475467; }

        /* ---------- Accent bars ---------- */
        .accent-bar { position: relative; }
        .accent-bar::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 4px; border-radius: var(--radius-md) 0 0 var(--radius-md);
            background: var(--jeny-primary);
        }
        .accent-bar.orange::before { background: var(--jeny-accent); }
        .accent-bar.green::before { background: var(--success); }
        .accent-bar.red::before { background: var(--danger); }

        /* ---------- Alertes ---------- */
        .alert-card {
            display: flex; align-items: flex-start; gap: .75rem;
            border-radius: var(--radius-md); padding: 1rem 1.25rem;
            margin-bottom: .75rem; border: 1px solid;
        }
        .alert-card.warning { background: #FFFAEB; border-color: rgba(201,162,75,.25); color: #B54708; }
        .alert-card.danger { background: #FEF3F2; border-color: rgba(217,45,32,.25); color: #B42318; }
        .alert-card.success { background: #ECFDF3; border-color: rgba(15,139,94,.25); color: #027A48; }
        .alert-card .alert-icon { flex-shrink: 0; margin-top: 2px; }
        .alert-card .alert-body { flex: 1; min-width: 0; }
        .alert-card .alert-title { font-size: .85rem; font-weight: 600; }
        .alert-card .alert-text { font-size: .78rem; margin-top: .2rem; opacity: .85; }

        /* ---------- Quick actions ---------- */
        .quick-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
        .quick-action {
            display: inline-flex; align-items: center; gap: .45rem;
            padding: .6rem 1.1rem; border-radius: var(--radius-sm);
            font-size: .82rem; font-weight: 600; text-decoration: none;
            transition: background .15s, color .15s, transform .1s;
            cursor: pointer; border: 0;
        }
        .quick-action.primary { background: var(--jeny-primary); color: white; }
        .quick-action.primary:hover { background: var(--jeny-secondary); transform: translateY(-1px); }
        .quick-action.accent { background: var(--jeny-accent); color: white; }
        .quick-action.accent:hover { background: #B8923F; transform: translateY(-1px); }
        .quick-action.outline {
            background: white; color: var(--jeny-primary);
            border: 1px solid var(--jeny-primary);
        }
        .quick-action.outline:hover { background: var(--jeny-primary); color: white; }

        /* ---------- Widget card ---------- */
        .widget-card {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: var(--radius-md); box-shadow: var(--shadow-sm);
            margin-bottom: 1rem;
        }
        .widget-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
        }
        .widget-title {
            font-size: .85rem; font-weight: 600; color: var(--text-primary);
            display: flex; align-items: center; gap: .5rem;
        }
        .widget-body { padding: 1.25rem; }

        /* ---------- Stat card améliorée ---------- */
        .stat.improved { padding-left: 1.4rem; }
        .stat.improved::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 4px; border-radius: var(--radius-md) 0 0 var(--radius-md);
            background: var(--jeny-primary);
        }
        .stat.improved.accent::before { background: var(--jeny-accent); }
        .stat.improved.green::before { background: var(--success); }
        .stat.improved.red::before { background: var(--danger); }

        /* ---------- Responsive ---------- */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .sidebar-overlay.open { display: block; }
            .main-content { margin-left: 0; }
            .topbar { padding-left: 3.5rem; }
        }
        @media (max-width: 640px) {
            .dl { grid-template-columns: 1fr; gap: .2rem; }
            .dl dd { margin-bottom: .5rem; }
            .page { padding: 1rem; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="@auth @else auth-page @endauth">
    @auth
        {{-- Mobile toggle --}}
        <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open');document.querySelector('.sidebar-overlay').classList.toggle('open')" aria-label="Menu">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
        </button>
        <div class="sidebar-overlay" onclick="document.querySelector('.sidebar').classList.remove('open');this.classList.remove('open')"></div>

        {{-- Sidebar --}}
        <aside class="sidebar">
            <a class="sidebar-brand" href="{{ route('dashboard.index') }}">
                <img src="{{ asset('img/logo-jeny.png') }}" alt="JENY SAS">
                <span>JENY SAS</span>
            </a>

            <nav class="sidebar-nav" aria-label="Navigation espace client">
                <div class="sidebar-section">Menu</div>
                <a class="sidebar-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
                    <i class="bi bi-grid-1x2"></i>
                    Tableau de bord
                </a>
                <a class="sidebar-link {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}" href="{{ route('client.invoices.index') }}">
                    <i class="bi bi-receipt"></i>
                    Factures
                </a>
                <a class="sidebar-link {{ request()->routeIs('client.payments.*') ? 'active' : '' }}" href="{{ route('client.payments.index') }}">
                    <i class="bi bi-credit-card"></i>
                    Paiements
                </a>
                <a class="sidebar-link {{ request()->routeIs('client.sessions.*') ? 'active' : '' }}" href="{{ route('client.sessions.index') }}">
                    <i class="bi bi-graph-up"></i>
                    Consommation
                </a>

                <div class="sidebar-section">Compte</div>
                <a class="sidebar-link {{ request()->routeIs('client.profile.*') ? 'active' : '' }}" href="{{ route('client.profile.show') }}">
                    <i class="bi bi-person"></i>
                    Mon profil
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <span class="sidebar-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name">{{ auth()->user()->name }}</span>
                        <span class="sidebar-user-role">{{ auth()->user()->isClient() ? 'Client' : 'Staff' }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="sidebar-logout" type="submit">
                        <i class="bi bi-box-arrow-left"></i>
                        Déconnexion
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="main-content">
            <div class="topbar">
                <span class="topbar-title">@yield('title', 'Tableau de bord')</span>
                <div class="topbar-right">
                    <span style="font-size:.75rem;color:var(--text-secondary)">{{ now()->locale('fr')->translatedFormat('d/m/Y') }}</span>
                </div>
            </div>

            <main class="page">
                @if (session('status'))
                    <div class="success">✓ {{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="error">✕ {{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="error">
                        @foreach ($errors->all() as $error)
                            <div>✕ {{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    @else
        {{-- Auth pages (no sidebar) --}}
        @if (session('status') || session('error') || $errors->any())
            <div style="position:fixed;top:1rem;right:1rem;z-index:9999;max-width:400px">
                @if (session('status'))
                    <div class="success">✓ {{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="error">✕ {{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="error">
                        @foreach ($errors->all() as $error)
                            <div>✕ {{ $error }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
        @yield('content')
    @endauth
</body>
</html>
