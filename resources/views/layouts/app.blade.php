<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Leitner Study System' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f8fb;
            --bg-2: #eaf3f8;
            --paper: #ffffff;
            --ink: #0b1b2b;
            --muted: #4c6275;
            --line: #d7e3ec;
            --brand: #0f9d8a;
            --brand-deep: #0a6f62;
            --accent: #ff9b4a;
            --danger: #c03636;
            --ok: #0d7a3e;
            --shadow: 0 14px 28px rgba(9, 30, 45, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: "Manrope", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 95% 0%, #c7f5eb 0%, transparent 32%),
                radial-gradient(circle at 5% 15%, #ffe7d1 0%, transparent 27%),
                linear-gradient(180deg, var(--bg) 0%, var(--bg-2) 100%);
            min-height: 100vh;
        }
        .container { width: min(1140px, calc(100% - 2rem)); margin-inline: auto; }
        .topbar {
            position: sticky; top: 0; z-index: 30; backdrop-filter: blur(8px);
            background: rgba(244, 248, 251, 0.85); border-bottom: 1px solid var(--line);
        }
        .nav { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.75rem 0; }
        .brand {
            text-decoration: none;
            color: var(--brand-deep);
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
            font-size: 1.08rem;
            letter-spacing: 0.2px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .brand-mark {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(140deg, var(--brand), var(--brand-deep));
            box-shadow: 0 6px 14px rgba(10, 111, 98, 0.25);
        }
        .brand-name {
            display: inline-flex;
            flex-direction: column;
            line-height: 1.02;
        }
        .brand-name small {
            color: #5a7084;
            font-size: 0.66rem;
            letter-spacing: 0.24px;
            font-weight: 700;
        }
        .nav-links { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .nav-links a {
            text-decoration: none; color: var(--ink); font-weight: 600; font-size: 0.92rem;
            padding: 0.45rem 0.78rem; border-radius: 999px; border: 1px solid transparent; transition: all 170ms ease;
        }
        .nav-links a:hover { background: #fff; border-color: var(--line); }
        .nav-links a.active { background: #fff; border-color: var(--brand); color: var(--brand-deep); }
        .page { padding: 2rem 0 3rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .panel { background: var(--paper); border: 1px solid var(--line); border-radius: 16px; padding: 1rem; box-shadow: var(--shadow); }
        .hero { padding: 1.7rem; border-radius: 20px; border: 1px solid #c8e9e3; background: linear-gradient(135deg, #ffffff 15%, #edfffa 85%); }
        .label-chip { display: inline-block; padding: 0.3rem 0.62rem; border-radius: 999px; background: #ddfaf3; color: var(--brand-deep); font-weight: 800; font-size: 0.78rem; letter-spacing: 0.2px; }
        .grid { display: grid; gap: 1rem; }
        .grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid.six { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .stat { border-radius: 14px; border: 1px solid var(--line); background: #fff; padding: 0.95rem; }
        .stat h3 { margin: 0; font-size: 0.95rem; }
        .stat p { margin: 0.35rem 0 0; font-size: 1.65rem; font-weight: 800; color: var(--brand-deep); }
        .muted { color: var(--muted); }
        h1, h2, h3 { margin: 0; font-family: "Space Grotesk", sans-serif; color: var(--ink); }
        p { color: var(--muted); }
        .btn {
            border: none; border-radius: 11px; padding: 0.62rem 0.92rem; font-weight: 700; font-size: 0.92rem;
            cursor: pointer; transition: transform 160ms ease, box-shadow 160ms ease;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(10, 111, 98, 0.2); }
        .btn-primary { color: #fff; background: linear-gradient(135deg, var(--brand), var(--brand-deep)); }
        .btn-soft { color: var(--ink); background: #fff; border: 1px solid var(--line); }
        .btn-danger { color: var(--danger); background: #fff; border: 1px solid #f3c2c2; }
        a.btn { text-decoration: none; display: inline-block; }
        .status { margin-bottom: 1rem; padding: 0.68rem 0.82rem; border-radius: 10px; font-weight: 700; border: 1px solid #b5f0da; background: #eafff7; color: var(--ok); }
        .status.error { border-color: #f2b6b6; background: #fff2f2; color: var(--danger); }
        form.stack { display: grid; gap: 0.85rem; }
        label { display: block; margin-bottom: 0.35rem; font-size: 0.89rem; font-weight: 700; color: var(--ink); }
        input, select, textarea {
            width: 100%; border: 1px solid #c7d8e5; border-radius: 10px; background: #fff;
            padding: 0.62rem 0.72rem; color: var(--ink); font: inherit;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(15, 157, 138, 0.14); }
        .list-item { border: 1px solid var(--line); border-radius: 12px; padding: 0.9rem; background: #fff; }
        .meta { color: #5e7385; font-size: 0.86rem; line-height: 1.45; }
        .actions { display: flex; gap: 0.55rem; flex-wrap: wrap; }
        .text-rich {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            line-height: 1.38;
        }
        .text-panel {
            border: 1px solid #dbe7ef;
            background: #fbfdff;
            border-radius: 10px;
            padding: 0.72rem 0.78rem;
        }
        .text-panel.scroll {
            max-height: 260px;
            overflow: auto;
        }
        .text-front {
            font-weight: 700;
            font-size: 0.98rem;
            color: var(--ink);
        }
        @media (max-width: 950px) {
            .grid.two,
            .grid.three,
            .grid.six,
            .grid[style*='repeat(5'] { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="container nav">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark">LL</span>
            <span class="brand-name">Leitner Lab<small>spaced repetition</small></span>
        </a>
        <nav class="nav-links">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            @auth
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('skills.index') }}" class="{{ request()->routeIs('skills.*') ? 'active' : '' }}">Skills</a>
                <a href="{{ route('cards.index') }}" class="{{ request()->routeIs('cards.index') ? 'active' : '' }}">Cards</a>
                <a href="{{ route('review.today') }}" class="{{ request()->routeIs('review.today') ? 'active' : '' }}">Review Today</a>
                <a href="{{ route('materials') }}" class="{{ request()->routeIs('materials') ? 'active' : '' }}">Materials</a>
                <a href="{{ route('backups.index') }}" class="{{ request()->routeIs('backups.*') ? 'active' : '' }}">Backups</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline; margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-soft" style="padding:0.42rem 0.72rem;">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
                <a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'active' : '' }}">Register</a>
            @endauth
        </nav>
    </div>
</header>
<main class="page">
    <div class="container">
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="status error">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </div>
</main>
</body>
</html>
