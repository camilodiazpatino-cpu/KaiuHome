<?php
// Inicia la sesión para poder usar variables de sesión del usuario
session_start();

// Incluye el archivo de conexión a la base de datos
include("../CONEXION/conexion.php");

/* =========================
   🔐 VALIDAR SESIÓN
========================= */

// Verifica si existe la sesión de usuario (Cedula)
if (!isset($_SESSION['Cedula'])) {

    // Si no hay sesión activa, redirige al login
    header("Location: ../LOGIN/login.php");

    // Detiene la ejecución del archivo
    exit;
}

/* =========================
   🔐 VALIDAR ROL
========================= */

// Obtiene el rol del usuario desde la sesión y lo normaliza
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));

// Reemplaza tildes y espacios para estandarizar el nombre del rol
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// Define si el usuario es administrador (jefe o admin)
$esAdmin = in_array($rol, ['JEFE_DE_MANTENIMIENTO', 'ADMINISTRADOR']);

// Define si el usuario es trabajador
$esTrabajador = ($rol === 'TRABAJADOR');

// Si no es ni admin ni trabajador, se redirige al dashboard
if (!$esAdmin && !$esTrabajador) {
    header("Location: ../DASHBOARD/dashboard.php");
    exit;
}

// Guarda la cédula del usuario logueado
$cedula = $_SESSION['Cedula'];

// Variable para manejar mensajes de alerta en la interfaz
$alerta = "";
$alerta = $_GET['msg'] ?? '';

/* =========================
   🟢 REGISTRAR (SOLO ADMIN)
========================= */

