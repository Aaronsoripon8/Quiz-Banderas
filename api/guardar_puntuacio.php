<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$nom = isset($input['nom']) ? $input['nom'] : 'anonim';
$total = isset($input['total']) ? intval($input['total']) : 0;
$correctes = isset($input['correctes']) ? intval($input['correctes']) : 0;

// Intentar guardar en la BD si conexio.php y config están disponibles
$dbSaved = false;
$dbError = null;
try {
	require_once __DIR__ . '/conexio.php';
	$pdo = getPDO();
	$stmt = $pdo->prepare('INSERT INTO scores (nom, total, correctes) VALUES (:nom, :total, :correctes)');
	$stmt->execute([':nom' => $nom, ':total' => $total, ':correctes' => $correctes]);
	$dbSaved = true;
	echo json_encode(['ok' => true, 'saved_to_db' => true]);
	exit;
} catch (Exception $e) {
	// BD no disponible o error -> fallback a fichero
	$dbError = $e->getMessage();
	error_log('guardar_puntuacio DB error: ' . $dbError);
}

$path = __DIR__ . '/scores.json';
$scores = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
if (!is_array($scores)) $scores = [];
$entry = ['nom' => $nom, 'total' => $total, 'correctes' => $correctes, 'data' => date('c')];
$scores[] = $entry;
file_put_contents($path, json_encode($scores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
// Devolver información al cliente; incluir mensaje de error de BD (sanitizado) cuando falle el guardado en BD
$response = ['ok' => true, 'saved_to_db' => false];
if ($dbError) {
    // incluir un mensaje corto de error para ayudar en debugging (no revelar credenciales)
    $response['db_error'] = substr($dbError, 0, 200);
}
echo json_encode($response);
