<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';
require_once '../includes/components_union.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';

try {
    $componentsSource = getComponentsUnionSource();
    $categoryMap = getCategoryMap();
    $selectedCategoryId = $category && isset($categoryMap[$category]) ? $categoryMap[$category] : null;
    
    if ($selectedCategoryId) {
        $query = "SELECT DISTINCT c.manufacturer 
                  FROM {$componentsSource} AS c 
                  WHERE c.manufacturer IS NOT NULL AND c.category_id = ? 
                  ORDER BY c.manufacturer";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$selectedCategoryId]);
    } else {
        $query = "SELECT DISTINCT c.manufacturer FROM {$componentsSource} AS c WHERE c.manufacturer IS NOT NULL ORDER BY c.manufacturer";
        $stmt = $pdo->query($query);
    }
    
    $manufacturers = $stmt->fetchAll();
    
    // Manufacturer logos map
    $manufacturerLogos = [
        'intel' => 'intel.png',
        'amd' => 'AMD.png',
        'nvidia' => 'NVIDIA.png',
        'msi' => 'MSI.png',
        'asus' => 'ASUS.png',
        'gigabyte' => 'Gigabyte.png',
        'corsair' => 'Corsair.png',
        'g.skill' => 'G.Skill.png',
        'gskill' => 'G.Skill.png',
        'kingston' => 'Kingston.png',
        'samsung' => 'Samsung.png',
        'western digital' => 'WD.png',
        'wd' => 'WD.png',
        'crucial' => 'Crucial.png',
        'seasonic' => 'Seasonic.png',
        'be quiet!' => 'be_quiet.png',
        'be quiet' => 'be_quiet.png',
        'lian li' => 'LIAN_LI.png',
        'fractal design' => 'Fractal_design.png',
        'nzxt' => 'NZXT.png',
        'noctua' => 'NOCTUA.png',
        'arctic' => 'ARCTIC.png'
        // Остальные производители будут отображаться с буквами
    ];
    
    function getManufacturerLogo(string $name, array $map): ?string {
        $key = strtolower($name);
        $logo = $map[$key] ?? null;
        
        // Check if file actually exists
        if ($logo && file_exists('../Company_logo/' . $logo)) {
            return $logo;
        }
        
        return null;
    }
    
    // Generate HTML for manufacturers
    ob_start();
    
    $mfrShowLimit = 5;
    $currentLetter = '';
    
    foreach ($manufacturers as $index => $mfr):
        $isHidden = $index >= $mfrShowLimit;
        $logo = getManufacturerLogo($mfr['manufacturer'], $manufacturerLogos);
        $firstLetter = mb_strtoupper(mb_substr($mfr['manufacturer'], 0, 1));
        
        // Show letter divider only for manufacturers without logos when expanded
        if ($isHidden && !$logo && $firstLetter !== $currentLetter) {
            $currentLetter = $firstLetter;
            echo '<div class="manufacturer-letter filter-hidden">' . htmlspecialchars($currentLetter) . '</div>';
        }
    ?>
    <label class="filter-option logo-option <?= $isHidden ? 'filter-hidden' : '' ?>" data-manufacturer="<?= htmlspecialchars($mfr['manufacturer']) ?>">
        <input type="checkbox" class="manufacturer-checkbox" value="<?= htmlspecialchars($mfr['manufacturer']) ?>" 
               onchange="toggleManufacturer(this)">
        <span class="manufacturer-pill">
            <span class="manufacturer-logo-box">
                <?php if ($logo): ?>
                    <img src="Company_logo/<?= $logo ?>" alt="<?= htmlspecialchars($mfr['manufacturer']) ?>">
                <?php else: ?>
                    <span class="manufacturer-initial"><?= $firstLetter ?></span>
                <?php endif; ?>
            </span>
            <span class="manufacturer-name"><?= htmlspecialchars($mfr['manufacturer']) ?></span>
        </span>
    </label>
    <?php endforeach;
    
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($manufacturers)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
