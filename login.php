<?php
// login.php - Inicio de sesión de usuarios
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

// Recuperar mensaje de registro exitoso
if (isset($_SESSION['register_success'])) {
    $success_msg = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $errors[] = "Todos los campos son obligatorios.";
    } else {
        try {
            // Permitimos iniciar sesión tanto con usuario como con correo electrónico
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Iniciar la sesión de forma segura
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Usuario, correo o contraseña incorrectos.";
            }
        } catch (\PDOException $e) {
            $errors[] = "Error al intentar iniciar sesión: " . $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<div class="auth-container">
    <div class="auth-header">
        <h2>Iniciar Sesión</h2>
        <p>Ingresa para administrar el sistema de frutas</p>
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
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username_email">Usuario o Correo Electrónico</label>
            <input type="text" id="username_email" name="username_email" placeholder="Ej. juan123 o juan@correo.com" value="<?php echo htmlspecialchars($_POST['username_email'] ?? ''); ?>" required autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 1rem;">Ingresar</button>
    </form>

    <div class="auth-footer">
        ¿Aún no tienes cuenta? <a href="register.php">Regístrate gratis aquí</a>
    </div>
</div>

<?php require_once 'footer.php'; ?>
