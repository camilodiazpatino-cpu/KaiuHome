<?php
// Inicia la sesión para poder verificar quién está logueado
session_start();

// Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

// Verifica dos cosas:
// 1️⃣ Que exista sesión activa (Cedula)
// 2️⃣ Que el usuario sea ADMINISTRADOR
if (!isset($_SESSION['Cedula']) || $_SESSION['Nombre_tipo'] !== 'ADMINISTRADOR') {

    // Si no cumple las condiciones, lo redirige al dashboard
    header("Location: ../DASHBOARD/dashboard.php");

    // Detiene la ejecución del script
    exit;
}

// Obtiene la cédula enviada por método GET
// (viene desde la URL, por ejemplo: usuario_eliminar.php?cedula=123)
$cedula = $_GET['cedula'];

// Ejecuta la consulta para eliminar el usuario
// Elimina el registro cuya cédula coincida
$conexion->query("DELETE FROM usuarios WHERE Cedula='$cedula'");

// Después de eliminar, redirige nuevamente al listado de usuarios
header("Location: usuarios.php");
