<?php
// cart_action.php - Lógica del carrito de compras en sesión
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar el carrito si no existe
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if ($action === 'add' && $id && is_numeric($id)) {
    try {
        // Verificar existencia y stock de la fruta
        $stmt = $pdo->prepare("SELECT name, stock FROM fruits WHERE id = ?");
        $stmt->execute([$id]);
        $fruit = $stmt->fetch();

        if ($fruit) {
            $current_qty = $_SESSION['cart'][$id] ?? 0;
            
            if ($fruit['stock'] <= 0) {
                $_SESSION['crud_error'] = "Lo sentimos, la fruta '{$fruit['name']}' se encuentra agotada.";
            } elseif ($current_qty >= $fruit['stock']) {
                $_SESSION['crud_error'] = "No puedes agregar más unidades de '{$fruit['name']}'. Límite de existencias alcanzado ({$fruit['stock']} disponibles).";
            } else {
                $_SESSION['cart'][$id] = $current_qty + 1;
                $_SESSION['cart_success'] = "¡Has agregado '{$fruit['name']}' a tu carrito de compras!";
            }
        } else {
            $_SESSION['crud_error'] = "El producto no existe.";
        }
    } catch (\PDOException $e) {
        $_SESSION['crud_error'] = "Error al verificar el stock: " . $e->getMessage();
    }
    
    header("Location: " . $referer);
    exit;
}

if ($action === 'update' && $id && is_numeric($id)) {
    $qty = intval($_GET['qty'] ?? 1);
    
    if ($qty <= 0) {
        unset($_SESSION['cart'][$id]);
    } else {
        try {
            $stmt = $pdo->prepare("SELECT name, stock FROM fruits WHERE id = ?");
            $stmt->execute([$id]);
            $fruit = $stmt->fetch();
            
            if ($fruit) {
                if ($qty > $fruit['stock']) {
                    $_SESSION['cart'][$id] = $fruit['stock'];
                    $_SESSION['crud_error'] = "Cantiad ajustada al límite de stock disponible ({$fruit['stock']} uds) para '{$fruit['name']}'.";
                } else {
                    $_SESSION['cart'][$id] = $qty;
                }
            } else {
                unset($_SESSION['cart'][$id]);
            }
        } catch (\PDOException $e) {
            $_SESSION['crud_error'] = "Error al actualizar cantidad: " . $e->getMessage();
        }
    }
    
    header("Location: cart.php");
    exit;
}

if ($action === 'remove' && $id && is_numeric($id)) {
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        $_SESSION['crud_success'] = "Producto retirado del carrito.";
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    $_SESSION['crud_success'] = "Carrito vaciado correctamente.";
    header("Location: cart.php");
    exit;
}

// Redirección por defecto si la acción no es válida
header("Location: index.php");
exit;
?>
