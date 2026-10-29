<?php

namespace App\Controllers\Admin;

use Throwable;

class Ai extends BaseAdminController
{
    public function generate()
    {
        if (! user_can('content.create') && ! user_can('content.edit')) {
            return $this->fail('You do not have permission to use the AI assistant.', 403);
        }

        if (! service('ai')->enabled()) {
            return $this->fail('The AI assistant is not configured. Add an API key under Settings → Integrations.', 503);
        }

        $key = 'destra_ai_' . (int) ($this->admin['id'] ?? 0);
        $count = (int) (cache($key) ?: 0);
        if ($count >= 20) {
            return $this->fail('The AI assistant is cooling down. Try again in a minute.', 429);
        }
        cache()->save($key, $count + 1, MINUTE);

        $action = (string) $this->request->getPost('action');
        $title  = service('sanitizer')->text($this->request->getPost('title'), 255);
        $body   = (string) $this->request->getPost('body');
        $body   = mb_substr(strip_tags($body), 0, 12000);

        $ai = service('ai');

        try {
            switch ($action) {
                case 'meta':
                    if ($title === '' && $body === '') {
                        return $this->fail('Add a title or some body text first.');
                    }
                    $result = $ai->generateMeta($title !== '' ? $title : mb_substr($body, 0, 120), $body);
                    if (! ($result['ok'] ?? false)) {
                        return $this->fail((string) ($result['error'] ?? 'The AI request failed.'), 502);
                    }

                    return $this->ok([
                        'title'       => service('sanitizer')->text($result['title'] ?? '', 255),
                        'description' => service('sanitizer')->text($result['description'] ?? '', 500),
                        'keywords'    => service('sanitizer')->text($result['keywords'] ?? '', 500),
                    ], 'SEO metadata generated.');

                case 'summary':
                    if ($body === '') {
                        return $this->fail('Add some body text first.');
                    }
                    $result = $ai->summarize($title, $body, 55);
                    if (! ($result['ok'] ?? false)) {
                        return $this->fail((string) ($result['error'] ?? 'The AI request failed.'), 502);
                    }

                    return $this->ok(['text' => service('sanitizer')->textarea($result['text'], 1000)], 'Summary generated.');

                case 'tags':
                    if ($title === '' && $body === '') {
                        return $this->fail('Add a title or some body text first.');
                    }
                    $result = $ai->generateTags($title, $body);
                    if (! ($result['ok'] ?? false)) {
                        return $this->fail((string) ($result['error'] ?? 'The AI request failed.'), 502);
                    }

                    $tags = array_values(array_filter(array_map(
                        static fn (string $tag): string => service('sanitizer')->text($tag, 60),
                        (array) ($result['tags'] ?? [])
                    )));

                    return $this->ok(['tags' => $tags, 'text' => implode(', ', $tags)], 'Tags generated.');

                case 'improve':
                    if ($body === '') {
                        return $this->fail('Add some body text first.');
                    }
                    $result = $ai->improve($body);
                    if (! ($result['ok'] ?? false)) {
                        return $this->fail((string) ($result['error'] ?? 'The AI request failed.'), 502);
                    }

                    return $this->ok(['text' => service('sanitizer')->html($result['text'])], 'Copy improved.');

                default:
                    return $this->fail('Unknown AI action.');
            }
        } catch (Throwable $e) {
            log_message('error', 'AI admin action failed: ' . $e->getMessage());

            return $this->fail('The AI request failed. Please try again.', 502);
        }
    }
}
