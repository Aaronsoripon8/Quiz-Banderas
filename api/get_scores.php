<?php
header('Content-Type: application/json');
// Devolver top 10 mejor puntuación por usuario como JSON (mejor por 'correctes')
$result = ['ok' => false, 'scores' => []];
try {
    require_once __DIR__ . '/conexio.php';
    $pdo = getPDO();
    // Seleccionar mejor por usuario (insensible a mayúsculas/minúsculas en el nombre). Usar agrupamiento por LOWER(nom).
    $sql = "SELECT MAX(nom) AS nom, MAX(correctes) AS correctes, MAX(total) AS total FROM scores GROUP BY LOWER(nom) ORDER BY correctes DESC, nom ASC LIMIT 10";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['ok'] = true;
    $result['scores'] = array_map(function($r){ return ['nom' => $r['nom'], 'correctes' => intval($r['correctes']), 'total' => isset($r['total']) ? intval($r['total']) : 0]; }, $rows);
    echo json_encode($result);
    exit;
} catch (Exception $e) {
    error_log('get_scores DB error: ' . $e->getMessage());
}
// Fallback a fichero
$path = __DIR__ . '/scores.json';
$raw = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
$map = [];
foreach ($raw as $s) {
    $name = isset($s['nom']) ? $s['nom'] : 'anonim';
    $key = mb_strtolower(trim($name));
    $correctes = isset($s['correctes']) ? intval($s['correctes']) : 0;
    $total = isset($s['total']) ? intval($s['total']) : 0;
    $created = isset($s['data']) ? $s['data'] : null;
    if (!isset($map[$key]) || $correctes > $map[$key]['correctes'] || ($correctes == $map[$key]['correctes'] && $created > $map[$key]['created_at'])) {
        $map[$key] = ['nom' => $name, 'total' => $total, 'correctes' => $correctes, 'created_at' => $created];
    }
}
$scores = array_values($map);
usort($scores, function($a,$b){ return $b['correctes'] - $a['correctes']; });
$scores = array_slice($scores, 0, 10);
$result['ok'] = true;
$result['scores'] = array_map(function($r){ return ['nom' => $r['nom'], 'correctes' => intval($r['correctes']), 'total' => isset($r['total']) ? intval($r['total']) : 0]; }, $scores);
echo json_encode($result);
