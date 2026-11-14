<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use App\Ai\Security\AssistantAccessPolicy;
use App\Ai\Support\AssistantRouteRegistry;
use App\Support\Navigation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class SystemMapTool extends AssistantTool
{
    public function name(): string
    {
        return 'system_map';
    }

    public function description(): string
    {
        return 'Peta aplikasi VeeStore: area menu, grup, fitur, route utama, domain data, dan contoh pertanyaan. Gunakan saat pengguna bertanya letak fitur, cakupan menu, atau data apa yang tersedia.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'search' => [
                    'type' => 'string',
                    'description' => 'Kata kunci nama menu, fitur, route, atau tabel. Kosongkan untuk semua.',
                ],
                'area' => [
                    'type' => 'string',
                    'enum' => ['all', 'operations', 'reports'],
                    'default' => 'all',
                ],
                'include_routes' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Sertakan daftar route GET yang dapat dibuka.',
                ],
            ],
        ];
    }

    protected function execute(array $arguments): array
    {
        $search = strtolower(trim((string) ($arguments['search'] ?? '')));
        $requestedArea = $arguments['area'] ?? 'all';
        $area = in_array($requestedArea, ['all', 'operations', 'reports'], true) ? $requestedArea : 'all';
        $includeRoutes = filter_var($arguments['include_routes'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $user = auth()->user();
        $registry = app(AssistantRouteRegistry::class);
        $navigation = Navigation::forUser($user);
        $areas = collect($navigation['areas'])
            ->when($area !== 'all', fn ($items) => $items->where('key', $area))
            ->map(function (array $node) use ($search, $registry, $user) {
                $groups = collect($node['groups'])
                    ->map(function (array $group) use ($search, $registry, $user) {
                        $items = collect($group['items'])
                            ->filter(fn (array $item) => $this->matches($search, [
                                $item['label'],
                                $item['route'],
                                $item['url'],
                            ]))
                            ->map(fn (array $item) => [
                                ...$item,
                                'link_available' => is_string($item['url'])
                                    && $item['url'] !== ''
                                    && $registry->isAllowed($item['url'], $user),
                            ])
                            ->values()
                            ->all();

                        return [
                            'label' => $group['label'],
                            'icon' => $group['icon'],
                            'items' => $items,
                        ];
                    })
                    ->filter(fn (array $group) => $group['items'] !== [])
                    ->values()
                    ->all();

                return [
                    'key' => $node['key'],
                    'label' => $node['label'],
                    'url' => $node['url'],
                    'groups' => $groups,
                ];
            })
            ->filter(fn (array $node) => $node['groups'] !== [])
            ->values()
            ->all();

        $policy = app(AssistantAccessPolicy::class);

        $features = collect(config('assistant.features', []))
            ->reject(fn (array $feature): bool => $feature['key'] === 'management' && ! $policy->canViewSchema($user))
            ->filter(fn (array $feature) => $this->matches($search, [
                $feature['label'],
                $feature['menu'],
                $feature['description'],
                $feature['key'],
            ]))
            ->map(fn (array $feature) => [
                'key' => $feature['key'],
                'label' => $feature['label'],
                'menu' => $feature['menu'],
                'description' => $feature['description'],
                'questions' => $feature['questions'] ?? [],
            ])
            ->values()
            ->all();

        return [
            'application' => [
                'name' => config('app.name'),
                'timezone' => config('app.timezone'),
                'date' => now()->toDateString(),
                'assistant' => config('assistant.name', 'VEE'),
            ],
            'navigation' => $areas,
            'menu' => $this->menu($search, $user),
            'features' => $features,
            'links' => $registry->linksForSearch($search, $user),
            'routes' => $includeRoutes && $policy->canViewSchema($user)
                ? $this->routes()
                : null,
            'hint' => 'Gunakan hanya field links dengan link_available true atau link dari navigation. Jangan membuat URL sendiri. Gunakan business_overview, product_sales, stock_health, dan tool domain lain untuk data.',
        ];
    }

    /**
     * @return list<array{name: string, path: string, slug: string, url: string, link_available: bool}>
     */
    private function menu(string $search, mixed $user): array
    {
        $path = resource_path('menu/verticalMenu.json');

        if (! File::exists($path)) {
            return [];
        }

        $payload = json_decode(File::get($path), true);
        $registry = app(AssistantRouteRegistry::class);
        $items = [];

        $collect = function (array $nodes, array $trail = []) use (&$collect, &$items, $search, $registry, $user): void {
            foreach ($nodes as $node) {
                $name = isset($node['name']) ? (string) $node['name'] : '';
                $breadcrumb = $name !== '' ? [...$trail, $name] : $trail;

                if (isset($node['name'], $node['slug']) && $this->matches($search, [
                    $node['name'],
                    $node['slug'],
                    $node['url'] ?? null,
                    implode(' > ', $breadcrumb),
                ])) {
                    $url = $this->normalizeUrl($node['url'] ?? null);
                    $available = $url !== '' && $registry->isAllowed($url, $user);

                    $items[] = [
                        'name' => $node['name'],
                        'path' => implode(' > ', $breadcrumb),
                        'slug' => $node['slug'],
                        'url' => $available ? $url : '',
                        'link_available' => $available,
                    ];
                }

                if (! empty($node['submenu']) && is_array($node['submenu'])) {
                    $collect($node['submenu'], $breadcrumb);
                }
            }
        };

        $collect($payload['menu'] ?? []);

        return $items;
    }

    private function normalizeUrl(mixed $url): string
    {
        $url = trim((string) ($url ?? ''));

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
            return $url;
        }

        return '/'.$url;
    }

    /**
     * @param  array<int, string|null>  $haystacks
     */
    private function matches(string $search, array $haystacks): bool
    {
        if ($search === '') {
            return true;
        }

        foreach ($haystacks as $value) {
            if ($value !== null && str_contains(strtolower($value), $search)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{method: string, uri: string, name: string|null, action: string}>
     */
    private function routes(): array
    {
        $skip = ['login', 'logout', 'register', 'api/', '_ignition', 'sanctum', 'storage/', 'up'];

        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods(), true))
            ->filter(function ($route) use ($skip) {
                $uri = $route->uri();

                if (! $route->getName()) {
                    return false;
                }

                foreach ($skip as $needle) {
                    if (str_contains($uri, $needle)) {
                        return false;
                    }
                }

                return true;
            })
            ->map(fn ($route) => [
                'method' => 'GET',
                'uri' => '/'.$route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ])
            ->sortBy('uri')
            ->take(250)
            ->values()
            ->all();
    }
}
