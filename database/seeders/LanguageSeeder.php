<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['name' => 'US English', 'code' => 'en-US', 'is_active' => true],
            ['name' => 'Arabic', 'code' => 'ar', 'is_active' => true],
            ['name' => 'Korean', 'code' => 'ko', 'is_active' => true],
            ['name' => 'Spanish', 'code' => 'es', 'is_active' => true],
            ['name' => 'French', 'code' => 'fr', 'is_active' => true],
            ['name' => 'German', 'code' => 'de', 'is_active' => true],
            ['name' => 'Chinese', 'code' => 'zh', 'is_active' => true],
            ['name' => 'Japanese', 'code' => 'ja', 'is_active' => true],
        ];

        foreach ($languages as $language) {
            \App\Models\Language::create($language);
        }
    }
}
