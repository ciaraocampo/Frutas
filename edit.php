<?php
// edit.php - Formulario para editar una fruta existente (Privado)
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
if (!$id || !is_numeric($id)) {
    $_SESSION['crud_error'] = "Identificador de fruta no válido.";
    header("Location: dashboard.php");
    exit;
}

// Obtener los datos actuales de la fruta
try {
    $stmt = $pdo->prepare("SELECT * FROM fruits WHERE id = ?");
    $stmt->execute([$id]);
    $fruit = $stmt->fetch();
    
    if (!$fruit) {
        $_SESSION['crud_error'] = "La fruta que intentas editar no existe.";
        header("Location: dashboard.php");
        exit;
    }
} catch (\PDOException $e) {
    $_SESSION['crud_error'] = "Error al consultar la fruta: " . $e->getMessage();
    header("Location: dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_path = $fruit['image_path']; // Conservar por defecto la imagen actual

    // Validar campos requeridos
    if (empty($name)) {
        $errors[] = "El nombre de la fruta es obligatorio.";
    }
    if ($price === '' || !is_numeric($price) || floatval($price) < 0) {
        $errors[] = "El precio debe ser un número positivo.";
    }
    if ($stock === '' || !is_numeric($stock) || intval($stock) < 0) {
        $errors[] = "El stock debe ser un número entero mayor o igual a 0.";
    }

    // Procesar nueva imagen si se sube una
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Formato de imagen no permitido. Solo se aceptan JPG, JPEG, PNG y WEBP.";
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "La imagen supera el tamaño máximo de 2 MB.";
        }

        if (empty($errors)) {
            // Eliminar la imagen anterior si existe físicamente
            if (!empty($fruit['image_path']) && file_exists($fruit['image_path'])) {
                unlink($fruit['image_path']);
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('fruit_', true) . '.' . $ext;
            $dest_path = 'uploads/' . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                $image_path = $dest_path;
            } else {
                $errors[] = "Error al guardar la imagen en el servidor.";
            }
        }
    }

    // Actualizar en base de datos si no hay errores
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE fruits SET name = ?, price = ?, stock = ?, description = ?, image_path = ? WHERE id = ?");
            if ($stmt->execute([$name, floatval($price), intval($stock), $description, $image_path, $id])) {
                $_SESSION['crud_success'] = "¡Fruta '{$name}' actualizada con éxito!";
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Hubo un error al intentar actualizar los datos.";
            }
        } catch (\PDOException $e) {
            $errors[] = "Error en la consulta de actualización: " . $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<div class="form-container wide">
    <div class="form-header">
        <h2>Editar Fruta</h2>
        <p>Modifica los datos de la fruta seleccionada</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
            <div>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Nombre de la Fruta *</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $fruit['name']); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="price">Precio por kg ($) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['price'] ?? $fruit['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="stock">Existencias (Unidades) *</label>
                <input type="number" id="stock" name="stock" min="0" step="1" value="<?php echo htmlspecialchars($_POST['stock'] ?? $fruit['stock']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Descripción Detallada</label>
            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? $fruit['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Imagen del Producto</label>
            
            <!-- Mostrar imagen actual si existe -->
            <?php 
            $has_img = !empty($fruit['image_path']) && file_exists($fruit['image_path']);
            if ($has_img): 
            ?>
                <div style="margin-bottom: 1rem; text-align: center;">
                    <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.5rem;">Imagen actual:</p>
                    <img src="<?php echo htmlspecialchars($fruit['image_path']); ?>" alt="Fruta" style="max-width: 120px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); object-fit: cover;">
                </div>
            <?php endif; ?>

            <div class="file-upload-wrapper">
                <div class="file-upload-info">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" id="upload-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5h10.5a4.5 4.5 0 004.5-4.5V9a2.25 2.25 0 00-2.25-2.25h-3.75L13 3H11L8.25 6.75H4.5A2.25 2.25 0 002.25 9v6z" />
                    </svg>
                    <p id="upload-text">Selecciona una nueva imagen para cambiarla (opcional)</p>
                    <span style="font-size: 0.8rem; color: var(--text-light);">Formatos: JPG, PNG, WEBP. Máx. 2MB</span>
                </div>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <!-- Vista previa para la nueva imagen cargada en cliente -->
            <div class="img-preview-container" id="preview-container">
                <img id="img-preview" src="#" alt="Vista previa de la nueva imagen">
            </div>
        </div>

        <div class="form-actions">
            <a href="dashboard.php" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
