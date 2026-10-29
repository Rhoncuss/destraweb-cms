<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * Session-based CSRF tokens are never exposed through a cookie. The token
     * stays stable for the session so AJAX widgets (uploads, toggles) can keep
     * sending the hash rendered into the page.
     */
    public string $csrfProtection = 'session';

    public bool $tokenRandomize = false;

    public string $tokenName = 'destra_csrf';

    public string $headerName = 'X-CSRF-TOKEN';

    public string $cookieName = 'destra_csrf';

    public int $expires = 14400;

    public bool $regenerate = false;

    public bool $redirect = false;
}
