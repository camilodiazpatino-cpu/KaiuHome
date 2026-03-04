<?php
// Inicia la sesión para verificar usuario logueado
session_start();

// Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

// Validación de acceso: solo ADMINISTRADOR puede editar usuarios
if (!isset($_SESSION['Cedula']) || $_SESSION['Nombre_tipo'] !== 'ADMINISTRADOR') {

    // Si no tiene acceso, redirige al dashboard
    header("Location: ../DASHBOARD/dashboard.php");
    exit;
}

// Obtiene la cédula del usuario a editar desde la URL
$cedula = $_GET['cedula'];

// Verifica si se envió el formulario (POST)
if ($_POST) {

    // Captura los valores del formulario
    $nombre = $_POST['Nombre'];
    $apellido = $_POST['Apellido'];
    $correo = $_POST['Correo'];
    $estado = $_POST['Estado'];

    // Construye la consulta para actualizar el usuario
    $sql = "UPDATE usuarios SET
        Nombre='$nombre',
        Apellido='$apellido',
        Correo='$correo',
        Estado='$estado'
        WHERE Cedula='$cedula'";

    // Ejecuta la consulta en la base de datos
    $conexion->query($sql);

    // Redirige nuevamente al listado de usuarios
    header("Location: usuarios.php");
}

// Consulta los datos actuales del usuario para llenar el formulario
$u = $conexion->query("SELECT * FROM usuarios WHERE Cedula='$cedula'")->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Editar usuario</title>
    <!-- Bootstrap para estilos y responsividad -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">

        <!-- Título de la página -->
        <h3>Editar usuario</h3>

        <!-- Formulario de edición -->
        <form method="POST">

            <!-- Campo Nombre -->
            <input value="<?= $u['Nombre'] ?>" name="Nombre" class="form-control mb-2">

            <!-- Campo Apellido -->
            <input value="<?= $u['Apellido'] ?>" name="Apellido" class="form-control mb-2">

            <!-- Campo Correo -->
            <input value="<?= $u['Correo'] ?>" name="Correo" class="form-control mb-2">

            <!-- Selector de Estado -->
            <select name="Estado" class="form-control mb-3">
                <option value="ACTIVO">ACTIVO</option>
                <option value="INACTIVO">INACTIVO</option>
            </select>

            <!-- Botón Actualizar -->
            <button class="btn btn-warning">Actualizar</button>

            <!-- Botón Cancelar → vuelve al listado de usuarios -->
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </form>

    </div>

</body>

</html>