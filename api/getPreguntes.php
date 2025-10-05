<?php
header('Content-Type: application/json');
$n = isset($_GET['n']) ? intval($_GET['n']) : 10;

// Leer desde la base de datos (exclusivo)
try {
    require_once __DIR__ . '/conexio.php';
    $pdo = getPDO();
    // Seleccionar n preguntas aleatorias. Nota: esta sintaxis funciona con MySQL
    $stmt = $pdo->prepare('SELECT id, pregunta, imatge, respostes, resposta_correcta FROM questions ORDER BY RAND() LIMIT :n');
    $stmt->bindValue(':n', (int)$n, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        // No hay preguntas en la BD: devolver error claro
        http_response_code(404);
        echo json_encode(['error' => 'No hay preguntas en la base de datos']);
        exit;
    }
    $preguntes = [];
    foreach ($rows as $r) {
        $respostes = json_decode($r['respostes'], true);
        $preguntes[] = [
            'id' => (int)$r['id'],
            'pregunta' => $r['pregunta'],
            'imatge' => $r['imatge'],
            'respostes' => $respostes ?: [],
            'resposta_correcta' => isset($r['resposta_correcta']) ? (int)$r['resposta_correcta'] : null
        ];
    }
    echo json_encode(['preguntes' => $preguntes]);
    exit;
} catch (Exception $e) {
    error_log('getPreguntes DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la conexión a la base de datos']);
    exit;
}
