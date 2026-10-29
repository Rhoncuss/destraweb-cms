<h2 style="margin:0 0 12px;font-size:20px;color:#111827;">Reset your password</h2>
<p style="margin:0 0 14px;">Hi <?= esc($name ?? 'there') ?>,</p>
<p style="margin:0 0 20px;">We received a request to reset the password for your <?= esc($site_name ?? $siteName ?? 'account') ?>. Click the button below to choose a new one.</p>
<p style="margin:0 0 24px;">
    <a href="<?= esc($link ?? '#', 'attr') ?>" style="display:inline-block;background:linear-gradient(135deg,#6d5efc,#00c2a8);color:#ffffff;padding:14px 26px;border-radius:12px;text-decoration:none;font-weight:700;">Choose a new password</a>
</p>
<p style="margin:0 0 8px;color:#475569;font-size:13px;">This link expires in <?= esc($expires ?? '60 minutes') ?> and can only be used once.</p>
<p style="margin:0;color:#64748b;font-size:13px;">If you didn't request a password reset, you can safely ignore this email — your password stays unchanged.</p>
