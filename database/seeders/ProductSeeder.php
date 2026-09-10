<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Paracetamol 500mg',
                'category' => 'Medicine',
                'price' => 5,
                'stock' => 500,
                'reorder_level' => 50
            ],
            [
                'name' => 'Vitamin C 500mg',
                'category' => 'Vitamins & Supplements',
                'price' => 8,
                'stock' => 300,
                'reorder_level' => 30
            ],
            [
                'name' => 'Amoxicillin 500mg',
                'category' => 'Medicine',
                'price' => 12,
                'stock' => 400,
                'reorder_level' => 40
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
