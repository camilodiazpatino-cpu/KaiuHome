<?php
// Inicia la sesión para poder verificar el usuario logueado
session_start();

// Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

// Validación de acceso: solo ADMINISTRADOR puede crear usuarios
if (!isset($_SESSION['Cedula']) || $_SESSION['Nombre_tipo'] !== 'ADMINISTRADOR') {
    // Si no cumple la condición, redirige al dashboard
    header("Location: ../DASHBOARD/dashboard.php");
    exit;
}

// Verifica si se envió el formulario (método POST)
if ($_POST) {

    // Captura los valores enviados por el formulario
    $cedula = $_POST['Cedula'];
    $nombre = $_POST['Nombre'];
    $apellido = $_POST['Apellido'];
    $correo = $_POST['Correo'];
    $usuario = $_POST['Usuario'];
    $contrasena = $_POST['Contrasena'];
    $tipo = $_POST['Id_tipo_usuario'];

    // Construye la consulta SQL para insertar el nuevo usuario
    $sql = "INSERT INTO usuarios
    (Cedula, Nombre, Apellido, Correo, Usuario, Contraseña, Id_tipo_usuario, Estado, Fecha_Creacion)
    VALUES
    ('$cedula','$nombre','$apellido','$correo','$usuario','$contrasena','$tipo','ACTIVO',NOW())";

    // Ejecuta la consulta en la base de datos
    $conexion->query($sql);

    // Redirige nuevamente al listado de usuarios después de guardar
    header("Location: usuarios.php");
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Crear usuario</title>
    <!-- Bootstrap para estilos y responsividad -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">

        <!-- Título de la página -->
        <h3>Registrar usuario</h3>

        <!-- Formulario para crear usuario -->
        <form method="POST">

            <!-- Campos de entrada para los datos del usuario -->
            <input name="Cedula" class="form-control mb-2" placeholder="Cédula" required>
            <input name="Nombre" class="form-control mb-2" placeholder="Nombre" required>
            <input name="Apellido" class="form-control mb-2" placeholder="Apellido" required>
            <input name="Correo" type="email" class="form-control mb-2" placeholder="Correo" required>
            <input name="Usuario" class="form-control mb-2" placeholder="Usuario" required>
            <input name="Contrasena" class="form-control mb-2" placeholder="Contraseña" required>

            <!-- Selector de rol -->
            <select name="Id_tipo_usuario" class="form-control mb-3" required>
                <option value="">Seleccione rol</option>
                <option value="1">ADMIN</option>
                <option value="2">LOGISTICA</option>
                <option value="3">MANTENIMIENTO</option>
            </select>

            <!-- Botones de acción -->
            <button class="btn btn-success">Guardar</button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>

        </form>
    </div>

</body>

</html>