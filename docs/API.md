# REST API v1

DESTRA exposes a token-authenticated JSON API under `/api/v1`. It is intended
for headless frontends, mobile apps, CI pipelines and integrations. The API is
read/write for content, media and comment moderation; business data such as
orders and subscribers is read-only.

- Base URL: `https://your-domain.tld/api/v1`
- Auth: `Authorization: Bearer dst_…` (or `X-API-Key: dst_…`)
- Format: JSON (`Accept: application/json`)
- Rate limit: 120 requests/minute per token by default (`api.rate_limit` setting)
- OpenAPI 3.0 document: `GET /api/v1/openapi.json` (token required unless
  **Settings → API → Public API docs** is enabled)

---

## 1. Create a token

Admin → **System → API tokens → Create token**:

1. Give it a descriptive name (`Mobile app`, `Zapier`, …).
2. Tick the scopes it needs.
3. Choose an expiry (30, 90, 365 days or never).
4. Copy the token immediately — only its HMAC-SHA256 hash is stored, and the
   plaintext is shown once.

Revoking a token takes effect instantly. The token owner must be an active user;
their role does not grant API access beyond the token's scopes.

### Scopes

| Scope | Grants |
| --- | --- |
| `content:read` | List and read published posts, pages, docs and taxonomy |
| `content:write` | Create and update content, including reading drafts/private items |
| `content:publish` | Publish or schedule content through the API |
| `media:read` | Browse the media library |
| `media:write` | Upload media |
| `comments:read` | Read comments |
| `comments:write` | Moderate comments |
| `orders:read` | Read orders and form submissions |
| `newsletter:read` | Read newsletter subscribers |
| `stats:read` | Read traffic and analytics figures |

Requests with an insufficient scope return `403 forbidden`.

---

## 2. Conventions

### Response envelope

Success:

```json
{
    "success": true,
    "data": { },
    "meta": { "page": 1, "per_page": 20, "total": 42, "pages": 3 }
}
```

Error:

```json
{
    "success": false,
    "error": {
        "code": "validation_failed",
        "message": "The title field is required.",
        "fields": { "title": "Title is required." }
    }
}
```

Common codes: `unauthorized` (401), `forbidden` (403), `not_found` (404),
`validation_failed` (422), `rate_limited` (429), `server_error` (500).

### Localization

Every readable resource accepts `?lang=xx` (default: the site's default
language). Slugs, titles, excerpts and body are returned in that language when a
translation exists.

### Pagination

`?page=2&per_page=50` — `per_page` is clamped to 1…100.

### Dates

All timestamps are returned in `Y-m-d H:i:s` (site timezone) unless noted.

---

## 3. Endpoints

### Content

| Method | Path | Scope | Description |
| --- | --- | --- | --- |
| `GET` | `/content` | `content:read` | Unified listing. Filters: `type`, `status`, `section`, `category`, `tag`, `q`, `lang`, `page`, `per_page` |
| `GET` | `/posts` | `content:read` | Listing restricted to posts |
| `GET` | `/pages` | `content:read` | Listing restricted to pages |
| `GET` | `/docs` | `content:read` | Listing restricted to documentation |
| `GET` | `/content/{id-or-slug}` | `content:read` | Single item with translations and SEO |
| `POST` | `/content` | `content:write` | Create content |
| `PUT/PATCH` | `/content/{id}` | `content:write` | Update content |
| `DELETE` | `/content/{id}` | `content:write` | Move to trash |

Listing defaults to `status=published`; reading drafts or private items requires
`content:write`. Publishing requires `content:publish`.

**Create example**

```bash
curl -X POST "https://example.com/api/v1/content?lang=en" \
  -H "Authorization: Bearer dst_xxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
        "title": "Shipping notes for release 1.4",
        "slug": "shipping-notes-1-4",
        "type": "post",
        "status": "draft",
        "excerpt": "What changed and why it matters.",
        "body": "<p>Full release notes…</p>",
        "categories": [2],
        "tags": ["release", "changelog"],
        "featured_image_id": 12,
        "sticky": false,
        "allow_comments": true,
        "translations": {
            "id": {
                "title": "Catatan rilis 1.4",
                "slug": "catatan-rilis-1-4",
                "excerpt": "Apa yang berubah dan mengapa penting.",
                "body": "<p>Catatan rilis lengkap…</p>"
            }
        }
    }'
```

**List example**

```bash
curl "https://example.com/api/v1/posts?category=security&tag=hardening&per_page=10&lang=en" \
  -H "Authorization: Bearer dst_xxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Accept: application/json"
```

The primary translation is written for the `?lang=` locale (default language
when omitted). Every additional entry in `translations` must include its own
`title`; `slug` is generated from the title when omitted and deduplicated
automatically. Category IDs must exist; tags are created on demand from their
names.

### Taxonomy

| Method | Path | Scope | Description |
| --- | --- | --- | --- |
| `GET` | `/categories` | `content:read` | Categories with slugs and descriptions |
| `GET` | `/tags` | `content:read` | Tags |
| `GET` | `/sections` | `content:read` | Content sections |
| `GET` | `/languages` | `content:read` | Active languages and the default code |

All accept `?lang=`.

### Media

| Method | Path | Scope | Description |
| --- | --- | --- | --- |
| `GET` | `/media` | `media:read` | List media, `?folder=` and pagination |
| `POST` | `/media` | `media:write` | Multipart upload (`file` or `files[]`, optional `folder_id`) |

```bash
curl -X POST https://example.com/api/v1/media \
  -H "Authorization: Bearer dst_xxxxxxxxxxxxxxxxxxxxxxxx" \
  -F "file=@cover.jpg" \
  -F "folder_id=3"
```

Images are converted to WebP and a thumbnail is generated automatically when
GD/Imagick is available.

### Comments

| Method | Path | Scope | Description |
| --- | --- | --- | --- |
| `GET` | `/comments` | `comments:read` | List comments, `?status=`, `?content_id=` |
| `POST` | `/comments/{id}/moderate` | `comments:write` | Set status: `approved`, `pending`, `spam`, `trash` |

```bash
curl -X POST https://example.com/api/v1/comments/42/moderate \
  -H "Authorization: Bearer dst_xxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{"status":"approved"}'
```

### Business data

| Method | Path | Scope | Description |
| --- | --- | --- | --- |
| `GET` | `/orders` | `orders:read` | Orders and form submissions incl. JSON payloads |
| `GET` | `/subscribers` | `newsletter:read` | Newsletter subscribers with status |
| `GET` | `/stats?days=30` | `stats:read` | Overview, daily views and popular content |

---

## 4. Errors and rate limiting

- Every response carries `X-RateLimit-Limit` and `X-RateLimit-Remaining`.
- On `429`, wait for `Retry-After` seconds (60).
- `401` includes `WWW-Authenticate: Bearer realm="DESTRA API"`.
- Malformed JSON returns `422 invalid_json`.
- CORS preflight (`OPTIONS`) is answered for all `/api/v1/*` paths.

The API is disabled when **Settings → API → Enable REST API** is off; every call
then returns `503 unavailable`.

---

## 5. OpenAPI document

`GET /api/v1/openapi.json` returns an OpenAPI 3.0.3 document that can be loaded
into Postman, Insomnia, Swagger UI or used to generate typed clients.

By default the document requires a token. Enable **Public API docs** in
Settings → API to expose it without authentication (useful for public developer
documentation).
