<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('languages')->insert([
            [
                'title' => 'Arabic',
                'name'  => 'ar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'French',
                'name'  => 'fr',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'English',
                'name'  => 'en',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
