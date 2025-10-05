<?php
// Mostrar la mejor puntuación por usuario (desde la BD si es posible)
require_once __DIR__ . '/conexio.php';
try {
    $pdo = getPDO();
    // Seleccionar una mejor puntuación por usuario (más correctes). En caso de empate, se usará el último created_at.
    $sql = "SELECT nom, MAX(correctes) AS correctes FROM scores GROUP BY nom ORDER BY correctes DESC, nom ASC";
    $stmt = $pdo->query($sql . " LIMIT 10");
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback a fichero
    $path = __DIR__ . '/scores.json';
    $raw = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    $map = [];
    foreach ($raw as $s) {
        $name = isset($s['nom']) ? $s['nom'] : 'anonim';
        $correctes = isset($s['correctes']) ? intval($s['correctes']) : 0;
        $created = isset($s['data']) ? $s['data'] : null;
        if (!isset($map[$name]) || $correctes > $map[$name]['correctes'] || ($correctes == $map[$name]['correctes'] && $created > $map[$name]['created_at'])) {
            $map[$name] = ['nom' => $name, 'total' => isset($s['total']) ? $s['total'] : 0, 'correctes' => $correctes, 'created_at' => $created];
        }
    }
    $scores = array_values($map);
    usort($scores, function($a,$b){ return $b['correctes'] - $a['correctes']; });
    $scores = array_slice($scores, 0, 10);
}
?>
<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>Puntuaciones</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<h1>Puntuaciones (Top 10 por respostes correctes)</h1>
<?php if (empty($scores)): ?>
    <p>No hi ha puntuacions encara.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Pos</th><th>Nom</th><th>Correctes</th></tr>
        <?php $pos = 1; foreach ($scores as $s): ?>
            <tr>
                <td><?php echo $pos++; ?></td>
                <td><?php echo htmlspecialchars($s['nom']); ?></td>
                <td><?php echo htmlspecialchars($s['correctes']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</body>
</html>
