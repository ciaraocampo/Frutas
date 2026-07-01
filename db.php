<?php
// db.php - Configuración y conexión a la base de datos mediante PDO

$host = 'localhost';
$dbname = 'frutas_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Si la base de datos no existe o no se puede conectar, informamos de forma clara.
    // Esto es muy útil en entornos XAMPP si el usuario olvidó importar el script schema.sql
    die("<h3>Error de Conexión a la Base de Datos</h3>" . 
        "No se pudo conectar a la base de datos. Por favor, asegúrate de:<br>" .
        "1. Tener activo MySQL en XAMPP.<br>" .
        "2. Haber importado el archivo <code>schema.sql</code> para crear la base de datos <code>frutas_db</code>.<br><br>" .
        "Detalle del error: " . htmlspecialchars($e->getMessage()));
}
?>
