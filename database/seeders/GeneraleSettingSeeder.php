<?php

namespace Database\Seeders;

use App\Models\GeneraleSetting;
use Illuminate\Database\Seeder;

class GeneraleSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $isLocal = app()->environment('local') ? true : false;

        GeneraleSetting::truncate();

        GeneraleSetting::create([
            'name' => config('app.name'),
            'title' => config('app.name'),
            'email' => null,
            'mobile' => null,
            'address' => null,
            'primary_color' => '#ee456b',
            'secondary_color' => '#fee5e8',
            'shop_type' => 'multi',
            'show_download_app' => false,
            'google_playstore_url' => null,
            'app_store_url' => null,
            'currency' => '$',
            'currency_position' => 'prefix',
            'direction' => 'ltr',
            'favicon_id' => null,
            'logo_id' => null,
            'show_footer' => true,
            'footer_phone' => $isLocal ? '+8801714231625' : null,
            'footer_email' => $isLocal ? 'razinsoftltd@gmail.com' : null,
            'footer_text' => 'All right reserved by RazinSoft',
            'footer_description' => 'The ultimate all-in-one solution for your eCommerce business worldwide.',
            'footer_logo_id' => null,
            'footer_qrcode_id' => null,
            'app_logo_id' => null,
        ]);
    }
}
