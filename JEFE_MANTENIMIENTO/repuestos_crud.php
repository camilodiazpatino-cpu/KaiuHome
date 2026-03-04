<?php
// Inicia la sesión para acceder a variables de usuario logueado
session_start();

// Conexión a la base de datos
include("../CONEXION/conexion.php");

/* =========================
   🔐 VALIDAR SESIÓN
========================= */

// Si no existe la cédula en sesión, el usuario no ha iniciado sesión
if (!isset($_SESSION['Cedula'])) {
    // Redirigir al login
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =========================
   🔐 VALIDAR ROL
========================= */

// Obtener el rol del usuario logueado
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));

// Reemplazar espacios y tildes para normalizar el rol
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// Verificar si el usuario es administrador o jefe de mantenimiento
$esAdmin = in_array($rol, ['JEFE_DE_MANTENIMIENTO', 'ADMINISTRADOR']);

// Verificar si es trabajador
$esTrabajador = ($rol === 'TRABAJADOR');

// Si no tiene permisos, redirigir al dashboard
if (!$esAdmin && !$esTrabajador) {
    header("Location: ../DASHBOARD/dashboard.php");
    exit;
}

/* =========================
   ALERTAS (SOLO GET)
========================= */

// Captura mensaje enviado por la URL para mostrar alertas visuales
$alerta = $_GET['msg'] ?? '';

/* =========================
   🟢 REGISTRAR REPUESTO
========================= */

