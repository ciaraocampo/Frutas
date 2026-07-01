<?php
// cart.php - Vista y procesamiento del Carrito de Compras
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$success_msg = '';

if (isset($_SESSION['crud_success'])) {
    $success_msg = $_SESSION['crud_success'];
    unset($_SESSION['crud_success']);
}
if (isset($_SESSION['crud_error'])) {
    $errors[] = $_SESSION['crud_error'];
    unset($_SESSION['crud_error']);
}

// 1. Lógica de Finalizar Compra (Checkout)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'checkout') {
    if (empty($_SESSION['cart'])) {
        $errors[] = "No puedes realizar una compra con el carrito vacío.";
    } else {
        // Nombre del cliente
        $customer_name = 'Cliente General';
        $user_id = null;

        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $customer_name = $_SESSION['username'];
        } else {
            $guest_name = trim($_POST['guest_name'] ?? '');
            if (!empty($guest_name)) {
                $customer_name = $guest_name;
            }
        }

        // Obtener detalles de productos para calcular totales y validar stock en tiempo real
        $cart_ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM fruits WHERE id IN ($placeholders)");
            $stmt->execute($cart_ids);
            $db_fruits = $stmt->fetchAll(PDO::FETCH_UNIQUE);
            
            // Validar stock antes de iniciar la transacción
            $total_order = 0;
            $items_to_save = [];

            foreach ($_SESSION['cart'] as $fid => $qty) {
                if (!isset($db_fruits[$fid])) {
                    $errors[] = "Uno de los productos en tu carrito ya no existe.";
                    break;
                }
                
                $fruit = $db_fruits[$fid];
                if ($fruit['stock'] < $qty) {
                    $errors[] = "No hay suficiente stock de '{$fruit['name']}' (solicitado: {$qty}, disponible: {$fruit['stock']}).";
                    break;
                }

                $subtotal = $fruit['price'] * $qty;
                $total_order += $subtotal;
                
                $items_to_save[] = [
                    'fruit_id' => $fid,
                    'quantity' => $qty,
                    'price' => $fruit['price']
                ];
            }

            // Si todo está correcto, proceder con la transacción en la base de datos
            if (empty($errors)) {
                $pdo->beginTransaction();

                // 1. Insertar el pedido
                $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, customer_name, total) VALUES (?, ?, ?)");
                $stmtOrder->execute([$user_id, $customer_name, $total_order]);
                $order_id = $pdo->lastInsertId();

                // 2. Insertar ítems del pedido y descontar stock
                $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, fruit_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmtUpdateStock = $pdo->prepare("UPDATE fruits SET stock = stock - ? WHERE id = ?");

                foreach ($items_to_save as $item) {
                    // Guardar detalle
                    $stmtItem->execute([
                        $order_id,
                        $item['fruit_id'],
                        $item['quantity'],
                        $item['price']
                    ]);

                    // Descontar stock
                    $stmtUpdateStock->execute([
                        $item['quantity'],
                        $item['fruit_id']
                    ]);
                }

                // Confirmar transacción
                $pdo->commit();

                // Vaciar el carrito de la sesión
                $_SESSION['cart'] = [];
                
                // Redirigir al ticket generado
                header("Location: ticket.php?order_id=" . $order_id);
                exit;
            }

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Error al procesar la compra: " . $e->getMessage();
        }
    }
}

// 2. Cargar productos actuales del carrito para mostrarlos en la vista
$cart_items = [];
$total_cart = 0;

if (!empty($_SESSION['cart'])) {
    $cart_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM fruits WHERE id IN ($placeholders)");
        $stmt->execute($cart_ids);
        $fruits_list = $stmt->fetchAll();

        foreach ($fruits_list as $f) {
            $qty = $_SESSION['cart'][$f['id']];
            $subtotal = $f['price'] * $qty;
            $total_cart += $subtotal;
            
            $cart_items[] = [
                'id' => $f['id'],
                'name' => $f['name'],
                'price' => $f['price'],
                'image_path' => $f['image_path'],
                'stock' => $f['stock'],
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
        }
    } catch (\PDOException $e) {
        $errors[] = "Error al cargar los artículos del carrito: " . $e->getMessage();
    }
}

require_once 'header.php';
?>

<div style="margin-bottom: 2rem;">
    <h2>Tu Carrito de Compras</h2>
    <p>Revisa tus artículos antes de imprimir tu ticket de compra</p>
</div>

