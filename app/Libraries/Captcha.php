<?php

namespace App\Libraries;

use Config\Services;
use Throwable;

/**
 * reCAPTCHA v2/v3, hCaptcha and Cloudflare Turnstile support. Verification is
 * server-side only and fails closed once a provider is configured.
 */
class Captcha
{
    private const ENDPOINTS = [
        'recaptcha_v2' => 'https://www.google.com/recaptcha/api/siteverify',
        'recaptcha_v3' => 'https://www.google.com/recaptcha/api/siteverify',
        'hcaptcha'     => 'https://hcaptcha.com/siteverify',
        'turnstile'    => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ];

    public function required(string $context = 'forms'): bool
    {
        $settings = service('settings');
        if (! $settings->bool('captcha.enabled', false)) {
            return false;
        }

        if ($settings->get('captcha.site_key', '') === '' || $settings->get('captcha.secret_key', '') === '') {
            return false;
        }

        $toggle = match ($context) {
            'login'      => 'captcha.on_login',
            'comments'   => 'captcha.on_comments',
            'newsletter' => 'captcha.on_newsletter',
            default      => 'captcha.on_forms',
        };

        return $settings->bool($toggle, true);
    }

    public function provider(): string
    {
        $provider = (string) service('settings')->get('captcha.provider', 'recaptcha_v2');

        return isset(self::ENDPOINTS[$provider]) ? $provider : 'recaptcha_v2';
    }

    public function siteKey(): string
    {
        return (string) service('settings')->get('captcha.site_key', '');
    }

    public function widget(string $context = 'forms'): string
    {
        if (! $this->required($context)) {
            return '';
        }

        $key   = esc($this->siteKey(), 'attr');
        $nonce = esc(csp_nonce(), 'attr');

        if ($this->provider() === 'recaptcha_v2') {
            return '<div class="dst-captcha"><div class="g-recaptcha" data-sitekey="' . $key . '"></div></div>'
                . '<script nonce="' . $nonce . '" src="https://www.google.com/recaptcha/api.js" async defer></script>';
        }

        if ($this->provider() === 'recaptcha_v3') {
            return '<input type="hidden" name="g-recaptcha-response" value="">'
                . '<script nonce="' . $nonce . '" src="https://www.google.com/recaptcha/api.js?render=' . $key . '" async defer></script>'
                . '<script nonce="' . $nonce . '">'
                . 'window.destraCaptcha=function(cb){if(!window.grecaptcha){return;}grecaptcha.ready(function(){grecaptcha.execute("' . $key . '",{action:"submit"}).then(function(t){'
                . 'document.querySelectorAll("input[name=g-recaptcha-response]").forEach(function(i){i.value=t;});if(cb){cb(t);}});});};'
                . 'document.querySelectorAll("input[name=g-recaptcha-response]").forEach(function(i){var f=i.closest("form");if(f){f.addEventListener("submit",function(){if(!i.value){event.preventDefault();window.destraCaptcha(function(){f.submit();});}});}});'
                . '</script>';
        }

        if ($this->provider() === 'turnstile') {
            return '<div class="dst-captcha"><div class="cf-turnstile" data-sitekey="' . $key . '" data-theme="auto"></div></div>'
                . '<script nonce="' . $nonce . '" src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
        }

        return '<div class="dst-captcha"><div class="h-captcha" data-sitekey="' . $key . '"></div></div>'
            . '<script nonce="' . $nonce . '" src="https://js.hcaptcha.com/1/api.js" async defer></script>';
    }

    public function responseFrom(array $post): string
    {
        foreach (['g-recaptcha-response', 'cf-turnstile-response', 'h-captcha-response'] as $key) {
            if (! empty($post[$key]) && is_string($post[$key])) {
                return trim($post[$key]);
            }
        }

        return '';
    }

    public function verify(string $response, string $context = 'forms', ?string $ip = null, string $action = 'submit'): bool
    {
        if (! $this->required($context)) {
            return true;
        }

        if ($response === '' || strlen($response) > 10000) {
            return false;
        }

        $secret = (string) service('settings')->get('captcha.secret_key', '');
        if ($secret === '') {
            return false;
        }

        try {
            $client = Services::curlrequest(['timeout' => 10, 'http_errors' => false], null, null, false);
            $payload = ['secret' => $secret, 'response' => $response];
            if ($ip !== null && $ip !== '' && $this->provider() !== 'turnstile') {
                $payload['remoteip'] = $ip;
            }

            $result = $client->post(self::ENDPOINTS[$this->provider()], [
                'form_params' => $payload,
                'headers'     => ['Accept' => 'application/json'],
            ]);
            $body = json_decode((string) $result->getBody(), true);
        } catch (Throwable $e) {
            log_message('error', 'Captcha verification failed: ' . $e->getMessage());

            return false;
        }

        if (! is_array($body) || empty($body['success'])) {
            return false;
        }

        if ($this->provider() === 'recaptcha_v3') {
            $threshold = (float) service('settings')->get('captcha.threshold', '0.5');
            if ((float) ($body['score'] ?? 0) < $threshold) {
                return false;
            }
            if (! empty($body['action']) && $body['action'] !== $action) {
                return false;
            }
        }

        return true;
    }

    public function verifyRequest(string $context = 'forms', ?string $action = 'submit'): bool
    {
        if (! $this->required($context)) {
            return true;
        }

        $request = service('request');
        $post    = $request->getPost() ?? [];

        return $this->verify($this->responseFrom($post), $context, $request->getIPAddress(), $action);
    }
}
