<?php

namespace App\Controllers\Frontend;

/**
 * Public newsletter endpoints: subscribe (AJAX), double opt-in confirmation
 * and one-click unsubscribe.
 */
class Newsletter extends BaseFrontendController
{
    public function subscribe()
    {
        if (! service('settings')->bool('newsletter.enabled', true)) {
            return $this->jsonFail('The newsletter is currently closed.', 403);
        }

        $post    = $this->request->getPost();
        $email   = (string) ($post['email'] ?? '');
        $name    = (string) ($post['name'] ?? '');
        $privacy = ! empty($post['privacy']);

        if ($email === '') {
            return $this->jsonFail('Please enter your email address.');
        }

        if (! $privacy) {
            return $this->jsonFail('Please accept the privacy notice to subscribe.');
        }

        $lastAttempt = (int) (session('newsletter_last_attempt') ?? 0);
        if ($lastAttempt > 0 && (time() - $lastAttempt) < 4) {
            return $this->jsonFail('Please wait a moment before trying again.', 429);
        }
        session()->set('newsletter_last_attempt', time());

        if (! service('captcha')->verifyRequest('newsletter')) {
            return $this->jsonFail('Captcha verification failed. Please try again.');
        }

        $result = service('newsletter')->subscribe($email, $name, $this->requestLocale(), 'footer', true);

        return $result['ok']
            ? $this->jsonOk(['status' => $result['status'] ?? ''], $result['message'])
            : $this->jsonFail($result['message']);
    }

    public function confirm(?string $token = null)
    {
        $token = $token ?? (string) $this->request->getGet('token');
        $ok    = $token !== '' && service('newsletter')->confirm($token);

        return $this->messagePage(
            $ok ? 'Subscription confirmed' : 'Confirmation link invalid',
            $ok
                ? 'Thank you for confirming. Fresh stories will land in your inbox soon.'
                : 'This confirmation link has already been used or has expired.',
            $ok ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation'
        );
    }

    public function unsubscribe(?string $token = null)
    {
        $token = $token ?? (string) $this->request->getGet('token');
        $token = trim((string) $token);
        $ok    = $token !== '' && service('newsletter')->unsubscribe($token);

        return $this->messagePage(
            $ok ? 'You have been unsubscribed' : 'Unsubscribe link invalid',
            $ok
                ? 'You will no longer receive newsletter emails from us.'
                : 'We could not process that unsubscribe link. Please contact us if the problem persists.',
            $ok ? 'fa-solid fa-envelope-circle-check' : 'fa-solid fa-circle-exclamation'
        );
    }

    private function messagePage(string $heading, string $message, string $icon): string
    {
        $seo = service('seo')->render([
            'title'   => $heading,
            'url'     => current_url(),
            'noindex' => true,
        ]);

        return $this->renderPage('message', [
            'seo'       => $seo,
            'bodyClass' => 'message-page',
            'heading'   => $heading,
            'message'   => $message,
            'icon'      => $icon,
            'crumbs'    => [['label' => $heading]],
        ]);
    }
}
