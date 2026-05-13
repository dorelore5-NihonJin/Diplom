<?php
// Helper file to build UNION ALL query across all component tables

function getComponentsUnionSource() {
    $componentTables = [
        'cpu' => [
            'id' => 1,
            'table' => 'components_cpu',
            'columns' => ['socket_type', 'cpu_cores', 'cpu_threads', 'cpu_base_clock']
        ],
        'gpu' => [
            'id' => 2,
            'table' => 'components_gpu',
            'columns' => ['gpu_memory', 'gpu_memory_type', 'pcie_version']
        ],
        'motherboard' => [
            'id' => 3,
            'table' => 'components_mobo',
            'columns' => ['socket_type', 'mobo_form_factor', 'mobo_chipset', 'mobo_ram_type', 'mobo_max_ram_speed', 'mobo_ram_slots', 'mobo_m2_slots']
        ],
        'ram' => [
            'id' => 4,
            'table' => 'components_ram',
            'columns' => ['ram_capacity', 'ram_type', 'ram_speed']
        ],
        'storage' => [
            'id' => 5,
            'table' => 'components_storage',
            'columns' => ['storage_capacity', 'storage_type', 'storage_interface']
        ],
        'psu' => [
            'id' => 6,
            'table' => 'components_psu',
            'columns' => ['psu_wattage', 'psu_efficiency']
        ],
        'case' => [
            'id' => 7,
            'table' => 'components_case',
            'columns' => ['case_form_factor', 'case_max_gpu_length', 'case_max_cooler_height', 'case_fan_slots', 'case_side_panel']
        ],
        'cooling' => [
            'id' => 8,
            'table' => 'components_cooling',
            'columns' => ['cooler_type', 'cooler_height', 'cooler_tdp', 'cooler_socket']
        ],
    ];

    $baseColumns = ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'release_year', 'warranty_months', 'image', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'stock_quantity', 'rating', 'tier'];
    $extendedColumns = ['socket_type', 'cpu_cores', 'cpu_threads', 'cpu_base_clock', 'gpu_memory', 'gpu_memory_type', 'pcie_version', 'mobo_form_factor', 'mobo_chipset', 'mobo_ram_type', 'mobo_max_ram_speed', 'mobo_ram_slots', 'mobo_m2_slots', 'ram_capacity', 'ram_type', 'ram_speed', 'storage_capacity', 'storage_type', 'storage_interface', 'psu_wattage', 'psu_efficiency', 'case_form_factor', 'case_max_gpu_length', 'case_max_cooler_height', 'case_fan_slots', 'case_side_panel', 'cooler_type', 'cooler_height', 'cooler_tdp', 'cooler_socket'];
    $allColumns = array_unique(array_merge($baseColumns, $extendedColumns));

    $unionSelects = [];
    foreach ($componentTables as $slug => $info) {
        $available = array_flip(array_merge($baseColumns, $info['columns']));
        $fields = [];
        foreach ($allColumns as $column) {
            $fields[] = (isset($available[$column]) ? "t.`{$column}`" : "NULL") . " AS `{$column}`";
        }
        $unionSelects[] = "SELECT " . implode(', ', $fields) . " FROM {$info['table']} t";
    }
    
    $componentsUnionSql = implode(' UNION ALL ', $unionSelects);
    return "({$componentsUnionSql})";
}

function getCategoryMap() {
    return [
        'cpu' => 1,
        'gpu' => 2,
        'motherboard' => 3,
        'ram' => 4,
        'storage' => 5,
        'psu' => 6,
        'case' => 7,
        'cooling' => 8,
    ];
}
?>
