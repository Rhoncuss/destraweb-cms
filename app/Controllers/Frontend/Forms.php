<?php

namespace App\Controllers\Frontend;

use Throwable;

/**
 * Public form submissions with schema-aware validation, spam protection and
 * email + admin notification delivery.
 */
class Forms extends BaseFrontendController
{
    public function submit(int $id = 0)
    {
        $form = $this->findForm($id);
        if ($form === null) {
            return $this->jsonFail('This form is no longer available.', 404);
        }

        $post   = $this->request->getPost();
        $schema = json_decode((string) ($form['fields'] ?? ''), true);
        $schema = is_array($schema) ? $schema : [];

        if ($schema === []) {
            return $this->jsonFail('This form is not configured yet.', 422);
        }

        if (trim((string) ($post['website'] ?? '')) !== '') {
            return $this->jsonFail('Your submission could not be processed.', 422);
        }

        if ((int) $form['captcha'] === 1 && ! service('captcha')->verifyRequest('forms')) {
            return $this->jsonFail('Captcha verification failed. Please try again.');
        }

        $input   = is_array($post['fields'] ?? null) ? $post['fields'] : [];
        $clean   = [];
        $errors  = [];
        $email   = '';

        foreach ($schema as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key      = (string) ($field['key'] ?? $field['name'] ?? '');
            $label    = (string) ($field['label'] ?? $key);
            $type     = (string) ($field['type'] ?? 'text');
            $required = ! empty($field['required']);
            $value    = $input[$key] ?? '';

            if ($type === 'checkbox') {
                $selected = array_values(array_filter(array_map(
                    static fn ($option): string => is_scalar($option) ? trim((string) $option) : '',
                    is_array($value) ? $value : []
                )));
                $allowed = array_map(static fn ($option): string => (string) $option, (array) ($field['options'] ?? []));
                $selected = array_values(array_intersect($selected, $allowed));
                $clean[$key] = implode(', ', $selected);

                if ($required && $clean[$key] === '') {
                    $errors[$key] = $label . ' is required.';
                }
                continue;
            }

            if (! is_scalar($value)) {
                $value = '';
            }
            $value = (string) $value;

            if ($type === 'email') {
                $sanitized = service('sanitizer')->email($value);
                if ($sanitized === '' && $value !== '') {
                    $errors[$key] = $label . ' must be a valid email address.';
                } elseif ($sanitized !== '') {
                    $email = $email !== '' ? $email : $sanitized;
                }
                $value = $sanitized;
            } elseif ($type === 'textarea') {
                $value = service('sanitizer')->textarea($value, 5000);
            } elseif ($type === 'date') {
                $value = service('sanitizer')->date($value) ?? '';
            } elseif (in_array($type, ['select', 'radio'], true)) {
                $value = service('sanitizer')->text($value, 190);
                $allowed = array_map(static fn ($option): string => (string) $option, (array) ($field['options'] ?? []));
                if ($value !== '' && ! in_array($value, $allowed, true)) {
                    $value = '';
                }
            } else {
                $value = service('sanitizer')->text($value, 1000);
            }

            if ($required && $value === '') {
                $errors[$key] = $label . ' is required.';
            }

            $clean[$key] = $value;
        }

        if ($errors !== []) {
            return $this->jsonFail('Please check the highlighted fields.', 422, ['errors' => $errors]);
        }

        $ipHash = service('crypto')->hashIp($this->request->getIPAddress());

        try {
            $db = db_connect();

            $recentSubmissions = (int) $db->table('form_submissions')
                ->where('form_id', $id)
                ->where('ip_hash', $ipHash)
                ->where('created_at >=', date('Y-m-d H:i:s', time() - 3600))
                ->countAllResults();

            if ($recentSubmissions >= 6) {
                return $this->jsonFail('You have reached the submission limit. Please try again later.', 429);
            }

            $db->table('form_submissions')->insert([
                'form_id'    => $id,
                'data'       => json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip_hash'    => $ipHash,
                'user_agent' => mb_substr((string) $this->request->getUserAgent(), 0, 255),
                'locale'     => $this->locale,
                'is_read'    => 0,
                'status'     => 'new',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $db->table('forms')->where('id', $id)->set('submissions_count', 'submissions_count + 1', false)->update();
        } catch (Throwable $e) {
            log_message('error', 'Form submission failed: ' . $e->getMessage());

            return $this->jsonFail('Something went wrong while sending your message.', 500);
        }

        $this->sendNotification($form, $schema, $clean, $email);
        $this->notifyAdmins(
            'form',
            'New ' . $form['name'] . ' submission',
            mb_substr(implode(' · ', array_filter($clean, static fn ($value): bool => $value !== '')), 0, 300),
            admin_url('inbox'),
            'fa-solid fa-inbox'
        );

        $message = trim((string) ($form['success_message'] ?? ''));

        return $this->jsonOk([], $message !== '' ? $message : 'Thank you! Your message has been sent.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findForm(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            $row = db_connect()->table('forms')->where('id', $id)->where('is_active', 1)->get()->getRowArray();
        } catch (Throwable) {
            return null;
        }

        return $row ?: null;
    }

    /**
     * @param array<string, mixed>                $form
     * @param list<array<string, mixed>>          $schema
     * @param array<string, string>               $values
     */
    private function sendNotification(array $form, array $schema, array $values, string $replyTo): void
    {
        $to = service('sanitizer')->email($form['email_to'] ?? '');
        if ($to === '') {
            $to = service('sanitizer')->email(setting('general.admin_email', ''));
        }
        if ($to === '') {
            return;
        }

        $labels = [];
        foreach ($schema as $field) {
            if (is_array($field)) {
                $labels[(string) ($field['key'] ?? $field['name'] ?? '')] = (string) ($field['label'] ?? '');
            }
        }

        $rows = '';
        foreach ($values as $key => $value) {
            if ($value === '') {
                continue;
            }
            $rows .= '<tr><td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;">'
                . esc($labels[$key] ?? $key)
                . '</td><td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:14px;">'
                . nl2br(esc($value))
                . '</td></tr>';
        }

        $subject = trim((string) ($form['email_subject'] ?? ''));
        if ($subject === '') {
            $subject = 'New ' . $form['name'] . ' submission';
        }

        $html = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc($form['name']) . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;">' . $rows . '</table>'
            . '<p style="margin:18px 0 0;color:#64748b;font-size:12px;">Received ' . date('M j, Y H:i') . ' · ' . esc((string) setting('general.site_name', 'DESTRA CMS')) . '</p>';

        try {
            service('mailer')->send($to, $subject, service('mailer')->wrap($html), [
                'reply_to' => $replyTo !== '' ? $replyTo : null,
                'context'  => 'form',
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Form notification failed: ' . $e->getMessage());
        }
    }
}
