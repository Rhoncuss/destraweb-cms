<?php

namespace Destra\SDK;

use App\Libraries\Hook;

/**
 * Stable extension base class for DESTRA plugins.
 *
 * A plugin lives in /plugins/{slug}/ with plugin.json and a PHP entry point
 * that declares a class extending this one.
 */
abstract class Plugin
{
    /** @var array<string, mixed> */
    protected array $meta = [];

    /** @var array<string, mixed> */
    protected array $settings = [];

    /**
     * Called on every requests after the plugin is activated. Register hooks,
     * routes or shortcodes here.
     */
    public function register(): void
    {
    }

    /**
     * Called once when the plugin is activated in the admin panel.
     */
    public function activate(): void
    {
    }

    /**
     * Called once when the plugin is deactivated.
     */
    public function deactivate(): void
    {
    }

    public function setContext(array $meta, array $settings): void
    {
        $this->meta     = $meta;
        $this->settings = $settings;
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        Hook::add($hook, $callback, $priority);
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        Hook::filter($hook, $callback, $priority);
    }

    /**
     * Return a simple settings definition: ['api_key' => ['label' => 'API key', 'type' => 'text']]
     *
     * @return array<string, array<string, mixed>>
     */
    public function settingsSchema(): array
    {
        return [];
    }
}
