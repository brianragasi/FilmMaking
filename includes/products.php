<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function fallback_products(): array
{
    return [
        [
            'id' => 1,
            'name' => 'School Supplies Set',
            'category' => 'Students',
            'description' => 'A complete study bundle with notebooks, pens, folders, and daily classroom essentials.',
            'price' => 199.00,
            'regular_price' => 499.00,
            'stock' => 38,
            'image_url' => 'image/School%20supplies%20set.jpg',
        ],
        [
            'id' => 2,
            'name' => 'Sale Sneakers',
            'category' => 'Students',
            'description' => 'Lightweight everyday sneakers with cushioned support for school, errands, and weekends.',
            'price' => 599.00,
            'regular_price' => 1299.00,
            'stock' => 31,
            'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'id' => 3,
            'name' => 'Wireless Headset',
            'category' => 'Students',
            'description' => 'Comfortable wireless headset for classes, calls, and entertainment.',
            'price' => 799.00,
            'regular_price' => 1699.00,
            'stock' => 28,
            'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'id' => 4,
            'name' => 'Work Safety Boots',
            'category' => 'Construction',
            'description' => 'Heavy-duty boots for the construction workers checking the sale during lunch break.',
            'price' => 899.00,
            'regular_price' => 1799.00,
            'stock' => 16,
            'image_url' => 'https://images.unsplash.com/photo-1605812860427-4024433a70fd?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'id' => 5,
            'name' => 'Work Safety Helmet',
            'category' => 'Construction',
            'description' => 'Durable protective helmet for job-site safety and field work.',
            'price' => 299.00,
            'regular_price' => 699.00,
            'stock' => 22,
            'image_url' => 'image/Work%20Safety%20Helmet.jpg',
        ],
        [
            'id' => 6,
            'name' => 'Basic Tool Set',
            'category' => 'Construction',
            'description' => 'A compact everyday tool kit with the essentials for repairs, assembly, and site work.',
            'price' => 399.00,
            'regular_price' => 899.00,
            'stock' => 19,
            'image_url' => 'https://images.unsplash.com/photo-1530124566582-a618bc2615dc?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'id' => 7,
            'name' => 'Motorcycle Phone Holder',
            'category' => 'Rider',
            'description' => 'A secure handlebar phone mount that keeps directions visible while riding.',
            'price' => 119.00,
            'regular_price' => 399.00,
            'stock' => 44,
            'image_url' => 'image/phone%20holder.jpg',
        ],
        [
            'id' => 8,
            'name' => 'Motorcycle Rain Gear',
            'category' => 'Rider',
            'description' => 'Lightweight rain jacket and waterproof pouch for daily delivery rides.',
            'price' => 499.00,
            'regular_price' => 999.00,
            'stock' => 17,
            'image_url' => 'image/motorcycle%20rain%20gear.jpg',
        ],
        [
            'id' => 9,
            'name' => 'Kalha Cooking Pot',
            'category' => 'Barangay',
            'description' => 'A sturdy stainless cooking pot for soups, stews, rice dishes, and family meals.',
            'price' => 249.00,
            'regular_price' => 599.00,
            'stock' => 26,
            'image_url' => 'image/kalha%20cooking%20pot.jpg',
        ],
        [
            'id' => 10,
            'name' => 'Home Curtain Set',
            'category' => 'Barangay',
            'description' => 'A clean curtain set for homes, waiting-shed shoppers, and family spaces.',
            'price' => 199.00,
            'regular_price' => 499.00,
            'stock' => 54,
            'image_url' => 'image/home%20curtain%20set.jpg',
        ],
        [
            'id' => 11,
            'name' => 'Baby Formula Pack',
            'category' => 'Family',
            'description' => 'A convenient formula multipack prepared for everyday feeding routines.',
            'price' => 549.00,
            'regular_price' => 899.00,
            'stock' => 13,
            'image_url' => 'image/Baby%20Formula%20Pack.jpg',
        ],
        [
            'id' => 12,
            'name' => 'Diaper Bundle',
            'category' => 'Family',
            'description' => 'Soft, absorbent diapers bundled for dependable everyday comfort and care.',
            'price' => 399.00,
            'regular_price' => 799.00,
            'stock' => 24,
            'image_url' => 'image/diaper_bundle.jpg',
        ],
        [
            'id' => 13,
            'name' => 'Baby Clothes Set',
            'category' => 'Family',
            'description' => 'Soft, breathable baby basics made for comfortable all-day wear.',
            'price' => 299.00,
            'regular_price' => 649.00,
            'stock' => 20,
            'image_url' => 'image/Baby%20set%20clothes.jpg',
        ],
        [
            'id' => 14,
            'name' => 'Feeding Bottles',
            'category' => 'Family',
            'description' => 'Baby feeding bottles for the mother and family essential-needs cart.',
            'price' => 119.00,
            'regular_price' => 399.00,
            'stock' => 30,
            'image_url' => 'image/feeding%20bottles.jpg',
        ],
    ];
}

function products(): array
{
    $pdo = db();

    if (!$pdo) {
        return fallback_products();
    }

    try {
        $statement = $pdo->query(
            'SELECT id, name, category, description, price, stock, image_url
             FROM products
             WHERE is_active = 1
             ORDER BY category, name'
        );

        $rows = $statement->fetchAll();
        if (!$rows) {
            return fallback_products();
        }

        $fallbackProductData = [];
        foreach (fallback_products() as $product) {
            $fallbackProductData[(string) $product['name']] = [
                'description' => (string) $product['description'],
                'image_url' => (string) $product['image_url'],
                'price' => (float) $product['price'],
                'regular_price' => (float) $product['regular_price'],
            ];
        }
        foreach ($rows as &$row) {
            $productName = (string) $row['name'];
            if (isset($fallbackProductData[$productName])) {
                $row['description'] = $fallbackProductData[$productName]['description'];
                $row['image_url'] = $fallbackProductData[$productName]['image_url'];
                $row['price'] = $fallbackProductData[$productName]['price'];
                $row['regular_price'] = $fallbackProductData[$productName]['regular_price'];
            }
        }
        unset($row);

        return $rows;
    } catch (Throwable $error) {
        return fallback_products();
    }
}

function product_lookup(): array
{
    $lookup = [];

    foreach (products() as $product) {
        $lookup[(int) $product['id']] = $product;
    }

    return $lookup;
}

function peso(float $amount): string
{
    return 'PHP ' . number_format($amount, 2);
}

function product_regular_price(array $product): float
{
    return max((float) ($product['regular_price'] ?? $product['price'] ?? 0), (float) ($product['price'] ?? 0));
}

function product_savings(array $product): float
{
    return max(0.0, product_regular_price($product) - (float) ($product['price'] ?? 0));
}

function product_discount_percentage(array $product): int
{
    $regularPrice = product_regular_price($product);

    return $regularPrice > 0
        ? (int) round((product_savings($product) / $regularPrice) * 100)
        : 0;
}
