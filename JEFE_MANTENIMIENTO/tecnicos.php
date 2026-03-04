<?php
// Inicia la sesión para acceder a variables de usuario logueado
session_start();

// Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

// Variable que controla las alertas del sistema
$alerta = "";

/* =========================
   🔐 VALIDAR ROL
========================= */

// Obtiene el rol del usuario en sesión
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));

// Normaliza el texto (quita tildes y espacios)
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// Verifica si el usuario es administrador o jefe de mantenimiento
$es_admin = ($rol === 'JEFE_DE_MANTENIMIENTO' || $rol === 'ADMINISTRADOR');


/* =========================
   🟢 REGISTRAR TÉCNICO
========================= */

// Si se presiona el botón registrar y el usuario es admin
if (isset($_POST['registrar']) && $es_admin) {

    // Inserta el nuevo técnico en la tabla usuarios
    $conexion->query("
        INSERT INTO usuarios
        (Cedula, Nombre, Apellido, Correo, Usuario, Contraseña, Id_Tipo_Usuario, Estado, Fecha_Creacion)
        VALUES (
            '{$_POST['Cedula']}',
            '{$_POST['Nombre']}',
            '{$_POST['Apellido']}',
            '{$_POST['Correo']}',
            '{$_POST['Usuario']}',
            '{$_POST['Contrasena']}',
            3,
            'ACTIVO',
            NOW()
        )
    ");

    // Activa alerta de registro exitoso
    $alerta = "registrado";
}


/* =========================
   🔴 DESACTIVAR TÉCNICO
========================= */

// Si se envía parámetro desactivar por GET y es admin
if ($es_admin && isset($_GET['desactivar'])) {

    // Convierte el ID a número entero por seguridad
    $id = intval($_GET['desactivar']);

    // Actualiza el estado del usuario a INACTIVO
    $conexion->query("
        UPDATE usuarios 
        SET Estado='INACTIVO'
        WHERE Cedula='$id'
    ");

    // Activa alerta
    $alerta = "desactivado";
}


/* =========================
   🟢 ACTIVAR TÉCNICO
========================= */

// Si se envía parámetro activar
if ($es_admin && isset($_GET['activar'])) {

    // Obtiene la cédula
    $id = intval($_GET['activar']);

    // Cambia el estado a ACTIVO
    $conexion->query("
        UPDATE usuarios 
        SET Estado='ACTIVO'
        WHERE Cedula='$id'
    ");

    // Activa alerta
    $alerta = "activado";
}


/* =========================
   🗑️ ELIMINAR TÉCNICO
========================= */

// Si se solicita eliminar un técnico
if ($es_admin && isset($_GET['eliminar'])) {

    // Obtiene el ID
    $id = intval($_GET['eliminar']);

    // Verifica si tiene mantenimientos asociados
    $check = $conexion->query("
        SELECT COUNT(*) AS total
        FROM mantenimiento
        WHERE Cedula_Usuario='$id'
    ")->fetch_assoc();

    // Si no tiene mantenimientos se puede eliminar
    if ($check['total'] == 0) {

        // Elimina el usuario de la tabla
        $conexion->query("
            DELETE FROM usuarios
            WHERE Cedula='$id' AND Id_Tipo_Usuario=3
        ");

        $alerta = "eliminado";
    } else {

        // No se puede eliminar porque tiene registros asociados
        $alerta = "no_eliminado";
    }
}



/* =========================
   ✏️ EDITAR TÉCNICO
========================= */

// Inicializa variables
$editar = false;
$trabajador_edit = [];

// Si se recibe parámetro editar y es admin
if (isset($_GET['editar']) && $es_admin) {

    // Consulta el usuario técnico por cédula
    $res_edit = $conexion->query("
    SELECT * FROM usuarios
    WHERE Cedula='{$_GET['editar']}' AND Id_tipo_usuario
");
    // Guarda los datos del técnico
    $trabajador_edit = $res_edit->fetch_assoc();

    // Activa modo edición
    $editar = true;
}


/* =========================
   💾 ACTUALIZAR TÉCNICO
========================= */

// Si se envía formulario de actualización
if (isset($_POST['actualizar']) && $es_admin) {

    // Actualiza los datos del técnico
    $conexion->query("
        UPDATE usuarios SET
        Nombre='{$_POST['Nombre']}',
        Apellido='{$_POST['Apellido']}',
        Correo='{$_POST['Correo']}',
        Usuario='{$_POST['Usuario']}'
        WHERE Cedula='{$_POST['Cedula']}' AND Id_tipo_usuario=3
    ");

    // Activa alerta
    $alerta = "actualizado";
}


/* =========================
   📄 LISTAR TÉCNICOS
========================= */

// Si el usuario es administrador
if ($es_admin) {

    // Consulta todos los técnicos y roles relacionados
    $res = $conexion->query("
        SELECT 
            u.Cedula,
            u.Nombre,
            u.Apellido,
            u.Correo,
            u.Usuario,
            u.Estado,
            u.Fecha_Creacion,
            t.Nombre_tipo
        FROM usuarios u
        INNER JOIN tipo_usuario t 
            ON u.Id_tipo_usuario = t.Id_tipo_usuario
        WHERE u.Id_tipo_usuario IN (3,8,10)
        ORDER BY u.Nombre ASC
    ");
} else {

    // Si es trabajador, solo puede ver su propia información
    $cedula = $_SESSION['Cedula'];

    $res = $conexion->query("
        SELECT 
            u.Cedula,
            u.Nombre,
            u.Apellido,
            u.Correo,
            u.Usuario,
            u.Estado,
            u.Fecha_Creacion,
            t.Nombre_tipo
        FROM usuarios u
        INNER JOIN tipo_usuario t 
            ON u.Id_tipo_usuario = t.Id_tipo_usuario
        WHERE u.Id_tipo_usuario IN (3,8,10)
        AND u.Cedula = '$cedula'
        ORDER BY u.Nombre ASC
    ");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Técnicos - Jefe</title>

    <!-- Bootstrap para estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos personalizados del jefe de mantenimiento -->
    <link rel="stylesheet" href="../CSS/jefe_mantenimiento.css">

    <!-- Librería SweetAlert para alertas bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container mt-4 jefe-mantenimiento">

        <!-- TÍTULO PRINCIPAL -->
        <h3 class="jefe-title">🦺 Técnicos de Mantenimiento • Kaiu Home</h3>

        <?php if ($es_admin): ?>
            <!-- 🟢 FORMULARIO DE REGISTRO / EDICIÓN -->
            <div class="card card-jefe-mant mb-4">
                <div class="card-header card-header-jefe-mant">
                    <!-- Cambia el título según modo edición -->
                    <?= $editar ? 'Editar Técnico' : 'Registrar Técnico' ?>
                </div>

                <div class="card-body">
                    <form method="POST" class="row g-2">

                        <!-- Campo oculto si se está editando -->
                        <?php if ($editar): ?>
                            <input type="hidden" name="Cedula" value="<?= $trabajador_edit['Cedula'] ?>">
                        <?php else: ?>
                            <!-- Campo cédula solo al registrar -->
                            <input type="number" name="Cedula" class="form-control col" placeholder="Cédula" required>
                        <?php endif; ?>

                        <!-- Nombre -->
                        <input type="text" name="Nombre" class="form-control col"
                            value="<?= $trabajador_edit['Nombre'] ?? '' ?>" placeholder="Nombre" required>

                        <!-- Apellido -->
                        <input type="text" name="Apellido" class="form-control col"
                            value="<?= $trabajador_edit['Apellido'] ?? '' ?>" placeholder="Apellido" required>

                        <!-- Correo -->
                        <input type="email" name="Correo" class="form-control col"
                            value="<?= $trabajador_edit['Correo'] ?? '' ?>" placeholder="Correo" required>

                        <!-- Usuario -->
                        <input type="text" name="Usuario" class="form-control col"
                            value="<?= $trabajador_edit['Usuario'] ?? '' ?>" placeholder="Usuario" required>

                        <!-- Contraseña solo al registrar -->
                        <?php if (!$editar): ?>
                            <input type="password" name="Contrasena" class="form-control col"
                                placeholder="Contraseña" required>
                        <?php endif; ?>

                        <!-- Botón dinámico -->
                        <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                            class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                            <?= $editar ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <!-- Botón cancelar si está editando -->
                        <?php if ($editar): ?>
                            <a href="trabajadores.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📋 TABLA DE TÉCNICOS -->
        <div class="table-responsive">
            <table class="table table-hover tabla-jefe-mant">
                <thead class="thead-jefe-mant">
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Correo</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <?php if ($es_admin): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <?php $i = 1; ?> <!-- CONTADOR VISUAL -->
                    <?php while ($t = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- ID visual -->
                            <td><span class="badge-mantenimientor">#<?= $i++ ?></span></td>

                            <!-- Datos del técnico -->
                            <td><strong><?= $t['Nombre'] ?></strong></td>
                            <td><strong><?= $t['Apellido'] ?></strong></td>
                            <td><strong><?= $t['Correo'] ?></strong></td>
                            <td><strong><?= $t['Usuario'] ?></strong></td>

                            <!-- Estado dinámico -->
                            <td>
                                <strong>
                                    <?= $t['Estado'] == 'ACTIVO'
                                        ? '<span class="estado-activa-mant">ACTIVO</span>'
                                        : '<span class="estado-inactiva-mant">INACTIVA</span>' ?>
                                </strong>
                            </td>

                            <!-- Fecha -->
                            <td><strong><?= $t['Fecha_Creacion'] ?></strong></td>

                            <!-- ACCIONES SOLO ADMIN -->
                            <?php if ($es_admin): ?>
                                <td>
                                    <div class="acciones-botones">

                                        <!-- Editar -->
                                        <a href="?editar=<?= $t['Cedula'] ?>" class="btn-editar">✏️</a>

                                        <!-- Cambiar estado -->
                                        <?php if ($t['Estado'] == 'ACTIVO'): ?>
                                            <button class="btn btn-secondary btn-sm"
                                                onclick="cambiarEstado(<?= $t['Cedula'] ?>,'desactivar')">🚫 Inactivar</button>
                                        <?php else: ?>
                                            <button class="btn btn-secondaryy btn-sm"
                                                onclick="cambiarEstado(<?= $t['Cedula'] ?>,'activar')">✅ Reactivar</button>

                                            <!-- Eliminar -->
                                            <button class="btn-eliminar-log"
                                                onclick="eliminarTrabajador(<?= $t['Cedula'] ?>)">🗑️</button>
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

    <!-- =========================
         🔥 FUNCIONES JS + ALERTAS
    ========================= -->
    <script>
        // Cambiar estado (activar / desactivar)
        function cambiarEstado(id, accion) {
            Swal.fire({
                title: '¿Confirmar acción?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar'
            }).then((r) => {
                if (r.isConfirmed) {
                    window.location = '?' + accion + '=' + id;
                }
            });
        }

        // Eliminar técnico
        function eliminarTrabajador(cedula) {
            Swal.fire({
                title: '¿Eliminar técnico?',
                text: 'Solo se eliminará si no tiene mantenimientos',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar'
            }).then((r) => {
                if (r.isConfirmed) {
                    window.location = '?eliminar=' + cedula;
                }
            });
        }

        // ALERTAS DEL SISTEMA SEGÚN ACCIÓN
        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Técnico registrado', 'success');
        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Técnico actualizado', 'success');
        <?php elseif ($alerta == "desactivado"): ?>
            Swal.fire('Hecho', 'Técnico desactivado', 'info');
        <?php elseif ($alerta == "activado"): ?>
            Swal.fire('Hecho', 'Técnico activado', 'success');
        <?php elseif ($alerta == "eliminado"): ?>
            Swal.fire('Eliminado', 'Técnico eliminado correctamente', 'success');
        <?php elseif ($alerta == "no_eliminado"): ?>
            Swal.fire('Error', 'No se puede eliminar: tiene mantenimientos', 'error');
        <?php endif; ?>
    </script>

</body>

</html>