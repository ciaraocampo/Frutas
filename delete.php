<?php
// delete.php - Backend para eliminar una fruta (Privado)
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteger ruta
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;

if ($id && is_numeric($id)) {
    try {
        // Obtener los datos actuales para poder borrar la imagen asociada
        $stmt = $pdo->prepare("SELECT name, image_path FROM fruits WHERE id = ?");
        $stmt->execute([$id]);
        $fruit = $stmt->fetch();

        if ($fruit) {
            // Borrar archivo físico de la imagen
            if (!empty($fruit['image_path']) && file_exists($fruit['image_path'])) {
                unlink($fruit['image_path']);
            }

            // Borrar registro de la base de datos
            $deleteStmt = $pdo->prepare("DELETE FROM fruits WHERE id = ?");
            if ($deleteStmt->execute([$id])) {
                $_SESSION['crud_success'] = "¡La fruta '{$fruit['name']}' ha sido eliminada exitosamente del sistema!";
            } else {
                $_SESSION['crud_error'] = "No se pudo eliminar la fruta de la base de datos.";
            }
        } else {
            $_SESSION['crud_error'] = "La fruta seleccionada no existe.";
        }
    } catch (\PDOException $e) {
        $_SESSION['crud_error'] = "Error al intentar eliminar la fruta: " . $e->getMessage();
    }
} else {
    $_SESSION['crud_error'] = "Identificador de fruta no válido.";
}

header("Location: dashboard.php");
exit;
?>
