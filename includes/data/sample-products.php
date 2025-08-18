<?php
/**
 * Dữ liệu mẫu cho sản phẩm
 * 
 * @package VinaPet
 */

/**
 * Danh sách sản phẩm mẫu
 */
function get_sample_products() {
    return [
        [
            'id' => 1,
            'item_code' => 'CAT-TRE-001',
            'item_name' => 'Cát tre',
            'description' => 'Siêu Khử Mùi & Khôi Mũi Tự Uy. Siêu Nhẹ & Thấm Hút Mạnh Mẽ',
            'image' => get_template_directory_uri() . '/assets/images/products/cat-tre.jpg',
            'item_group' => 'cat-ve-sinh',
            'standard_rate' => 150000,
            'created_date' => '2024-01-15'
        ],
        [
            'id' => 2,
            'item_code' => 'CAT-NANH-001',
            'item_name' => 'Cát đậu nành',
            'description' => 'Vón Cục Tốt & Ít Bụi. Thân thiện môi trường',
            'image' => get_template_directory_uri() . '/assets/images/products/cat-dau-nanh.jpg',
            'item_group' => 'cat-ve-sinh',
            'standard_rate' => 120000,
            'created_date' => '2024-01-20'
        ],
        [
            'id' => 3,
            'item_code' => 'CAT-TRAU-001',
            'item_name' => 'Cát vỏ trấu',
            'description' => 'Khang Khuẩn & Khang Nấm Mốc. Tự Nhiên trong môi trường ẩm',
            'image' => get_template_directory_uri() . '/assets/images/products/cat-vo-trau.jpg',
            'item_group' => 'cat-ve-sinh',
            'standard_rate' => 95000,
            'created_date' => '2024-01-25'
        ],
        [
            'id' => 4,
            'item_code' => 'CAT-SET-001',
            'item_name' => 'Cát đất sét',
            'description' => 'Vón Cực Chặt-Chắn & Tức Thì. Xả thông toilet dễ dàng lót',
            'image' => get_template_directory_uri() . '/assets/images/products/cat-dat-set.jpg',
            'item_group' => 'cat-ve-sinh',
            'standard_rate' => 85000,
            'created_date' => '2024-01-30'
        ],
        [
            'id' => 5,
            'item_code' => 'PET-BOWL-001',
            'item_name' => 'Pet Bowl',
            'description' => 'For Cats and Dogs Best Seller Auto Dog Feeder',
            'image' => get_template_directory_uri() . '/assets/images/products/pet-bowl.jpg',
            'item_group' => 'dung-cu-an-uong',
            'standard_rate' => 75000,
            'created_date' => '2024-02-01'
        ],
        [
            'id' => 6,
            'item_code' => 'PET-CUSHION-001',
            'item_name' => 'Pet Soft Cushion',
            'description' => 'Comfortable Bed for Dog Cats Washable Winter Warm Mattress',
            'image' => get_template_directory_uri() . '/assets/images/products/pet-soft-cushion.jpg',
            'item_group' => 'nha-o-thu-cung',
            'standard_rate' => 280000,
            'created_date' => '2024-02-05'
        ],
        [
            'id' => 7,
            'item_code' => 'PET-CAVE-001',
            'item_name' => 'Pet Cave',
            'description' => 'Cozy House Cats Tent',
            'image' => get_template_directory_uri() . '/assets/images/products/pet-cave.jpg',
            'item_group' => 'nha-o-thu-cung',
            'standard_rate' => 320000,
            'created_date' => '2024-02-10'
        ],
        [
            'id' => 8,
            'item_code' => 'CAT-LITTER-001',
            'item_name' => 'Metal Cat Litter',
            'description' => 'Pet Cleaning Products with Small Hole Dog Accessories',
            'image' => get_template_directory_uri() . '/assets/images/products/metal-cat-litter.jpg',
            'item_group' => 'dung-cu-ve-sinh',
            'standard_rate' => 45000,
            'created_date' => '2024-02-15'
        ],
        [
            'id' => 9,
            'item_code' => 'LUOC-MEO-001',
            'item_name' => 'Lược chải lông mèo',
            'description' => 'Removing Cat Hair and Loose Hair for Cat Dog Pet',
            'image' => get_template_directory_uri() . '/assets/images/products/luoc-chai-long-meo.jpg',
            'item_group' => 'dung-cu-ve-sinh',
            'standard_rate' => 65000,
            'created_date' => '2024-02-20'
        ],
        [
            'id' => 10,
            'item_code' => 'VONG-CO-001',
            'item_name' => 'Vòng cổ thú cưng',
            'description' => 'Pet Dog Necklace Collar Dogs Valentine\'s Day New Year Gift',
            'image' => get_template_directory_uri() . '/assets/images/products/vong-co-thu-cung.jpg',
            'item_group' => 'phu-kien',
            'standard_rate' => 95000,
            'created_date' => '2024-02-25'
        ]
    ];
}

