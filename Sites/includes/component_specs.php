<?php
function format_ru_plural($value, $one, $few, $many) {
    $value = abs((int)$value);
    $mod10 = $value % 10;
    $mod100 = $value % 100;
    if ($mod10 === 1 && $mod100 !== 11) {
        return $one;
    }
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
        return $few;
    }
    return $many;
}

function normalize_spec_string($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    return str_ireplace(['ghz', 'mhz', 'gb', 'tb'], ['ГГц', 'МГц', 'ГБ', 'ТБ'], $value);
}

function format_numeric($value) {
    if (!is_numeric($value)) {
        return normalize_spec_string($value);
    }
    $formatted = rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
    return $formatted === '' ? null : $formatted;
}

function format_frequency($value, $unit) {
    $normalized = normalize_spec_string($value);
    if ($normalized && (stripos($normalized, 'ггц') !== false || stripos($normalized, 'мгц') !== false)) {
        return $normalized;
    }
    $numeric = format_numeric($value);
    if (!$numeric) {
        return null;
    }
    return $numeric . ' ' . $unit;
}

function format_watts($value) {
    $normalized = normalize_spec_string($value);
    if (!$normalized) {
        return null;
    }
    if (preg_match('/\d+\s*w\b/i', $normalized)) {
        return preg_replace('/\s*w\b/i', ' Вт', $normalized);
    }
    if (preg_match('/\d+\s*вт\b/iu', $normalized)) {
        return $normalized;
    }
    if (is_numeric($value)) {
        return format_numeric($value) . ' Вт';
    }
    return $normalized . ' Вт';
}

function format_length_mm($value) {
    $normalized = normalize_spec_string($value);
    if (!$normalized) {
        return null;
    }
    if (preg_match('/\d+\s*мм\b/iu', $normalized)) {
        return $normalized;
    }
    if (preg_match('/\d+\s*mm\b/i', $normalized)) {
        return preg_replace('/\s*mm\b/i', ' мм', $normalized);
    }
    if (is_numeric($value)) {
        return format_numeric($value) . ' мм';
    }
    return $normalized . ' мм';
}

function format_capacity_gb($value) {
    if (!is_numeric($value)) {
        return normalize_spec_string($value);
    }
    $value = (int)$value;
    if ($value >= 1000 && $value % 1000 === 0) {
        return ($value / 1000) . ' ТБ';
    }
    return $value . ' ГБ';
}

