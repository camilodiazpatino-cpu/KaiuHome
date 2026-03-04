<?php
// Redirige automáticamente al usuario a la página LOGIN/login.php
// Esto hace que cuando alguien entre a index.php,
// sea enviado directamente al formulario de inicio de sesión
header("Location: LOGIN/login.php");

// Finaliza la ejecución del script inmediatamente
// Es importante usar exit para evitar que se ejecute
// cualquier otro código después de la redirección
exit;