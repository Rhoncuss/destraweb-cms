<?php

namespace App\Controllers\Frontend;

use Throwable;

/**
 * Public URL dispatcher.
 *
 * Every friendly URL (blog posts, pages, docs, archives, search) funnels
 * through here. Language prefixes are stripped first, then the request is
 * resolved against the content repository. Themes may override any of the
 * rendered views without touching this controller.
 */
class Router extends BaseFrontendController
{
    public function home()
    {
        if ($this->isDownForMaintenance()) {
            return $this->maintenanceResponse();
        }

        $contentService = service('contentService');
        $mode           = (string) setting('reading.home_mode', 'latest');
        $homeId         = setting_int('reading.home_page_id', 0);

        if ($mode === 'page' && $homeId > 0) {
            $page = $contentService->findById($homeId, $this->locale);
            if ($page !== null && $page['type'] === 'page') {
                return $this->contentResponse($page);
            }
        }

        $perPage = max(3, min(24, setting_int('reading.posts_per_page', 12)));

        $sticky = $contentService->query([
            'sticky'   => true,
            'per_page' => 3,
            'locale'   => $this->locale,
        ])['items'];

        $latest = $contentService->latest(min(9, $perPage), ['locale' => $this->locale]);

        if ($mode === 'section') {
            $sectionSlug = (string) (service('settings')->list('reading.home_sections')[0] ?? '');
            $section     = $sectionSlug !== '' ? $contentService->sectionBySlug($sectionSlug) : null;
            if ($section !== null) {
                $latest = $contentService->query([
                    'section_id' => (int) $section['id'],
                    'per_page'   => min(9, $perPage),
                    'locale'     => $this->locale,
                ])['items'];
            }
        }

        $popular    = $contentService->popular(5, ['locale' => $this->locale]);
        $blocks     = $this->homeSectionBlocks(4, 4);
        $categories = $contentService->categories(null, true);
        $tags       = $contentService->tags(22);

        $seo = service('seo')->render([
            'url'  => loc_url('', $this->locale),
            'type' => 'website',
            'jsonld' => [[
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => (string) setting('general.site_name', 'DESTRA CMS'),
                'url'      => base_url('/'),
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => search_url('{search_term_string}', $this->locale),
                    'query-input' => 'required name=search_term_string',
                ],
            ]],
        ]);

        service('stats')->recordVisit('/', null, $this->locale);

