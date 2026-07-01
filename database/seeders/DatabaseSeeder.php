<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    const PRODUCTS_COUNT = 5000;
    const CUSTOMERS_COUNT = 20000;
    const ORDER_ITEMS_COUNT = 250000;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key checks & query logging to maximize speed
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::connection()->disableQueryLog();

        $faker = Faker::create();

        // 1. Seed default user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $now = Carbon::now()->toDateTimeString();

        // 2. Seed Products
        $this->command->info('Seeding ' . self::PRODUCTS_COUNT . ' products...');
        $products = [];
        $productPrices = []; // in-memory map of product_id => price
        $batchSize = 1000;
        
        for ($i = 1; $i <= self::PRODUCTS_COUNT; $i++) {
            $price = round($faker->randomFloat(2, 5, 500), 2);
            $productPrices[$i] = $price;
            
            $products[] = [
                'id' => $i,
                'name' => implode(' ', $faker->words(3)),
                'sku' => 'PROD-' . str_pad($i, 5, '0', STR_PAD_LEFT) . '-' . strtoupper($faker->lexify('??')),
                'description' => $faker->sentence(10),
                'price' => $price,
                'stock_quantity' => $faker->numberBetween(0, 1000),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($i % $batchSize === 0) {
                DB::table('products')->insert($products);
                $products = [];
            }
        }
        if (!empty($products)) {
            DB::table('products')->insert($products);
        }

        // 3. Seed Customers
        $this->command->info('Seeding ' . self::CUSTOMERS_COUNT . ' customers...');
        $customers = [];
        $batchSize = 2000;
        
        for ($i = 1; $i <= self::CUSTOMERS_COUNT; $i++) {
            $customers[] = [
                'id' => $i,
                'name' => $faker->name(),
                'email' => 'customer' . $i . '@example.com', // unique and fast to generate
                'phone' => $faker->phoneNumber(),
                'city' => $faker->city(),
                'country' => $faker->country(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($i % $batchSize === 0) {
                DB::table('customers')->insert($customers);
                $customers = [];
            }
        }
        if (!empty($customers)) {
            DB::table('customers')->insert($customers);
        }

        // 4. Seed Orders and Order Items
        $this->command->info('Seeding orders and ' . self::ORDER_ITEMS_COUNT . ' order items...');
        $orders = [];
        $orderItems = [];
        $orderBatchSize = 1000;
        
        $statuses = ['pending', 'completed', 'cancelled', 'processing'];
        
        $orderId = 1;
        $totalItemsGenerated = 0;

        while ($totalItemsGenerated < self::ORDER_ITEMS_COUNT) {
            // Determine how many items to add to this order, ensuring we don't exceed the target
            $remaining = self::ORDER_ITEMS_COUNT - $totalItemsGenerated;
            $numItems = rand(1, min(50, $remaining));
            
            $orderTotal = 0;
            $orderDate = $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s');
            
            // Choose distinct product IDs for this order to prevent database constraint issues
            $selectedProductIds = [];
            while (count($selectedProductIds) < $numItems) {
                $pId = rand(1, self::PRODUCTS_COUNT);
                $selectedProductIds[$pId] = true;
            }
            $selectedProductIds = array_keys($selectedProductIds);

            foreach ($selectedProductIds as $productId) {
                $quantity = rand(1, 5);
                $unitPrice = $productPrices[$productId];
                $subtotal = round($quantity * $unitPrice, 2);
                $orderTotal += $subtotal;

                $orderItems[] = [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ];
                $totalItemsGenerated++;
            }

            $orders[] = [
                'id' => $orderId,
                'customer_id' => rand(1, self::CUSTOMERS_COUNT),
                'order_date' => $orderDate,
                'status' => $statuses[array_rand($statuses)],
                'total_amount' => $orderTotal,
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ];

            if ($orderId % $orderBatchSize === 0) {
                DB::table('orders')->insert($orders);
                // Chunk order items to avoid MySQL prepared statement placeholder limits (max 65,535 placeholders)
                foreach (array_chunk($orderItems, 1000) as $chunk) {
                    DB::table('order_items')->insert($chunk);
                }
                $orders = [];
                $orderItems = [];
            }
            $orderId++;
        }

        if (!empty($orders)) {
            DB::table('orders')->insert($orders);
            foreach (array_chunk($orderItems, 1000) as $chunk) {
                DB::table('order_items')->insert($chunk);
            }
        }

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info('Database seeding completed successfully.');
    }
}
