<?php
// 1. Iniciamos la sesión para poder acceder a ella
session_start();

// 2. Vaciamos todas las variables de sesión (correo, rol, id, etc.)
$_SESSION = array();

// 3. Si se desea destruir la sesión completamente, también se debe borrar la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruimos la sesión en el servidor
session_destroy();

// 5. Redirigimos al usuario a la página de login de forma segura
header("Location: login.php");
exit();
?>