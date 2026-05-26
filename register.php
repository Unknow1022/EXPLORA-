<?php
session_start();
require_once 'includes/config.php';

$error = "";
$success = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Token CSRF inválido.";
    } else {
        $correo = trim($_POST['correo']);
        $pass = trim($_POST['password']);
        
        if (strlen($pass) < 8 || !preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
            $error = "La contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.";
        } else {
        try {
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (correo, password, rol) VALUES (?, ?, 'user')");
            $stmt->execute([$correo, $hashed_pass]);
            
            header("Location: login.php?msg=registrado");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Este correo ya está registrado.";
            } else {
                $error = "Error crítico. Intente más tarde.";
            }
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Registro | Explora</title>
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Crear Cuenta</h2>
        <?php if($error): ?> <p style="color:orange;"><?php echo $error; ?></p> <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="password" placeholder="Contraseña (mín. 6 caracteres)" required>
            <button type="submit">Registrar</button>
        </form>
        <a href="login.php">Ya tengo cuenta</a>
    </div>
</body>
</html>