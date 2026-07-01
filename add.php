<?php
// add.php - Formulario para agregar una nueva fruta (Privado)
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteger ruta
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_path = null;

    // Validar campos requeridos
    if (empty($name)) {
        $errors[] = "El nombre de la fruta es obligatorio.";
    }
    if ($price === '' || !is_numeric($price) || floatval($price) < 0) {
        $errors[] = "El precio debe ser un número positivo.";
    }
    if ($stock === '' || !filter_var($stock, FILTER_VALIDATE_INT) === false || intval($stock) < 0) {
        // Corrección de validación de entero:
        if (!is_numeric($stock) || intval($stock) < 0) {
            $errors[] = "El stock debe ser un número entero mayor o igual a 0.";
        }
    }

    // Procesar la subida de imagen
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        
        // Crear carpeta uploads si no existe
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Validar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Formato de imagen no permitido. Solo se aceptan JPG, JPEG, PNG y WEBP.";
        }

        // Validar tamaño (máximo 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "La imagen supera el tamaño máximo permitido de 2 MB.";
        }

        // Mover archivo
        if (empty($errors)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('fruit_', true) . '.' . $ext;
            $dest_path = 'uploads/' . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                $image_path = $dest_path;
            } else {
                $errors[] = "Error al guardar la imagen en el servidor. Inténtalo de nuevo.";
            }
        }
    }

    // Guardar en la base de datos si no hay errores
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO fruits (name, price, stock, description, image_path) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, floatval($price), intval($stock), $description, $image_path])) {
                $_SESSION['crud_success'] = "¡Fruta '{$name}' agregada con éxito!";
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Hubo un error al intentar registrar la fruta en la base de datos.";
            }
        } catch (\PDOException $e) {
            $errors[] = "Error en la consulta de inserción: " . $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<div class="form-container wide">
    <div class="form-header">
        <h2>Nueva Fruta</h2>
        <p>Introduce los datos para publicar la fruta en el catálogo</p>
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

    <form action="add.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Nombre de la Fruta *</label>
            <input type="text" id="name" name="name" placeholder="Ej. Mandarina Clementina" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="price">Precio por kg ($) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" placeholder="Ej. 2.99" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="stock">Existencias (Unidades) *</label>
                <input type="number" id="stock" name="stock" min="0" step="1" placeholder="Ej. 50" value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Descripción Detallada</label>
            <textarea id="description" name="description" rows="4" placeholder="Escribe las características principales de la fruta, beneficios, origen, etc..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Imagen del Producto</label>
            <div class="file-upload-wrapper">
                <div class="file-upload-info">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" id="upload-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                    </svg>
                    <p id="upload-text">Arrastra una imagen o haz clic para buscar</p>
                    <span style="font-size: 0.8rem; color: var(--text-light);">Formatos: JPG, PNG, WEBP. Máx. 2MB</span>
                </div>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <!-- Vista previa de imagen seleccionada -->
            <div class="img-preview-container" id="preview-container">
                <img id="img-preview" src="#" alt="Vista previa de la imagen">
            </div>
        </div>

        <div class="form-actions">
            <a href="dashboard.php" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Guardar Fruta</button>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
