<?php
// Dirección del servidor donde está la base de datos
// "localhost" significa que está en el mismo servidor donde corre el proyecto
$host = "localhost";

// Usuario de la base de datos
// En entornos locales normalmente es "root"
$usuario = "root";

// Contraseña del usuario de la base de datos
// En XAMPP o entornos locales suele estar vacía
$password = "";

// Nombre de la base de datos que se va a utilizar
$bd = "maderarte_sas";

// Puerto de conexión a MySQL
// 3306 es el puerto por defecto de MySQL
$puerto = 3306;

// Se crea una nueva conexión usando la clase mysqli
// Se pasan como parámetros: host, usuario, contraseña, base de datos y puerto
$conexion = new mysqli($host, $usuario, $password, $bd, $puerto);

// Verifica si ocurrió un error al conectar
if ($conexion->connect_error) {

    // Si hay error, detiene completamente el programa
    // y muestra el mensaje de error
    die("Error de conexión: " . $conexion->connect_error);
}

/* Charset para evitar errores con tildes y ñ */

// Establece la codificación de caracteres a utf8mb4
// Esto permite guardar correctamente:
// - Tildes (á, é, í, ó, ú)
// - Ñ
// - Emojis
// - Caracteres especiales
$conexion->set_charset("utf8mb4");