// Solo el administrador puede registrar repuestos
if ($esAdmin && isset($_POST['registrar'])) {

    // Inserta un nuevo repuesto en la base de datos
    $conexion->query("
        INSERT INTO repuestos (Nombre, Stock, Costo, Estado_Registroo)
        VALUES (
            '{$_POST['Nombre']}',
            '{$_POST['Stock']}',
            '{$_POST['Costo']}',
            'ACTIVO'
        )
    ");

    // Redirecciona con mensaje de éxito
    header("Location: repuestos_crud.php?msg=registrado");
    exit;
}

/* =========================
   🚫 INACTIVAR
========================= */

// Solo el admin puede inactivar repuestos
if ($esAdmin && isset($_GET['inactivar'])) {

    // Obtener el ID del repuesto
    $id = $_GET['inactivar'];

    // Cambiar el estado a INACTIVO
    $conexion->query("
        UPDATE repuestos 
        SET Estado_Registroo='INACTIVO'
        WHERE Id_Repuesto='$id'
    ");

    // Redireccionar con mensaje
    header("Location: repuestos_crud.php?msg=inactivado");
    exit;
}

/* =========================
   ✅ REACTIVAR
========================= */

// Reactivar repuesto
if ($esAdmin && isset($_GET['activar'])) {

    // Obtener ID
    $id = $_GET['activar'];

    // Cambiar estado a ACTIVO
    $conexion->query("
        UPDATE repuestos 
        SET Estado_Registroo='ACTIVO'
        WHERE Id_Repuesto='$id'
    ");

    // Redirigir con mensaje
    header("Location: repuestos_crud.php?msg=activado");
    exit;
}

/* =========================
   🗑️ ELIMINAR REPUESTO
========================= */

// Eliminar repuesto solo si no tiene relación con mantenimiento
if ($esAdmin && isset($_GET['eliminar'])) {

    // Obtener ID del repuesto
    $id = $_GET['eliminar'];

    // Verificar si el repuesto está siendo usado en mantenimiento
    $check = $conexion->query("
        SELECT COUNT(*) total 
        FROM repuestos_mantenimiento
        WHERE Id_Repuesto='$id'
    ")->fetch_assoc();

    // Si no está en uso se puede eliminar
    if ($check['total'] == 0) {

        // Eliminar el repuesto
        $conexion->query("
            DELETE FROM repuestos
            WHERE Id_Repuesto='$id'
        ");

        header("Location: repuestos_crud.php?msg=eliminado");
    } else {
        // Si está en uso no se puede eliminar
        header("Location: repuestos_crud.php?msg=no_eliminado");
    }

    exit;
}


/* =========================
   ✏️ EDITAR
========================= */

// Variable que indica si se está editando
$editar = false;

// Arreglo que almacenará los datos del repuesto a editar
$repuesto_edit = [];

// Si se presiona editar
if ($esAdmin && isset($_GET['editar'])) {

    // Consulta del repuesto seleccionado
    $resEdit = $conexion->query("
        SELECT * FROM repuestos 
        WHERE Id_Repuesto = '{$_GET['editar']}'
    ");

    // Obtener datos del repuesto
    $repuesto_edit = $resEdit->fetch_assoc();

    // Activar modo edición
    $editar = true;
}

/* =========================
   💾 ACTUALIZAR
========================= */

// Actualizar repuesto existente
if ($esAdmin && isset($_POST['actualizar'])) {

    // Ejecutar actualización
    $conexion->query("
        UPDATE repuestos SET
            Nombre = '{$_POST['Nombre']}',
            Stock = '{$_POST['Stock']}',
            Costo = '{$_POST['Costo']}'
        WHERE Id_Repuesto = '{$_POST['Id_Repuesto']}'
    ");

    // Guardar mensaje para mostrar alerta
    $alerta = "actualizado";
}

/* =========================
   📄 LISTAR REPUESTOS
========================= */

// Consulta general de todos los repuestos ordenados por ID
$res = $conexion->query("
    SELECT * FROM repuestos
    ORDER BY Id_Repuesto ASC
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Configuración de caracteres -->
    <meta charset="UTF-8">

    <!-- Título de la pestaña del navegador -->
    <title>Gestión de Repuestos - Jefe</title>

    <!-- Bootstrap para estilos y diseño responsive -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos personalizados del módulo de jefe de mantenimiento -->
    <link rel="stylesheet" href="../CSS/jefe_mantenimiento.css">

    <!-- Librería SweetAlert2 para alertas visuales -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="container mt-4 jefe-mantenimiento">

        <!-- TÍTULO PRINCIPAL -->
        <h3 class="jefe-title text-center">🧰 Gestión de Repuestos • Kaiu Home</h3>

        <!-- ==================== FORMULARIO (SOLO ADMIN) ==================== -->
        <?php if ($esAdmin): ?>
            <div class="card card-jefe-mant mb-4">

                <!-- ENCABEZADO DEL FORMULARIO -->
                <div class="card-header card-header-jefe-mant">
                    <!-- Cambia el texto dependiendo si está editando o registrando -->
                    <?= $editar ? 'Editar repuesto' : 'Registrar repuesto' ?>
                </div>

                <div class="card-body">

                    <!-- FORMULARIO DE REGISTRO / ACTUALIZACIÓN -->
                    <form method="POST" class="row g-2">

                        <!-- Si está editando, envía el ID oculto -->
                        <?php if ($editar): ?>
                            <input type="hidden" name="Id_Repuesto" value="<?= $repuesto_edit['Id_Repuesto'] ?>">
                        <?php endif; ?>

                        <!-- INPUT NOMBRE DEL REPUESTO -->
                        <input type="text" name="Nombre" class="form-control"
                            value="<?= $repuesto_edit['Nombre'] ?? '' ?>" required placeholder="Nombre del repuesto">

                        <!-- INPUT STOCK DISPONIBLE -->
                        <input type="number" name="Stock" class="form-control"
                            value="<?= $repuesto_edit['Stock'] ?? '' ?>" required placeholder="Stock">

                        <!-- INPUT COSTO DEL REPUESTO -->
                        <input type="number" step="0.01" name="Costo" class="form-control"
                            value="<?= $repuesto_edit['Costo'] ?? '' ?>" required placeholder="Costo">

                        <!-- BOTÓN DINÁMICO (REGISTRAR O ACTUALIZAR) -->
                        <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                            class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                            <?= $editar ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <!-- BOTÓN CANCELAR (SOLO CUANDO EDITA) -->
                        <?php if ($editar): ?>
                            <a href="repuestos_crud.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- ==================== TABLA DE REPUESTOS ==================== -->
        <div class="table-responsive">
            <table class="table table-hover tabla-jefe-mant">

                <!-- ENCABEZADO DE TABLA -->
                <thead class="thead-jefe-mant">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Stock</th>
                        <th>Costo</th>
                        <th>Estado</th>
                        <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <!-- CONTADOR VISUAL PARA ENUMERAR REGISTROS -->
                    <?php $i = 1; ?>

                    <!-- RECORRIDO DE RESULTADOS DE LA BD -->
                    <?php while ($r = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- ID VISUAL -->
                            <td><span class="badge-mantenimientor">#<?= $i++ ?></span></td>

                            <!-- NOMBRE DEL REPUESTO -->
                            <td><strong><?= $r['Nombre'] ?></strong></td>

                            <!-- STOCK DISPONIBLE -->
                            <td><strong><?= $r['Stock'] ?></strong></td>

                            <!-- COSTO FORMATEADO -->
                            <td>
                                <span class="precio-mant <?= $r['Costo'] >= 200000 ? 'alto' : '' ?>">
                                    $<?= number_format($r['Costo'], 0, ',', '.') ?>
                                </span>
                            </td>

                            <!-- ESTADO DEL REPUESTO -->
                            <td>
                                <span class="badge bg-<?= $r['Estado_Registroo'] == 'ACTIVO' ? 'success' : 'danger' ?>">
                                    <?= $r['Estado_Registroo'] ?>
                                </span>
                            </td>

                            <!-- ACCIONES SOLO PARA ADMIN -->
                            <?php if ($esAdmin): ?>
                                <td>
                                    <div class="acciones-botones">

                                        <!-- BOTÓN EDITAR -->
                                        <a href="?editar=<?= $r['Id_Repuesto'] ?>" class="btn-editar">✏️</a>

                                        <!-- SI ESTÁ ACTIVO -->
                                        <?php if ($r['Estado_Registroo'] == 'ACTIVO'): ?>
                                            <button class="btn btn-secondary btn-sm"
                                                onclick="inactivarRepuesto(<?= $r['Id_Repuesto'] ?>)">
                                                🚫 Inactivar
                                            </button>

                                            <!-- SI ESTÁ INACTIVO -->
                                        <?php else: ?>
                                            <button class="btn btn-secondaryy btn-sm"
                                                onclick="activarRepuesto(<?= $r['Id_Repuesto'] ?>)">
                                                ✅ Reactivar
                                            </button>

                                            <!-- ELIMINACIÓN LÓGICA -->
                                            <button class="btn-eliminar-log"
                                                onclick="eliminarRepuestos(<?= $r['Id_Repuesto'] ?>)">
                                                🗑️
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            <?php endif; ?>

                        </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>

        <!-- BOTÓN VOLVER -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">⬅ Volver</a>
        </div>

    </div>

    <!-- ==================== SCRIPTS DE ACCIONES ==================== -->
    <script>
        // FUNCIÓN PARA INACTIVAR REPUESTO
        function inactivarRepuesto(id) {
            Swal.fire({
                title: '¿Inactivar repuesto?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, inactivar'
            }).then((r) => {
                if (r.isConfirmed) location = '?inactivar=' + id;
            });
        }

        // FUNCIÓN PARA ACTIVAR REPUESTO
        function activarRepuesto(id) {
            Swal.fire({
                title: '¿Reactivar repuesto?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, reactivar'
            }).then((r) => {
                if (r.isConfirmed) location = '?activar=' + id;
            });
        }

        // FUNCIÓN PARA ELIMINAR REPUESTO
        function eliminarRepuestos(id) {
            Swal.fire({
                title: '¿Eliminar repuesto?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar'
            }).then((r) => {
                if (r.isConfirmed) {
                    location = '?eliminar=' + id;
                }
            });
        }

        // ==================== ALERTAS DINÁMICAS DESDE PHP ====================

        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Repuesto registrado', 'success');

        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Repuesto actualizado', 'success');

        <?php elseif ($alerta == "inactivado"): ?>
            Swal.fire('Listo', 'Repuesto inactivado', 'success');

        <?php elseif ($alerta == "activado"): ?>
            Swal.fire('Listo', 'Repuesto reactivado', 'success');

        <?php elseif ($alerta == "eliminado"): ?>
            Swal.fire('Eliminado', 'Repuesto eliminado correctamente', 'success');

        <?php elseif ($alerta == "no_eliminado"): ?>
            Swal.fire('Error', 'No se puede eliminar: tiene Repuestos', 'error');
        <?php endif; ?>
    </script>

</body>

</html>