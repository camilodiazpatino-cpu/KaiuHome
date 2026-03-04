<?php
// Inicia la sesión para poder acceder a las variables de sesión activas
session_start();

// Elimina todas las variables de sesión registradas
// (borra los datos almacenados en $_SESSION)
session_unset();

// Destruye completamente la sesión en el servidor
// (cierra la sesión del usuario definitivamente)
session_destroy();

// Redirige al usuario al archivo index.php
// Normalmente index.php redirige al login
header("Location: ../index.php");

// Detiene la ejecución del script inmediatamente
// para asegurarse de que no se ejecute nada después
exit;