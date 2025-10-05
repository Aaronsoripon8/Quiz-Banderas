<?php
/**
 * conexio.php
 * Función utilitaria para obtener una conexión PDO a la base de datos.
 *
 * Este archivo lee la configuración desde `config.php` (si existe)
 * o desde variables de entorno (getenv). Devuelve una instancia PDO o lanza
 * una excepción si hay un error.
 *
 * Instrucciones de uso:
 * - Crea `api/config.php` a partir de `api/config.sample.php` con tus credenciales
 *   o define las variables de entorno correspondientes.
 * - Incluye este archivo en otros scripts y llama a getPDO() para obtener la conexión.
 */

// Cargar configuración si existe
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

// Función que retorna las variables de configuración (con fallback a getenv)
function dbConfig() {
    // Preferimos constantes definidas en config.php, si existen
    $cfg = [];
    $cfg['driver']  = defined('DB_DRIVER')  ? DB_DRIVER  : (getenv('DB_DRIVER') ?: 'mysql');
    $cfg['host']    = defined('DB_HOST')    ? DB_HOST    : (getenv('DB_HOST') ?: '127.0.0.1');
    $cfg['port']    = defined('DB_PORT')    ? DB_PORT    : (getenv('DB_PORT') ?: '3306');
    $cfg['dbname']  = defined('DB_NAME')    ? DB_NAME    : (getenv('DB_NAME') ?: '');
    $cfg['user']    = defined('DB_USER')    ? DB_USER    : (getenv('DB_USER') ?: 'root');
    $cfg['pass']    = defined('DB_PASS')    ? DB_PASS    : (getenv('DB_PASS') ?: '');
    $cfg['charset'] = defined('DB_CHARSET') ? DB_CHARSET : (getenv('DB_CHARSET') ?: 'utf8mb4');
    $cfg['options'] = defined('DB_OPTIONS') ? DB_OPTIONS : null; // opcional
    return $cfg;
}

/**
 * getPDO
 * Devuelve una conexión PDO configurada.
 *
 * @return PDO
 * @throws PDOException
 */
function getPDO() {
    $c = dbConfig();

    if (!$c['dbname']) {
        throw new Exception('DB_NAME no está configurado. Crea api/config.php a partir de config.sample.php o define la variable de entorno DB_NAME.');
    }

    $driver = strtolower($c['driver']);

    // Construir DSN para MySQL/MariaDB
    if ($driver === 'mysql' || $driver === 'mysqli' || $driver === 'mariadb') {
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', 'mysql', $c['host'], $c['port'], $c['dbname'], $c['charset']);
    } else {
        // Para otros drivers, el usuario puede ajustar aquí
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $driver, $c['host'], $c['port'], $c['dbname']);
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // Permitir a config.php pasar opciones extra
    if (is_array($c['options'])) {
        $options = $c['options'] + $options;
    }

    // Opcional: usar conexión persistente si se define la constante DB_PERSISTENT
    if (defined('DB_PERSISTENT') && DB_PERSISTENT) {
        $options[PDO::ATTR_PERSISTENT] = true;
    }

    // Crear y devolver PDO
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
        return $pdo;
    } catch (PDOException $e) {
        // No hacer dump de credenciales; registrar un mensaje útil en el log
        error_log('DB connection error: ' . $e->getMessage());
        throw $e;
    }
}

// Si se desea, se puede hacer una pequeña prueba rápida cuando se ejecuta directamente (opcional)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0])) {
    try {
        $pdo = getPDO();
        echo "Conexión a la BD correcta\n";
    } catch (Exception $e) {
        echo "Error de conexión: " . $e->getMessage() . "\n";
        exit(1);
    }
}
