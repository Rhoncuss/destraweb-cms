<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

class V1 extends BaseApiController
{
    public function preflight(): ResponseInterface
    {
        return $this->response->setStatusCode(204)->setHeader('Content-Length', '0');
    }

    public function index(): ResponseInterface
    {
        return $this->ok([
            'name'      => 'DESTRA CMS REST API',
            'version'   => 'v1',
            'status'    => 'ok',
            'locale'    => default_locale(),
            'endpoints' => [
                'content'    => base_url('api/v1/content'),
                'posts'      => base_url('api/v1/posts'),
                'pages'      => base_url('api/v1/pages'),
                'categories' => base_url('api/v1/categories'),
                'tags'       => base_url('api/v1/tags'),
                'sections'   => base_url('api/v1/sections'),
                'languages'  => base_url('api/v1/languages'),
                'media'      => base_url('api/v1/media'),
                'comments'   => base_url('api/v1/comments'),
                'orders'     => base_url('api/v1/orders'),
                'subscribers'=> base_url('api/v1/subscribers'),
                'stats'      => base_url('api/v1/stats'),
                'openapi'    => base_url('api/v1/openapi.json'),
            ],
        ]);
    }

    public function openapi(): ResponseInterface
    {
        if (! service('settings')->bool('api.docs_public', false)) {
            $result = (new \App\Filters\ApiAuth())->before($this->request);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->response->setJSON($this->spec())->setHeader('Cache-Control', 'public, max-age=3600');
    }

    /**
     * @return array<string, mixed>
     */
    private function spec(): array
    {
        $base = rtrim(base_url(), '/');
        $bearer = [['bearerAuth' => []]];
        $page = [
            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
            ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20]],
        ];
        $lang = ['name' => 'lang', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Language code, e.g. en or id.'];
        $ok = fn (string $ref) => ['200' => ['description' => 'Successful response', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/' . $ref]]]]];

        $paths = [
            '/content' => [
                'get' => [
                    'summary'  => 'List content',
                    'tags'     => ['Content'],
                    'security' => $bearer,
                    'parameters' => array_merge($page, [
                        $lang,
                        ['name' => 'type', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['post', 'page', 'doc']]],
                        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['published', 'draft', 'private']], 'description' => 'Defaults to published. Requires content:write for other values.'],
                        ['name' => 'section', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'category', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'tag', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'q', 'in' => 'query', 'schema' => ['type' => 'string']],
                    ]),
                    'responses' => $ok('List'),
                ],
                'post' => [
                    'summary'  => 'Create content',
                    'tags'     => ['Content'],
                    'security' => $bearer,
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ContentInput']]]],
                    'responses' => ['201' => ['description' => 'Created'], '422' => ['description' => 'Validation failed']],
                ],
            ],
            '/content/{id}' => [
                'get' => [
                    'summary'  => 'Get content',
                    'tags'     => ['Content'],
                    'security' => $bearer,
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']], $lang],
                    'responses' => $ok('Content'),
                ],
                'patch' => [
                    'summary'  => 'Update content',
                    'tags'     => ['Content'],
                    'security' => $bearer,
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ContentInput']]]],
                    'responses' => ['200' => ['description' => 'Updated']],
                ],
                'delete' => [
                    'summary'  => 'Move content to trash',
                    'tags'     => ['Content'],
                    'security' => $bearer,
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Deleted']],
                ],
            ],
            '/posts' => ['get' => ['summary' => 'List posts', 'tags' => ['Content'], 'security' => $bearer, 'parameters' => array_merge($page, [$lang]), 'responses' => $ok('List')]],
            '/pages' => ['get' => ['summary' => 'List pages', 'tags' => ['Content'], 'security' => $bearer, 'parameters' => array_merge($page, [$lang]), 'responses' => $ok('List')]],
            '/docs' => ['get' => ['summary' => 'List documentation pages', 'tags' => ['Content'], 'security' => $bearer, 'parameters' => array_merge($page, [$lang]), 'responses' => $ok('List')]],
            '/categories' => ['get' => ['summary' => 'List categories', 'tags' => ['Taxonomy'], 'security' => $bearer, 'parameters' => array_merge($page, [$lang]), 'responses' => $ok('List')]],
            '/tags' => ['get' => ['summary' => 'List tags', 'tags' => ['Taxonomy'], 'security' => $bearer, 'parameters' => array_merge($page, [$lang]), 'responses' => $ok('List')]],
            '/sections' => ['get' => ['summary' => 'List sections', 'tags' => ['Taxonomy'], 'security' => $bearer, 'responses' => $ok('List')]],
            '/languages' => ['get' => ['summary' => 'List languages', 'tags' => ['Taxonomy'], 'security' => $bearer, 'responses' => $ok('List')]],
            '/media' => [
                'get' => ['summary' => 'List media', 'tags' => ['Media'], 'security' => $bearer, 'parameters' => $page, 'responses' => $ok('List')],
                'post' => [
                    'summary'  => 'Upload media',
                    'tags'     => ['Media'],
                    'security' => $bearer,
                    'requestBody' => ['required' => true, 'content' => ['multipart/form-data' => ['schema' => ['type' => 'object', 'properties' => ['file' => ['type' => 'string', 'format' => 'binary'], 'files' => ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'binary']], 'folder_id' => ['type' => 'integer']]]]]],
                    'responses' => ['201' => ['description' => 'Uploaded']],
                ],
            ],
            '/comments' => [
                'get' => ['summary' => 'List comments', 'tags' => ['Community'], 'security' => $bearer, 'parameters' => array_merge($page, [['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['pending', 'approved', 'spam', 'trash']]]]), 'responses' => $ok('List')],
            ],
            '/comments/{id}/moderate' => [
                'post' => ['summary' => 'Moderate a comment', 'tags' => ['Community'], 'security' => $bearer, 'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]], 'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['status'], 'properties' => ['status' => ['type' => 'string', 'enum' => ['pending', 'approved', 'spam', 'trash']]]]]]], 'responses' => ['200' => ['description' => 'Updated']]],
            ],
            '/orders' => ['get' => ['summary' => 'List order requests', 'tags' => ['Community'], 'security' => $bearer, 'parameters' => $page, 'responses' => $ok('List')]],
            '/subscribers' => ['get' => ['summary' => 'List newsletter subscribers', 'tags' => ['Community'], 'security' => $bearer, 'parameters' => array_merge($page, [['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['pending', 'active', 'unsubscribed']]]]), 'responses' => $ok('List')]],
            '/stats' => ['get' => ['summary' => 'Site statistics', 'tags' => ['Insights'], 'security' => $bearer, 'parameters' => [['name' => 'days', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 90, 'default' => 30]]], 'responses' => $ok('Stats')]],
        ];

        return [
            'openapi' => '3.0.3',
            'info'    => [
                'title'       => 'DESTRA CMS API',
                'version'     => '1.0.0',
                'description' => 'Token-authenticated REST API for reading and writing DESTRA CMS content. Create tokens in Admin → API tokens.',
            ],
            'servers'   => [['url' => $base . '/api/v1']],
            'security'  => $bearer,
            'tags'      => [['name' => 'Content'], ['name' => 'Taxonomy'], ['name' => 'Media'], ['name' => 'Community'], ['name' => 'Insights']],
            'paths'     => $paths,
            'components'=> [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'dst_*'],
                ],
                'schemas' => [
                    'List' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'data'    => ['type' => 'array', 'items' => ['type' => 'object']],
                            'meta'    => ['type' => 'object', 'properties' => ['page' => ['type' => 'integer'], 'per_page' => ['type' => 'integer'], 'total' => ['type' => 'integer'], 'pages' => ['type' => 'integer']]],
                        ],
                    ],
                    'Content' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'data'    => ['$ref' => '#/components/schemas/ContentItem'],
                        ],
                    ],
                    'ContentItem' => [
                        'type' => 'object',
                        'properties' => [
                            'id'         => ['type' => 'integer'],
                            'type'       => ['type' => 'string'],
                            'status'     => ['type' => 'string'],
                            'title'      => ['type' => 'string'],
                            'slug'       => ['type' => 'string'],
                            'excerpt'    => ['type' => 'string', 'nullable' => true],
                            'body'       => ['type' => 'string'],
                            'url'        => ['type' => 'string', 'format' => 'uri'],
                            'published_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                            'updated_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                        ],
                    ],
                    'ContentInput' => [
                        'type' => 'object',
                        'required' => ['title'],
                        'properties' => [
                            'title'    => ['type' => 'string', 'maxLength' => 255],
                            'slug'     => ['type' => 'string'],
                            'body'     => ['type' => 'string', 'description' => 'Allow-listed HTML.'],
                            'excerpt'  => ['type' => 'string'],
                            'type'     => ['type' => 'string', 'enum' => ['post', 'page', 'doc']],
                            'status'   => ['type' => 'string', 'enum' => ['draft', 'published', 'private']],
                            'section_id' => ['type' => 'integer'],
                            'featured_image_id' => ['type' => 'integer'],
                            'seo_title' => ['type' => 'string'],
                            'seo_description' => ['type' => 'string'],
                            'seo_keywords' => ['type' => 'string'],
                            'categories' => ['type' => 'array', 'items' => ['type' => 'integer']],
                            'tags'     => ['type' => 'array', 'items' => ['type' => 'string']],
                            'translations' => ['type' => 'object', 'additionalProperties' => ['type' => 'object']],
                        ],
                    ],
                    'Stats' => ['type' => 'object'],
                ],
            ],
        ];
    }
}
