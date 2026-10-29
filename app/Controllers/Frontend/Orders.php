<?php

namespace App\Controllers\Frontend;

use Throwable;

/**
 * Order / booking requests attached to a content item with order_form enabled.
 */
class Orders extends BaseFrontendController
{
    public function store(int $contentId = 0)
    {
        $content = $contentId > 0 ? service('contentService')->findById($contentId, $this->locale) : null;

        if ($content === null || (int) ($content['order_form'] ?? 0) !== 1) {
            return $this->jsonFail('This order form is not available.', 404);
        }

        $post = $this->request->getPost();

        if (trim((string) ($post['website'] ?? '')) !== '') {
            return $this->jsonFail('Your request could not be processed.', 422);
        }

        if (! service('captcha')->verifyRequest('forms')) {
            return $this->jsonFail('Captcha verification failed. Please try again.');
        }

        $sanitizer = service('sanitizer');
        $name      = $sanitizer->text($post['name'] ?? '', 120);
        $email     = $sanitizer->email($post['email'] ?? '');
        $phone     = $sanitizer->text($post['phone'] ?? '', 50);
        $message   = $sanitizer->textarea($post['message'] ?? '', 2000);

        if ($name === '') {
            return $this->jsonFail('Please add your name.');
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonFail('Please enter a valid email address.');
        }

        $details = [
            'quantity' => max(1, min(999, (int) ($post['quantity'] ?? 1))),
            'option'   => $sanitizer->text($post['option'] ?? '', 120),
            'locale'   => $this->locale,
        ];

        $ipHash = service('crypto')->hashIp($this->request->getIPAddress());

        try {
            $db = db_connect();

            $recent = (int) $db->table('orders')
                ->where('content_id', $contentId)
                ->where('ip_hash', $ipHash)
                ->where('created_at >=', date('Y-m-d H:i:s', time() - 600))
                ->countAllResults();

            if ($recent >= 3) {
                return $this->jsonFail('You just sent a request. Please wait before trying again.', 429);
            }

            $db->table('orders')->insert([
                'content_id' => $contentId,
                'name'       => $name,
                'email'      => $email,
                'phone'      => $phone,
                'message'    => $message,
                'data'       => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status'     => 'new',
                'ip_hash'    => $ipHash,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Order failed: ' . $e->getMessage());

            return $this->jsonFail('Something went wrong while sending your request.', 500);
        }

        $this->notifyAdmins(
            'order',
            'New order request from ' . $name,
            mb_substr($message !== '' ? $message : 'New order for ' . $content['title'], 0, 300),
            admin_url('orders'),
            'fa-solid fa-bag-shopping'
        );

        try {
            service('mailer')->notifyAdmin(
                'New order request: ' . (string) $content['title'],
                '<p><strong>' . esc($name) . '</strong> (' . esc($email) . ($phone !== '' ? ' · ' . esc($phone) : '') . ') requested:</p>'
                . '<p>Quantity: ' . (int) $details['quantity'] . '</p>'
                . ($details['option'] !== '' ? '<p>Option: ' . esc($details['option']) . '</p>' : '')
                . ($message !== '' ? '<blockquote>' . nl2br(esc($message)) . '</blockquote>' : '')
                . '<p><a href="' . esc(admin_url('orders'), 'attr') . '">Open orders in DESTRA</a></p>'
            );
        } catch (Throwable) {
        }

        return $this->jsonOk([], 'Thank you! Your request has been received.');
    }
}