/**
 * Danh sách nhóm sản phẩm
 */
function get_sample_product_categories() {
    return [
        [
            'id' => 1,
            'name' => 'cat-ve-sinh',
            'display_name' => 'Cát vệ sinh',
            'parent_item_group' => ''
        ],
        [
            'id' => 2,
            'name' => 'dung-cu-an-uong',
            'display_name' => 'Dụng cụ ăn uống',
            'parent_item_group' => ''
        ],
        [
            'id' => 3,
            'name' => 'nha-o-thu-cung',
            'display_name' => 'Nhà ở thú cưng',
            'parent_item_group' => ''
        ],
        [
            'id' => 4,
            'name' => 'dung-cu-ve-sinh',
            'display_name' => 'Dụng cụ vệ sinh',
            'parent_item_group' => ''
        ],
        [
            'id' => 5,
            'name' => 'phu-kien',
            'display_name' => 'Phụ kiện',
            'parent_item_group' => ''
        ]
    ];
}

/**
 * Hàm lấy một sản phẩm theo mã
 */
function get_sample_product_by_code($product_code) {
    $products = get_sample_products();
    
    foreach ($products as $product) {
        if ($product['item_code'] === $product_code) {
            return $product;
        }
    }
    
    return null;
}

/**
 * Hàm lấy nhóm sản phẩm theo tên
 */
function get_sample_category_by_name($category_name) {
    $categories = get_sample_product_categories();
    
    foreach ($categories as $category) {
        if ($category['name'] === $category_name) {
            return $category;
        }
    }
    
    return null;
}

/**
 * Hàm lọc và sắp xếp sản phẩm theo tham số
 */
function filter_sample_products($params = []) {
    $products = get_sample_products();
    $results = [];
    
    // Lọc theo danh mục
    if (!empty($params['category'])) {
        foreach ($products as $product) {
            if ($product['item_group'] === $params['category']) {
                $results[] = $product;
            }
        }
    } else {
        $results = $products;
    }
    
    // Lọc theo từ khóa tìm kiếm
    if (!empty($params['search'])) {
        $search = strtolower($params['search']);
        $filtered = [];
        
        foreach ($results as $product) {
            if (stripos(strtolower($product['item_name']), $search) !== false || 
                stripos(strtolower($product['description']), $search) !== false) {
                $filtered[] = $product;
            }
        }
        
        $results = $filtered;
    }
    
    // Loại trừ sản phẩm theo mã
    if (!empty($params['exclude'])) {
        $filtered = [];
        
        foreach ($results as $product) {
            if ($product['item_code'] !== $params['exclude']) {
                $filtered[] = $product;
            }
        }
        
        $results = $filtered;
    }
    
    // Sắp xếp sản phẩm
    if (!empty($params['sort'])) {
        switch ($params['sort']) {
            case 'name-asc':
                usort($results, function($a, $b) {
                    return strcmp($a['item_name'], $b['item_name']);
                });
                break;
            case 'name-desc':
                usort($results, function($a, $b) {
                    return strcmp($b['item_name'], $a['item_name']);
                });
                break;
            case 'price-asc':
                usort($results, function($a, $b) {
                    return $a['standard_rate'] - $b['standard_rate'];
                });
                break;
            case 'price-desc':
                usort($results, function($a, $b) {
                    return $b['standard_rate'] - $a['standard_rate'];
                });
                break;
            case 'newest':
                usort($results, function($a, $b) {
                    return strtotime($b['created_date']) - strtotime($a['created_date']);
                });
                break;
        }
    }
    
    // Phân trang
    $page = isset($params['page']) ? intval($params['page']) : 1;
    $limit = isset($params['limit']) ? intval($params['limit']) : count($results);
    
    $offset = ($page - 1) * $limit;
    $results = array_slice($results, $offset, $limit);
    
    return $results;
}