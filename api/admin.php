<?php
// Admin sencillo para gestionar preguntas en la tabla `questions` (sin autenticación).
// Permite listar, crear, editar y borrar preguntas. Las respuestas se guardan como JSON.
require_once __DIR__ . '/conexio.php';
$msg = '';
$error = '';
try {
    $pdo = getPDO();
} catch (Exception $e) {
    $error = 'No se ha podido conectar a la base de datos: ' . $e->getMessage();
}

// Gestionar acciones POST: guardar o eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $action = isset($_POST['action']) ? $_POST['action'] : 'save';
    if ($action === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare('DELETE FROM questions WHERE id = ?');
        $stmt->execute([$id]);
        $msg = 'Pregunta eliminada.';
    } elseif ($action === 'save') {
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
        $pregunta = isset($_POST['pregunta']) ? trim($_POST['pregunta']) : '';
        $imatge = isset($_POST['imatge']) ? trim($_POST['imatge']) : null;
        $respostes_raw = isset($_POST['respostes']) ? trim($_POST['respostes']) : '[]';
        $resposta_correcta = isset($_POST['resposta_correcta']) && $_POST['resposta_correcta'] !== '' ? intval($_POST['resposta_correcta']) : null;
        $decoded = json_decode($respostes_raw, true);
        if ($decoded === null || !is_array($decoded)) {
            $error = 'Respuestas no son JSON válido (debe ser un array).';
        } else {
            // si no se ha especificado id, generar uno automático como max(id)+1
            if ($id === null) {
                $row = $pdo->query('SELECT MAX(id) AS m FROM questions')->fetch(PDO::FETCH_ASSOC);
                $id = $row && $row['m'] ? intval($row['m']) + 1 : 1;
            }
            $stmt = $pdo->prepare('REPLACE INTO questions (id, pregunta, imatge, respostes, resposta_correcta) VALUES (:id, :pregunta, :imatge, :respostes, :resposta_correcta)');
            $stmt->execute([
                ':id' => $id,
                ':pregunta' => $pregunta,
                ':imatge' => $imatge,
                ':respostes' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
                ':resposta_correcta' => $resposta_correcta
            ]);
            $msg = 'Pregunta guardada.';
        }
    }
}

// Cargar lista de preguntas
$questions = [];
if (!$error) {
    $stmt = $pdo->query('SELECT id, pregunta, imatge, respostes, resposta_correcta FROM questions ORDER BY id ASC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $r['respostes'] = json_decode($r['respostes'], true) ?: [];
        $questions[] = $r;
    }
}

