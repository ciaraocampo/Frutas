<?php
// ticket.php - Impresión del recibo / ticket de venta
require_once 'db.php';

$order_id = $_GET['order_id'] ?? null;
if (!$order_id || !is_numeric($order_id)) {
    header("Location: index.php");
    exit;
}

try {
    // 1. Obtener la información general del pedido
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['crud_error'] = "El pedido no existe.";
        header("Location: index.php");
        exit;
    }

    // 2. Obtener los productos comprados en este pedido
    $stmtItems = $pdo->prepare("
        SELECT oi.*, f.name 
        FROM order_items oi 
        JOIN fruits f ON oi.fruit_id = f.id 
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$order_id]);
    $items = $stmtItems->fetchAll();

} catch (\PDOException $e) {
    die("Error al consultar el ticket: " . htmlspecialchars($e->getMessage()));
}

require_once 'header.php';
?>

<div class="ticket-container">
    <div class="ticket-actions no-print" style="max-width: 420px; margin: 0 auto 1.5rem; display: flex; gap: 1rem;">
        <a href="index.php" class="btn-secondary" style="margin: 0; padding: 0.6rem 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver
        </a>
        <button onclick="window.print()" class="btn-primary" style="margin: 0; padding: 0.6rem 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.618 0-1.113-.493-1.12-1.112L6.34 18m11.32 0a3 3 0 0 0 3-3V9a3 3 0 0 0-3-3H6.34a3 3 0 0 0-3 3v6a3 3 0 0 0 3 3m12-9V3.75a2.25 2.25 0 0 0-2.25-2.25h-5.25A2.25 2.25 0 0 0 8.25 3.75V6m0 0h11.25" />
            </svg>
            Imprimir Ticket
        </button>
    </div>

    <!-- El Ticket de Compra Estilizado -->
    <div class="ticket-box">
        <div class="ticket-header">
            <h3>FRUTERA PREMIUM</h3>
            <p>AV. DE LA FRESCURA 101, CIUDAD FRUTAL</p>
            <p>RFC: FPR-090822-AAA</p>
            <p>TEL: 555-FRUTAS</p>
            <div class="ticket-divider"></div>
        </div>

        <div class="ticket-info">
            <p><strong>TICKET ID:</strong> #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
            <p><strong>FECHA:</strong> <?php echo date('d/m/Y H:i:s', strtotime($order['created_at'])); ?></p>
            <p><strong>CLIENTE:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <div class="ticket-divider"></div>
        </div>

        <div class="ticket-items">
            <div class="ticket-item-row header">
                <span class="col-desc">DESCRIPCIÓN</span>
                <span class="col-qty">CANT</span>
                <span class="col-price">P.U.</span>
                <span class="col-total">TOTAL</span>
            </div>
            <div class="ticket-divider-thin"></div>
            
            <?php foreach ($items as $item): ?>
                <div class="ticket-item-row">
                    <span class="col-desc"><?php echo htmlspecialchars(mb_strtoupper($item['name'], 'UTF-8')); ?></span>
                    <span class="col-qty"><?php echo $item['quantity']; ?></span>
                    <span class="col-price">$<?php echo number_format($item['price'], 2); ?></span>
                    <span class="col-total">$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            
            <div class="ticket-divider"></div>
        </div>

        <div class="ticket-total">
            <div class="total-row">
                <span>SUBTOTAL:</span>
                <span>$<?php echo number_format($order['total'] / 1.16, 2); ?></span>
            </div>
            <div class="total-row">
                <span>IVA (16%):</span>
                <span>$<?php echo number_format($order['total'] - ($order['total'] / 1.16), 2); ?></span>
            </div>
            <div class="ticket-divider-thin"></div>
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>$<?php echo number_format($order['total'], 2); ?></span>
            </div>
            <div class="ticket-divider"></div>
        </div>

        <div class="ticket-footer">
            <p>¡GRACIAS POR COMPRAR CON NOSOTROS!</p>
            <p>FRUTERA PREMIUM CUIDA TU SALUD</p>
            
            <!-- Representación visual de código de barras para realismo -->
            <div class="barcode">
                || | |||| | || ||| || ||| ||| | |||
                <p>*FRUTERA-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>*</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
