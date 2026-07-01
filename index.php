<?php
// index.php - Catálogo público de frutas
require_once 'db.php';
require_once 'header.php';

// Inicializar variables de búsqueda y filtrado
$search = trim($_GET['search'] ?? '');
$filter_stock = $_GET['filter_stock'] ?? 'all';

// Construir la consulta SQL
$sql = "SELECT * FROM fruits WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($filter_stock === 'in_stock') {
    $sql .= " AND stock > 0";
} elseif ($filter_stock === 'out_of_stock') {
    $sql .= " AND stock = 0";
}

$sql .= " ORDER BY name ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fruits = $stmt->fetchAll();
} catch (\PDOException $e) {
    echo "<div class='alert alert-error'>Error al consultar las frutas: " . htmlspecialchars($e->getMessage()) . "</div>";
    $fruits = [];
}
?>

<!-- Sección Hero / Portada -->
<div class="hero">
    <h1>Frescura Directa a tu <span>Mesa</span></h1>
    <p>Consulta nuestro catálogo exclusivo de frutas frescas importadas y nacionales. Calidad premium garantizada al mejor precio.</p>
</div>

<?php if (isset($_SESSION['cart_success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5z" clip-rule="evenodd" />
        </svg>
        <div>
            <p><?php echo htmlspecialchars($_SESSION['cart_success']); ?></p>
        </div>
    </div>
    <?php unset($_SESSION['cart_success']); ?>
<?php endif; ?>


<!-- Barra de Controles (Buscador y Filtros) -->
<form action="index.php" method="GET" class="controls-bar">
    <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.608 10.608z" />
        </svg>
        <input type="text" id="search-input" name="search" placeholder="Buscar por nombre o descripción..." value="<?php echo htmlspecialchars($search); ?>">
    </div>
    
    <div class="filter-box">
        <select name="filter_stock" onchange="this.form.submit()">
            <option value="all" <?php echo $filter_stock === 'all' ? 'selected' : ''; ?>>Todas las existencias</option>
            <option value="in_stock" <?php echo $filter_stock === 'in_stock' ? 'selected' : ''; ?>>Disponible (En Stock)</option>
            <option value="out_of_stock" <?php echo $filter_stock === 'out_of_stock' ? 'selected' : ''; ?>>Agotado</option>
        </select>
        
        <?php if ($search !== '' || $filter_stock !== 'all'): ?>
            <a href="index.php" class="btn-secondary" style="padding: 0.8rem 1.2rem; display: flex; align-items: center; justify-content: center; height: 100%;">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<!-- Grid de Tarjetas (Cards) de Frutas -->
<div class="fruits-grid">
    <?php if (count($fruits) > 0): ?>
        <?php foreach ($fruits as $fruit): ?>
            <div class="fruit-card">
                <!-- Indicador de Disponibilidad / Stock -->
                <?php if ($fruit['stock'] > 10): ?>
                    <span class="badge-stock in-stock">En Stock (<?php echo $fruit['stock']; ?> u.)</span>
                <?php elseif ($fruit['stock'] > 0): ?>
                    <span class="badge-stock low-stock">Pocas Unidades (<?php echo $fruit['stock']; ?> u.)</span>
                <?php else: ?>
                    <span class="badge-stock out-of-stock">Agotado</span>
                <?php endif; ?>

                <!-- Contenedor de Imagen con Fallback SVG si no existe archivo físico -->
                <div class="fruit-img-container">
                    <?php 
                    $has_img = !empty($fruit['image_path']) && file_exists($fruit['image_path']);
                    if ($has_img): 
                    ?>
                        <img src="<?php echo htmlspecialchars($fruit['image_path']); ?>" alt="<?php echo htmlspecialchars($fruit['name']); ?>">
                    <?php else: ?>
                        <div class="fruit-img-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                            </svg>
                            <span>Sin Imagen</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info de la fruta -->
                <div class="fruit-info">
                    <h3 class="fruit-name"><?php echo htmlspecialchars($fruit['name']); ?></h3>
                    <p class="fruit-desc"><?php echo htmlspecialchars($fruit['description'] ?: 'Fruta seleccionada cuidadosamente para brindarte la mejor calidad y frescura en tu hogar.'); ?></p>
                    
                    <div class="fruit-footer">
                        <div class="fruit-price">$<?php echo number_format($fruit['price'], 2); ?> <span>/ kg</span></div>
                        <a href="cart_action.php?action=add&id=<?php echo $fruit['id']; ?>" class="btn-buy <?php echo $fruit['stock'] == 0 ? 'disabled' : ''; ?>" <?php echo $fruit['stock'] == 0 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                            <?php echo $fruit['stock'] == 0 ? 'Agotado' : 'Comprar'; ?>
                        </a>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-results">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3>No encontramos resultados</h3>
            <p>Intenta cambiar la búsqueda o el filtro seleccionado.</p>
        </div>
    <?php endif; ?>
    
    <!-- Contenedor de no resultados para el filtro rápido en JavaScript -->
    <div id="client-no-results" class="no-results" style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3>No encontramos resultados</h3>
        <p>No se encontraron frutas que coincidan con tu búsqueda en esta página.</p>
    </div>
</div>

<?php require_once 'footer.php'; ?>