// Si se solicita editar, cargar la pregunta
$edit = null;
if (isset($_GET['edit']) && !$error) {
    $eid = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT id, pregunta, imatge, respostes, resposta_correcta FROM questions WHERE id = ?');
    $stmt->execute([$eid]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit) $edit['respostes'] = json_decode($edit['respostes'], true) ?: [];
}
?>
<!doctype html>
<html lang="ca">
<head>
<meta charset="utf-8">
<title>Admin Preguntes (BD)</title>
<link rel="stylesheet" href="/styles.css">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
/* Estilos específicos para administración: mayor espaciado, botones visuales y tabla clara */
textarea{width:100%;height:140px;font-family:monospace}
#adminPage{--admin-bg:#f3f9ff;--accent:#1e90ff;--muted:#6b7d8b;--danger:#ff6b6b;--success:#28a745; background:var(--admin-bg); min-height:100vh; color:var(--text,#04263b)}
main{padding:28px 18px}
.admin-section{background:#ffffff;border-radius:12px;padding:18px;box-shadow:0 6px 18px rgba(16,40,60,0.06);margin-bottom:20px;border:1px solid rgba(30,144,255,0.06)}
.admin-section h2{margin-top:0;margin-bottom:12px;font-size:1.15rem}
.admin-form{display:block}
.form-row{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
.form-row label{font-weight:600;color:var(--muted);font-size:0.95rem}
.form-row input[type="text"], .form-row input[type="number"], .form-row input, .form-row textarea{padding:10px;border-radius:8px;border:1px solid #d7eafc;background:#fbfdff;box-shadow:inset 0 1px 0 rgba(255,255,255,0.5)}
.form-actions{display:flex;align-items:center;gap:10px}
.form-actions button{background:var(--accent);color:#fff;border:none;padding:10px 14px;border-radius:8px;cursor:pointer;font-weight:600}
.form-actions a{display:inline-block;padding:8px 12px;border-radius:8px;color:var(--accent);background:transparent;border:1px solid rgba(30,144,255,0.12);text-decoration:none}
.form-actions button:hover{filter:brightness(0.95)}
.admin-table{width:100%;border-collapse:collapse;margin-top:6px}
.admin-table thead th{background:linear-gradient(180deg,#eaf6ff,#dff0ff);padding:10px;text-align:left;border-bottom:1px solid rgba(0,0,0,0.04);font-weight:600;color:#08324a}
.admin-table tbody td{padding:12px;vertical-align:top;border-bottom:1px solid rgba(15,55,90,0.03)}
.admin-table tr:hover td{background:rgba(30,144,255,0.02)}
.actions{display:flex;gap:8px;align-items:center}
.actions a{padding:6px 10px;border-radius:8px;background:#fff;border:1px solid rgba(30,144,255,0.14);color:var(--accent);text-decoration:none;font-weight:600}
.actions a:hover{background:rgba(30,144,255,0.06)}
.actions form{display:inline}
.actions form button{padding:6px 10px;border-radius:8px;background:var(--danger);color:#fff;border:none;cursor:pointer;font-weight:600}
.actions form button:hover{filter:brightness(0.95)}
.success{background:#ebfff0;border:1px solid rgba(40,167,69,0.12);color:#1b6f3a;padding:10px;border-radius:8px}
.error{background:#fff6f6;border:1px solid rgba(255,107,107,0.12);color:#7a1b1b;padding:10px;border-radius:8px}
.small-muted{color:#6b7d8b;font-size:0.9rem}
</style>
  </head>
<body id="adminPage">
<header>
  <div style="max-width:1100px;margin:0 auto;display:flex;justify-content:flex-start;align-items:center;padding:18px 0;">
    <h1 style="margin:0;">Admin: preguntes (base de dades)</h1>
  </div>
</header>
<main style="max-width:1100px;margin:0 auto;">
  <?php if ($msg): ?><div class="success" style="margin-bottom:12px;padding:10px;border-radius:8px;"><strong><?php echo htmlspecialchars($msg); ?></strong></div><?php endif; ?>
  <?php if ($error): ?><div class="error" style="margin-bottom:12px;padding:10px;border-radius:8px;"><strong><?php echo htmlspecialchars($error); ?></strong></div><?php endif; ?>

  <section class="admin-section">
    <h2><?php echo $edit ? 'Editar pregunta #' . intval($edit['id']) : 'Crear nova pregunta'; ?></h2>
    <form method="post" class="admin-form" style="display:grid;gap:12px;">
      <input type="hidden" name="action" value="save">

      <div class="form-row">
        <label for="id_field">ID (dejar en blanco para auto)</label>
        <input id="id_field" name="id" type="number" class="form-input" value="<?php echo $edit ? intval($edit['id']) : ''; ?>">
      </div>

      <div class="form-row">
        <label for="pregunta_field">Pregunta</label>
        <input id="pregunta_field" name="pregunta" type="text" class="form-input" value="<?php echo $edit ? htmlspecialchars($edit['pregunta']) : ''; ?>">
      </div>

      <div class="form-row">
        <label for="imatge_field">Imagen (URL)</label>
        <input id="imatge_field" name="imatge" type="text" class="form-input" value="<?php echo $edit ? htmlspecialchars($edit['imatge']) : ''; ?>">
      </div>

      <div class="form-row">
        <label for="respostes_field">Respuestas (JSON array)</label>
        <div class="responses-grid" style="display:grid;grid-template-columns:60px 1fr;gap:8px 12px;align-items:center;">
          <?php
            // preparar hasta 4 ranuras de respuesta, rellenar con $edit si está presente
            $existing = $edit ? ($edit['respostes'] ?? []) : [];
            for ($i = 0; $i < 4; $i++):
              $r = isset($existing[$i]) ? $existing[$i] : ["id" => ($i+1), "etiqueta" => ""];
          ?>
            <input type="number" name="resp_id_<?php echo $i; ?>" id="resp_id_<?php echo $i; ?>" class="form-input" value="<?php echo htmlspecialchars($r['id']); ?>">
            <input type="text" name="resp_text_<?php echo $i; ?>" id="resp_text_<?php echo $i; ?>" class="form-input" value="<?php echo htmlspecialchars($r['etiqueta']); ?>" placeholder="Resposta <?php echo $i+1; ?>">
          <?php endfor; ?>
        </div>
        <!-- textarea oculto que el servidor espera; el JS lo rellenará antes de enviar -->
        <textarea id="respostes_field" name="respostes" style="display:none"><?php echo $edit ? htmlspecialchars(json_encode($edit['respostes'], JSON_UNESCAPED_UNICODE)) : ''; ?></textarea>
        <div class="small-muted">Rellena els camps de resposta (id i etiqueta). Al prémer "Desar" es generarà automàticament el JSON.</div>
      </div>

      <div class="form-row">
        <label for="correct_field">Id de la resposta correcta</label>
        <input id="correct_field" name="resposta_correcta" type="number" class="form-input" value="<?php echo $edit && isset($edit['resposta_correcta']) ? intval($edit['resposta_correcta']) : ''; ?>">
      </div>

      <div class="form-actions" style="margin-top:6px;">
        <button type="submit">Desar</button>
        <?php if ($edit): ?> <a href="admin.php" style="margin-left:8px;">Crear nova</a><?php endif; ?>
      </div>
    </form>
  </section>

  <section class="admin-section" style="margin-top:20px;">
    <h2>Llista de preguntes</h2>
    <?php if (empty($questions)): ?><p>No hi ha preguntes a la base de dades.</p><?php else: ?>
    <div style="overflow:auto;background:transparent;padding:6px 0;">
    <table class="admin-table" role="table">
      <thead>
        <tr><th>ID</th><th>Pregunta</th><th>Respostes</th><th>Correcta</th><th>Accions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($questions as $q): ?>
        <tr>
            <td><?php echo intval($q['id']); ?></td>
            <td><?php echo htmlspecialchars($q['pregunta']); ?></td>
            <td><?php foreach ($q['respostes'] as $r) echo htmlspecialchars($r['id'] . ': ' . $r['etiqueta']) . '<br>'; ?></td>
            <td><?php echo htmlspecialchars($q['resposta_correcta']); ?></td>
            <td class="actions">
                <a href="?edit=<?php echo intval($q['id']); ?>">Editar</a>
                <form method="post" style="display:inline;margin-left:8px;" onsubmit="return confirm('Segur?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo intval($q['id']); ?>">
                    <button type="submit">Esborrar</button>
                </form>
            </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </section>

  <footer style="margin-top:18px;">
    <p class="small-muted">Proves: <a href="getPreguntes.php?n=5">getPreguntes.php?n=5</a> | <a href="scores.php">Classificació</a></p>
  </footer>
</main>
</body>
</html>
