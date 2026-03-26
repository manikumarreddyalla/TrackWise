<?php
declare(strict_types=1);

function trackwiseEnv(string $key, string $default): string
{
    $value = getenv($key);
    if ($value === false || trim($value) === '') {
        return $default;
    }

    return trim($value);
}

$host = trackwiseEnv('TRACKWISE_DB_HOST', '127.0.0.1');
$dbName = trackwiseEnv('TRACKWISE_DB_NAME', 'trackwise');
$dbUser = trackwiseEnv('TRACKWISE_DB_USER', 'root');
$dbPass = trackwiseEnv('TRACKWISE_DB_PASS', '');
$charset = trackwiseEnv('TRACKWISE_DB_CHARSET', 'utf8mb4');
$connectTimeout = max(1, (int) trackwiseEnv('TRACKWISE_DB_CONNECT_TIMEOUT', '2'));
$portsRaw = trackwiseEnv('TRACKWISE_DB_PORTS', trackwiseEnv('TRACKWISE_DB_PORT', '3306,3307'));
$ports = array_values(array_filter(array_map('trim', explode(',', $portsRaw)), static fn (string $port): bool => $port !== ''));
if ($ports === []) {
    $ports = ['3306', '3307'];
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => $connectTimeout,
];

$lastException = null;
$connectedDbPort = null;

foreach ($ports as $port) {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        $connectedDbPort = $port;
        break;
    } catch (PDOException $exception) {
        $lastException = $exception;
    }
}

if (!isset($pdo)) {
    http_response_code(500);
    error_log('TrackWise DB connection failed: ' . ($lastException?->getMessage() ?? 'Unknown error'));
    exit('Database connection failed. Update TRACKWISE_DB_* values in backend/dbConnection.php.');
}
