<?php
declare(strict_types=1);

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

$result = $conn->query('SELECT id, name, email FROM students ORDER BY id DESC');
if (!$result) {
    http_response_code(500);
    exit('Query failed: ' . htmlspecialchars($conn->error));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            background: #f6f8fb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #d7deea;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #d7deea;
            text-align: left;
        }

        th {
            background: #ebf1fb;
        }
    </style>
</head>
<body>
    <h2>Students</h2>
    <p><a href="student-form.html">Add New Student</a></p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="3">No records found.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$result->free();
$conn->close();
