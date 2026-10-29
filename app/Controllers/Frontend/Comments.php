<?php

namespace App\Controllers\Frontend;

use Throwable;

/**
 * Public comment submission with honeypot, captcha, rate limiting and
 * optional moderation.
 */
class Comments extends BaseFrontendController
{
    public function store()
    {
        $post      = $this->request->getPost();
        $contentId = (int) ($post['content_id'] ?? 0);
        $parentId  = (int) ($post['parent_id'] ?? 0);
        $body      = (string) ($post['body'] ?? '');

        if (trim((string) ($post['website'] ?? '')) !== '') {
            return $this->jsonFail('Your comment could not be submitted.', 422);
        }

        if (! service('settings')->bool('comments.enabled', true)) {
            return $this->jsonFail('Comments are disabled.', 403);
        }

        if ($contentId <= 0) {
            return $this->jsonFail('Missing article reference.');
        }

        $content = service('contentService')->findById($contentId, $this->locale);
        if ($content === null || $content['type'] === 'page') {
            return $this->jsonFail('The article could not be found.', 404);
        }

        if ((int) $content['allow_comments'] !== 1) {
            return $this->jsonFail('Comments are closed for this article.', 403);
        }

        $user         = auth_user();
        $requireLogin = service('settings')->bool('comments.require_login', false);
        $allowGuests  = service('settings')->bool('comments.allow_guests', true);

        if ($requireLogin && $user === null) {
            return $this->jsonFail('Please sign in to join the conversation.', 401);
        }

        if ($user === null && ! $allowGuests) {
            return $this->jsonFail('Please sign in to comment.', 401);
        }

        if (! service('captcha')->verifyRequest('comments')) {
            return $this->jsonFail('Captcha verification failed. Please try again.');
        }

        $sanitizer = service('sanitizer');
        $body      = $sanitizer->textarea($body, max(200, setting_int('comments.max_length', 3000)));

        if (mb_strlen($body) < 3) {
            return $this->jsonFail('Please write a slightly longer comment.');
        }

        $email = $user['email'] ?? $sanitizer->email($post['email'] ?? '');
        $name  = $user['name'] ?? $sanitizer->text($post['name'] ?? '', 120);

        if ($name === '') {
            return $this->jsonFail('Please add your name.');
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonFail('Please enter a valid email address.');
        }

        $ipHash = service('crypto')->hashIp($this->request->getIPAddress());

        try {
            $db = db_connect();

            $recent = (int) $db->table('comments')
                ->where('ip_hash', $ipHash)
                ->where('created_at >=', date('Y-m-d H:i:s', time() - 60))
                ->countAllResults();

            if ($recent > 0) {
                return $this->jsonFail('You just posted a comment. Please wait a moment.', 429);
            }

            $hourly = (int) $db->table('comments')
                ->where('ip_hash', $ipHash)
                ->where('created_at >=', date('Y-m-d H:i:s', time() - 3600))
                ->countAllResults();

            if ($hourly >= 12) {
                return $this->jsonFail('You have reached the comment limit for this hour.', 429);
            }

            if ($parentId > 0) {
                $parent = $db->table('comments')
                    ->where('id', $parentId)
                    ->where('content_id', $contentId)
                    ->get()
                    ->getRowArray();
                if (! $parent) {
                    $parentId = 0;
                }
            } else {
                $parentId = 0;
            }

            $autoApprove = service('settings')->bool('comments.auto_approve', false);
            $status      = $autoApprove ? 'approved' : 'pending';

            $db->table('comments')->insert([
                'content_id'  => $contentId,
                'parent_id'   => $parentId > 0 ? $parentId : null,
                'user_id'     => $user['id'] ?? null,
                'author_name' => $name,
                'author_email'=> $email,
                'body'        => $body,
                'status'      => $status,
                'ip_hash'     => $ipHash,
                'user_agent'  => mb_substr((string) $this->request->getUserAgent(), 0, 255),
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Comment failed: ' . $e->getMessage());

            return $this->jsonFail('Something went wrong while saving your comment.', 500);
        }

        if (service('settings')->bool('comments.notify_admin', true)) {
            $this->notifyAdmins(
                'comment',
                'New comment from ' . $name,
                mb_substr($body, 0, 300),
                admin_url('comments'),
                'fa-solid fa-comments'
            );

            try {
                service('mailer')->notifyAdmin(
                    'New comment on ' . (string) $content['title'],
                    '<p><strong>' . esc($name) . '</strong> wrote:</p><blockquote>' . nl2br(esc($body)) . '</blockquote>'
                    . '<p><a href="' . esc(admin_url('comments'), 'attr') . '">Moderate in DESTRA</a></p>'
                );
            } catch (Throwable) {
            }
        }

        return $this->jsonOk([
            'status' => $status,
        ], $status === 'approved'
            ? 'Thank you! Your comment is live.'
            : 'Thanks! Your comment is awaiting moderation.');
    }
}