        return $this->renderPage('home', [
            'seo'          => $seo,
            'bodyClass'    => 'home',
            'sticky'       => $sticky,
            'latest'       => $latest,
            'popular'      => $popular,
            'blocks'       => $blocks,
            'categories'   => $categories,
            'tags'         => $tags,
            'slider'       => $this->ads('home_slider'),
            'headerBanner' => $this->ads('header'),
            'footerBanner' => $this->ads('footer'),
        ]);
    }

    public function dispatch(?string $path = null)
    {
        $segments = $this->segments($path);
        $codes    = service('localization')->codes();
        $default  = service('localization')->defaultCode();

        if ($segments !== [] && in_array($segments[0], $codes, true)) {
            $code = array_shift($segments);

            if ($code === $default) {
                $target = implode('/', $segments);
                $query  = $this->request->getUri()->getQuery();

                return redirect()->to(base_url($target) . ($query !== '' ? '?' . $query : ''), 301);
            }

            $this->setLocale($code);
        }

        if ($segments === []) {
            return $this->home();
        }

        foreach ($segments as $segment) {
            if (preg_match('/[\\\\\x00]/', $segment)) {
                return $this->notFound();
            }
        }

        if ($this->isDownForMaintenance()) {
            return $this->maintenanceResponse();
        }

        $path = implode('/', $segments);

        if ($redirect = $this->handleRedirect($path)) {
            return $redirect;
        }

        if ($path === 'sitemap.xml') {
            return $this->xmlResponse(service('seo')->sitemap($this->locale));
        }

        if ($path === 'robots.txt') {
            return $this->response->setContentType('text/plain')->setBody(service('seo')->robots());
        }

        if ($path === 'feed' || $path === 'rss.xml') {
            return $this->rssResponse(service('seo')->feed($this->locale));
        }

        $first = $segments[0];

        if ($first === 'search') {
            return $this->searchPage();
        }

        if ($first === 'category' && isset($segments[1])) {
            return $this->categoryArchive($segments[1]);
        }

        if ($first === 'tag' && isset($segments[1])) {
            return $this->tagArchive($segments[1]);
        }

        if ($first === 'author' && isset($segments[1])) {
            return $this->authorArchive($segments[1]);
        }

        if ($first === 'p' && isset($segments[1]) && ctype_digit($segments[1])) {
            return $this->contentById((int) $segments[1]);
        }

        if ($first === 's' && isset($segments[1]) && ctype_digit($segments[1])) {
            return $this->sectionById((int) $segments[1]);
        }

        if ($first === 'docs') {
            return isset($segments[1]) ? $this->showDoc($segments[1]) : $this->docsIndex();
        }

        if (count($segments) >= 2) {
            return $this->showContent($segments[0], $segments[1]);
        }

        return $this->resolveSingle($first);
    }

    /**
     * @return list<string>
     */
    private function segments(?string $path): array
    {
        $path = trim((string) ($path ?? $this->request->getPath()), '/');
        if ($path === '') {
            return [];
        }

        $segments = array_map(static fn (string $segment): string => urldecode($segment), explode('/', $path));

        return array_values(array_filter($segments, static fn (string $segment): bool => $segment !== ''));
    }

    private function resolveSingle(string $slug)
    {
        $preview = $this->canPreview();

        $page = service('contentService')->findBySlug($slug, $this->locale, 'page', $preview);
        if ($page !== null) {
            return $this->contentResponse($page);
        }

        $section = service('contentService')->sectionBySlug($slug);
        if ($section !== null) {
            return $this->sectionArchive($section);
        }

        return $this->notFound();
    }

    private function showContent(string $sectionSlug, string $slug)
    {
        $preview = $this->canPreview();
        $service = service('contentService');

        $items = $service->query([
            'slug'         => $slug,
            'section_slug' => $sectionSlug,
            'per_page'     => 1,
            'with_body'    => true,
            'locale'       => $this->locale,
            'preview'      => $preview,
        ])['items'];

        if ($items === [] && in_array($sectionSlug, ['post', 'page', 'doc'], true)) {
            $items = $service->query([
                'slug'      => $slug,
                'type'      => $sectionSlug,
                'per_page'  => 1,
                'with_body' => true,
                'locale'    => $this->locale,
                'preview'   => $preview,
            ])['items'];
        }

        if ($items === []) {
            return $this->notFound();
        }

        return $this->contentResponse($items[0]);
    }

    private function contentById(int $id)
    {
        $content = service('contentService')->findById($id, $this->locale, $this->canPreview());
        if ($content === null) {
            return $this->notFound();
        }

        return $this->contentResponse($content);
    }

    private function sectionById(int $id)
    {
        try {
            $section = db_connect()->table('sections')
                ->where('id', $id)
                ->where('is_active', 1)
                ->where('visibility', 'public')
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            $section = null;
        }

        if ($section === null) {
            return $this->notFound();
        }

        return $this->sectionArchive($section);
    }

    /**
     * @param array<string, mixed> $section
     */
    private function sectionArchive(array $section): string
    {
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $result = service('contentService')->query([
            'section_id' => (int) $section['id'],
            'page'       => $page,
            'locale'     => $this->locale,
        ]);

        service('stats')->recordVisit('/' . $section['slug'], null, $this->locale);

        $seo = service('seo')->render([
            'title'       => (string) $section['name'],
            'description' => (string) ($section['description'] ?? ''),
            'url'         => section_url($section, $this->locale),
            'type'        => 'website',
        ]);

        return $this->renderArchive([
            'heading'    => (string) $section['name'],
            'subheading' => (string) ($section['description'] ?? ''),
            'icon'       => (string) ($section['icon'] ?? ''),
            'section'    => $section,
            'items'      => $result['items'],
            'pagination' => $this->paginationData($result),
            'baseUrl'    => section_url($section, $this->locale),
            'crumbs'     => [['label' => (string) $section['name']]],
            'seo'        => $seo,
        ]);
    }

    private function categoryArchive(string $slug)
    {
        $result = service('contentService')->byCategory($slug, [
            'page'   => max(1, (int) ($this->request->getGet('page') ?? 1)),
            'locale' => $this->locale,
        ]);

        $category = $result['category'] ?? null;
        if ($category === null) {
            return $this->notFound();
        }

        $seo = service('seo')->render([
            'title'       => (string) $category['name'],
            'description' => (string) ($category['description'] ?? ''),
            'url'         => category_url($category, (string) $category['slug'], $this->locale),
            'type'        => 'website',
        ]);

        return $this->renderArchive([
            'heading'    => (string) $category['name'],
            'subheading' => (string) ($category['description'] ?? ''),
            'kicker'     => 'Category',
            'items'      => $result['items'],
            'pagination' => $this->paginationData($result),
            'baseUrl'    => category_url($category, (string) $category['slug'], $this->locale),
            'crumbs'     => [['label' => (string) $category['name']]],
            'seo'        => $seo,
        ]);
    }

    private function tagArchive(string $slug)
    {
        $result = service('contentService')->byTag($slug, [
            'page'   => max(1, (int) ($this->request->getGet('page') ?? 1)),
            'locale' => $this->locale,
        ]);

        $tag = $result['tag'] ?? null;
        if ($tag === null) {
            return $this->notFound();
        }

        $seo = service('seo')->render([
            'title'       => '#' . $tag['name'],
            'description' => 'Articles tagged ' . $tag['name'] . ' on ' . (string) setting('general.site_name', 'DESTRA CMS') . '.',
            'url'         => tag_url((string) $tag['slug'], $this->locale),
            'type'        => 'website',
        ]);

        return $this->renderArchive([
            'heading'    => '#' . $tag['name'],
            'subheading' => '',
            'kicker'     => 'Tag',
            'items'      => $result['items'],
            'pagination' => $this->paginationData($result),
            'baseUrl'    => tag_url((string) $tag['slug'], $this->locale),
            'crumbs'     => [['label' => 'Tag: ' . $tag['name']]],
            'seo'        => $seo,
        ]);
    }

    private function authorArchive(string $username)
    {
        try {
            $author = db_connect()->table('users')
                ->select('id, name, username, avatar, bio')
                ->where('username', $username)
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            $author = null;
        }

        if ($author === null) {
            return $this->notFound();
        }

        $result = service('contentService')->query([
            'author_id' => (int) $author['id'],
            'page'      => max(1, (int) ($this->request->getGet('page') ?? 1)),
            'locale'    => $this->locale,
        ]);

        $seo = service('seo')->render([
            'title'       => (string) $author['name'],
            'description' => (string) ($author['bio'] ?? ''),
            'url'         => author_url($author, $this->locale),
            'type'        => 'website',
        ]);

        return $this->renderArchive([
            'heading'    => (string) $author['name'],
            'subheading' => (string) ($author['bio'] ?? ''),
            'kicker'     => 'Author',
            'avatar'     => avatar_url($author),
            'items'      => $result['items'],
            'pagination' => $this->paginationData($result),
            'baseUrl'    => author_url($author, $this->locale),
            'crumbs'     => [['label' => (string) $author['name']]],
            'seo'        => $seo,
        ]);
    }

    private function searchPage(): string
    {
        $query = trim((string) ($this->request->getGet('q') ?? ''));
        $query = mb_substr(strip_tags($query), 0, 120);
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));

        $result = [
            'items' => [], 'total' => 0, 'page' => 1, 'per_page' => 0, 'pages' => 1,
        ];

        if ($query !== '') {
            $result = service('contentService')->search($query, [
                'page'   => $page,
                'locale' => $this->locale,
            ]);
        }

        $seo = service('seo')->render([
            'title'       => $query !== '' ? 'Search: ' . $query : 'Search',
            'description' => 'Search results on ' . (string) setting('general.site_name', 'DESTRA CMS') . '.',
            'url'         => search_url($query, $this->locale),
            'type'        => 'website',
            'noindex'     => $query === '' || $page > 1,
        ]);

        return $this->renderArchive([
            'heading'    => $query !== '' ? 'Results for “' . $query . '”' : 'Search the site',
            'subheading' => $query !== '' ? ((int) $result['total']) . ' matching stories' : 'Find stories, docs and pages.',
            'kicker'     => 'Search',
            'query'      => $query,
            'items'      => $result['items'],
            'pagination' => $this->paginationData($result, ['q' => $query]),
            'baseUrl'    => search_url('', $this->locale),
            'crumbs'     => [['label' => 'Search']],
            'seo'        => $seo,
            'bodyClass'  => 'search',
        ]);
    }

    private function showDoc(string $slug)
    {
        $service = service('contentService');
        $doc     = $service->findBySlug($slug, $this->locale, 'doc', $this->canPreview());

        if ($doc === null) {
            return $this->notFound();
        }

        $section = $this->docsSection();
        $docs    = $this->docItems($section !== null ? (int) $section['id'] : null);

        $index = array_search((int) $doc['id'], array_column($docs, 'id'), true);
        $prev  = $index !== false && isset($docs[$index - 1]) ? $docs[$index - 1] : null;
        $next  = $index !== false && isset($docs[$index + 1]) ? $docs[$index + 1] : null;

        $crumbs = [['label' => 'Docs', 'url' => loc_url('docs', $this->locale)], ['label' => (string) $doc['title']]];

        $alternates = $service->alternates($doc);
        $seo = service('seo')->render($alternates !== []
            ? service('seo')->contentContext($doc, $doc['translation'] ?? [], $doc['author'], $alternates)
            : [
                'title'       => (string) $doc['title'],
                'description' => (string) $doc['excerpt'],
                'image'       => $doc['featured_image_id'] ?: null,
                'type'        => 'article',
                'url'         => $doc['url'],
                'published_at' => (string) $doc['published_at'],
                'modified_at'  => (string) $doc['updated_at'],
                'author'       => (string) ($doc['author']['name'] ?? ''),
            ]);

        service('stats')->recordVisit('/docs/' . $slug, (int) $doc['id'], $this->locale);

        return $this->renderPage('content', [
            'seo'        => $seo,
            'bodyClass'  => 'docs-single',
            'content'    => $doc,
            'isPage'     => false,
            'isDoc'      => true,
            'crumbs'     => $crumbs,
            'docs'       => $docs,
            'docsTree'   => $this->docTree($docs),
            'section'    => $section,
            'prevDoc'    => $prev,
            'nextDoc'    => $next,
            'related'    => [],
            'comments'   => [],
            'commentsOpen' => false,
            'share'      => share_links($doc, $doc['translation'] ?? []),
        ]);
    }

    private function docsIndex(): string
    {
        $section = $this->docsSection();
        if ($section === null) {
            return $this->notFound();
        }

        $docs   = $this->docItems((int) $section['id']);
        $first  = $docs[0] ?? null;

        $seo = service('seo')->render([
            'title'       => (string) $section['name'],
            'description' => (string) ($section['description'] ?? ''),
            'url'         => section_url($section, $this->locale),
            'type'        => 'website',
        ]);

        service('stats')->recordVisit('/docs', null, $this->locale);

        return $this->renderPage('docs', [
            'seo'       => $seo,
            'bodyClass' => 'docs-index',
            'section'   => $section,
            'docs'      => $docs,
            'docsTree'  => $this->docTree($docs),
            'activeDoc' => $first,
        ]);
    }

    private function docsSection(): ?array
    {
        $section = service('contentService')->sectionBySlug('docs');
        if ($section !== null) {
            return $section;
        }

        try {
            $row = db_connect()->table('sections')
                ->where('type', 'doc')
                ->where('is_active', 1)
                ->where('visibility', 'public')
                ->orderBy('sort_order', 'ASC')
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            return null;
        }

        return $row ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function docItems(?int $sectionId): array
    {
        $filters = [
            'type'     => 'doc',
            'per_page' => 100,
            'locale'   => $this->locale,
        ];
        if ($sectionId !== null) {
            $filters['section_id'] = $sectionId;
        }

        $items = service('contentService')->query($filters)['items'];

        usort($items, static function (array $a, array $b): int {
            $order = ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));

            return $order !== 0 ? $order : strcasecmp((string) $a['title'], (string) $b['title']);
        });

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function docTree(array $items): array
    {
        $nodes = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $nodes[(int) $item['id']] = $item;
        }

        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parent = (int) ($node['parent_id'] ?? 0);
            if ($parent > 0 && isset($nodes[$parent])) {
                $nodes[$parent]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }

    /**
     * @param array<string, mixed> $content
     */
    private function contentResponse(array $content): string
    {
        $id      = (int) $content['id'];
        $service = service('contentService');
        $isPage  = $content['type'] === 'page';

        $crumbs = [];
        if (! $isPage && (string) $content['section_name'] !== '') {
            $crumbs[] = [
                'label' => (string) $content['section_name'],
                'url'   => (string) ($content['section_slug'] !== ''
                    ? section_url(['id' => (int) $content['section_id'], 'slug' => (string) $content['section_slug']], $this->locale)
                    : ''),
            ];
        }
        $crumbs[] = ['label' => (string) $content['title']];

        $alternates = $service->alternates($content);
        $seo        = service('seo')->contentContext($content, $content['translation'] ?? [], $content['author'], $alternates);

        if ((string) ($content['seo_description'] ?? '') !== '') {
            $seo['description'] = (string) $content['seo_description'];
        }
        if ((string) ($content['seo_keywords'] ?? '') !== '') {
            $seo['keywords'] = (string) $content['seo_keywords'];
        }
        if ((string) ($content['canonical_url'] ?? '') !== '') {
            $seo['canonical'] = (string) $content['canonical_url'];
        }

        $commentsEnabled = service('settings')->bool('comments.enabled', true)
            && (int) $content['allow_comments'] === 1
            && ! $isPage;

        $commentUser = auth_user();

        $data = [
            'seo'            => service('seo')->render($seo),
            'bodyClass'      => $isPage ? 'page-single' : 'single',
            'content'        => $content,
            'isPage'         => $isPage,
            'isDoc'          => $content['type'] === 'doc',
            'crumbs'         => $crumbs,
            'related'        => $isPage ? [] : $service->related($id, 3, $content),
            'comments'       => $commentsEnabled ? $this->commentsFor($id) : [],
            'commentCount'   => $commentsEnabled ? $this->commentCount($id) : 0,
            'commentsOpen'   => $commentsEnabled,
            'commentCaptcha' => $commentsEnabled ? service('captcha')->widget('comments') : '',
            'commentUser'    => $commentUser,
            'commentRequiresLogin' => service('settings')->bool('comments.require_login', false),
            'commentGuestsAllowed' => service('settings')->bool('comments.allow_guests', true),
            'commentMaxLength'     => max(200, setting_int('comments.max_length', 3000)),
            'share'          => share_links($content, $content['translation'] ?? []),
            'inlineAds'      => $this->ads('inline'),
            'form'           => $isPage ? $this->pageForm($content) : null,
            'orderForm'      => (int) ($content['order_form'] ?? 0) === 1,
            'sidebar'        => [
                'popular'    => $service->popular(5, ['locale' => $this->locale]),
                'categories' => $service->categories(null, true),
                'tags'       => $service->tags(18),
                'ads'        => $this->ads('sidebar'),
            ],
        ];

        $view = $isPage ? 'page' : 'content';

        service('stats')->recordVisit($content['url'] !== '' ? parse_url($content['url'], PHP_URL_PATH) ?: '/' : '/', $id, $this->locale);

        return $this->renderPage($view, $data);
    }

    /**
     * Contact/quote form attached to a page, if one exists.
     *
     * @param array<string, mixed> $content
     * @return array<string, mixed>|null
     */
    private function pageForm(array $content): ?array
    {
        try {
            $db   = db_connect();
            $form = $db->table('forms')
                ->groupStart()
                    ->where('slug', (string) $content['slug'])
                    ->orWhere('slug', 'contact')
                ->groupEnd()
                ->where('is_active', 1)
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            return null;
        }

        if (! $form) {
            return null;
        }

        $decoded      = json_decode((string) ($form['fields'] ?? ''), true);
        $form['fields'] = is_array($decoded) ? $decoded : [];

        if ($form['fields'] === []) {
            return null;
        }

        return $form;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function commentsFor(int $contentId): array
    {
        try {
            $rows = db_connect()->table('comments')
                ->select('id, parent_id, author_name, body, created_at')
                ->where('content_id', $contentId)
                ->where('status', 'approved')
                ->where('deleted_at', null)
                ->orderBy('created_at', 'ASC')
                ->limit(200)
                ->get()
                ->getResultArray();
        } catch (Throwable) {
            return [];
        }

        $nodes = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $nodes[(int) $row['id']] = $row;
        }

        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parent = (int) ($node['parent_id'] ?? 0);
            if ($parent > 0 && isset($nodes[$parent])) {
                $nodes[$parent]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }

    private function commentCount(int $contentId): int
    {
        try {
            return (int) db_connect()->table('comments')
                ->where('content_id', $contentId)
                ->where('status', 'approved')
                ->where('deleted_at', null)
                ->countAllResults();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function paginationData(array $result, array $query = []): array
    {
        return [
            'page'  => (int) ($result['page'] ?? 1),
            'pages' => (int) ($result['pages'] ?? 1),
            'total' => (int) ($result['total'] ?? 0),
            'query' => $query,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderArchive(array $data): string
    {
        $data += [
            'bodyClass'  => 'archive',
            'kicker'     => '',
            'subheading' => '',
            'query'      => '',
        ];

        if (! isset($data['sidebar'])) {
            $service = service('contentService');
            $data['sidebar'] = [
                'popular'    => $service->popular(5, ['locale' => $this->locale]),
                'categories' => $service->categories(null, true),
                'tags'       => $service->tags(18),
                'ads'        => $this->ads('sidebar'),
            ];
        }

        return $this->renderPage('archive', $data);
    }

    /**
     * @return list<array{section: array<string, mixed>, items: list<array<string, mixed>>}>
     */
    private function homeSectionBlocks(int $perBlock = 4, int $max = 4): array
    {
        $wanted = service('settings')->list('reading.home_sections');

        try {
            $sections = db_connect()->table('sections')
                ->where('is_active', 1)
                ->where('visibility', 'public')
                ->where('type !=', 'page')
                ->orderBy('sort_order', 'ASC')
                ->get()
                ->getResultArray();
        } catch (Throwable) {
            return [];
        }

        if ($wanted !== []) {
            $ordered = [];
            foreach ($wanted as $key) {
                foreach ($sections as $section) {
                    if ((string) $section['slug'] === $key || (string) $section['id'] === $key) {
                        $ordered[] = $section;
                        break;
                    }
                }
            }
            $sections = $ordered;
        }

        $sections = array_slice($sections, 0, max(1, $max));
        $service  = service('contentService');
        $blocks   = [];

        foreach ($sections as $section) {
            $items = $service->query([
                'section_id' => (int) $section['id'],
                'per_page'   => max(1, $perBlock),
                'locale'     => $this->locale,
            ])['items'];

            if ($items === []) {
                continue;
            }

            $section['url'] = section_url($section, $this->locale);
            $blocks[]       = ['section' => $section, 'items' => $items];
        }

        return $blocks;
    }

    private function canPreview(): bool
    {
        return $this->request->getGet('preview') === '1'
            && auth_check()
            && (user_can('content.edit') || user_can('content.view'));
    }

    private function xmlResponse(string $body)
    {
        return $this->response
            ->setContentType('application/xml; charset=UTF-8')
            ->setBody($body);
    }

    private function rssResponse(string $body)
    {
        return $this->response
            ->setContentType('application/rss+xml; charset=UTF-8')
            ->setBody($body);
    }

    private function notFound(): string
    {
        $this->response->setStatusCode(404);

        $seo = service('seo')->render([
            'title'   => 'Page not found',
            'url'     => current_url(),
            'noindex' => true,
        ]);

        return $this->renderPage('404', [
            'seo'       => $seo,
            'bodyClass' => 'error-page',
            'heading'   => 'We could not find that page',
            'message'   => 'The link may be broken or the story may have moved. Try a search instead.',
            'crumbs'    => [['label' => 'Not found']],
        ]);
    }
}
