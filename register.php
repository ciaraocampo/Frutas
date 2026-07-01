<?php
// register.php - Registro de nuevos usuarios
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado, redirigir al catálogo
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validaciones básicas
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "Todos los campos son obligatorios.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato de correo electrónico no es válido.";
    }

    if (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    // Verificar si el usuario o email ya existen
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = "El nombre de usuario o el correo electrónico ya están registrados.";
            }
        } catch (\PDOException $e) {
            $errors[] = "Error al verificar disponibilidad: " . $e->getMessage();
        }
    }

    // Registrar al usuario
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $_SESSION['register_success'] = "¡Registro exitoso! Ya puedes iniciar sesión con tus credenciales.";
                header("Location: login.php");
                exit;
            } else {
                $errors[] = "Hubo un problema al registrar la cuenta. Inténtalo de nuevo.";
            }
        } catch (\PDOException $e) {
            $errors[] = "Error al guardar el usuario: " . $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<div class="auth-container">
    <div class="auth-header">
        <h2>Crear Cuenta</h2>
        <p>Regístrate para gestionar el inventario de frutas</p>
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

    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="username">Nombre de Usuario</label>
            <input type="text" id="username" name="username" placeholder="Ej. juan123" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autocomplete="username">
        </div>

        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" placeholder="Ej. juan@correo.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email">
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite tu contraseña" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 1rem;">Registrarme</button>
    </form>

    <div class="auth-footer">
        ¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión aquí</a>
    </div>
</div>

<?php require_once 'footer.php'; ?>
