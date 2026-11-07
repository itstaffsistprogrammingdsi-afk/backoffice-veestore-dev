<?php

namespace Tests\Unit;

use App\Domain\Analytics\Support\FinancialBreakdown;
use PHPUnit\Framework\TestCase;

class FinancialBreakdownTest extends TestCase
{
    public function test_it_builds_a_reconciled_financial_bridge(): void
    {
        $result = FinancialBreakdown::make(
            grossSales: 1000000,
            productDiscount: 100000,
            voucherDiscount: 50000,
            netSales: 850000,
            basePrice: 600000,
            transactions: 4,
            qty: 10
        );

        $this->assertSame(1000000.0, $result['gross_sales']);
        $this->assertSame(100000.0, $result['product_discount']);
        $this->assertSame(900000.0, $result['net_after_product_discount']);
        $this->assertSame(50000.0, $result['voucher_discount']);
        $this->assertSame(850000.0, $result['net_sales']);
        $this->assertSame(600000.0, $result['base_price']);
        $this->assertSame(250000.0, $result['gross_profit']);
        $this->assertSame(29.41, $result['gross_margin_percent']);
        $this->assertSame(15.0, $result['effective_discount_percent']);
        $this->assertSame(212500.0, $result['average_net_per_transaction']);
        $this->assertTrue($result['reconciliation']['is_balanced']);
        $this->assertSame(0.0, $result['reconciliation']['difference']);
    }

    public function test_it_separates_clamped_discounts_from_unexplained_differences(): void
    {
        $clamped = FinancialBreakdown::make(
            grossSales: 100000,
            productDiscount: 120000,
            voucherDiscount: 10000,
            netSales: 0,
            basePrice: 0,
            transactions: 0,
            qty: 0,
            netAfterProductDiscount: 0
        );

        $this->assertSame(0.0, $clamped['gross_margin_percent']);
        $this->assertSame(0.0, $clamped['average_net_per_transaction']);
        $this->assertSame(10000.0, $clamped['reconciliation']['difference']);
        $this->assertSame(10000.0, $clamped['reconciliation']['clamped_discount']);
        $this->assertSame(0.0, $clamped['reconciliation']['unexplained_difference']);
        $this->assertTrue($clamped['reconciliation']['is_balanced']);

        $unexplained = FinancialBreakdown::make(
            grossSales: 100000,
            productDiscount: 0,
            voucherDiscount: 0,
            netSales: 80000,
            basePrice: 0,
            transactions: 1,
            qty: 1
        );

        $this->assertSame(-20000.0, $unexplained['reconciliation']['difference']);
        $this->assertSame(0.0, $unexplained['reconciliation']['clamped_discount']);
        $this->assertSame(-20000.0, $unexplained['reconciliation']['unexplained_difference']);
        $this->assertFalse($unexplained['reconciliation']['is_balanced']);
    }
}