function get_component_spec_items(array $component) {
    $specs = json_decode($component['specs'] ?? '', true);
    if (!is_array($specs)) {
        $specs = [];
    }

    $categoryId = (int)($component['category_id'] ?? 0);
    $items = [];

    switch ($categoryId) {
        case 1: // CPU
            $cores = $component['cpu_cores'] ?? ($specs['cores'] ?? null);
            if ($cores !== null && $cores !== '') {
                $items[] = $cores . ' ' . format_ru_plural($cores, 'ядро', 'ядра', 'ядер');
            }
            $threads = $component['cpu_threads'] ?? ($specs['threads'] ?? null);
            if ($threads !== null && $threads !== '') {
                $items[] = $threads . ' ' . format_ru_plural($threads, 'поток', 'потока', 'потоков');
            }
            $baseClock = $component['cpu_base_clock'] ?? ($specs['base_clock'] ?? null);
            $baseClockLabel = format_frequency($baseClock, 'ГГц');
            if ($baseClockLabel) {
                $items[] = $baseClockLabel;
            }
            break;
        case 2: // GPU
            $memory = $component['gpu_memory'] ?? ($specs['memory'] ?? null);
            $memoryType = $component['gpu_memory_type'] ?? ($specs['memory_type'] ?? null);
            $memoryLabel = $memory !== null ? format_capacity_gb($memory) : null;
            if ($memoryLabel && $memoryType && stripos($memoryLabel, $memoryType) === false) {
                $memoryLabel .= ' ' . $memoryType;
            }
            if ($memoryLabel) {
                $items[] = $memoryLabel;
            }
            $clockKeys = ['boost_clock' => 'Boost', 'game_clock' => 'Game', 'core_clock' => 'Core'];
            foreach ($clockKeys as $key => $label) {
                if (isset($specs[$key])) {
                    $clockLabel = format_frequency($specs[$key], 'МГц');
                    if ($clockLabel) {
                        $items[] = $label . ' ' . $clockLabel;
                    }
                    break;
                }
            }
            if (isset($specs['cuda_cores'])) {
                $items[] = 'CUDA ' . $specs['cuda_cores'];
            } elseif (isset($specs['stream_processors'])) {
                $items[] = 'SP ' . $specs['stream_processors'];
            } elseif (isset($specs['xe_cores'])) {
                $items[] = 'Xe ' . $specs['xe_cores'];
            }
            break;
        case 3: // Motherboard
            $chipset = $component['mobo_chipset'] ?? ($specs['chipset'] ?? null);
            if ($chipset) {
                $items[] = 'чипсет ' . $chipset;
            }
            $formFactor = $component['mobo_form_factor'] ?? ($specs['form_factor'] ?? null);
            if ($formFactor) {
                $items[] = $formFactor;
            }
            $ramSlots = $component['mobo_ram_slots'] ?? ($specs['ram_slots'] ?? null);
            if ($ramSlots) {
                $items[] = $ramSlots . ' ' . format_ru_plural($ramSlots, 'слот RAM', 'слота RAM', 'слотов RAM');
            } else {
                $m2Slots = $component['mobo_m2_slots'] ?? ($specs['m2_slots'] ?? null);
                if ($m2Slots) {
                    $items[] = $m2Slots . ' ' . format_ru_plural($m2Slots, 'слот M.2', 'слота M.2', 'слотов M.2');
                }
            }
            break;
        case 4: // RAM
            $capacity = $component['ram_capacity'] ?? ($specs['capacity'] ?? null);
            if ($capacity) {
                $items[] = format_capacity_gb($capacity);
            }
            $type = $component['ram_type'] ?? ($specs['type'] ?? null);
            if ($type) {
                $items[] = $type;
            }
            $speed = $component['ram_speed'] ?? ($specs['speed'] ?? null);
            $speedLabel = format_frequency($speed, 'МГц');
            if ($speedLabel) {
                $items[] = $speedLabel;
            }
            $latency = $specs['cas_latency'] ?? ($specs['latency'] ?? ($specs['cl'] ?? null));
            if ($latency) {
                $latencyValue = strtoupper(preg_replace('/\s+/', '', (string)$latency));
                $items[] = str_starts_with($latencyValue, 'CL') ? $latencyValue : 'CL' . $latencyValue;
            }
            $modules = $specs['modules'] ?? ($specs['module_count'] ?? ($specs['sticks'] ?? null));
            if ($modules) {
                if (is_numeric($modules)) {
                    $items[] = $modules . ' ' . format_ru_plural($modules, 'модуль', 'модуля', 'модулей');
                } else {
                    $items[] = normalize_spec_string($modules);
                }
            }
            return array_slice($items, 0, 4);
        case 5: // Storage
            $capacity = $component['storage_capacity'] ?? ($specs['capacity'] ?? null);
            if ($capacity) {
                $items[] = format_capacity_gb($capacity);
            }
            $type = $component['storage_type'] ?? ($specs['type'] ?? null);
            if ($type) {
                $items[] = $type;
            }
            $interface = $component['storage_interface'] ?? ($specs['interface'] ?? null);
            if ($interface) {
                $items[] = normalize_spec_string($interface);
            }
            break;
        case 6: // PSU
            $wattage = $component['psu_wattage'] ?? ($specs['wattage'] ?? null);
            $wattLabel = format_watts($wattage);
            if ($wattLabel) {
                $items[] = $wattLabel;
            }
            $efficiency = $component['psu_efficiency'] ?? ($specs['efficiency'] ?? null);
            if ($efficiency) {
                $items[] = normalize_spec_string($efficiency);
            }
            break;
        case 7: // Case
            $formFactor = $component['case_form_factor'] ?? ($specs['form_factor'] ?? null);
            if ($formFactor) {
                $items[] = $formFactor;
            }
            $maxGpu = $component['case_max_gpu_length'] ?? ($specs['max_gpu_length'] ?? null);
            if ($maxGpu) {
                $items[] = 'GPU до ' . format_length_mm($maxGpu);
            }
            $maxCooler = $component['case_max_cooler_height'] ?? ($specs['max_cooler_height'] ?? null);
            if ($maxCooler) {
                $items[] = 'кулер до ' . format_length_mm($maxCooler);
            }
            break;
        case 8: // Cooling
            $type = $component['cooler_type'] ?? ($specs['type'] ?? null);
            if ($type) {
                $items[] = normalize_spec_string($type);
            }
            $height = $component['cooler_height'] ?? ($specs['height'] ?? null);
            if ($height) {
                $items[] = 'высота ' . format_length_mm($height);
            } elseif (!empty($specs['radiator_size'])) {
                $items[] = 'радиатор ' . normalize_spec_string($specs['radiator_size']);
            }
            $tdp = $component['cooler_tdp'] ?? ($specs['tdp'] ?? ($specs['max_tdp'] ?? null));
            if ($tdp) {
                $items[] = 'TDP ' . format_watts($tdp);
            }
            break;
        default:
            break;
    }

    if (count($items) === 0 && $specs) {
        $values = array_values($specs);
        foreach ($values as $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $normalized = normalize_spec_string($value);
            if ($normalized) {
                $items[] = $normalized;
            }
            if (count($items) >= 3) {
                break;
            }
        }
    }

    return array_slice($items, 0, 3);
}
