<?php

namespace App\Libraries;

use Config\Services;
use Throwable;

/**
 * OpenAI-compatible AI assistant (works with OpenAI, OpenRouter, Groq,
 * Together, Ollama and similar endpoints). Only reachable by authenticated
 * administrators and only when explicitly enabled.
 */
class AiService
{
    public function enabled(): bool
    {
        $settings = service('settings');
        if (! $settings->bool('integrations.ai_enabled', false)) {
            return false;
        }

        $base = (string) $settings->get('integrations.ai_base_url', '');
        if ($this->baseUrl() === '') {
            return false;
        }

        $key = (string) $settings->get('integrations.ai_api_key', '');
        if ($key !== '') {
            return true;
        }

        $host = strtolower((string) parse_url($base, PHP_URL_HOST));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * @return array{ok: bool, text: string, error: string, usage: array<string, int>}
     */
    public function chat(array $messages, array $options = []): array
    {
        $fail = ['ok' => false, 'text' => '', 'error' => 'AI is not configured.', 'usage' => []];

        if (! $this->enabled()) {
            return $fail;
        }

        $settings = service('settings');
        $payload  = [
            'model'       => (string) ($options['model'] ?? $settings->get('integrations.ai_model', 'gpt-4o-mini')),
            'messages'    => $messages,
            'temperature' => (float) ($options['temperature'] ?? $settings->get('integrations.ai_temperature', '0.7')),
            'max_tokens'  => (int) ($options['max_tokens'] ?? $settings->get('integrations.ai_max_tokens', 1200)),
        ];

        if (! empty($options['json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = $this->client()->post($this->baseUrl() . '/chat/completions', [
                'headers' => $this->headers(),
                'json'    => $payload,
            ]);

            $status = $response->getStatusCode();
            $body   = json_decode((string) $response->getBody(), true);

            if ($status >= 400 || ! is_array($body)) {
                $message = is_array($body) ? (string) ($body['error']['message'] ?? 'HTTP ' . $status) : 'HTTP ' . $status;
                log_message('error', 'AI request failed: ' . $message);

                return ['ok' => false, 'text' => '', 'error' => $message, 'usage' => []];
            }

            $text = (string) ($body['choices'][0]['message']['content'] ?? '');
            $usage = [
                'prompt'     => (int) ($body['usage']['prompt_tokens'] ?? 0),
                'completion' => (int) ($body['usage']['completion_tokens'] ?? 0),
                'total'      => (int) ($body['usage']['total_tokens'] ?? 0),
            ];

            return ['ok' => true, 'text' => trim($text), 'error' => '', 'usage' => $usage];
        } catch (Throwable $e) {
            log_message('error', 'AI exception: ' . $e->getMessage());

            return ['ok' => false, 'text' => '', 'error' => 'The AI service is unreachable.', 'usage' => []];
        }
    }

    public function complete(string $prompt, array $options = []): array
    {
        return $this->chat([
            ['role' => 'system', 'content' => 'You are a concise, accurate writing assistant for a professional multilingual CMS.'],
            ['role' => 'user', 'content' => $prompt],
        ], $options);
    }

    /**
     * @return array{ok: bool, title?: string, description?: string, keywords?: string, error?: string}
     */
    public function generateMeta(string $title, string $body): array
    {
        $body = mb_substr(strip_tags($body), 0, 6000);
        $prompt = "Create SEO metadata for this article.\nTitle: {$title}\nArticle:\n{$body}\n\n"
            . 'Return strict JSON with keys "title" (max 60 chars), "description" (max 155 chars) and "keywords" (5 comma separated terms).';

        $result = $this->complete($prompt, ['json' => true, 'temperature' => 0.4]);
        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error']];
        }

        $data = $this->decodeJson($result['text']);
        if ($data === null) {
            return ['ok' => false, 'error' => 'The AI response was not valid JSON.'];
        }

        return [
            'ok'          => true,
            'title'       => mb_substr(trim((string) ($data['title'] ?? '')), 0, 70),
            'description' => mb_substr(trim((string) ($data['description'] ?? '')), 0, 170),
            'keywords'    => mb_substr(trim((string) ($data['keywords'] ?? '')), 0, 250),
        ];
    }

