<?php
$host = getenv('DB_HOST') ?: 'mysql';
$db = getenv('DB_NAME') ?: 'capstonedb';
$user = getenv('DB_USER') ?: 'capstoneuser';
$pass = getenv('DB_PASSWORD') ?: '';
$error = null;
$messages = [];
try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $pdo->exec('CREATE TABLE IF NOT EXISTS messages (id INT AUTO_INCREMENT PRIMARY KEY, message VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = trim($_POST['message'] ?? '');
        if ($message !== '') {
            $stmt = $pdo->prepare('INSERT INTO messages (message) VALUES (?)');
            $stmt->execute([substr($message, 0, 255)]);
        }
        header('Location: /');
        exit;
    }
    $messages = $pdo->query('SELECT message, created_at FROM messages ORDER BY id DESC LIMIT 20')->fetchAll();
} catch (Throwable $e) {
    http_response_code(503);
    $error = 'Database connection is not ready. Please retry shortly.';
    error_log($e->getMessage());
}
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>AWS PHP CI/CD Capstone</title>
<style>body{font-family:system-ui,Arial;background:#f4f7fb;color:#17243b;margin:0;padding:50px 18px}.wrap{max-width:750px;margin:auto;background:white;padding:30px;border-radius:16px;box-shadow:0 10px 32px #2233 551a}h1{color:#173a72}input{padding:12px;width:70%;box-sizing:border-box}button{padding:12px;background:#1662c5;color:white;border:0;border-radius:5px}.tag{color:#17845d}.error{color:#bb3030}li{padding:12px;border-bottom:1px solid #eee}</style></head><body><main class="wrap"><h1>3-Tier PHP Application on AWS</h1><p>GitHub &rarr; Jenkins &rarr; ECR &rarr; Helm &rarr; Kubernetes &rarr; Prometheus/Grafana</p>
<?php if ($error): ?><p class="error"><?=h($error)?></p><?php else: ?><p class="tag">&#10003; PHP connected to MySQL through the Kubernetes Service.</p><form method="post"><label for="message">Save a message in MySQL:</label><br><input id="message" name="message" maxlength="255" required placeholder="Enter a test message"><button type="submit">Save</button></form><h2>Saved messages</h2><ul><?php foreach ($messages as $row): ?><li><?=h($row['message'])?> <small><?=h($row['created_at'])?></small></li><?php endforeach; ?></ul><?php endif; ?></main></body></html>
