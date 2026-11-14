<?php

namespace App\Ai\Support;

use App\Models\User;
use App\Support\Navigation;
use Illuminate\Support\Facades\Route;

class AssistantRouteRegistry
{
    /** @var array<string, list<array{label: string, path: string, url: string, route: string|null}>> */
    private array $cache = [];

    /**
     * @return list<array{label: string, path: string, url: string, route: string|null}>
     */
    public function linksForUser(?User $user): array
    {
        $key = $user?->getKey() ?? 'service';

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $links = [];
        $navigation = Navigation::forUser($user);

        foreach ($navigation['areas'] ?? [] as $area) {
            foreach ($area['groups'] ?? [] as $group) {
                foreach ($group['items'] ?? [] as $item) {
                    $url = (string) ($item['url'] ?? '');
                    $path = $this->path($url);

                    if ($path === null || ! Route::has((string) ($item['route'] ?? ''))) {
                        continue;
                    }

                    $links[] = [
                        'label' => (string) ($item['label'] ?? ''),
                        'path' => $path,
                        'url' => $url,
                        'route' => (string) ($item['route'] ?? ''),
                    ];
                }
            }
        }

        if (Route::has('ai-assistant.index')) {
            $url = route('ai-assistant.index');
            $links[] = [
                'label' => 'Asisten VEE',
                'path' => (string) $this->path($url),
                'url' => $url,
                'route' => 'ai-assistant.index',
            ];
        }

        $unique = [];

        foreach ($links as $link) {
            $unique[$link['path']] ??= $link;
        }

        return $this->cache[$key] = array_values($unique);
    }

    /**
     * @return list<array{label: string, path: string, url: string, route: string|null}>
     */
    public function linksForSearch(string $search, ?User $user): array
    {
        $search = strtolower(trim($search));

        return array_values(array_filter(
            $this->linksForUser($user),
            function (array $link) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str_contains(strtolower($link['label']), $search)
                    || str_contains(strtolower($link['path']), $search);
            }
        ));
    }

    public function isAllowed(string $url, ?User $user): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with(strtolower($url), 'mailto:') || str_starts_with(strtolower($url), 'tel:')) {
            return false;
        }

        $candidate = $this->path($url);

        if ($candidate === null || $this->isPlaceholder($url)) {
            return false;
        }

        foreach ($this->linksForUser($user) as $link) {
            if ($link['path'] === $candidate) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{answer: string, removed: int, links: list<string>}
     */
    public function sanitize(string $answer, ?User $user): array
    {
        $removed = 0;
        $links = [];

        $answer = preg_replace_callback(
            '/\[([^\]]+)\]\((?:<)?([^)\s>]+)(?:>)?\)/u',
            function (array $match) use ($user, &$removed, &$links): string {
                $label = $match[1];
                $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5);

                if ($this->isAllowed($url, $user)) {
                    $links[] = $url;

                    return $match[0];
                }

                $removed++;

                return $label;
            },
            $answer
        ) ?? $answer;

        $answer = preg_replace_callback(
            '/(?<!\]\()(?<!=\")https?:\/\/[^\s<>()]+/iu',
            function (array $match) use ($user, &$removed, &$links): string {
                $url = rtrim($match[0], '.,;:!?');
                $suffix = substr($match[0], strlen($url));

                if ($this->isAllowed($url, $user)) {
                    $links[] = $url;

                    return $match[0];
                }

                $removed++;

                return '[tautan tidak tersedia]'.$suffix;
            },
            $answer
        ) ?? $answer;

        $answer = preg_replace_callback(
            '/(?<![\w\/])contoh\/[^\s)]*/iu',
            function (array $match) use (&$removed): string {
                $removed++;

                return '[tautan tidak tersedia]';
            },
            $answer
        ) ?? $answer;

        return [
            'answer' => $answer,
            'removed' => $removed,
            'links' => array_values(array_unique($links)),
        ];
    }

    private function path(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;

        if ($host !== null) {
            $allowedHosts = array_filter([
                request()->getHost(),
                parse_url((string) config('app.url'), PHP_URL_HOST),
            ]);

            if (! in_array($host, $allowedHosts, true)) {
                return null;
            }
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');

        if ($path === '' || ! str_starts_with($path, '/')) {
            return null;
        }

        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    private function isPlaceholder(string $url): bool
    {
        $normalized = strtolower($url);

        foreach ((array) config('assistant.route_registry.placeholder_patterns', []) as $pattern) {
            if ($pattern !== '' && str_contains($normalized, strtolower((string) $pattern))) {
                return true;
            }
        }

        return str_contains($normalized, '...');
    }
}
