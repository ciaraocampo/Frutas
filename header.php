<?php
// header.php - Cabecera común y barra de navegación de la aplicación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener el nombre del archivo actual para marcar el enlace activo en el menú
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frutera Premium - Tienda de Frutas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="navbar">
            <a href="index.php" class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28" style="color: var(--secondary-color);">
                    <path d="M19.388 5.95a6.015 6.015 0 0 0-3.8-3.061C14.774 2.637 13.9 3.09 13.25 3.65a5.556 5.556 0 0 0-1.25 1.48A5.553 5.553 0 0 0 10.75 3.65c-.65-.56-1.524-1.013-2.338-.76a6.017 6.017 0 0 0-3.8 3.06 6.13 6.13 0 0 0 .738 5.25c1.173 1.83 3.324 3.75 6.275 6.42a.576.576 0 0 0 .75 0c2.951-2.67 5.102-4.59 6.275-6.42a6.13 6.13 0 0 0 .738-5.25Z" />
                    <path d="M12 17.5s-4-3-6-5.5a4 4 0 0 1 6-5.5 4 4 0 0 1 6 5.5c-2 2.5-6 5.5-6 5.5Z" opacity="0.3" />
                </svg>
                Frutera<span>Premium</span>
            </a>
            
            <ul class="nav-links">
                <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Catálogo</a></li>
                <?php
                $cart_count = 0;
                if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                    $cart_count = array_sum($_SESSION['cart']);
                }
                ?>
                <li>
                    <a href="cart.php" class="<?php echo $current_page == 'cart.php' ? 'active' : ''; ?>" style="display: flex; align-items: center; gap: 0.35rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.116 60.116 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        Carrito
                        <?php if ($cart_count > 0): ?>
                            <span style="background-color: var(--secondary-color); color: #ffffff; font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 50px; line-height: 1;"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php' || $current_page == 'add.php' || $current_page == 'edit.php') ? 'active' : ''; ?>">Administrar (CRUD)</a></li>
                    <li>
                        <span class="user-tag">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
                            </svg>
                            Hola, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                        </span>
                    </li>
                    <li><a href="logout.php" class="btn-nav-auth" style="background: linear-gradient(135deg, var(--accent-color) 0%, #b71c1c 100%); box-shadow: 0 4px 15px rgba(231, 29, 54, 0.2);">Salir</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="<?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Iniciar Sesión</a></li>
                    <li><a href="register.php" class="btn-nav-auth">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <main>
