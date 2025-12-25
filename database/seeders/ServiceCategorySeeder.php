<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web',
                'slug' => 'web',
                'description' => 'Monitor website uptime and availability',
                'icon' => 'ri-global-line',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Server',
                'slug' => 'server',
                'description' => 'Monitor server performance and metrics',
                'icon' => 'ri-server-line',
                'is_active' => true,
                'order' => 2,
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
