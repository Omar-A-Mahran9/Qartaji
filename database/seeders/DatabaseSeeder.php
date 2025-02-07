<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(GeneraleSettingSeeder::class);
        $this->call(LegalPageSeeder::class);
        $this->call(PaymentGatewaySeeder::class);
        $this->call(SocialLinkSeeder::class);
        $this->call(ThemeColorSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(SocialAuthSeeder::class);

        if (app()->environment('local')) {
            $this->call(CustomerSeeder::class);
            $this->call(ShopSeeder::class);
            $this->call(CategorySeeder::class);
            $this->call(BrandSeeder::class);
            $this->call(SizeSeeder::class);
            $this->call(ColorSeeder::class);
            $this->call(UnitSeeder::class);
            $this->call(ProductSeeder::class);
            $this->call(BannerSeeder::class);
            $this->call(CouponSeeder::class);
            $this->call(AddressSeeder::class);
            $this->call(OrderSeeder::class);
            $this->call(ReviewSeeder::class);
            $this->call(FavoriteSeeder::class);
        } else {
            $this->call(RootAdminShopSeeder::class);
        }
        $this->call(WalletSeeder::class);
        $this->command->info('Database seeded successfully');

        //clear cache
        Artisan::call('cache:clear');

        $this->userInfo();
    }

    private function userInfo()
    {
        // info for root user in command line
        $this->command->line('');
        $this->command->info('Root user created:');
        $this->command->warn('- Email: root@readyecommerce.com');
        $this->command->warn('- Password: secret');
        $this->command->info('');

        if (app()->environment('local')) {
            //info for shop user in command line
            $this->command->info('Shop created:');
            $this->command->warn('- Email: shop@readyecommerce.com');
            $this->command->warn('- Password: secret');
        }
    }
}
