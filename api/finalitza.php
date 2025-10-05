<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$respostes = isset($input['respostes']) ? $input['respostes'] : [];

// Validar contra la base de datos
try {
    require_once __DIR__ . '/conexio.php';
    $pdo = getPDO();
    // Construir mapa id -> resposta_correcta para las preguntas enviadas
    $ids = array_unique(array_map(function($r){ return isset($r['idPregunta']) ? intval($r['idPregunta']) : 0; }, $respostes));
    $ids = array_filter($ids, function($v){ return $v > 0; });
    $map = [];
    if (!empty($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, resposta_correcta FROM questions WHERE id IN ($in)");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $map[intval($row['id'])] = isset($row['resposta_correcta']) ? intval($row['resposta_correcta']) : null;
        }
    }
    $total = count($respostes);
    $correctes = 0;
    foreach ($respostes as $r) {
        $idPregunta = isset($r['idPregunta']) ? intval($r['idPregunta']) : null;
        $resposta = isset($r['resposta']) ? intval($r['resposta']) : null;
        if ($idPregunta !== null && isset($map[$idPregunta])) {
            if ($map[$idPregunta] == $resposta) $correctes++;
        }
    }
    echo json_encode(['total' => $total, 'correctes' => $correctes]);
    exit;
} catch (Exception $e) {
    error_log('finalitza DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la validación de respuestas']);
    exit;
}
