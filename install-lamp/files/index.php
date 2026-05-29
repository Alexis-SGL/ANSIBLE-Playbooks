<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alexis SGL — Serveur opérationnel</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0b0d14;
            --surface:   rgba(255,255,255,0.04);
            --border:    rgba(255,255,255,0.08);
            --purple:    #a78bfa;
            --blue:      #60a5fa;
            --text:      #e2e8f0;
            --muted:     #64748b;
            --success:   #34d399;
            --radius:    14px;
        }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(22px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes pulse-ring {
            0%   { transform:scale(1);   opacity:.6; }
            70%  { transform:scale(1.6); opacity:0; }
            100% { transform:scale(1.6); opacity:0; }
        }
        @keyframes neon-shift {
            0%,100% { background-position: 0% 50%; }
            50%      { background-position: 100% 50%; }
        }

        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'Georgia', 'Times New Roman', serif;
            overflow-x: hidden;
        }

        /* ── Fond animé ── */
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 60% 50% at 20% 20%, rgba(124,58,237,.18) 0%, transparent 70%),
                radial-gradient(ellipse 50% 40% at 80% 75%, rgba(37,99,235,.14) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Grille subtile */
        body::after {
            content: '';
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        main {
            position: relative; z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .card {
            width: 100%;
            max-width: 500px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            backdrop-filter: blur(18px);
            padding: 44px 40px 40px;
            animation: fadeUp .6s ease both;
            box-shadow:
                0 0 0 1px rgba(167,139,250,.06),
                0 24px 60px rgba(0,0,0,.5);
        }

        /* ── Badge statut ── */
        .status-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 32px;
            animation: fadeUp .6s .1s ease both;
            opacity: 0;
        }
        .dot-wrap {
            position: relative;
            width: 10px; height: 10px;
            flex-shrink: 0;
        }
        .dot-wrap::before {
            content: '';
            position: absolute; inset: 0;
            border-radius: 50%;
            background: var(--success);
            animation: pulse-ring 2s ease-out infinite;
        }
        .dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--success);
            position: relative; z-index: 1;
        }
        .status-label {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--success);
        }

        /* ── En-tête ── */
        .signature {
            font-size: 11px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
            animation: fadeUp .6s .15s ease both;
            opacity: 0;
        }

        h1 {
            font-size: clamp(1.6rem, 4vw, 2.1rem);
            font-weight: 400;
            line-height: 1.25;
            margin-bottom: 14px;
            animation: fadeUp .6s .2s ease both;
            opacity: 0;
        }
        h1 em {
            font-style: normal;
            background: linear-gradient(90deg, var(--purple), var(--blue), var(--purple));
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: neon-shift 4s ease infinite;
        }

        .intro {
            font-size: .95rem;
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 32px;
            border-left: 2px solid rgba(167,139,250,.3);
            padding-left: 14px;
            animation: fadeUp .6s .25s ease both;
            opacity: 0;
        }

        /* ── Infos stack ── */
        .stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
            animation: fadeUp .6s .3s ease both;
            opacity: 0;
        }

        .stack-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 14px;
            background: rgba(255,255,255,.03);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: .82rem;
        }
        .stack-row .key {
            color: var(--muted);
            white-space: nowrap;
        }
        .stack-row .val {
            color: var(--text);
            text-align: right;
            word-break: break-all;
        }
        .stack-row .val.ok  { color: var(--success); }
        .stack-row .val.hl  { color: var(--purple); }

        /* ── Séparateur ── */
        .sep {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border), transparent);
            margin: 28px 0;
        }

        /* ── Footer card ── */
        .card-foot {
            font-size: .78rem;
            color: var(--muted);
            text-align: center;
            letter-spacing: .04em;
            animation: fadeUp .6s .35s ease both;
            opacity: 0;
        }
        .card-foot a {
            color: var(--purple);
            text-decoration: none;
        }
        .card-foot a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<main>
    <div class="card">

        <div class="status-wrap">
            <div class="dot-wrap"><div class="dot"></div></div>
            <span class="status-label">Serveur opérationnel</span>
        </div>

        <p class="signature">Alexis SGL</p>

        <h1>Bienvenue sur<br>votre <em>serveur LAMP</em></h1>

        <p class="intro">
            Ce serveur a été déployé automatiquement via Ansible.<br>
            Apache, PHP et MySQL sont actifs et configurés.
        </p>

        <div class="stack">
            <?php
                $os      = php_uname('s') . ' ' . php_uname('r');
                $apache  = apache_get_version() ?? 'N/A';
                $php     = phpversion();
                $db_ver  = shell_exec("mysql --version 2>/dev/null || mariadb --version 2>/dev/null");
                $db_ver  = $db_ver ? trim(preg_replace('/\s+/', ' ', $db_ver)) : 'N/A';
                $host = $_SERVER['HTTP_HOST'];
            ?>
            <div class="stack-row">
                <span class="key">URL</span>
                <span class="val hl"><?= htmlspecialchars($host) ?></span>
            </div>
            <div class="stack-row">
                <span class="key">Système</span>
                <span class="val"><?= htmlspecialchars($os) ?></span>
            </div>
            <div class="stack-row">
                <span class="key">Apache</span>
                <span class="val ok"><?= htmlspecialchars($apache) ?></span>
            </div>
            <div class="stack-row">
                <span class="key">PHP</span>
                <span class="val ok"><?= htmlspecialchars($php) ?></span>
            </div>
            <div class="stack-row">
                <span class="key">Base de données</span>
                <span class="val ok"><?= htmlspecialchars($db_ver) ?></span>
            </div>
        </div>

        <div class="sep"></div>

        <p class="card-foot">
            Déployé par <a href="https://alexis-sgl.fr" target="_blank" rel="noopener">Alexis SGL</a>
            &nbsp;·&nbsp; <?= date('Y') ?>
        </p>

    </div>
</main>
</body>
</html>