<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5z" clip-rule="evenodd" />
        </svg>
        <div>
            <p><?php echo htmlspecialchars($success_msg); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
        </svg>
        <div>
            <?php foreach ($errors as $err): ?>
                <p><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($cart_items)): ?>
    <div class="no-results" style="padding: 5rem 2rem;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 72px; height: 72px; margin-bottom: 1.5rem;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.116 60.116 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
        </svg>
        <h3>Tu carrito está vacío</h3>
        <p style="margin-bottom: 2rem;">Añade frutas frescas desde nuestro catálogo para poder realizar una compra.</p>
        <a href="index.php" class="btn-primary" style="display: inline-block; width: auto; padding: 0.8rem 2rem;">Volver al Catálogo</a>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start; flex-wrap: wrap;">
        <!-- Detalles del Carrito -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Imagen</th>
                        <th>Fruta</th>
                        <th>Precio</th>
                        <th style="width: 130px; text-align: center;">Cantidad</th>
                        <th>Subtotal</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <?php 
                                $has_img = !empty($item['image_path']) && file_exists($item['image_path']);
                                if ($has_img): 
                                ?>
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="Fruta" class="table-img">
                                <?php else: ?>
                                    <div class="table-img" style="display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-light); font-size: 0.7rem; text-align: center; border: 1px solid var(--border-color);">
                                        Sin Foto
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <a href="cart_action.php?action=update&id=<?php echo $item['id']; ?>&qty=<?php echo $item['qty'] - 1; ?>" class="btn-action" style="padding: 0.2rem 0.5rem; background: #e2e8f0; border-radius: 4px;">-</a>
                                    <span style="font-weight: 600; min-width: 24px; display: inline-block; text-align: center;"><?php echo $item['qty']; ?></span>
                                    <a href="cart_action.php?action=update&id=<?php echo $item['id']; ?>&qty=<?php echo $item['qty'] + 1; ?>" class="btn-action" style="padding: 0.2rem 0.5rem; background: #e2e8f0; border-radius: 4px;">+</a>
                                </div>
                            </td>
                            <td style="font-weight: 600;">$<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td>
                                <a href="cart_action.php?action=remove&id=<?php echo $item['id']; ?>" class="btn-action btn-delete" style="padding: 0.4rem;" title="Eliminar artículo">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background-color: #fafbfc; border-top: 1px solid var(--border-color);">
                <a href="cart_action.php?action=clear" class="btn-secondary" style="width: auto; padding: 0.6rem 1.2rem; font-size: 0.9rem;" onclick="return confirm('¿Seguro que deseas vaciar todo tu carrito?')">Vaciar Carrito</a>
                <a href="index.php" class="btn-secondary" style="width: auto; padding: 0.6rem 1.2rem; font-size: 0.9rem;">+ Seguir Comprando</a>
            </div>
        </div>

        <!-- Panel de Checkout -->
        <div style="background: var(--card-bg); border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2rem; box-shadow: var(--shadow-md);">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Resumen de Compra</h3>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1rem;">
                <span style="color: var(--text-light);">Artículos totales:</span>
                <span style="font-weight: 600;"><?php echo array_sum($_SESSION['cart']); ?></span>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; font-size: 1.3rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <strong>Total General:</strong>
                <strong style="color: var(--text-color);">$<?php echo number_format($total_cart, 2); ?></strong>
            </div>

            <!-- Formulario de Finalización -->
            <form action="cart.php?action=checkout" method="POST">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="background-color: rgba(46, 196, 182, 0.05); border: 1px solid var(--primary-color); border-radius: var(--radius-sm); padding: 1rem; margin-bottom: 1.5rem; font-size: 0.9rem;">
                        <span style="color: var(--primary-dark); font-weight: 600;">Compra Registrada</span>
                        <p style="margin-top: 0.25rem;">Se emitirá a nombre del usuario activo: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>.</p>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="guest_name">Nombre de Cliente (Opcional)</label>
                        <input type="text" id="guest_name" name="guest_name" placeholder="Ej. Pedro Pérez" style="padding: 0.6rem 0.8rem; font-size: 0.9rem;">
                    </div>
                    
                    <div style="background-color: rgba(255, 159, 28, 0.05); border: 1px dashed var(--secondary-color); border-radius: var(--radius-sm); padding: 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: #b77a1c;">
                        <strong>Tip del Día:</strong> Si <a href="login.php" style="text-decoration: underline; font-weight: 600;">inicias sesión</a> antes de comprar, tus recibos quedarán guardados en tu historial.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-primary" style="width: 100%;">Finalizar Compra</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
