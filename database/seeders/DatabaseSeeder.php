<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            /*
            |--------------------------------------------------------------------------
            | Seeder Data Master
            |--------------------------------------------------------------------------
            */

            CustomerSeeder::class,
          

            
           

            /*
            |--------------------------------------------------------------------------
            | Seeder Permission
            |--------------------------------------------------------------------------
            */

            BrandPermissionSeeder::class,
            CourierPermissionSeeder::class,
            DiscountPermissionSeeder::class,
            EcommerceBannerPermissionSeeder::class,
            ItemConditionPermissionSeeder::class,
            LocationStorePermissionSeeder::class,
            LocationWarehousePermissionSeeder::class,
            LoyaltyModulePermissionSeeder::class,
            LoyaltyPointMutationPermissionSeeder::class,
            MembershipBenefitPermissionSeeder::class,
            MembershipPermissionSeeder::class,
            PaymentMethodPermissionSeeder::class,
            ProductCategoryPermissionSeeder::class,
            ProductDiscountPermissionSeeder::class,
            ProductPermissionSeeder::class,
            ProductPricePermissionSeeder::class,
            ProductSpecificationPermissionSeeder::class,
            PromoPermissionSeeder::class,
            StorePermissionSeeder::class,
            SupplierPermissionSeeder::class,
            UnitPermissionSeeder::class,
            VoucherPermissionSeeder::class,

            WarehousePermissionSeeder::class,
            BackofficeRolePermissionSeeder::class,
        ]);
    }
}