// Verifica si es admin y si se envió el formulario de registrar
if ($esAdmin && isset($_POST['registrar'])) {

    // Inserta un nuevo repuesto en mantenimiento
    $conexion->query("
    INSERT INTO repuestos_mantenimiento
    (Id_Repuesto, Cantidad, Estado_Registrooo)
    VALUES (
        '{$_POST['Id_Repuesto']}',
        '{$_POST['Cantidad']}',
        'ACTIVO'
    )
");

    // Define la alerta para el frontend
    header("Location: repuestos_mantenimiento_crud.php?msg=registrado");
    exit;
}

/* =========================
   🚫 INACTIVAR
========================= */

// Verifica si viene el parámetro GET para inactivar
if (isset($_GET['inactivar'])) {

    // Captura el ID del repuesto
    $id_r = $_GET['inactivar'];

    // Actualiza el estado a INACTIVO
    $conexion->query("
        UPDATE repuestos_mantenimiento 
        SET Estado_Registrooo = 'INACTIVO'
        WHERE Id = '$id_r'
    ");

    // Mensaje de alerta
    $alerta = "inactivado";
}

/* =========================
   ✅ REACTIVAR
========================= */

// Verifica si viene el parámetro GET para activar
if (isset($_GET['activar'])) {

    // Captura el ID del repuesto
    $id_r = intval($_GET['activar']);

    // Actualiza el estado a ACTIVO
    $conexion->query("
        UPDATE repuestos_mantenimiento 
        SET Estado_Registrooo = 'ACTIVO'
        WHERE Id = '$id_r'
    ");

    // Mensaje de alerta
    $alerta = "activado";
}

/* =========================
   🗑️ ELIMINAR
========================= */

// Solo administradores pueden eliminar
if ($esAdmin && isset($_GET['eliminar'])) {

    // Obtiene el ID del repuesto a eliminar
    $id_r = $_GET['eliminar'];

    // Elimina el registro de la tabla
    $conexion->query("
        DELETE FROM repuestos_mantenimiento
        WHERE Id = '$id_r'
    ");

    // Alerta para el usuario
    $alerta = "eliminado";
}

/* =========================
   ✏️ EDITAR
========================= */

// Variable de control para saber si está en modo edición
$editar = false;

// Arreglo donde se guardan los datos a editar
$edit = [];

// Verifica si se pidió editar y es admin
if ($esAdmin && isset($_GET['editar'])) {

    // 👇 CORREGIDO: buscar por el ID del registro
    $id = intval($_GET['editar']);

    $resEdit = $conexion->query("
        SELECT * FROM repuestos_mantenimiento
        WHERE Id = '$id'
    ");

    // Obtiene los datos del registro
    $edit = $resEdit->fetch_assoc();

    // Activa el modo edición
    $editar = true;
}

/* =========================
   💾 ACTUALIZAR
========================= */

// Verifica si el formulario de actualización fue enviado
if ($esAdmin && isset($_POST['actualizar'])) {

    // Actualiza la cantidad del repuesto
    $conexion->query("
        UPDATE repuestos_mantenimiento SET
        Cantidad = '{$_POST['Cantidad']}'
        WHERE Id_Repuesto = '{$_POST['Id_Repuesto']}'
    ");

    // Mensaje de confirmación
    $alerta = "actualizado";
}

/* =========================
   📄 LISTAR
========================= */

// Consulta principal de repuestos en mantenimiento con su nombre
$res = $conexion->query("
    SELECT rm.*, r.Nombre AS Repuesto
    FROM repuestos_mantenimiento rm
    JOIN repuestos r ON rm.Id_Repuesto = r.Id_Repuesto
");

/* =========================
   SELECT REPUESTOS
========================= */

// Consulta ordenada de los repuestos con ID
$res = $conexion->query("
    SELECT rm.*, r.Nombre AS Repuesto
    FROM repuestos_mantenimiento rm
    JOIN repuestos r ON rm.Id_Repuesto = r.Id_Repuesto
    ORDER BY rm.Id ASC
");

/* =========================
   🔽 CARGAR REPUESTOS PARA EL SELECT
========================= */

// Consulta para cargar todos los repuestos disponibles
$repuestos = $conexion->query("
    SELECT Id_Repuesto, Nombre 
    FROM repuestos 
    ORDER BY Nombre ASC
");

// Validación de error en la consulta
if (!$repuestos) {
    die("Error en la consulta de repuestos: " . $conexion->error);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Define codificación de caracteres -->
    <meta charset="UTF-8">

    <!-- Título de la pestaña del navegador -->
    <title>Repuestos usados • Jefe</title>

    <!-- Bootstrap para estilos rápidos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS personalizado del módulo de mantenimiento -->
    <link rel="stylesheet" href="../CSS/jefe_mantenimiento.css">

    <!-- Librería SweetAlert2 para alertas bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- Contenedor principal -->
    <div class="container mt-4 jefe-mantenimiento">

        <!-- Título del módulo -->
        <h3 class="jefe-title text-center">🪛 Repuestos utilizados • Kaiu Home</h3>

        <!-- =========================
         🟢 FORMULARIO
    ========================= -->
        <?php if ($esAdmin): ?>
            <!-- Tarjeta contenedora del formulario -->
            <div class="card card-jefe-mant mb-4">

                <!-- Encabezado dinámico (editar o registrar) -->
                <div class="card-header card-header-jefe-mant">
                    <?= $editar ? 'Editar registro' : 'Registrar repuesto usado' ?>
                </div>

                <!-- Cuerpo del formulario -->
                <div class="card-body">
                    <form method="POST" class="row g-2">

                        <!-- Campo oculto con ID cuando se está editando -->
                        <?php if ($editar): ?>
                            <input type="hidden" name="Id" value="<?= $edit['Id'] ?>">
                        <?php endif; ?>

                        <!-- =========================
                     SELECT DE REPUESTOS
                ========================= -->
                        <select name="Id_Repuesto" class="form-control" required>

                            <!-- Opción por defecto -->
                            <option value="">Seleccione repuesto</option>

                            <!-- Bucle que carga todos los repuestos -->
                            <?php while ($r = $repuestos->fetch_assoc()): ?>
                                <option value="<?= $r['Id_Repuesto'] ?>"
                                    <?= isset($edit['Id_Repuesto']) && $edit['Id_Repuesto'] == $r['Id_Repuesto'] ? 'selected' : '' ?>>

                                    <!-- Nombre del repuesto -->
                                    <?= $r['Nombre'] ?>
                                </option>
                            <?php endwhile; ?>

                        </select>

                        <!-- =========================
                     INPUT CANTIDAD
                ========================= -->
                        <input type="number" name="Cantidad" class="form-control"
                            value="<?= $edit['Cantidad'] ?? '' ?>" required placeholder="Cantidad">

                        <!-- Botón dinámico registrar o actualizar -->
                        <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                            class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                            <?= $editar ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <!-- Botón cancelar en modo edición -->
                        <?php if ($editar): ?>
                            <a href="repuestos_mantenimiento_crud.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        <?php endif; ?>


        <!-- =========================
         📋 TABLA DE REGISTROS
    ========================= -->
        <div class="table-responsive">
            <table class="table table-hover tabla-jefe-mant">

                <!-- Cabecera de la tabla -->
                <thead class="thead-jefe-mant">
                    <tr>
                        <th>ID</th>
                        <th>Repuesto</th>
                        <th>Cantidad</th>
                        <th>Estado</th>

                        <!-- Columna acciones solo para admin -->
                        <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>

                <!-- Cuerpo de la tabla -->
                <tbody>

                    <!-- Contador visual -->
                    <?php $i = 1; ?>

                    <!-- Bucle que recorre los registros -->
                    <?php while ($row = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- Número visual -->
                            <td><span class="badge-mantenimientor">#<?= $i++ ?></span></td>

                            <!-- Nombre del repuesto -->
                            <td><strong><?= $row['Repuesto'] ?></strong></td>

                            <!-- Cantidad utilizada -->
                            <td><strong><?= $row['Cantidad'] ?></strong></td>

                            <!-- Estado con color dinámico -->
                            <td>
                                <span class="badge bg-<?= $row['Estado_Registrooo'] == 'ACTIVO' ? 'success' : 'danger' ?>">
                                    <?= $row['Estado_Registrooo'] ?>
                                </span>
                            </td>

                            <!-- Acciones solo si es admin -->
                            <?php if ($esAdmin): ?>
                                <td>
                                    <div class="acciones-botones">

                                        <!-- Botón editar -->
                                        <a href="?editar=<?= $row['Id'] ?>" class="btn-editar">✏️</a>

                                        <!-- Si está activo -->
                                        <?php if ($row['Estado_Registrooo'] == 'ACTIVO'): ?>
                                            <button class="btn btn-secondary btn-sm"
                                                onclick="inactivar(<?= $row['Id'] ?>)">🚫 inactivar</button>
                                        <?php else: ?>

                                            <!-- Botón reactivar -->
                                            <button class="btn btn-secondaryy btn-sm"
                                                onclick="activar(<?= $row['Id'] ?>)">✅ Reactivar</button>

                                            <!-- Botón eliminar -->
                                            <button class="btn-eliminar-log"
                                                onclick="eliminar(<?= $row['Id'] ?>)">🗑️</button>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            <?php endif; ?>

                        </tr>
                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>

        <!-- Botón volver -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">⬅ Volver</a>
        </div>

    </div>


    <!-- =========================
     🔥 ALERTAS JS
========================= -->
    <script>
        // Función para inactivar
        function inactivar(id) {
            Swal.fire({
                title: '¿Inactivar?',
                icon: 'warning',
                showCancelButton: true
            }).then(r => {
                if (r.isConfirmed) {
                    location = '?inactivar=' + id;
                }
            });
        }

        // Función para activar
        function activar(id) {
            Swal.fire({
                title: '¿Activar?',
                icon: 'question',
                showCancelButton: true
            }).then(r => {
                if (r.isConfirmed) {
                    location = '?activar=' + id;
                }
            });
        }

        // Función para eliminar
        function eliminar(id) {
            Swal.fire({
                title: '¿Eliminar?',
                text: 'No se puede deshacer',
                icon: 'warning',
                showCancelButton: true
            }).then(r => {
                if (r.isConfirmed) {
                    location = '?eliminar=' + id;
                }
            });
        }

        // =========================
        // ALERTAS DINÁMICAS PHP
        // =========================
        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Repuesto registrado', 'success');
        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Repuesto actualizado', 'success');
        <?php elseif ($alerta == "inactivado"): ?>
            Swal.fire('Inactivado', 'Estado cambiado', 'info');
        <?php elseif ($alerta == "activado"): ?>
            Swal.fire('Activado', 'Estado cambiado', 'success');
        <?php elseif ($alerta == "eliminado"): ?>
            Swal.fire('Eliminado', 'Registro eliminado', 'success');
        <?php endif; ?>
    </script>

</body>

</html>