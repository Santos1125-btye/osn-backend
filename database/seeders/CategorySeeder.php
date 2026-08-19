<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Salon', 'icon' => 'salon', 'status' => true],
            ['name' => 'Interior Design', 'icon' => 'interior_design', 'status' => true],
            ['name' => 'Cleaning', 'icon' => 'cleaning', 'status' => true],
            ['name' => 'Plumbing', 'icon' => 'plumbing', 'status' => true],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}