<?php
// Inicia la sesión para poder usar variables de sesión (login del usuario)
session_start();

// Incluye la conexión a la base de datos
require_once("../CONEXION/conexion.php");

// Verifica si el usuario está autenticado
if (!isset($_SESSION['Cedula'])) {
    // Si no hay sesión activa lo manda al login
    header("Location: ../LOGIN/login.php");
    exit;
}

// Guarda la cédula del usuario logueado
$cedula = $_SESSION['Cedula'];

/* ===================== CONSULTA ===================== */
// Consulta SQL para traer todo el cronograma de mantenimiento
$sql = "
SELECT  
    c.*,
    CONCAT(u.Nombre,' ',u.Apellido) AS Nombre_Tecnico
FROM cronograma_mantenimiento c
INNER JOIN usuarios u ON c.Cedula = u.Cedula
ORDER BY c.Fecha_Registro ASC
";


// Ejecuta la consulta en la base de datos
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Configuración de codificación para tildes -->
    <meta charset="UTF-8">

    <!-- Título de la pestaña -->
    <title>Cronograma Mantenimiento - Jefe</title>

    <!-- Framework Bootstrap para diseño -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos personalizados del módulo mantenimiento -->
    <link rel="stylesheet" href="../CSS/jefe_mantenimiento.css">
</head>

<body>

    <!-- Contenedor principal -->
    <div class="container mt-4 jefe-mantenimiento">

        <!-- Título principal -->
        <h3 class="jefe-title">🧠 Vista General de Mantenimiento • Kaiu Home</h3>

        <!-- Tarjeta contenedora -->
        <div class="card card-jefe-mant mb-4">

            <!-- ================= TABLA ================= -->
            <div class="table-responsive">
                <!-- Tabla principal -->
                <table class="table table-hover tabla-jefe-mant">

                    <!-- Encabezados -->
                    <thead class="thead-jefe-mant">
                        <tr>
                            <th>Técnico</th>
                            <th>Equipo</th>
                            <th>Trabajo que le hizo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Sistema</th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- Recorre todos los registros traídos de la base de datos -->
                        <?php while ($row = $resultado->fetch_assoc()): ?>

                            <tr>

                                <!-- Nombre del técnico -->
                                <td><strong><?= $row['Nombre_Tecnico'] ?></strong></td>

                                <!-- Descripción del equipo -->
                                <td><strong><?= $row['Descripcion_Equipo'] ?></strong></td>

                                <!-- Trabajo realizado -->
                                <td><strong><?= $row['Descripcion_Trabajo'] ?></strong></td>

                                <!-- Fecha del mantenimiento -->
                                <td><strong><?= $row['Fecha_Registro'] ?></strong></td>

                                <!-- ================= ESTADO DEL EQUIPO ================= -->
                                <td>
                                    <?php if ($row['Estado'] == 'BUENA'): ?>
                                        <!-- Si el estado es BUENA se muestra como activo -->
                                        <span class="btn btn-secondaryy btn-sm">ACTIVO</span>
                                    <?php else: ?>
                                        <!-- Si no, se muestra como inactivo -->
                                        <span class="btn btn-secondary btn-sm">INACTIVO</span>
                                    <?php endif; ?>
                                </td>

                                <!-- ================= ESTADO DEL SISTEMA ================= -->
                                <td>
                                    <?php if ($row['Activo'] == 1): ?>
                                        <!-- Si está activo en sistema -->
                                        <span class="btn btn-secondaryy btn-sm">✅ REACTIVADO</span>
                                    <?php else: ?>
                                        <!-- Si está inactivo en sistema -->
                                        <span class="btn btn-secondary btn-sm">🚫 INACTIVADO</span>
                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

        </div>

        <!-- Botón para volver al dashboard -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">
                ⬅ Volver
            </a>
        </div>

    </div>

</body>

</html>