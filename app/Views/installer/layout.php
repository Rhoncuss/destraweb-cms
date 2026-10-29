<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title ?? 'Installer') ?> · DESTRA CMS</title>
    <style>
        :root {
            --ink: #0b1020;
            --ink-soft: #404867;
            --line: rgba(15, 23, 42, .09);
            --brand: #6d5efc;
            --brand-2: #00c2a8;
            --brand-3: #ff7a59;
            --danger: #e5484d;
            --ok: #12b76a;
            --card: rgba(255, 255, 255, .86);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Inter, system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1000px 700px at 8% -10%, rgba(109, 94, 252, .32), transparent 60%),
                radial-gradient(900px 620px at 100% 0%, rgba(0, 194, 168, .26), transparent 55%),
                radial-gradient(900px 700px at 50% 115%, rgba(255, 122, 89, .22), transparent 60%),
                #f4f6ff;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { width: 100%; max-width: 1080px; margin: 0 auto; padding: 40px 20px 64px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 30px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-mark {
            width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center;
            background: linear-gradient(140deg, var(--brand), #8f7bff 55%, var(--brand-2));
            color: #fff; font-weight: 800; font-size: 20px; letter-spacing: .5px;
            box-shadow: 0 14px 30px -12px rgba(109, 94, 252, .75);
        }
        .brand-name { font-size: 18px; font-weight: 800; letter-spacing: .04em; }
        .brand-sub { font-size: 12.5px; color: var(--ink-soft); margin-top: 3px; }
        .chip {
            font-size: 12px; font-weight: 600; color: var(--ink-soft); background: rgba(255, 255, 255, .7);
            border: 1px solid var(--line); padding: 7px 12px; border-radius: 999px; white-space: nowrap;
        }
        .steps { display: flex; align-items: center; gap: 8px; margin-bottom: 26px; flex-wrap: wrap; }
        .step { display: flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 600; color: #7b8199; }
        .step-dot {
            width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center;
            background: #fff; border: 1px solid var(--line); font-size: 12px; color: #7b8199;
        }
        .step.is-active { color: var(--ink); }
        .step.is-active .step-dot { background: linear-gradient(140deg, var(--brand), var(--brand-2)); border-color: transparent; color: #fff; box-shadow: 0 8px 18px -8px rgba(109, 94, 252, .9); }
        .step.is-done .step-dot { background: rgba(18, 183, 106, .12); color: var(--ok); border-color: rgba(18, 183, 106, .3); }
        .step-line { width: 28px; height: 1px; background: var(--line); }
        .card {
            background: var(--card);
            border: 1px solid rgba(255, 255, 255, .9);
            border-radius: 24px;
            box-shadow: 0 34px 70px -40px rgba(16, 24, 64, .5), 0 2px 0 rgba(255, 255, 255, .6) inset;
            backdrop-filter: blur(14px);
            overflow: hidden;
        }
        .card-head { padding: 30px 34px 0; }
        .eyebrow { font-size: 11.5px; letter-spacing: .17em; text-transform: uppercase; font-weight: 700; color: var(--brand); }
        h1 { font-size: clamp(22px, 3vw, 28px); margin: 10px 0 8px; letter-spacing: -.02em; }
        .lead { color: var(--ink-soft); font-size: 14.5px; line-height: 1.65; margin: 0; max-width: 72ch; }
        .card-body { padding: 26px 34px 34px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px; }
        .grid .span-2 { grid-column: span 2; }
        label.field { display: block; font-size: 12.5px; font-weight: 700; letter-spacing: .01em; margin-bottom: 7px; color: #333a56; }
        .hint { font-weight: 500; color: #8a90a8; font-size: 11.5px; }
        input[type=text], input[type=password], input[type=email], input[type=number], select {
            width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(15, 23, 42, .14);
            background: #fff; font-size: 14px; color: var(--ink); outline: none; transition: border-color .15s, box-shadow .15s;
            font-family: inherit;
        }
        input:focus, select:focus { border-color: var(--brand); box-shadow: 0 0 0 4px rgba(109, 94, 252, .13); }
        .checklist { display: grid; gap: 10px; margin: 0 0 26px; }
        .check {
            display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 12px;
            background: rgba(255, 255, 255, .72); border: 1px solid var(--line); font-size: 13.5px;
        }
        .check .dot { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; font-size: 12px; flex: 0 0 auto; font-weight: 800; }
        .check.ok .dot { background: rgba(18, 183, 106, .14); color: var(--ok); }
        .check.bad .dot { background: rgba(229, 72, 77, .13); color: var(--danger); }
        .check.warn .dot { background: rgba(245, 158, 11, .16); color: #b45309; }
        .check .name { font-weight: 600; flex: 1 1 auto; }
        .check .detail { color: #8a90a8; font-size: 12px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 46%; }
        .alert { border-radius: 14px; padding: 13px 16px; font-size: 13.5px; margin-bottom: 18px; border: 1px solid; }
        .alert-error { background: #fff1f1; border-color: #ffd2d4; color: #a21d23; }
        .alert-info { background: #eef1ff; border-color: #d9defe; color: #3730a3; }
        .alert ul { margin: 6px 0 0; padding-left: 18px; }
        .actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 28px; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 9px; border: 0; cursor: pointer; font-family: inherit;
            font-size: 14px; font-weight: 700; padding: 13px 22px; border-radius: 13px; color: #fff;
            background: linear-gradient(135deg, var(--brand), #8f7bff 55%, var(--brand-2));
            box-shadow: 0 18px 34px -18px rgba(109, 94, 252, .95); transition: transform .14s, box-shadow .14s;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 22px 38px -18px rgba(109, 94, 252, 1); text-decoration: none; color: #fff; }
        .btn[disabled] { opacity: .55; cursor: not-allowed; transform: none; }
        .btn-ghost { background: #fff; color: #333a56; border: 1px solid var(--line); box-shadow: none; }
        .btn-ghost:hover { color: var(--ink); box-shadow: 0 10px 24px -18px rgba(16, 24, 64, .6); }
        .footer-note { text-align: center; font-size: 12px; color: #7b8199; margin-top: 22px; }
        .success { text-align: center; padding: 20px 10px 8px; }
        .success-badge {
            width: 84px; height: 84px; margin: 0 auto 20px; border-radius: 28px; display: grid; place-items: center;
            background: linear-gradient(150deg, rgba(18, 183, 106, .18), rgba(0, 194, 168, .22));
            color: var(--ok); font-size: 40px; border: 1px solid rgba(18, 183, 106, .3);
        }
        .credential {
            display: inline-flex; gap: 10px; align-items: center; margin: 6px 0; padding: 10px 16px; border-radius: 12px;
            background: #f6f7ff; border: 1px dashed #c9cffb; font-size: 13.5px; color: #333a56;
        }
        .credential strong { font-family: ui-monospace, Menlo, Consolas, monospace; color: var(--ink); }
        .site-links { display: flex; gap: 12px; justify-content: center; margin-top: 26px; flex-wrap: wrap; }
        @media (max-width: 640px) {
            .shell { padding: 24px 14px 48px; }
            .card-head { padding: 24px 20px 0; }
            .card-body { padding: 20px; }
            .grid { grid-template-columns: 1fr; }
            .grid .span-2 { grid-column: span 1; }
            .step span.label { display: none; }
            .brand-sub { display: none; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <div class="brand">
                <div class="brand-mark">D</div>
                <div>
                    <div class="brand-name">DESTRA CMS</div>
                    <div class="brand-sub">Secure publishing platform · Installer</div>
                </div>
            </div>
            <div class="chip">v<?= esc(config(\Config\Destra::class)->version) ?> · <?= esc(config(\Config\Destra::class)->codename) ?></div>
        </div>

        <div class="steps">
            <?php $step = (int) ($step ?? 1); ?>
            <div class="step <?= $step === 1 ? 'is-active' : ($step > 1 ? 'is-done' : '') ?>">
                <span class="step-dot"><?= $step > 1 ? '✓' : '1' ?></span><span class="label">Requirements</span>
            </div>
            <span class="step-line"></span>
            <div class="step <?= $step === 2 ? 'is-active' : ($step > 2 ? 'is-done' : '') ?>">
                <span class="step-dot"><?= $step > 2 ? '✓' : '2' ?></span><span class="label">Database</span>
            </div>
            <span class="step-line"></span>
            <div class="step <?= $step === 3 ? 'is-active' : ($step > 3 ? 'is-done' : '') ?>">
                <span class="step-dot"><?= $step > 3 ? '✓' : '3' ?></span><span class="label">Site &amp; Admin</span>
            </div>
        </div>

        <div class="card">
            <?= $this->renderSection('content') ?>
        </div>

        <div class="footer-note">
            DESTRA CMS installer · Everything is written locally, nothing is sent to third parties.
        </div>
    </div>
</body>
</html>
