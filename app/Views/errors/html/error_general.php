<?php
$statusCode = (int) ($code ?? 400);
$detail     = trim((string) ($message ?? ''));
$siteName   = 'DESTRA CMS';
try {
    if (function_exists('setting')) {
        $siteName = (string) (setting('general.site_name', $siteName) ?: $siteName);
    }
} catch (\Throwable) {
}
$homeUrl = function_exists('site_url') ? site_url('/') : '/';
$searchUrl = function_exists('site_url') ? site_url('search') : '/search';
$titles  = [
    400 => 'Bad request',
    403 => 'Access denied',
    404 => 'Page not found',
    405 => 'Method not allowed',
    413 => 'Request too large',
    422 => 'Unprocessable request',
    500 => 'Something went wrong',
];
$title = $titles[$statusCode] ?? 'Request could not be completed';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $statusCode ?> · <?= esc($title) ?> · <?= esc($siteName) ?></title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #5b6478;
            --line: rgba(148, 163, 184, .28);
            --brand: #4f46e5;
            --brand-2: #0ea5e9;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: Inter, "Segoe UI", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 520px at 12% -10%, rgba(79, 70, 229, .20), transparent 60%),
                radial-gradient(800px 520px at 92% 0%, rgba(14, 165, 233, .18), transparent 55%),
                radial-gradient(700px 700px at 50% 120%, rgba(245, 158, 11, .12), transparent 60%),
                #f4f6fb;
            display: grid;
            place-items: center;
            padding: 32px 18px;
        }
        .wrap {
            width: min(560px, 100%);
            text-align: center;
        }
        .mark {
            width: 64px; height: 64px; margin: 0 auto 22px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            color: #fff; font-weight: 800; font-size: 26px; letter-spacing: -.03em;
            display: grid; place-items: center;
            box-shadow: 0 22px 45px -22px rgba(79, 70, 229, .75);
        }
        .card {
            background: rgba(255, 255, 255, .86);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, .9);
            border-radius: 26px;
            padding: 38px 34px 34px;
            box-shadow: 0 40px 80px -50px rgba(15, 23, 42, .55);
        }
        .code {
            display: block;
            font-size: clamp(56px, 14vw, 84px);
            font-weight: 800;
            letter-spacing: -.06em;
            line-height: 1;
            background: linear-gradient(120deg, var(--brand), var(--brand-2));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        h1 { font-size: clamp(20px, 4vw, 26px); margin: 14px 0 10px; letter-spacing: -.02em; }
        p { margin: 0 auto; max-width: 42ch; color: var(--muted); line-height: 1.65; font-size: 15px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 26px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 46px; padding: 0 22px; border-radius: 14px;
            font-weight: 650; font-size: 14px; text-decoration: none;
            transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            color: #fff; background: linear-gradient(135deg, var(--brand), #6366f1);
            box-shadow: 0 18px 32px -18px rgba(79, 70, 229, .85);
        }
        .btn-ghost { color: var(--ink); background: #fff; border: 1px solid var(--line); }
        .foot { margin-top: 22px; font-size: 12.5px; color: #8a92a6; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="mark" aria-hidden="true">D</div>
        <div class="card" role="alert">
            <span class="code"><?= $statusCode ?></span>
            <h1><?= esc($title) ?></h1>
            <p><?= esc($detail !== '' ? $detail : 'The request could not be completed. Please go back and try again.') ?></p>
            <div class="actions">
                <a class="btn btn-primary" href="<?= esc($homeUrl, 'attr') ?>">Back to home</a>
                <a class="btn btn-ghost" href="<?= esc($searchUrl, 'attr') ?>">Search the site</a>
            </div>
        </div>
        <div class="foot"><?= esc($siteName) ?> · Request blocked for your safety</div>
    </div>
</body>
</html>
