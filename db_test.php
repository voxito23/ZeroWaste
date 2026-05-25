<?php
$envPath = __DIR__ . '/laravel_zerowaste/.env';
if (!file_exists($envPath)) {
    die("No .env found");
}

$env = parse_ini_file($envPath);
$host = isset($env['DB_HOST']) ? $env['DB_HOST'] : '127.0.0.1';
$port = isset($env['DB_PORT']) ? $env['DB_PORT'] : '5432';
$db   = isset($env['DB_DATABASE']) ? $env['DB_DATABASE'] : 'forge';
$user = isset($env['DB_USERNAME']) ? $env['DB_USERNAME'] : 'forge';
$pass = isset($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $stmt = $pdo->query("SELECT nombre, imagen_url FROM campaigns LIMIT 10");
    echo "CAMPAIGNS:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - " . $row['nombre'] . " | img: " . $row['imagen_url'] . "\n";
    }

    $stmt = $pdo->query("SELECT nombre, imagen FROM locations LIMIT 10");
    echo "\nLOCATIONS:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - " . $row['nombre'] . " | img: " . $row['imagen'] . "\n";
    }

    $stmt = $pdo->query("SELECT nombre, foto_perfil FROM users LIMIT 3");
    echo "\nUSERS:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - " . $row['nombre'] . " | img: " . $row['foto_perfil'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
