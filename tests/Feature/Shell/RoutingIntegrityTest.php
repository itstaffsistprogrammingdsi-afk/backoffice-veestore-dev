<?php

namespace Tests\Feature\Shell;

use App\Support\Navigation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class RoutingIntegrityTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_routes_do_not_have_duplicate_names_or_missing_actions(): void
    {
        $names = [];
        $signatures = [];
        $broken = [];
        $duplicates = [];
        $duplicateSignatures = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (is_string($name) && $name !== '') {
                if (isset($names[$name])) {
                    $duplicates[] = $name;
                }

                $names[$name] = true;
            }

            $signature = implode('|', $route->methods()).' '.$route->uri();

            if (isset($signatures[$signature])) {
                $duplicateSignatures[] = $signature;
            }

            $signatures[$signature] = true;

            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action, 2);

            if (! class_exists($class) || ! method_exists($class, $method)) {
                $broken[] = "{$signature} -> {$action}";
            }
        }

        $this->assertSame([], $broken, 'Route menunjuk controller/method yang tidak ada.');
        $this->assertSame([], $duplicates, 'Nama route duplikat.');
        $this->assertSame([], $duplicateSignatures, 'Kombinasi method + URI duplikat.');
    }

    public function test_menu_json_has_no_duplicate_urls_or_slugs(): void
    {
        $payload = json_decode(File::get(resource_path('menu/verticalMenu.json')), true);
        $urls = [];
        $slugs = [];
        $duplicateUrls = [];
        $duplicateSlugs = [];

        $collect = function (array $items) use (&$collect, &$urls, &$slugs, &$duplicateUrls, &$duplicateSlugs): void {
            foreach ($items as $item) {
                $url = trim((string) ($item['url'] ?? ''));
                $slug = trim((string) ($item['slug'] ?? ''));

                if ($url !== '') {
                    if (isset($urls[$url])) {
                        $duplicateUrls[] = $url;
                    }

                    $urls[$url] = true;
                }

                if ($slug !== '') {
                    if (isset($slugs[$slug])) {
                        $duplicateSlugs[] = $slug;
                    }

                    $slugs[$slug] = true;
                }

                if (is_array($item['submenu'] ?? null)) {
                    $collect($item['submenu']);
                }
            }
        };

        $collect($payload['menu'] ?? []);

        $this->assertSame([], $duplicateUrls, 'URL menu duplikat.');
        $this->assertSame([], $duplicateSlugs, 'Slug menu duplikat.');
    }

    public function test_sidebar_navigation_has_no_duplicate_items(): void
    {
        $navigation = Navigation::forUser($this->admin());
        $routes = [];

        foreach ($navigation['areas'] as $area) {
            foreach ($area['groups'] as $group) {
                foreach ($group['items'] as $item) {
                    $route = $item['route'];

                    $this->assertArrayNotHasKey($route, $routes, "Menu duplikat: {$route}");
                    $routes[$route] = true;
                }
            }
        }
    }

    public function test_reports_area_opens_business_overview_first(): void
    {
        $navigation = Navigation::forUser($this->admin());
        $reports = collect($navigation['areas'])->firstWhere('key', 'reports');

        $this->assertNotNull($reports);
        $this->assertSame(route('reports.business-overview.index'), $reports['url']);
    }
}
