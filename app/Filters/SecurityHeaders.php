<?php

namespace App\Filters;

use App\Libraries\SecurityContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SecurityHeaders implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-XSS-Protection', '0');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(), usb=()');
        $response->setHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->setHeader('X-Permitted-Cross-Domain-Policies', 'none');

        $isHttps = $request->isSecure() || str_starts_with(strtolower(config('App')->baseURL), 'https://');
        if ($isHttps) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (trim($request->getPath(), '/') !== '' && str_starts_with(trim($request->getPath(), '/'), 'api/')) {
            $this->applyCors($request, $response);
        }

        $cspEnabled = true;
        try {
            $cspEnabled = service('settings')->bool('security.csp', true);
        } catch (\Throwable) {
        }

        if ($cspEnabled) {
            $nonce  = SecurityContext::nonce();
            $directives = [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'self'",
                "form-action 'self'",
                "script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com https://www.google-analytics.com https://www.google.com https://www.gstatic.com https://challenges.cloudflare.com https://js.hcaptcha.com https://cdn.tiny.cloud",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tiny.cloud https://cdn.jsdelivr.net",
                "font-src 'self' data: https://fonts.gstatic.com https://cdn.tiny.cloud",
                "img-src 'self' data: blob: https:",
                "media-src 'self' https:",
                "worker-src 'self' blob:",
                "manifest-src 'self'",
                "connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://www.googletagmanager.com https://www.google.com https://www.gstatic.com https://challenges.cloudflare.com https://hcaptcha.com",
                "frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.openstreetmap.org https://challenges.cloudflare.com https://newassets.hcaptcha.com https://js.hcaptcha.com",
            ];

            if ($isHttps) {
                $directives[] = 'upgrade-insecure-requests';
            }

            $response->setHeader('Content-Security-Policy', implode('; ', $directives));
        }

        return $response;
    }

    private function applyCors(RequestInterface $request, ResponseInterface $response): void
    {
        $origin  = $request->getHeaderLine('Origin');
        $allowed = '';
        try {
            $allowed = (string) service('settings')->get('api.allow_origins', '');
        } catch (\Throwable) {
        }

        $list = array_values(array_filter(array_map('trim', explode(',', $allowed))));
        if ($origin !== '' && (in_array('*', $list, true) || in_array($origin, $list, true))) {
            $response->setHeader('Access-Control-Allow-Origin', in_array('*', $list, true) ? '*' : $origin);
            $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-API-Key, Accept, X-Requested-With');
            $response->setHeader('Access-Control-Max-Age', '86400');
            $response->setHeader('Vary', 'Origin');
        }
    }
}
