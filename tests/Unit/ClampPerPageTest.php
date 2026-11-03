<?php

namespace Tests\Unit;

use App\Http\Middleware\ClampPerPage;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ClampPerPageTest extends TestCase
{
    public function test_large_query_page_sizes_are_capped(): void
    {
        $request = Request::create('/?per_page=100000', 'GET');

        (new ClampPerPage)->handle($request, fn () => new Response);

        $this->assertSame(100, $request->query('per_page'));
    }

    public function test_invalid_and_non_positive_page_sizes_are_normalized(): void
    {
        $middleware = new ClampPerPage;

        $invalid = Request::create('/?per_page=all', 'GET');
        $middleware->handle($invalid, fn () => new Response);
        $this->assertSame(20, $invalid->query('per_page'));

        $nonPositive = Request::create('/?per_page=0', 'GET');
        $middleware->handle($nonPositive, fn () => new Response);
        $this->assertSame(1, $nonPositive->query('per_page'));

        $arrayValue = Request::create('/?per_page%5B%5D=20', 'GET');
        $middleware->handle($arrayValue, fn () => new Response);
        $this->assertSame(20, $arrayValue->query('per_page'));
    }

    public function test_post_page_sizes_are_capped(): void
    {
        $request = Request::create('/', 'POST', ['per_page' => 250]);

        (new ClampPerPage)->handle($request, fn () => new Response);

        $this->assertSame(100, $request->input('per_page'));
    }

    public function test_json_page_sizes_are_capped(): void
    {
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['per_page' => 250])
        );

        (new ClampPerPage)->handle($request, fn () => new Response);

        $this->assertSame(100, $request->input('per_page'));
    }
}
