<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student-form.html');
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('TRACKWISE_DB_HOST') ?: '127.0.0.1';
$user = getenv('TRACKWISE_DB_USER') ?: 'root';
$pass = getenv('TRACKWISE_DB_PASS') ?: '';
$db = getenv('TRACKWISE_STUDENT_DB_NAME') ?: 'student_db';
$portsRaw = getenv('TRACKWISE_DB_PORTS') ?: '3306,3307';
$ports = array_values(array_filter(array_map('trim', explode(',', $portsRaw)), static fn (string $port): bool => $port !== ''));
if ($ports === []) {
    $ports = ['3306', '3307'];
}

$conn = null;
$lastError = '';
foreach ($ports as $port) {
    $mysqli = new mysqli($host, $user, $pass, $db, (int) $port);
    if ($mysqli->connect_error === null) {
        $conn = $mysqli;
        break;
    }
    $lastError = $mysqli->connect_error;
}

if (!$conn) {
    http_response_code(500);
    exit('Connection failed: ' . htmlspecialchars($lastError));
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($name === '' || $email === '') {
    http_response_code(400);
    exit('Name and email are required.');
}

$stmt = $conn->prepare('INSERT INTO students (name, email) VALUES (?, ?)');
if (!$stmt) {
    http_response_code(500);
    exit('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param('ss', $name, $email);
$ok = $stmt->execute();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Result</title>
</head>
<body>
<?php if ($ok): ?>
    <p>Data inserted successfully.</p>
<?php else: ?>
    <p>Error: <?php echo htmlspecialchars($stmt->error); ?></p>
<?php endif; ?>

<p><a href="student-form.html">Back to Form</a></p>
<p><a href="display.php">View Students</a></p>
</body>
</html>
<?php
$stmt->close();
$conn->close();