    public function summarize(string $title, string $body, int $words = 60): array
    {
        $body   = mb_substr(strip_tags($body), 0, 8000);
        $prompt = "Summarise this article in at most {$words} words, plain text only.\nTitle: {$title}\n\n{$body}";

        return $this->complete($prompt, ['temperature' => 0.4]);
    }

    /**
     * @return array{ok: bool, tags?: list<string>, error?: string}
     */
    public function generateTags(string $title, string $body): array
    {
        $body   = mb_substr(strip_tags($body), 0, 5000);
        $prompt = "Suggest 6 short tags for this article. Return JSON: {\"tags\":[\"...\"]}.\nTitle: {$title}\n\n{$body}";

        $result = $this->complete($prompt, ['json' => true, 'temperature' => 0.4]);
        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error']];
        }

        $data = $this->decodeJson($result['text']);
        $tags = array_values(array_filter(array_map('trim', (array) ($data['tags'] ?? []))));

        return ['ok' => true, 'tags' => array_slice($tags, 0, 8)];
    }

    public function improve(string $text): array
    {
        $prompt = "Rewrite the following content to be clearer and more engaging while preserving meaning, tone and any HTML structure. Return only the rewritten text.\n\n" . mb_substr($text, 0, 12000);

        return $this->complete($prompt, ['temperature' => 0.6]);
    }

    /**
     * @return array{ok: bool, media?: array<string, mixed>, error?: string}
     */
    public function generateImage(string $prompt, ?int $userId = null): array
    {
        if (! $this->enabled()) {
            return ['ok' => false, 'error' => 'AI is not configured.'];
        }

        $settings = service('settings');
        $payload  = [
            'model'  => (string) $settings->get('integrations.ai_image_model', 'gpt-image-1'),
            'prompt' => mb_substr($prompt, 0, 3000),
            'size'   => '1024x1024',
            'n'      => 1,
        ];

        try {
            $response = $this->client()->post($this->baseUrl() . '/images/generations', [
                'headers' => $this->headers(),
                'json'    => $payload,
            ]);

            $status = $response->getStatusCode();
            $body   = json_decode((string) $response->getBody(), true);
            if ($status >= 400 || ! is_array($body)) {
                return ['ok' => false, 'error' => (string) ($body['error']['message'] ?? 'HTTP ' . $status)];
            }

            $item = (array) ($body['data'][0] ?? []);
            $binary = '';

            if (! empty($item['b64_json'])) {
                $binary = (string) base64_decode((string) $item['b64_json'], true);
            } elseif (! empty($item['url'])) {
                $download = $this->client()->get(service('sanitizer')->url((string) $item['url']));
                $binary   = (string) $download->getBody();
            }

            if ($binary === '' || strlen($binary) > 12 * 1024 * 1024) {
                return ['ok' => false, 'error' => 'The AI did not return a usable image.'];
            }

            $media = service('media')->storeImageBinary($binary, 'ai-' . date('Ymd-His'), $userId ?? ((int) service('auth')->id() ?: 1));

            return ['ok' => true, 'media' => $media];
        } catch (Throwable $e) {
            log_message('error', 'AI image generation failed: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Image generation failed.'];
        }
    }

    private function client()
    {
        return Services::curlrequest([
            'timeout'         => 90,
            'connect_timeout' => 10,
            'http_errors'     => false,
        ], null, null, false);
    }

    private function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
        $key = (string) service('settings')->get('integrations.ai_api_key', '');
        if ($key !== '') {
            $headers['Authorization'] = 'Bearer ' . $key;
        }

        return $headers;
    }

    private function baseUrl(): string
    {
        $base = rtrim(trim((string) service('settings')->get('integrations.ai_base_url', '')), '/');
        if ($base === '' || ! preg_match('~^https?://~i', $base)) {
            return '';
        }

        $host = strtolower((string) parse_url($base, PHP_URL_HOST));
        if ($host === '' || $host === '169.254.169.254') {
            return '';
        }

        return $base;
    }

    private function decodeJson(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;
        if (preg_match('/\{.*\}/s', $text, $match)) {
            $text = $match[0];
        }
        $data = json_decode($text, true);

        return is_array($data) ? $data : null;
    }
}
