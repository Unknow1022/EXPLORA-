<?php
session_start();
require_once 'includes/config.php';

$error = "";

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_SESSION['login_attempts'] >= 5) {
        $error = "Demasiados intentos. Intente más tarde.";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Token CSRF inválido.";
    } else {
        $correo = trim($_POST['correo']);
        $pass = trim($_POST['password']);

        if (!empty($correo) && !empty($pass)) {
        $stmt = $pdo->prepare("SELECT id, correo, password, rol FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $auth = false;
            // Verificar hash moderno
            if (password_verify($pass, $usuario['password'])) {
                $auth = true;
            }

            if ($auth) {
                $_SESSION['login_attempts'] = 0;
                session_regenerate_id(true);
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['correo']  = $usuario['correo'];
                $_SESSION['rol']     = $usuario['rol'];
                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['login_attempts']++;
                $error = "Contraseña incorrecta.";
            }
        } else {
            $_SESSION['login_attempts']++;
            $error = "El correo no está registrado.";
        }
    } else {
        $error = "Por favor, complete todos los campos.";
    }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Ingresar | Explora Talara</title>
</head>
<body class="login-body">
    <div class="login-card">
        <h2>📍 Bienvenida</h2>
        <?php if($error): ?> <p class="error-msg" style="color:red;"><?php echo $error; ?></p> <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="email" name="correo" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
        </form>
        <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </div>
</body>
</html>