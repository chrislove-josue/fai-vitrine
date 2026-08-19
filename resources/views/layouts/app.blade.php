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
        }
        * { box-sizing: border-box; margin: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }
        :focus-visible { outline: 3px solid var(--jeny-primary); outline-offset: 2px; }

        /* ---------- Header blanc ---------- */
        .site-header {
            position: sticky; top: 0; z-index: 50;
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(11, 37, 69, 0.06);
        }
        .site-header .header-inner {
            max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;
            height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .brand { display: flex; align-items: center; gap: .75rem; color: var(--text-primary); text-decoration: none; }
        .brand img { height: 36px; width: auto; }
        .brand .brand-name { font-weight: 700; font-size: 1rem; color: var(--jeny-primary); }
        .header-right { display: flex; align-items: center; gap: 1rem; }
        .user-chip {
            display: flex; align-items: center; gap: .6rem;
            padding: .35rem .9rem .35rem .35rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
        }
        .user-chip .avatar {
            width: 32px; height: 32px; border-radius: var(--radius-sm);
            background: var(--jeny-primary); color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: .75rem;
        }
        .user-chip .user-name { font-weight: 600; font-size: .82rem; color: var(--text-primary); line-height: 1.1; }
        .user-chip .user-role { font-size: .68rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }

        /* ---------- Sub-nav ---------- */
        .sub-nav {
            background: var(--bg);
            border-bottom: 1px solid var(--border);
        }
        .sub-nav .nav-inner {
            max-width: 1200px; margin: 0 auto; padding: .6rem 1.5rem;
            display: flex; align-items: center; gap: .35rem; overflow-x: auto; scrollbar-width: none;
        }
        .sub-nav .nav-inner::-webkit-scrollbar { display: none; }
        .nav-pill {
            display: inline-flex; align-items: center; gap: .45rem;
            padding: .45rem 1rem; border-radius: var(--radius-sm);
            color: var(--text-secondary); text-decoration: none;
            font-size: .82rem; font-weight: 500; white-space: nowrap;
            transition: background .15s, color .15s;
        }
        .nav-pill:hover { background: var(--bg-secondary); color: var(--text-primary); }
        .nav-pill.active { background: var(--jeny-primary); color: white; }
        .nav-pill.active svg { color: white; }

        /* ---------- Container ---------- */
        .page { width: 100%; max-width: 1200px; margin: 0 auto; padding: 1.5rem 1.5rem 3rem; flex: 1; }

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
        .error, .success {
            border-radius: var(--radius-sm); padding: .75rem 1rem; margin-bottom: 1rem;
            font-size: .85rem; font-weight: 500; display: flex; align-items: center; gap: .5rem;
        }
        .error { background: #FEF3F2; color: #B42318; border: 1px solid #FECDCA; }
        .success { background: #ECFDF3; color: #027A48; border: 1px solid #A6F4C5; }

        /* ---------- Auth ---------- */
        .auth-page { background: var(--bg-secondary); min-height: 100vh; }
        .auth-wrap { max-width: 420px; margin: 0 auto; padding: 3rem 1.5rem; display: flex; flex-direction: column; align-items: center; }
        .auth-wrap .brand { margin-bottom: 1.5rem; }
        .auth-box.card { width: 100%; padding: 2rem; }
        .auth-box h2 { margin: 0 0 1.25rem; font-size: 1.2rem; font-weight: 700; color: var(--text-primary); }
        .auth-foot { margin-top: 1.25rem; font-size: .8rem; }
        label { display: block; margin: .75rem 0 .3rem; font-weight: 500; font-size: .8rem; color: var(--text-primary); }
        input[type=email], input[type=password], input[type=text], input[type=checkbox] {
            width: 100%; padding: .6rem .85rem;
            border: 1px solid var(--border); border-radius: var(--radius-sm);
            font-size: .88rem; font-family: inherit; color: var(--text-primary);
            background: white;
        }
        input:focus { outline: 2px solid var(--jeny-primary); outline-offset: 0; border-color: var(--jeny-primary); }

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

        @media (max-width: 640px) {
            .dl { grid-template-columns: 1fr; gap: .2rem; }
            .dl dd { margin-bottom: .5rem; }
            .header-inner { height: 56px; }
            .page { padding: 1rem; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="@auth @else auth-page @endauth">
    @auth
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="{{ auth()->user()->homeRoute() }}">
                    <img src="{{ asset('img/logo-jeny.png') }}" alt="JENY SAS">
                </a>
                <div class="header-right">
                    <div class="user-chip">
                        <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span>
                            <span class="user-name">{{ auth()->user()->name }}</span><br>
                            <span class="user-role">{{ auth()->user()->isClient() ? 'Client' : 'Staff' }}</span>
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn outline sm" type="submit" title="Se déconnecter">Déconnexion</button>
                    </form>
                </div>
            </div>
        </header>

        @if (auth()->user()->isClient())
            <nav class="sub-nav" aria-label="Navigation espace client">
                <div class="nav-inner">
                    <a class="nav-pill {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm1 7a1 1 0 011-1h12a1 1 0 011 1v5a1 1 0 01-1 1H4a1 1 0 01-1-1v-5z" clip-rule="evenodd"/></svg>
                        Tableau de bord
                    </a>
                    <a class="nav-pill {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}" href="{{ route('client.invoices.index') }}">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                        Factures
                    </a>
                    <a class="nav-pill {{ request()->routeIs('client.payments.*') ? 'active' : '' }}" href="{{ route('client.payments.index') }}">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zm14 5H2v6a2 2 0 002 2h12a2 2 0 002-2V9zM5 13a1 1 0 011-1h1a1 1 0 110 2H6a1 1 0 01-1-1z"/></svg>
                        Paiements
                    </a>
                    <a class="nav-pill {{ request()->routeIs('client.sessions.*') ? 'active' : '' }}" href="{{ route('client.sessions.index') }}">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2 10a8 8 0 1116 0 8 8 0 01-16 0zm11.3-3.3a1 1 0 00-1.4-1.4L9 8.6 7.7 7.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
                        Consommation
                    </a>
                    <a class="nav-pill {{ request()->routeIs('client.profile.*') ? 'active' : '' }}" href="{{ route('client.profile.show') }}">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        Profil
                    </a>
                </div>
            </nav>
        @endif
    @endauth

    @auth
    <main class="page">
    @else
    <div class="auth-wrap">
        <a class="brand" href="/">
            <img src="{{ asset('img/logo-jeny.png') }}" alt="JENY SAS">
        </a>
    @endauth

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

    @auth
    </main>
    @else
    </div>
    @endauth
</body>
</html>
