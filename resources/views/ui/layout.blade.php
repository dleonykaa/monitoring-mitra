<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Monitoring Kinerja Mitra BPS' }}</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --ink: #1c2533;
            --muted: #627089;
            --card: #ffffff;
            --line: #dbe3ee;
            --accent: #0f766e;
            --accent-2: #c2410c;
            --ok: #15803d;
            --warn: #b45309;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Arial", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 10% 0%, #d7efe9 0, transparent 35%),
                radial-gradient(circle at 90% 100%, #ffe4d6 0, transparent 30%),
                var(--bg);
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .brand { font-weight: 800; letter-spacing: 0.5px; }
        .sub { color: var(--muted); font-size: 14px; }
        .grid { display: grid; gap: 16px; }
        .g4 { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .g3 { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(28, 37, 51, 0.05);
        }
        .metric { font-size: 30px; font-weight: 800; margin: 6px 0; }
        .label { color: var(--muted); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .row { display: flex; gap: 10px; align-items: center; }
        .dot { width: 10px; height: 10px; border-radius: 100%; background: var(--accent); }
        .btn {
            border: 0; border-radius: 10px; padding: 9px 12px;
            color: #fff; background: var(--accent); text-decoration: none; font-size: 14px;
            display: inline-block;
        }
        .btn.alt { background: var(--accent-2); }
        .kpis { margin-bottom: 18px; }
        .bar {
            height: 10px; border-radius: 999px; background: #ecf1f7; overflow: hidden;
        }
        .bar > span { display: block; height: 100%; background: linear-gradient(90deg, var(--accent), #0ea5e9); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border-bottom: 1px solid var(--line); text-align: left; padding: 10px 8px; }
        th { color: var(--muted); font-weight: 600; }
        .pill { padding: 3px 8px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .ok { background: #dcfce7; color: var(--ok); }
        .warn { background: #fef3c7; color: var(--warn); }
        .links { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
    </style>
</head>
<body>
<div class="container">
    @yield('content')
</div>
<!-- impeccable-live-start -->
<script src="http://localhost:8400/live.js"></script>
<!-- impeccable-live-end -->
</body>
</html>
