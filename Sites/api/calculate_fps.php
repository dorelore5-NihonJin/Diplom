<?php
require_once '../config.php';

header('Content-Type: application/json');

$componentId = $_GET['component_id'] ?? '';
$gameId = $_GET['game_id'] ?? '';
$resolution = $_GET['resolution'] ?? '1920x1080';

if (!$componentId || !$gameId) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

function interpolateFps(int $score, array $anchors): float
{
    if ($score <= $anchors[0]['score']) {
        return $anchors[0]['fps'] * max($score, 1000) / $anchors[0]['score'];
    }

    $lastAnchor = end($anchors);
    if ($score >= $lastAnchor['score']) {
        return $lastAnchor['fps'] + ($score - $lastAnchor['score']) * 0.02;
    }

    for ($i = 0; $i < count($anchors) - 1; $i++) {
        $current = $anchors[$i];
        $next = $anchors[$i + 1];
        if ($score >= $current['score'] && $score <= $next['score']) {
            $rangeScore = $next['score'] - $current['score'];
            $rangeFps = $next['fps'] - $current['fps'];
            $progress = ($score - $current['score']) / ($rangeScore ?: 1);
            return $current['fps'] + $rangeFps * $progress;
        }
    }

    // fallback (не должно достигаться)
    return 60.0;
}

try {
    // Try to get real benchmark data
    $stmt = $pdo->prepare("
        SELECT avg_fps, min_fps, max_fps 
        FROM benchmarks 
        WHERE component_id = ? AND game_id = ? AND resolution = ?
    ");
    $stmt->execute([$componentId, $gameId, $resolution]);
    $benchmark = $stmt->fetch();
    
    if ($benchmark) {
        echo json_encode($benchmark);
    } else {
        // Estimate FPS based on performance score (check GPU table)
        $stmt = $pdo->prepare("SELECT performance_score FROM components_gpu WHERE id = ?");
        $stmt->execute([$componentId]);
        $component = $stmt->fetch();
        
        if ($component) {
            $baseScore = (int) $component['performance_score'];

            // Эмпирические множители по разрешениям (на основе усреднённых тестов TechPowerUp/HardwareUnboxed)
            $resolutionMultipliers = [
                '1920x1080' => 1.00, // базовая точка (ультра-пресет)
                '2560x1440' => 0.74,
                '3840x2160' => 0.52,
                '5120x1440' => 0.48
            ];

            // Профили игр: < 1 — требовательные проекты, > 1 — киберспортивные тайтлы
            $gameMultipliers = [
                '1' => 0.62,  // Cyberpunk 2077
                '2' => 0.70,  // Red Dead Redemption 2
                '3' => 0.82,  // Elden Ring
                '4' => 2.30,  // CS2 (Counter-Strike 2)
                '5' => 0.55,  // Starfield
                '6' => 0.78   // Baldur's Gate 3
            ];

            $resolutionMultiplier = $resolutionMultipliers[$resolution] ?? 1.0;
            $gameMultiplier = $gameMultipliers[$gameId] ?? 1.0;

            // Плавно интерполируем FPS относительно производительности видеокарты
            $anchors = [
                ['score' => 3500, 'fps' => 28],   // GTX 1060 / RX 580
                ['score' => 4000, 'fps' => 35],   // GTX 1660
                ['score' => 5000, 'fps' => 55],   // RTX 2060 / RX 5600 XT
                ['score' => 6000, 'fps' => 78],   // RTX 3060 / RX 6600
                ['score' => 7000, 'fps' => 105],  // RTX 3060 Ti / RX 6700 XT
                ['score' => 8000, 'fps' => 132],  // RTX 4070 / RX 7800 XT
                ['score' => 9000, 'fps' => 160],  // RTX 4070 Ti / RX 7900 GRE
                ['score' => 10000, 'fps' => 188], // RTX 4080 / RX 7900 XT
                ['score' => 11000, 'fps' => 215], // RTX 4090
                ['score' => 12000, 'fps' => 240]  // RTX 5090 и выше
            ];

            $baseFps = interpolateFps($baseScore, $anchors);

            // Применяем профили игры/разрешения
            $estimatedFps = round($baseFps * $resolutionMultiplier * $gameMultiplier);
            $estimatedFps = max(min($estimatedFps, 999), 10);

            echo json_encode([
                'avg_fps' => $estimatedFps,
                'min_fps' => round($estimatedFps * 0.85),
                'max_fps' => round($estimatedFps * 1.10),
                'estimated' => true
            ]);
        } else {
            echo json_encode(['error' => 'Component not found']);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
