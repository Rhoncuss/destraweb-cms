<?php

namespace App\Libraries;

use Config\Email as EmailConfig;
use Config\Services;
use Throwable;

/**
 * SMTP/Mail transport built on top of CodeIgniter's Email class, configured
 * entirely from the encrypted settings table.
 */
class MailService
{
    private ?EmailConfig $config = null;

    public function enabled(): bool
    {
        return service('settings')->bool('mail.enabled', false);
    }

    public function config(): EmailConfig
    {
        if ($this->config instanceof EmailConfig) {
            return $this->config;
        }

        $settings = service('settings');
        $config   = config(EmailConfig::class);

        $fromEmail = (string) $settings->get('mail.from_email', '');
        if ($fromEmail === '') {
            $fromEmail = (string) $settings->get('general.admin_email', '');
        }
        if (! filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $host      = parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
            $fromEmail = 'noreply@' . preg_replace('/[^a-z0-9.-]/i', '', (string) $host);
        }

        $config->protocol    = (string) $settings->get('mail.protocol', 'smtp');
        $config->SMTPHost    = (string) $settings->get('mail.host', '');
        $config->SMTPPort    = (int) $settings->get('mail.port', 587);
        $config->SMTPUser    = (string) $settings->get('mail.username', '');
        $config->SMTPPass    = (string) $settings->get('mail.password', '');
        $config->SMTPCrypto  = (string) $settings->get('mail.encryption', 'tls');
        $config->SMTPTimeout = 15;
        $config->fromEmail   = $fromEmail;
        $config->fromName    = (string) $settings->get('mail.from_name', setting('general.site_name', 'DESTRA CMS'));
        $config->mailType    = 'html';
        $config->charset     = 'UTF-8';
        $config->newline     = "\r\n";
        $config->CRLF        = true;
        $config->validate    = true;
        $config->wordWrap    = false;

        return $this->config = $config;
    }

    /**
     * @param array{attachments?: array<int, string>, cc?: string, bcc?: string, reply_to?: string} $options
     */
    public function send(string $to, string $subject, string $html, array $options = []): bool
    {
        if (! $this->enabled()) {
            log_message('info', 'Mail disabled; skipped "{subject}" to {to}', ['subject' => $subject, 'to' => $to]);

            return false;
        }

        $to = trim(str_replace(["\r", "\n"], '', $to));
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            log_message('error', 'Mail rejected: invalid recipient.');

            return false;
        }

        $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
        if ($subject === '') {
            $subject = (string) setting('general.site_name', 'DESTRA CMS');
        }

        try {
            $email = Services::email($this->config(), false);
            $email->setFrom($this->config()->fromEmail, $this->config()->fromName);
            $email->setTo($to);
            $email->setSubject(mb_substr($subject, 0, 180));

            $replyTo = (string) ($options['reply_to'] ?? setting('mail.reply_to', ''));
            if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $email->setReplyTo($replyTo, (string) setting('general.site_name', ''));
            }

            foreach (['cc', 'bcc'] as $type) {
                if (! empty($options[$type]) && filter_var($options[$type], FILTER_VALIDATE_EMAIL)) {
                    $type === 'cc' ? $email->setCC($options[$type]) : $email->setBCC($options[$type]);
                }
            }

            $email->setMessage($html);

            foreach ((array) ($options['attachments'] ?? []) as $path) {
                $safe = $this->safeAttachment((string) $path);
                if ($safe !== null) {
                    $email->attach($safe);
                }
            }

            $sent = $email->send(false);
            if (! $sent) {
                log_message('error', 'Mail send failed: ' . $email->printDebugger(['headers']));
            }

            $email->clear(true);

            return (bool) $sent;
        } catch (Throwable $e) {
            log_message('error', 'Mail exception: ' . $e->getMessage());

            return false;
        }
    }

    public function sendTemplate(string $to, string $subject, string $view, array $data = [], array $options = []): bool
    {
        $data = array_merge([
            'siteName' => (string) setting('general.site_name', 'DESTRA CMS'),
            'siteUrl'  => base_url('/'),
            'tagline'  => (string) setting('general.tagline', ''),
            'year'     => date('Y'),
        ], $data);

        $file = APPPATH . 'Views/emails/' . ltrim($view, '/') . '.php';
        if (is_file($file)) {
            $body = view('emails/' . ltrim($view, '/'), $data);
        } else {
            $body = '<p>' . esc((string) ($data['message'] ?? '')) . '</p>';
        }

        $html = $this->wrap($body, (string) ($data['preheader'] ?? ''));

        return $this->send($to, $subject, $html, $options);
    }

    public function notifyAdmin(string $subject, string $message, array $options = []): bool
    {
        if (! service('settings')->bool('comments.notify_admin', true)) {
            return false;
        }

        $to = (string) setting('mail.notify_email', '');
        if ($to === '') {
            $to = (string) setting('general.admin_email', '');
        }
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return $this->sendTemplate($to, $subject, 'notification', ['message' => $message, 'preheader' => $subject], $options);
    }

    public function wrap(string $content, string $preheader = ''): string
    {
        $site  = esc((string) setting('general.site_name', 'DESTRA CMS'));
        $url   = esc(base_url('/'), 'attr');
        $year  = date('Y');
        $color = service('sanitizer')->color(setting('appearance.primary'), '#4f46e5');
        $accent = service('sanitizer')->color(setting('appearance.secondary'), '#0ea5e9');

        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $site . '</title></head>'
            . '<body style="margin:0;padding:0;background:#eef2ff;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#111827;">'
            . ($preheader !== '' ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . esc($preheader) . '</div>' : '')
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2ff;padding:32px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 50px rgba(15,23,42,.14);">'
            . '<tr><td style="background:linear-gradient(120deg,' . $color . ',' . $accent . ');padding:26px 32px;color:#ffffff;font-size:20px;font-weight:700;letter-spacing:.2px;">'
            . '<a href="' . $url . '" style="color:#ffffff;text-decoration:none;">' . $site . '</a>'
            . '</td></tr>'
            . '<tr><td style="padding:32px;font-size:15px;line-height:1.65;color:#1f2937;">' . $content . '</td></tr>'
            . '<tr><td style="padding:20px 32px;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.6;">'
            . '&copy; ' . $year . ' ' . $site . ' &middot; <a href="' . $url . '" style="color:#475569;">' . esc(parse_url(base_url(), PHP_URL_HOST) ?: '') . '</a><br>'
            . 'You are receiving this message because you interacted with our website.'
            . '</td></tr></table></td></tr></table></body></html>';
    }

    private function safeAttachment(string $path): ?string
    {
        $path = str_replace(['\\', "\0"], ['/', ''], $path);
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        $real = realpath($path);
        if ($real === false || ! is_file($real)) {
            return null;
        }

        $allowedRoots = [realpath(WRITEPATH) ?: WRITEPATH, realpath(FCPATH) ?: FCPATH];
        foreach ($allowedRoots as $root) {
            $root = rtrim(str_replace('\\', '/', (string) $root), '/');
            if ($root !== '' && str_starts_with(str_replace('\\', '/', $real), $root . '/')) {
                return $real;
            }
        }

        return null;
    }
}
