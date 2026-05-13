<?php

function normalizeFilterList(?string $raw): array {
    if (!$raw) {
        return [];
    }
    $parts = array_map('trim', explode(',', $raw));
    return array_values(array_filter($parts, static fn($value) => $value !== ''));
}

function applyCategoryFilters(string &$query, array &$params, int $categoryId, array $filters): void
{
    switch ($categoryId) {
        case 1: // CPUs
            if ($filters['cpu_cores_min'] !== '') {
                $query .= " AND c.cpu_cores >= ?";
                $params[] = (int) $filters['cpu_cores_min'];
            }
            if ($filters['cpu_cores_max'] !== '') {
                $query .= " AND c.cpu_cores <= ?";
                $params[] = (int) $filters['cpu_cores_max'];
            }
            if ($filters['cpu_tdp_min'] !== '') {
                $query .= " AND c.power_consumption >= ?";
                $params[] = (int) $filters['cpu_tdp_min'];
            }
            if ($filters['cpu_tdp_max'] !== '') {
                $query .= " AND c.power_consumption <= ?";
                $params[] = (int) $filters['cpu_tdp_max'];
            }
            break;

        case 2: // GPUs
            if ($filters['gpu_memory_min'] !== '') {
                $query .= " AND c.gpu_memory >= ?";
                $params[] = (int) $filters['gpu_memory_min'];
            }
            if ($filters['gpu_memory_max'] !== '') {
                $query .= " AND c.gpu_memory <= ?";
                $params[] = (int) $filters['gpu_memory_max'];
            }
            if ($filters['gpu_tdp_min'] !== '') {
                $query .= " AND c.power_consumption >= ?";
                $params[] = (int) $filters['gpu_tdp_min'];
            }
            if ($filters['gpu_tdp_max'] !== '') {
                $query .= " AND c.power_consumption <= ?";
                $params[] = (int) $filters['gpu_tdp_max'];
            }
            break;

        case 3: // Motherboards
            $formFactors = array_map('strtolower', normalizeFilterList($filters['mobo_form_factor'] ?? ''));
            if ($formFactors) {
                $placeholders = implode(',', array_fill(0, count($formFactors), '?'));
                $query .= " AND LOWER(c.mobo_form_factor) IN ($placeholders)";
                foreach ($formFactors as $factor) {
                    $params[] = $factor;
                }
            }
            
            $chipsets = array_map('strtoupper', normalizeFilterList($filters['mobo_chipset'] ?? ''));
            if ($chipsets) {
                $placeholders = implode(',', array_fill(0, count($chipsets), '?'));
                $query .= " AND UPPER(c.mobo_chipset) IN ($placeholders)";
                foreach ($chipsets as $chipset) {
                    $params[] = $chipset;
                }
            }
            
            $sockets = array_map('strtoupper', normalizeFilterList($filters['mobo_socket'] ?? ''));
            if ($sockets) {
                $placeholders = implode(',', array_fill(0, count($sockets), '?'));
                $query .= " AND UPPER(c.socket_type) IN ($placeholders)";
                foreach ($sockets as $socket) {
                    $params[] = $socket;
                }
            }

            $moboRamSlotsMin = trim((string)($filters['mobo_ram_slots_min'] ?? ''));
            if ($moboRamSlotsMin !== '' && $moboRamSlotsMin !== '0') {
                $query .= " AND c.mobo_ram_slots >= ?";
                $params[] = (int) $moboRamSlotsMin;
            }

            $moboRamSlotsMax = trim((string)($filters['mobo_ram_slots_max'] ?? ''));
            if ($moboRamSlotsMax !== '' && $moboRamSlotsMax !== '0') {
                $query .= " AND c.mobo_ram_slots <= ?";
                $params[] = (int) $moboRamSlotsMax;
            }

            $moboM2SlotsMin = trim((string)($filters['mobo_m2_slots_min'] ?? ''));
            if ($moboM2SlotsMin !== '' && $moboM2SlotsMin !== '0') {
                $query .= " AND c.mobo_m2_slots >= ?";
                $params[] = (int) $moboM2SlotsMin;
            }

            $moboM2SlotsMax = trim((string)($filters['mobo_m2_slots_max'] ?? ''));
            if ($moboM2SlotsMax !== '' && $moboM2SlotsMax !== '0') {
                $query .= " AND c.mobo_m2_slots <= ?";
                $params[] = (int) $moboM2SlotsMax;
            }
            break;

        case 4: // RAM
            if ($filters['ram_capacity_min'] !== '') {
                $query .= " AND c.ram_capacity >= ?";
                $params[] = (int) $filters['ram_capacity_min'];
            }
            if ($filters['ram_capacity_max'] !== '') {
                $query .= " AND c.ram_capacity <= ?";
                $params[] = (int) $filters['ram_capacity_max'];
            }
            if ($filters['ram_speed_min'] !== '') {
                $query .= " AND c.ram_speed >= ?";
                $params[] = (int) $filters['ram_speed_min'];
            }
            if ($filters['ram_speed_max'] !== '') {
                $query .= " AND c.ram_speed <= ?";
                $params[] = (int) $filters['ram_speed_max'];
            }
            break;

        case 5: // Storage
            if ($filters['storage_capacity_min'] !== '') {
                $query .= " AND c.storage_capacity >= ?";
                $params[] = (int) $filters['storage_capacity_min'];
            }
            if ($filters['storage_capacity_max'] !== '') {
                $query .= " AND c.storage_capacity <= ?";
                $params[] = (int) $filters['storage_capacity_max'];
            }

            $interfaces = array_map('strtolower', normalizeFilterList($filters['storage_interface'] ?? ''));
            if ($interfaces) {
                $placeholders = implode(',', array_fill(0, count($interfaces), '?'));
                $query .= " AND LOWER(c.storage_interface) IN ($placeholders)";
                foreach ($interfaces as $iface) {
                    $params[] = $iface;
                }
            }
            break;

        case 6: // Power supplies
            if ($filters['psu_wattage_min'] !== '') {
                $query .= " AND c.psu_wattage >= ?";
                $params[] = (int) $filters['psu_wattage_min'];
            }
            if ($filters['psu_wattage_max'] !== '') {
                $query .= " AND c.psu_wattage <= ?";
                $params[] = (int) $filters['psu_wattage_max'];
            }

            $efficiencies = array_map('strtolower', normalizeFilterList($filters['psu_efficiency'] ?? ''));
            if ($efficiencies) {
                $placeholders = implode(',', array_fill(0, count($efficiencies), '?'));
                $query .= " AND LOWER(c.psu_efficiency) IN ($placeholders)";
                foreach ($efficiencies as $eff) {
                    $params[] = $eff;
                }
            }
            break;

        case 7: // Cases
            $caseGpuLengthMin = trim((string)($filters['case_gpu_length_min'] ?? ''));
            if ($caseGpuLengthMin !== '' && $caseGpuLengthMin !== '0') {
                $query .= " AND c.case_max_gpu_length >= ?";
                $params[] = (int) $caseGpuLengthMin;
            }

            $caseGpuLengthMax = trim((string)($filters['case_gpu_length_max'] ?? ''));
            if ($caseGpuLengthMax !== '' && $caseGpuLengthMax !== '0') {
                $query .= " AND c.case_max_gpu_length <= ?";
                $params[] = (int) $caseGpuLengthMax;
            }
            
            $caseCoolerHeightMin = trim((string)($filters['case_cooler_height_min'] ?? ''));
            if ($caseCoolerHeightMin !== '' && $caseCoolerHeightMin !== '0') {
                $query .= " AND c.case_max_cooler_height >= ?";
                $params[] = (int) $caseCoolerHeightMin;
            }

            $caseCoolerHeightMax = trim((string)($filters['case_cooler_height_max'] ?? ''));
            if ($caseCoolerHeightMax !== '' && $caseCoolerHeightMax !== '0') {
                $query .= " AND c.case_max_cooler_height <= ?";
                $params[] = (int) $caseCoolerHeightMax;
            }

            $caseFansMin = trim((string)($filters['case_fans_min'] ?? ''));
            if ($caseFansMin !== '' && $caseFansMin !== '0') {
                $query .= " AND c.case_fan_slots >= ?";
                $params[] = (int) $caseFansMin;
            }

            $caseFansMax = trim((string)($filters['case_fans_max'] ?? ''));
            if ($caseFansMax !== '' && $caseFansMax !== '0') {
                $query .= " AND c.case_fan_slots <= ?";
                $params[] = (int) $caseFansMax;
            }

            $caseForms = array_map('strtolower', normalizeFilterList($filters['case_form_factor'] ?? ''));
            if ($caseForms) {
                $placeholders = implode(',', array_fill(0, count($caseForms), '?'));
                $query .= " AND LOWER(c.case_form_factor) IN ($placeholders)";
                foreach ($caseForms as $form) {
                    $params[] = $form;
                }
            }

            $caseSidePanels = array_map('strtolower', normalizeFilterList($filters['case_side_panel'] ?? ''));
            if ($caseSidePanels) {
                $placeholders = implode(',', array_fill(0, count($caseSidePanels), '?'));
                $query .= " AND LOWER(c.case_side_panel) IN ($placeholders)";
                foreach ($caseSidePanels as $panel) {
                    $params[] = $panel;
                }
            }
            break;

        case 8: // Cooling
            $coolerTypes = array_map('strtolower', normalizeFilterList($filters['cooler_type'] ?? ''));
            if ($coolerTypes) {
                $placeholders = implode(',', array_fill(0, count($coolerTypes), '?'));
                $query .= " AND LOWER(c.cooler_type) IN ($placeholders)";
                foreach ($coolerTypes as $type) {
                    $params[] = $type;
                }
            }

            if ($filters['cooler_height_max'] !== '') {
                $query .= " AND c.cooler_height <= ?";
                $params[] = (int) $filters['cooler_height_max'];
            }
            break;

        default:
            // No additional filters
            break;
    }
}
