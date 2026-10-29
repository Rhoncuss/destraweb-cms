<!DOCTYPE html>
<html lang="<?= esc(current_locale()) ?>" dir="<?= service('localization')->isRtl() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title ?? 'Sign in') ?> · <?= esc(service('settings')->get('general.site_name', 'DESTRA CMS')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
    <style>
        :root {
            --ink: #0c1124;
            --ink-soft: #4a5169;
            --muted: #7c8299;
            --line: rgba(12, 17, 36, .1);
            --brand: #6d5efc;
            --brand-2: #00c2a8;
            --brand-3: #ff7a59;
            --danger: #e5484d;
            --ok: #12b76a;
            --surface: #ffffff;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: Inter, "Segoe UI", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: #f5f6fc;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .auth-shell { display: grid; grid-template-columns: 1.05fr .95fr; min-height: 100vh; }
        .stage {
            position: relative;
            overflow: hidden;
            padding: 54px 58px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                radial-gradient(900px 620px at 110% -10%, rgba(0, 194, 168, .55), transparent 55%),
                radial-gradient(760px 620px at -20% 110%, rgba(255, 122, 89, .45), transparent 58%),
                linear-gradient(155deg, #121633 0%, #241d63 48%, #3b2a8b 100%);
        }
        .stage::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(70% 70% at 40% 35%, #000 30%, transparent 100%);
            pointer-events: none;
        }
        .stage-top { position: relative; z-index: 1; display: flex; align-items: center; gap: 14px; }
        .mark {
            width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center;
            font-weight: 800; font-size: 19px; letter-spacing: .5px; color: #171a3b;
            background: linear-gradient(140deg, #fff, #d8d4ff);
            box-shadow: 0 18px 40px -18px rgba(0, 0, 0, .6);
            font-family: Sora, Inter, sans-serif;
        }
        .stage-brand { font-family: Sora, Inter, sans-serif; font-weight: 700; letter-spacing: .08em; font-size: 15px; }
        .stage-brand small { display: block; font-family: Inter, sans-serif; font-weight: 500; letter-spacing: .02em; font-size: 12px; color: rgba(255,255,255,.62); margin-top: 3px; }
        .stage-body { position: relative; z-index: 1; max-width: 460px; }
        .stage-body h2 {
            font-family: Sora, Inter, sans-serif;
            font-size: clamp(28px, 3.4vw, 42px);
            line-height: 1.12;
            letter-spacing: -.03em;
            margin: 0 0 18px;
        }
        .stage-body h2 em { font-style: normal; color: #a9f6e6; }
        .stage-body p { color: rgba(255,255,255,.72); line-height: 1.7; font-size: 14.5px; margin: 0 0 26px; }
        .stage-points { display: grid; gap: 12px; }
        .stage-point { display: flex; gap: 12px; align-items: flex-start; font-size: 13.5px; color: rgba(255,255,255,.85); }
        .stage-point .tick {
            width: 22px; height: 22px; border-radius: 8px; flex: 0 0 auto; display: grid; place-items: center;
            background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.2); font-size: 11px;
        }
        .stage-foot { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 16px; color: rgba(255,255,255,.55); font-size: 12px; }
        .stage-foot .pill { border: 1px solid rgba(255,255,255,.22); border-radius: 999px; padding: 6px 12px; }
        .panel { display: flex; align-items: center; justify-content: center; padding: 48px 32px; }
        .panel-inner { width: 100%; max-width: 420px; }
        .panel h1 { font-family: Sora, Inter, sans-serif; font-size: 27px; letter-spacing: -.02em; margin: 0 0 8px; }
        .panel .sub { color: var(--muted); font-size: 14px; line-height: 1.6; margin: 0 0 26px; }
        .alert { border-radius: 14px; padding: 13px 16px; font-size: 13.5px; margin-bottom: 18px; border: 1px solid; display: flex; gap: 10px; align-items: flex-start; }
        .alert-error { background: #fff1f1; border-color: #ffd2d4; color: #a21d23; }
        .alert-success { background: #ecfdf3; border-color: #bff5d2; color: #067647; }
        label.field { display: block; font-size: 12.5px; font-weight: 700; margin-bottom: 7px; color: #2b3049; }
        .control { position: relative; margin-bottom: 16px; }
        .control .icon { position: absolute; inset-inline-start: 14px; top: 50%; transform: translateY(-50%); color: #9aa0b5; pointer-events: none; display: flex; }
        .control input {
            width: 100%; padding: 13px 14px 13px 44px; border-radius: 12px; border: 1px solid var(--line);
            background: #fff; font-size: 14px; font-family: inherit; color: var(--ink); outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .control input:focus { border-color: var(--brand); box-shadow: 0 0 0 4px rgba(109, 94, 252, .12); }
        .control .toggle {
            position: absolute; inset-inline-end: 8px; top: 50%; transform: translateY(-50%);
            border: 0; background: transparent; cursor: pointer; color: #9aa0b5; padding: 8px; border-radius: 8px;
        }
        .control .toggle:hover { color: var(--ink); }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 4px 0 22px; }
        .check { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--ink-soft); cursor: pointer; user-select: none; }
        .check input { width: 16px; height: 16px; accent-color: var(--brand); }
        .link { font-size: 13px; font-weight: 600; }
        .btn {
            width: 100%; border: 0; cursor: pointer; font-family: inherit; font-size: 14.5px; font-weight: 700;
            color: #fff; padding: 14px 20px; border-radius: 13px;
            background: linear-gradient(135deg, var(--brand), #8f7bff 52%, var(--brand-2));
            box-shadow: 0 20px 38px -20px rgba(109, 94, 252, .95);
            transition: transform .14s, box-shadow .14s;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 24px 42px -20px rgba(109, 94, 252, 1); }
        .btn-ghost { background: #fff; color: #2b3049; border: 1px solid var(--line); box-shadow: none; }
        .btn-ghost:hover { box-shadow: 0 12px 26px -20px rgba(12, 17, 36, .5); }
        .separator { display: flex; align-items: center; gap: 12px; margin: 24px 0; color: #a3a8bb; font-size: 12px; }
        .separator::before, .separator::after { content: ""; flex: 1; height: 1px; background: var(--line); }
        .foot { margin-top: 26px; text-align: center; font-size: 13px; color: var(--muted); }
        .foot a { font-weight: 600; }
        .captcha { margin: 4px 0 20px; }
        .social-login { margin-top: 22px; text-align: center; }
        .social-login__label { display: block; margin-bottom: 12px; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .social-login__row { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .social-login__btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border: 1px solid var(--line); border-radius: 12px; background: #fff; color: #2b3049; font-size: 13px; font-weight: 600; text-decoration: none; transition: transform .15s ease, box-shadow .2s ease; }
        .social-login__btn:hover { transform: translateY(-1px); box-shadow: 0 12px 26px -20px rgba(12, 17, 36, .45); }
        .grid-two { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
        .strength { height: 6px; border-radius: 99px; background: #eceef6; overflow: hidden; margin: -6px 0 16px; }
        .strength span { display: block; height: 100%; width: 0; border-radius: 99px; background: linear-gradient(90deg, var(--brand-3), var(--brand), var(--brand-2)); transition: width .2s; }
        @media (max-width: 980px) {
            .auth-shell { grid-template-columns: 1fr; }
            .stage { display: none; }
            .panel { min-height: 100vh; padding: 30px 20px; background:
                radial-gradient(700px 460px at 110% -10%, rgba(0, 194, 168, .22), transparent 60%),
                radial-gradient(600px 460px at -20% 110%, rgba(109, 94, 252, .2), transparent 60%), #f6f7ff; }
            .mobile-brand { display: flex !important; }
        }
        .mobile-brand { display: none; align-items: center; gap: 12px; margin-bottom: 30px; }
        .mobile-brand .mark { background: linear-gradient(140deg, var(--brand), var(--brand-2)); color: #fff; width: 42px; height: 42px; border-radius: 12px; }
        .mobile-brand strong { font-family: Sora, Inter, sans-serif; letter-spacing: .06em; font-size: 14px; }
    </style>
</head>
<body>
    <div class="auth-shell">
        <aside class="stage">
            <div class="stage-top">
                <div class="mark">D</div>
                <div class="stage-brand">DESTRA CMS<small>Secure publishing platform</small></div>
            </div>
            <div class="stage-body">
                <h2>Publish with confidence,<br><em>grow without limits.</em></h2>
                <p>A hardened content platform with a modular plugin and theme architecture, multilingual publishing and an analytics-ready dashboard.</p>
                <div class="stage-points">
                    <div class="stage-point"><span class="tick">✓</span><span>Argon2id passwords, CSRF on every form, AES-256 encryption</span></div>
                    <div class="stage-point"><span class="tick">✓</span><span>SEO-first URLs, sitemaps and structured data out of the box</span></div>
                    <div class="stage-point"><span class="tick">✓</span><span>Themes and plugins you can develop completely separately</span></div>
                </div>
            </div>
            <div class="stage-foot">
                <span>© <?= date('Y') ?> <?= esc(service('settings')->get('general.site_name', 'DESTRA CMS')) ?></span>
                <span class="pill">v<?= esc(config(\Config\Destra::class)->version) ?></span>
            </div>
        </aside>

        <main class="panel">
            <div class="panel-inner">
                <div class="mobile-brand">
                    <div class="mark">D</div>
                    <strong>DESTRA CMS</strong>
                </div>
                <?= $this->renderSection('form') ?>
                <div class="foot">
                    <a href="<?= base_url('/') ?>">← Back to website</a>
                </div>
            </div>
        </main>
    </div>
    <script nonce="<?= esc(csp_nonce(), 'attr') ?>">
        document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.getAttribute('data-toggle-password'));
                if (!input) { return; }
                var showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
        });
        document.querySelectorAll('[data-strength]').forEach(function (input) {
            var bar = document.querySelector(input.getAttribute('data-strength'));
            if (!bar) { return; }
            input.addEventListener('input', function () {
                var value = input.value;
                var score = 0;
                if (value.length >= 8) score += 25;
                if (value.length >= 12) score += 25;
                if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 25;
                if (/\d/.test(value) && /[^A-Za-z0-9]/.test(value)) score += 25;
                bar.style.width = score + '%';
            });
        });
    </script>
</body>
</html>
