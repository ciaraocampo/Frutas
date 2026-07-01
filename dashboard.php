<?php
// dashboard.php - Panel de Administración CRUD (Privado)
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteger la ruta: solo usuarios logueados pueden acceder
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

if (isset($_SESSION['crud_success'])) {
    $success_msg = $_SESSION['crud_success'];
    unset($_SESSION['crud_success']);
}
if (isset($_SESSION['crud_error'])) {
    $error_msg = $_SESSION['crud_error'];
    unset($_SESSION['crud_error']);
}

// Consultar todas las frutas de la base de datos
try {
    $stmt = $pdo->query("SELECT * FROM fruits ORDER BY id DESC");
    $fruits = $stmt->fetchAll();
} catch (\PDOException $e) {
    $error_msg = "Error al cargar la lista de frutas: " . $e->getMessage();
    $fruits = [];
}

require_once 'header.php';
?>

<div class="dashboard-header">
    <div>
        <h2>Gestión de Frutas</h2>
        <p>Añade, edita o elimina las frutas del catálogo disponible</p>
    </div>
    <a href="add.php" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Nueva Fruta
    </a>
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

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
        </svg>
        <div>
            <p><?php echo htmlspecialchars($error_msg); ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Imagen</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Stock</th>
                <th style="width: 180px; text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($fruits) > 0): ?>
                <?php foreach ($fruits as $fruit): ?>
                    <tr>
                        <td>
                            <?php 
                            $has_img = !empty($fruit['image_path']) && file_exists($fruit['image_path']);
                            if ($has_img): 
                            ?>
                                <img src="<?php echo htmlspecialchars($fruit['image_path']); ?>" alt="Fruta" class="table-img">
                            <?php else: ?>
                                <div class="table-img" style="display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-light); font-size: 0.7rem; text-align: center; border: 1px solid var(--border-color);">
                                    Sin Foto
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($fruit['name']); ?></strong></td>
                        <td>
                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.9rem; color: var(--text-light);" title="<?php echo htmlspecialchars($fruit['description'] ?? ''); ?>">
                                <?php echo htmlspecialchars($fruit['description'] ?: 'Sin descripción'); ?>
                            </div>
                        </td>
                        <td><span style="font-weight: 600;">$<?php echo number_format($fruit['price'], 2); ?></span></td>
                        <td>
                            <?php if ($fruit['stock'] > 10): ?>
                                <span class="table-stock" style="color: var(--success-color);"><?php echo $fruit['stock']; ?> uds.</span>
                            <?php elseif ($fruit['stock'] > 0): ?>
                                <span class="table-stock" style="color: var(--warning-color);"><?php echo $fruit['stock']; ?> uds.</span>
                            <?php else: ?>
                                <span class="table-stock" style="color: var(--error-color); font-weight: 700;">Agotado</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <div class="table-actions">
                                <a href="edit.php?id=<?php echo $fruit['id']; ?>" class="btn-action btn-edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                    </svg>
                                    Editar
                                </a>
                                <a href="delete.php?id=<?php echo $fruit['id']; ?>" class="btn-action btn-delete btn-delete-confirm" data-name="<?php echo htmlspecialchars($fruit['name']); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    Borrar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-light);">
                        No hay frutas registradas en el catálogo. ¡Haz clic en "Nueva Fruta" para añadir la primera!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>
