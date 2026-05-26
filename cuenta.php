<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

require_once 'includes/config.php';

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['nueva_password'])) {
        $nueva_pass = trim($_POST['nueva_password']);
        $confirmar_pass = trim($_POST['confirmar_password']);

        if ($nueva_pass !== $confirmar_pass) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($nueva_pass) < 8 || !preg_match('/[A-Z]/', $nueva_pass) || !preg_match('/[0-9]/', $nueva_pass)) {
            $error = "La contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.";
        } else {
            $hashed_pass = password_hash($nueva_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_pass, $_SESSION['user_id']])) {
                $mensaje = "Contraseña actualizada exitosamente.";
            } else {
                $error = "Error al actualizar la contraseña.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Cuenta | Explora Talara</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        // Aplicar tema guardado
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>

    <nav class="navbar">
        <strong class="brand"><a href="dashboard.php" style="color:inherit; text-decoration:none;">📍 Explora Talara</a></strong>
        
        <div class="user-menu-container">
            <div class="user-pill" onclick="toggleDropdown(event)">
                <span id="nav-user-email"><?php echo htmlspecialchars($_SESSION['correo']); ?></span>
                <span>▼</span>
            </div>
            <div class="dropdown-menu" id="userDropdown">
                <a href="dashboard.php" class="dropdown-item">⬅️ Volver al Mapa</a>
                <a href="logout.php" class="dropdown-item text-danger">🚪 Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="account-container">
        <h2 style="margin-bottom: 20px;">👤 Mi Cuenta</h2>
        
        <p><strong>Correo electrónico:</strong> <?php echo htmlspecialchars($_SESSION['correo']); ?></p>
        <p><strong>Rol:</strong> <?php echo htmlspecialchars($_SESSION['rol']); ?></p>
        
        <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border);">

        <h3>Ajustes de Interfaz</h3>
        <p style="margin: 10px 0;">Selecciona el tema de la aplicación:</p>
        <button onclick="toggleTheme()" style="width: auto; background: var(--slate);">🌗 Alternar Modo Claro / Oscuro</button>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border);">

        <h3>Cambiar Contraseña</h3>
        <?php if($mensaje): ?> <p style="color: green; font-weight: bold; margin: 10px 0;"><?php echo $mensaje; ?></p> <?php endif; ?>
        <?php if($error): ?> <p style="color: red; font-weight: bold; margin: 10px 0;"><?php echo $error; ?></p> <?php endif; ?>

        <form method="POST" style="margin-top: 15px;">
            <label>Nueva Contraseña:</label>
            <input type="password" name="nueva_password" required placeholder="Mín. 8 caracteres, 1 mayúscula, 1 número">
            
            <label>Confirmar Contraseña:</label>
            <input type="password" name="confirmar_password" required placeholder="Repita la nueva contraseña">
            
            <button type="submit" style="width: auto;">Actualizar Contraseña</button>
        </form>
    </div>

    <script>
        function toggleDropdown(e) {
            e.stopPropagation();
            document.getElementById('userDropdown').classList.toggle('show');
        }

        window.onclick = function(event) {
            if (!event.target.closest('.user-menu-container')) {
                const dropdowns = document.getElementsByClassName("dropdown-menu");
                for (let i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].classList.remove('show');
                }
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }
    </script>
</body>
</html>
