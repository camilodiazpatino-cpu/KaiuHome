<?php
// Inicia la sesión para poder guardar datos del usuario cuando inicie sesión
session_start();

// Incluye el archivo de conexión a la base de datos
include("../CONEXION/conexion.php");

// Verifica si el formulario fue enviado (método POST)
if ($_POST) {

    // Guarda el correo ingresado en el formulario
    $correo = $_POST['correo'];

    // Guarda la contraseña ingresada en el formulario
    $contrasena = $_POST['contrasena'];

    // Consulta SQL para buscar el usuario en la base de datos
    $sql = "
        SELECT u.*, t.Nombre_tipo
        FROM usuarios u
        JOIN tipo_usuario t ON u.Id_tipo_usuario = t.Id_tipo_usuario
        WHERE u.Correo='$correo'
        AND u.Contraseña='$contrasena'
        AND u.Estado='ACTIVO'
    ";
    // Selecciona todos los datos del usuario
    // Une la tabla usuarios con tipo_usuario
    // Verifica que el correo coincida
    // Verifica que la contraseña coincida
    // Verifica que el usuario esté ACTIVO

    // Ejecuta la consulta
    $res = $conexion->query($sql);

    // Si la consulta fue exitosa y encontró al menos un registro
    if ($res && $res->num_rows > 0) {

        // Obtiene los datos del usuario
        $usuario = $res->fetch_assoc();

        // Guarda datos importantes en variables de sesión
        $_SESSION['Cedula'] = $usuario['Cedula'];
        $_SESSION['Nombre'] = $usuario['Nombre'];
        $_SESSION['Id_tipo_usuario'] = $usuario['Id_tipo_usuario'];
        $_SESSION['Nombre_tipo'] = $usuario['Nombre_tipo'];

        // Redirige al dashboard después de iniciar sesión
        header("Location: ../DASHBOARD/dashboard.php");

        // Detiene la ejecución del script
        exit;
    } else {
        // Si no encontró usuario válido, muestra mensaje de error
        $error = "Correo o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8"> <!-- Define codificación UTF-8 -->
    <title>KAIU HOME | Login</title> <!-- Título de la pestaña -->

    <!-- Importa Bootstrap desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Importa hoja de estilos personalizada -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <!-- Contenedor principal con animación -->
    <div class="container mt-5 fade-in">

        <!-- Centra el contenido -->
        <div class="row justify-content-center">

            <!-- Columna centrada de tamaño mediano -->
            <div class="col-md-4">

                <!-- Tarjeta del login -->
                <div class="card p-4 shadow-lg border-0 rounded-4 text-center">

                    <!-- LOGO -->
                    <img src="../IMG/logo-kaiu.png"
                        alt="Kaiu Home"
                        style="max-width: 120px;"
                        class="mb-3 mx-auto">
                    <!-- Imagen del logo del sistema -->

                    <!-- Texto descriptivo -->
                    <p class="text-muted mb-4">Muebles que cuentan historias</p>

                    <!-- Si existe error, lo muestra en pantalla -->
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de inicio de sesión -->
                    <form method="POST">

                        <!-- Campo para ingresar correo -->
                        <input type="email"
                            name="correo"
                            class="form-control mb-3"
                            placeholder="Correo electrónico"
                            required>

                        <!-- Campo para ingresar contraseña -->
                        <input type="password"
                            name="contrasena"
                            class="form-control mb-4"
                            placeholder="Contraseña"
                            required>

                        <!-- Botón para enviar el formulario -->
                        <button class="btn btn-dark w-100">
                            Ingresar
                        </button>
                    </form>

                </div>

            </div>
        </div>
    </div>

</body>

</html>