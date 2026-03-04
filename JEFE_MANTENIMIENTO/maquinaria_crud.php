<?php
// Inicia la sesión para acceder a variables como Cedula y rol
session_start();

// Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

/* =========================
   🔐 VALIDAR SESIÓN
========================= */
// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['Cedula'])) {
    // Si no hay sesión, lo redirige al login
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =========================
   🔐 VALIDAR ROL
========================= */
// Obtiene el nombre del rol del usuario y lo normaliza
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// Define permisos según el rol
$esAdmin = in_array($rol, ['JEFE_DE_MANTENIMIENTO', 'ADMINISTRADOR']);
$esTrabajador = ($rol === 'TRABAJADOR');

// Si no es admin ni trabajador, no puede entrar a este módulo
if (!$esAdmin && !$esTrabajador) {
    header("Location: ../DASHBOARD/dashboard.php");
    exit;
}

/* =========================
   VARIABLES SWEETALERT
========================= */
// Captura el mensaje enviado por URL para mostrar alertas visuales
$alerta = $_GET['msg'] ?? '';

/* =========================
   🟢 REGISTRAR MAQUINA
========================= */
// Solo los administradores pueden registrar maquinaria
if ($esAdmin && isset($_POST['registrar'])) {

    // Inserta una nueva máquina en la base de datos
    $conexion->query("
        INSERT INTO maquinaria
        (Nombre, Marca, Modelo, Fecha_Compra, Estado)
        VALUES (
            '{$_POST['Nombre']}',
            '{$_POST['Marca']}',
            '{$_POST['Modelo']}',
            '{$_POST['Fecha_Compra']}',
            'ACTIVA'
        )
    ");

    // Redirige para mostrar mensaje de éxito
    header("Location: maquinaria_crud.php?msg=registrado");
    exit;
}

/* =========================
   🔴 INACTIVAR MAQUINA
========================= */
// Cambia el estado de la máquina a INACTIVA
if ($esAdmin && isset($_GET['inactivar'])) {

    $conexion->query("
        UPDATE maquinaria 
        SET Estado='INACTIVA' 
        WHERE Id_Maquina='{$_GET['inactivar']}'
    ");

    // Redirige con mensaje
    header("Location: maquinaria_crud.php?msg=inactivado");
    exit;
}

/* =========================
   🟢 ACTIVAR MAQUINA
========================= */
// Cambia el estado de la máquina a ACTIVA
if ($esAdmin && isset($_GET['activar'])) {

    $conexion->query("
        UPDATE maquinaria 
        SET Estado='ACTIVA' 
        WHERE Id_Maquina='{$_GET['activar']}'
    ");

    // Redirige con mensaje
    header("Location: maquinaria_crud.php?msg=activado");
    exit;
}

/* =========================
   🗑️ ELIMINAR MAQUINA
========================= */
// Permite eliminar una máquina
if ($esAdmin && isset($_GET['eliminar'])) {

    $id = intval($_GET['eliminar']);

    $stmt = $conexion->prepare("
        DELETE FROM maquinaria 
        WHERE Id_Maquina = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: maquinaria_crud.php?msg=eliminado");
    } else {
        header("Location: maquinaria_crud.php?msg=error");
    }

    exit;
}

/* =========================
   ✏️ OBTENER MAQUINA (EDITAR)
========================= */
// Variables para modo edición
$editar = false;
$maquina_edit = [];

// Si se recibe parámetro editar, carga los datos de la máquina
if ($esAdmin && isset($_GET['editar'])) {

    $resEdit = $conexion->query("SELECT * FROM maquinaria WHERE Id_Maquina='{$_GET['editar']}'");

    // Guarda los datos en un arreglo para llenar el formulario
    $maquina_edit = $resEdit->fetch_assoc();

    // Activa el modo edición
    $editar = true;
}

/* =========================
   💾 ACTUALIZAR MAQUINA
========================= */
// Actualiza los datos de la máquina
if ($esAdmin && isset($_POST['actualizar'])) {

    $conexion->query("
        UPDATE maquinaria SET
        Nombre='{$_POST['Nombre']}',
        Marca='{$_POST['Marca']}',
        Modelo='{$_POST['Modelo']}',
        Fecha_Compra='{$_POST['Fecha_Compra']}',
        Estado='{$_POST['Estado']}'
        WHERE Id_Maquina='{$_POST['Id_Maquina']}'
    ");

    // Redirige con mensaje de actualizado
    header("Location: maquinaria_crud.php?msg=actualizado");
    exit;
}

/* =========================
   📄 LISTAR MAQUINARIA
========================= */
// Consulta todas las máquinas registradas
$res = $conexion->query("SELECT * FROM maquinaria");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Maquinaria - Jefe</title>

    <!-- Bootstrap para estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS personalizado del módulo de jefe de mantenimiento -->
    <link rel="stylesheet" href="../CSS/jefe_mantenimiento.css">

    <!-- Librería SweetAlert para alertas bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="container mt-4 jefe-mantenimiento">

        <!-- TÍTULO DEL MÓDULO -->
        <h3 class="jefe-title">🏭 Gestión de Maquinaria • Kaiu Home</h3>

        <!-- =========================================================
             FORMULARIO (SOLO VISIBLE PARA ADMINISTRADOR)
        ========================================================== -->
        <?php if ($esAdmin): ?>

            <div class="card card-jefe-mant mb-4">

                <!-- CABECERA DEL FORMULARIO -->
                <div class="card-header card-header-jefe-mant">
                    <!-- Cambia dinámicamente si está editando o registrando -->
                    <?= $editar ? 'Editar máquina' : 'Registrar máquina' ?>
                </div>

                <div class="card-body">

                    <!-- FORMULARIO PRINCIPAL -->
                    <form method="POST" class="row g-2">

                        <!-- Si está editando, se envía el ID oculto -->
                        <?php if ($editar): ?>
                            <input type="hidden" name="Id_Maquina" value="<?= $maquina_edit['Id_Maquina'] ?>">
                        <?php endif; ?>

                        <!-- NOMBRE DE LA MÁQUINA -->
                        <input type="text" name="Nombre" class="form-control"
                            value="<?= $maquina_edit['Nombre'] ?? '' ?>" required placeholder="Nombre">

                        <!-- MARCA -->
                        <input type="text" name="Marca" class="form-control"
                            value="<?= $maquina_edit['Marca'] ?? '' ?>" required placeholder="Marca">

                        <!-- MODELO -->
                        <input type="text" name="Modelo" class="form-control"
                            value="<?= $maquina_edit['Modelo'] ?? '' ?>" required placeholder="Modelo">

                        <!-- FECHA DE COMPRA -->
                        <input type="date" name="Fecha_Compra" class="form-control"
                            value="<?= $maquina_edit['Fecha_Compra'] ?? '' ?>" required>

                        <!-- ESTADO (solo visible cuando se está editando) -->
                        <?php if ($editar): ?>
                            <select name="Estado" class="form-control" required>
                                <option value="ACTIVA" <?= $maquina_edit['Estado'] == 'ACTIVA' ? 'selected' : '' ?>>ACTIVA</option>
                                <option value="INACTIVA" <?= $maquina_edit['Estado'] == 'INACTIVA' ? 'selected' : '' ?>>INACTIVA</option>
                            </select>
                        <?php endif; ?>

                        <!-- BOTÓN PRINCIPAL (REGISTRAR O ACTUALIZAR) -->
                        <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                            class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                            <?= $editar ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <!-- BOTÓN CANCELAR SOLO EN MODO EDICIÓN -->
                        <?php if ($editar): ?>
                            <a href="maquinaria_crud.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>

        <?php endif; ?>


        <!-- =========================================================
             TABLA DE MAQUINARIA
        ========================================================== -->
        <div class="table-responsive">

            <table class="table table-hover tabla-jefe-mant">

                <!-- ENCABEZADO -->
                <thead class="thead-jefe-mant">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Fecha Compra</th>
                        <th>Estado</th>

                        <!-- Solo admin puede ver acciones -->
                        <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>

                <!-- CUERPO DE LA TABLA -->
                <tbody>

                    <!-- Contador visual -->
                    <?php $i = 1; ?>

                    <!-- Recorrer todas las máquinas -->
                    <?php while ($m = $res->fetch_assoc()): ?>

                        <tr>
                            <!-- ID VISUAL -->
                            <td><span class="badge-mantenimientor">#<?= $i++ ?></span></td>

                            <!-- DATOS -->
                            <td><strong><?= $m['Nombre'] ?></strong></td>
                            <td><strong><?= $m['Marca'] ?></strong></td>
                            <td><strong><?= $m['Modelo'] ?></strong></td>
                            <td><strong><?= $m['Fecha_Compra'] ?></strong></td>

                            <!-- ESTADO DE LA MÁQUINA -->
                            <td>
                                <strong>
                                    <?= $m['Estado'] == 'ACTIVA'
                                        ? '<span class="estado-activa-mant">ACTIVA</span>'
                                        : '<span class="estado-inactiva-mant">INACTIVA</span>' ?>
                                </strong>
                            </td>

                            <!-- ACCIONES SOLO ADMIN -->
                            <?php if ($esAdmin): ?>
                                <td>
                                    <div class="acciones-botones">

                                        <!-- BOTÓN EDITAR -->
                                        <a href="?editar=<?= $m['Id_Maquina'] ?>" class="btn-editar">✏️</a>

                                        <!-- SI ESTÁ ACTIVA -->
                                        <?php if ($m['Estado'] == 'ACTIVA'): ?>
                                            <button class="btn btn-secondary btn-sm"
                                                onclick="inactivarMaquina(<?= $m['Id_Maquina'] ?>)">
                                                🚫 Inactivar
                                            </button>

                                            <!-- SI ESTÁ INACTIVA -->
                                        <?php else: ?>
                                            <button class="btn btn-secondaryy btn-sm"
                                                onclick="activarMaquina(<?= $m['Id_Maquina'] ?>)">
                                                ✅ Reactivar
                                            </button>

                                            <!-- ELIMINAR -->
                                            <button class="btn-eliminar-log"
                                                onclick="eliminarMaquina(<?= $m['Id_Maquina'] ?>)">
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

    <!-- =========================================================
         FUNCIONES JS + SWEETALERT
    ========================================================== -->
    <script>
        // CONFIRMAR INACTIVACIÓN
        function inactivarMaquina(id) {
            Swal.fire({
                title: '¿Inactivar máquina?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, inactivar'
            }).then((r) => {
                if (r.isConfirmed) location = '?inactivar=' + id;
            });
        }

        // CONFIRMAR REACTIVACIÓN
        function activarMaquina(id) {
            Swal.fire({
                title: '¿Reactivar máquina?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, reactivar'
            }).then((r) => {
                if (r.isConfirmed) location = '?activar=' + id;
            });
        }

        // CONFIRMAR ELIMINACIÓN
        function eliminarMaquina(id) {
            Swal.fire({
                title: '¿Eliminar máquina?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            }).then((r) => {
                if (r.isConfirmed) {
                    location = '?eliminar=' + id;
                }
            });
        }

        /* ================= ALERTAS ================= */

        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Máquina registrada', 'success');

        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Máquina actualizada', 'success');

        <?php elseif ($alerta == "inactivado"): ?>
            Swal.fire('Listo', 'Máquina inactivada', 'success');

        <?php elseif ($alerta == "activado"): ?>
            Swal.fire('Listo', 'Máquina reactivada', 'success');

        <?php elseif ($alerta == "eliminado"): ?>
            Swal.fire('Eliminada', 'Máquina eliminada correctamente', 'success');

        <?php endif; ?>
    </script>

</body>

</html>