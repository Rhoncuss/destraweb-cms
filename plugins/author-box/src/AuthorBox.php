<?php

namespace Destra\Plugins\AuthorBox;

use App\Libraries\Hook;
use Throwable;

/**
 * Reference plugin for DESTRA CMS.
 *
 * Demonstrates the extension surface available to separately distributed
 * plugins: a manifest with a settings schema, hook registration and a
 * self-contained view that is injected through the "content.render" filter.
 */
class AuthorBox
{
    /** @var array<string, mixed> */
    private array $meta = [];

    /** @var array<string, mixed> */
    private array $settings = [];

    public function setContext(array $meta, array $settings = []): void
    {
        $this->meta     = $meta;
        $this->settings = $settings;
    }

    public function register(): void
    {
        Hook::filter('content.render', [$this, 'appendToContent'], 20);
        Hook::add('frontend.body_end', [$this, 'styles'], 20);
    }

    public function appendToContent(string $html, array $content = []): string
    {
        if (($content['type'] ?? 'post') !== 'post' || empty($content['author_id'])) {
            return $html;
        }

        $author = $this->author((int) $content['author_id']);
        if ($author === null) {
            return $html;
        }

        $heading = $this->setting('heading', 'About the author');
        $bio     = trim((string) ($author['bio'] ?? '')) ?: $this->setting('fallback_bio', '');
        $showAvatar = (string) $this->setting('show_avatar', '1') !== '0';

        $avatar = '';
        if ($showAvatar) {
            $initial = mb_strtoupper(mb_substr((string) $author['name'], 0, 1));
            if (! empty($author['avatar'])) {
                $avatar = '<img class="dst-authorbox__img" src="' . esc((string) $author['avatar'], 'attr') . '" alt="' . esc((string) $author['name'], 'attr') . '" loading="lazy" width="56" height="56">';
            } else {
                $avatar = '<span class="dst-authorbox__img dst-authorbox__img--initial" aria-hidden="true">' . esc($initial) . '</span>';
            }
        }

        return $html . '<aside class="dst-authorbox">'
            . '<div class="dst-authorbox__card">'
            . $avatar
            . '<div class="dst-authorbox__body">'
            . '<span class="dst-authorbox__eyebrow">' . esc($heading) . '</span>'
            . '<strong class="dst-authorbox__name">' . esc((string) $author['name']) . '</strong>'
            . ($bio !== '' ? '<p class="dst-authorbox__bio">' . esc($bio) . '</p>' : '')
            . '</div></div></aside>';
    }

    public function styles(): void
    {
        echo '<style>.dst-authorbox{margin-top:32px}.dst-authorbox__card{display:flex;gap:16px;align-items:flex-start;padding:22px;border:1px solid var(--dst-line,#e2e8f0);border-radius:18px;background:var(--dst-surface,#fff)}'
            . '.dst-authorbox__img{width:56px;height:56px;border-radius:50%;object-fit:cover;flex:none}'
            . '.dst-authorbox__img--initial{display:grid;place-items:center;background:var(--dst-accent,#6d5efc);color:#fff;font-weight:700;font-size:1.25rem}'
            . '.dst-authorbox__eyebrow{display:block;font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;color:var(--dst-muted,#64748b);margin-bottom:2px}'
            . '.dst-authorbox__name{display:block;font-size:1.05rem}'
            . '.dst-authorbox__bio{margin:6px 0 0;color:var(--dst-muted,#475569);font-size:.95rem;line-height:1.6}</style>';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function author(int $id): ?array
    {
        try {
            return db_connect()->table('users')
                ->select('name, bio, avatar')
                ->where('id', $id)
                ->where('deleted_at', null)
                ->get()->getRowArray() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function setting(string $key, string $default = ''): string
    {
        $value = $this->settings[$key] ?? null;

        return $value === null || $value === '' ? $default : (string) $value;
    }
}
