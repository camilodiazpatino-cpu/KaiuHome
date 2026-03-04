<?php
// Inicia la sesión para poder usar variables de sesión
session_start();

// Incluye el archivo de conexión a la base de datos
include("../CONEXION/conexion.php");

/* =========================
   🔐 VALIDAR SESIÓN
========================= */

// Verifica si la variable de sesión 'Cedula' NO está definida
if (!isset($_SESSION['Cedula'])) {
    // Si no existe sesión activa, redirige al login
    header("Location: ../LOGIN/login.php");
    // Detiene la ejecución del script
    exit;
}

// Guarda la cédula del usuario que inició sesión
$cedula = $_SESSION['Cedula'];

// Consulta SQL para obtener la lista de repuestos
$sql = "
SELECT 
    r.Id_Repuesto,                         -- ID del repuesto
    r.Nombre,                              -- Nombre del repuesto
    r.Stock,                               -- Cantidad disponible en inventario
    r.Costo,                               -- Precio o costo del repuesto
    IFNULL(MAX(r.Estado_Registroo), 'ACTIVO') AS Estado_Registroo
                                            -- Si el estado es NULL, lo coloca como 'ACTIVO'
FROM repuestos r                           -- Tabla principal de repuestos
LEFT JOIN repuestos_mantenimiento rm       -- Unión con tabla de mantenimiento
    ON r.Id_Repuesto = rm.Id_Repuesto      -- Relación entre ambas tablas
GROUP BY r.Id_Repuesto                     -- Agrupa por ID para evitar duplicados
ORDER BY r.Id_Repuesto ASC                 -- Ordena los resultados de menor a mayor
";

// Ejecuta la consulta en la base de datos
$res = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8"> <!-- Define codificación UTF-8 -->
    <title>Repuestos</title> <!-- Título de la pestaña -->

    <!-- Importa Bootstrap desde CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Importa hoja de estilos personalizada -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <!-- Contenedor principal con margen superior -->
    <div class="container mt-5 kaiu-container">

        <!-- Tarjeta personalizada -->
        <div class="card-kaiu">

            <!-- Título principal -->
            <h3 class="kaiu-title">🔩 Listado de Repuestos • Kaiu Home</h3>

            <!-- Tabla con estilos personalizados -->
            <table class="table kaiu-table">

                <!-- Encabezado de la tabla -->
                <thead>
                    <tr>
                        <th>ID</th> <!-- Columna ID -->
                        <th>Nombre</th> <!-- Columna Nombre -->
                        <th>Stock</th> <!-- Columna Stock -->
                        <th>Costo</th> <!-- Columna Costo -->
                        <th>Estado</th> <!-- Columna Estado -->
                    </tr>
                </thead>

                <tbody>

                    <?php $i = 1; ?> <!-- Inicializa contador para numeración -->

                    <!-- Recorre todos los registros obtenidos de la consulta -->
                    <?php while ($r = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- Muestra número consecutivo en vez del ID real -->
                            <td>
                                <span class="badge-mantenimiento">
                                    #<?= $i++ ?>
                                </span>
                            </td>

                            <!-- Muestra el nombre del repuesto en negrita -->
                            <td>
                                <strong><?= $r['Nombre'] ?></strong>
                            </td>

                            <!-- Muestra el stock disponible -->
                            <td>
                                <?= $r['Stock'] ?>
                            </td>

                            <!-- Muestra el costo formateado como moneda -->
                            <td class="text-success fw-bold">
                                $<?= number_format($r['Costo'], 0, ',', '.') ?>
                            </td>

                            <td>
                                <?php
                                // Convierte el estado a mayúsculas
                                $estado = strtoupper($r['Estado_Registroo']);

                                // Si el estado es ACTIVO
                                if ($estado === 'ACTIVO') {
                                    // Muestra badge verde
                                    echo '<span class="badge-activa">ACTIVO</span>';
                                } else {
                                    // Si no es ACTIVO, lo muestra como INACTIVO
                                    echo '<span class="badge-inactivo">INACTIVO</span>';
                                }
                                ?>
                            </td>

                        </tr>
                    <?php endwhile; ?> <!-- Fin del while -->

                </tbody>
            </table>

            <!-- Botón para volver al dashboard -->
            <div class="text-center mt-4">
                <a href="../DASHBOARD/dashboard.php" class="btn btn-kaiu">
                    ⬅ Volver
                </a>
            </div>

        </div>
    </div>

</body>

</html>