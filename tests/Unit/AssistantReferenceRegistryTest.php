<?php

namespace Tests\Unit;

use App\Ai\Support\AssistantReferenceRegistry;
use Tests\TestCase;

class AssistantReferenceRegistryTest extends TestCase
{
    public function test_stock_reference_points_to_stock_page(): void
    {
        $links = app(AssistantReferenceRegistry::class)->forTool('stock_health', ['scope' => 'store']);

        $this->assertNotEmpty($links);
        $this->assertSame(route('monitoring.stok-percabang'), $links[0]['url']);
    }

    public function test_master_data_customer_reference_is_clickable(): void
    {
        $links = app(AssistantReferenceRegistry::class)->forTool('master_data', ['entity' => 'customers']);

        $this->assertNotEmpty($links);
        $this->assertSame(route('operations.customers.index'), $links[0]['url']);
    }

    public function test_query_data_pos_reference_points_to_ecommerce_page(): void
    {
        $links = app(AssistantReferenceRegistry::class)->forTool('query_data', ['connection' => 'pos']);

        $this->assertNotEmpty($links);
        $this->assertSame(route('ecommerce.rejections.index'), $links[0]['url']);
    }
}
