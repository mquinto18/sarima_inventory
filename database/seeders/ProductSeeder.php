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
                'name' => 'POLO SHIRT',
                'category' => 'Apparel',
                'price' => 1000,
                'stock' => 500,
                'reorder_level' => 50
            ],
            [
                'name' => 'CAP',
                'category' => 'Accessories',
                'price' => 500,
                'stock' => 300,
                'reorder_level' => 30
            ],
            [
                'name' => 'JEANS',
                'category' => 'Apparel',
                'price' => 2000,
                'stock' => 400,
                'reorder_level' => 40
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
