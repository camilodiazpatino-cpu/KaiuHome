<?php
session_start(); // ▶ Inicia la sesión para poder acceder a variables de usuario logueado
include("../CONEXION/conexion.php"); // ▶ Incluye la conexión a la base de datos

// ▶ Activa los reportes de errores de MySQLi para mostrar errores en desarrollo
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   🔐 VALIDAR ROL
========================= */

// ▶ Obtiene el nombre del tipo de usuario desde la sesión (ej: "Jefe de logística")
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));

// ▶ Normaliza el texto: elimina tildes y reemplaza espacios por guiones bajos
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// ▶ Verifica si el usuario es administrador o jefe de logística
$es_admin = in_array($rol, ['JEFE_DE_LOGISTICA', 'ADMINISTRADOR']);

// ▶ Si NO tiene permisos, lo saca del módulo
if (!$es_admin) {
    header("Location: ../DASHBOARD/dashboard.php");
    exit;
}

// ▶ Variables de control para la interfaz
$alerta = "";          // Mensaje que se mostrará en SweetAlert
$editar = false;       // Bandera para saber si estamos editando
$trabajador_edit = []; // Arreglo donde se guardan los datos del trabajador a editar

/* =========================
   🟢 REGISTRAR
========================= */

// ▶ Si el formulario se envió para registrar un trabajador
if (isset($_POST['registrar'])) {

    // ▶ Inserta un nuevo usuario tipo trabajador (Id_Tipo_Usuario = 2)
    $conexion->prepare("
        INSERT INTO usuarios
        (Cedula, Nombre, Apellido, Correo, Usuario, Contraseña, Id_Tipo_Usuario, Estado, Fecha_Creacion)
        VALUES (?, ?, ?, ?, ?, ?, 2, 'ACTIVO', NOW())
    ")->execute([
        $_POST['Cedula'],     // Cédula del trabajador
        $_POST['Nombre'],     // Nombre
        $_POST['Apellido'],   // Apellido
        $_POST['Correo'],     // Correo
        $_POST['Usuario'],    // Usuario de acceso
        $_POST['Contrasena']  // Contraseña
    ]);

    // ▶ Activa alerta de éxito
    $alerta = "registrado";
}

/* =========================
   🔴 DESACTIVAR
========================= */

// ▶ Si se recibe un parámetro GET llamado "desactivar"
if (isset($_GET['desactivar'])) {

    // ▶ Cambia el estado del usuario a INACTIVO
    $conexion->query("
        UPDATE usuarios 
        SET Estado='INACTIVO'
        WHERE Cedula='{$_GET['desactivar']}'
    ");

    // ▶ Activa alerta
    $alerta = "desactivado";
}

/* =========================
   🟢 ACTIVAR
========================= */

// ▶ Si se recibe el parámetro "activar"
if (isset($_GET['activar'])) {

    // ▶ Cambia el estado del usuario a ACTIVO
    $conexion->query("
        UPDATE usuarios 
        SET Estado='ACTIVO'
        WHERE Cedula='{$_GET['activar']}'
    ");

    // ▶ Activa alerta
    $alerta = "activado";
}

/* =========================
   🗑️ ELIMINAR (SOLO INACTIVO)
========================= */

// ▶ Si se solicita eliminar un trabajador
if (isset($_GET['eliminar'])) {

    // ▶ Guarda la cédula del trabajador a eliminar
    $cedula = $_GET['eliminar'];

    // ▶ Verifica si el trabajador tiene compras asociadas
    $check = $conexion->query("
        SELECT COUNT(*) total
        FROM compras
        WHERE Cedula_Usuario='$cedula'
    ")->fetch_assoc();

    // ▶ Si NO tiene registros relacionados, se puede eliminar
    if ($check['total'] == 0) {

        // ▶ Elimina el usuario SOLO si está INACTIVO y es tipo trabajador
        $conexion->query("
            DELETE FROM usuarios
            WHERE Cedula='$cedula'
            AND Estado='INACTIVO'
            AND Id_Tipo_Usuario=2
        ");

        $alerta = "eliminado";
    } else {
        // ▶ Si tiene compras relacionadas, NO se elimina
        $alerta = "relacion";
    }
}

/* =========================
   ✏️ EDITAR
========================= */

// ▶ Si se recibe el parámetro editar con una cédula
if (isset($_GET['editar'])) {

    // ▶ Busca el trabajador en la base de datos
    $res_edit = $conexion->query("
        SELECT * FROM usuarios
        WHERE Cedula='{$_GET['editar']}' AND Id_Tipo_Usuario=2
    ");

    // ▶ Si existe, se cargan los datos para el formulario
    if ($res_edit->num_rows) {
        $trabajador_edit = $res_edit->fetch_assoc(); // Datos del trabajador
        $editar = true; // Activa modo edición
    }
}

/* =========================
   💾 ACTUALIZAR
========================= */

// ▶ Si se envió el formulario de actualización
if (isset($_POST['actualizar'])) {

    // ▶ Actualiza los datos del trabajador
    $conexion->query("
        UPDATE usuarios SET
        Nombre='{$_POST['Nombre']}',
        Apellido='{$_POST['Apellido']}',
        Correo='{$_POST['Correo']}',
        Usuario='{$_POST['Usuario']}'
        WHERE Cedula='{$_POST['Cedula']}' AND Id_Tipo_Usuario=2
    ");

    // ▶ Activa alerta
    $alerta = "actualizado";
}

/* =========================
   📄 LISTAR
========================= */

// ▶ Consulta todos los trabajadores y otros tipos definidos
$res = $conexion->query("
    SELECT u.*, t.Nombre_tipo
    FROM usuarios u
    INNER JOIN tipo_usuario t 
        ON u.Id_tipo_usuario = t.Id_tipo_usuario
    WHERE u.Id_tipo_usuario IN (2,4,7,9,11,12)
    ORDER BY u.Nombre ASC
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Codificación de caracteres para soportar tildes y ñ -->
    <meta charset="UTF-8">

    <!-- Título de la pestaña del navegador -->
    <title>Trabajadores Logística</title>

    <!-- Bootstrap para estilos rápidos y responsive -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS personalizado del módulo de jefe de logística -->
    <link rel="stylesheet" href="../CSS/jefe_logistica.css">

    <!-- Librería SweetAlert2 para alertas bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Contenedor principal con margen superior y clase personalizada -->
    <div class="container mt-4 jefe-logistica">

        <!-- Título principal del módulo -->
        <h3 class="jefe-title">👷‍♂️ Trabajadores de Logística • Kaiu Home</h3>

        <!-- 🟢 TARJETA DEL FORMULARIO -->
        <div class="card card-jefe-logis mb-4">

            <!-- Encabezado dinámico: cambia entre editar o registrar -->
            <div class="card-header card-header-jefe-logis">
                <?= $editar ? 'Editar trabajador' : 'Registrar trabajador' ?>
            </div>

            <div class="card-body">

                <!-- Formulario principal -->
                <form method="POST" class="row g-2">

                    <!-- Si está en modo edición se oculta la cédula -->
                    <?php if ($editar): ?>
                        <input type="hidden" name="Cedula" value="<?= $trabajador_edit['Cedula'] ?>">
                    <?php else: ?>
                        <!-- Campo cédula para registrar -->
                        <input type="number" name="Cedula" class="form-control" placeholder="Cédula" required>
                    <?php endif; ?>

                    <!-- Campo nombre -->
                    <input name="Nombre" class="form-control" placeholder="Nombre"
                        value="<?= $trabajador_edit['Nombre'] ?? '' ?>" required>

                    <!-- Campo apellido -->
                    <input name="Apellido" class="form-control" placeholder="Apellido"
                        value="<?= $trabajador_edit['Apellido'] ?? '' ?>" required>

                    <!-- Campo correo electrónico -->
                    <input type="email" name="Correo" class="form-control" placeholder="Correo"
                        value="<?= $trabajador_edit['Correo'] ?? '' ?>" required>

                    <!-- Campo usuario -->
                    <input name="Usuario" class="form-control" placeholder="Usuario"
                        value="<?= $trabajador_edit['Usuario'] ?? '' ?>" required>

                    <!-- Contraseña solo cuando se registra -->
                    <?php if (!$editar): ?>
                        <input type="password" name="Contrasena" class="form-control" placeholder="Contraseña" required>
                    <?php endif; ?>

                    <!-- Botón dinámico: registrar o actualizar -->
                    <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                        class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                        <?= $editar ? 'Actualizar' : 'Registrar' ?>
                    </button>

                    <!-- Botón cancelar solo cuando se está editando -->
                    <?php if ($editar): ?>
                        <a href="trabajadores.php" class="btn btn-secondaryi">Cancelar</a>
                    <?php endif; ?>

                </form>
            </div>
        </div>

        <!-- 📋 TABLA DE TRABAJADORES -->
        <div class="table-responsive">
            <table class="table table-hover tabla-jefe-logis">

                <!-- Encabezado de la tabla -->
                <thead class="thead-jefe-logis">
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Correo</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- Contador visual -->
                    <?php $i = 1; ?>

                    <!-- Recorrer todos los trabajadores -->
                    <?php while ($t = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- Número visual -->
                            <td><span class="badge-logistica">#<?= $i++ ?></span></td>

                            <!-- Datos del trabajador -->
                            <td><strong><?= $t['Nombre'] ?></strong></td>
                            <td><strong><?= $t['Apellido'] ?></strong></td>
                            <td><strong><?= $t['Correo'] ?></strong></td>
                            <td><strong><?= $t['Usuario'] ?></strong></td>

                            <!-- Estado ACTIVO o INACTIVO con estilos -->
                            <td>
                                <strong>
                                    <?= $t['Estado'] == 'ACTIVO'
                                        ? '<span class="estado-activo-mant">ACTIVO</span>'
                                        : '<span class="estado-inactivo-mant">INACTIVO</span>' ?>
                                </strong>
                            </td>

                            <!-- Fecha de creación -->
                            <td><strong><?= $t['Fecha_Creacion'] ?></strong></td>

                            <!-- Botones de acción -->
                            <td>
                                <div class="acciones-botones">

                                    <!-- Botón editar -->
                                    <a href="?editar=<?= $t['Cedula'] ?>" class="btn-editar">✏️</a>

                                    <!-- Si está activo se puede inactivar -->
                                    <?php if ($t['Estado'] == 'ACTIVO'): ?>
                                        <button class="btn btn-secondary btn-sm"
                                            onclick="cambiarEstado(<?= $t['Cedula'] ?>,'desactivar')">🚫 Inactivar</button>

                                        <!-- Si está inactivo se puede activar o eliminar -->
                                    <?php else: ?>
                                        <button class="btn btn-secondaryy btn-sm"
                                            onclick="cambiarEstado(<?= $t['Cedula'] ?>,'activar')">✅ Reactivar</button>

                                        <button class="btn-eliminar-log"
                                            onclick="eliminarTrabajador(<?= $t['Cedula'] ?>)">🗑️</button>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>

        <!-- Botón volver al dashboard -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">⬅ Volver</a>
        </div>

    </div>

    <!-- 🔥 SCRIPTS JS -->
    <script>
        // Cambiar estado (activar o desactivar)
        function cambiarEstado(id, accion) {
            Swal.fire({
                title: '¿Confirmar acción?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí'
            }).then((r) => {
                if (r.isConfirmed) {
                    location.href = '?' + accion + '=' + id;
                }
            });
        }

        // Eliminar trabajador
        function eliminarTrabajador(id) {
            Swal.fire({
                title: '¿Eliminar trabajador?',
                text: 'Solo si está INACTIVO y sin registros',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar'
            }).then((r) => {
                if (r.isConfirmed) {
                    location.href = '?eliminar=' + id;
                }
            });
        }

        // <!-- ALERTAS SEGÚN ACCIONES DEL PHP -->

        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Trabajador registrado', 'success');

        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Trabajador actualizado', 'success');

        <?php elseif ($alerta == "desactivado"): ?>
            Swal.fire('Hecho', 'Trabajador desactivado', 'info');

        <?php elseif ($alerta == "activado"): ?>
            Swal.fire('Hecho', 'Trabajador activado', 'success');

        <?php elseif ($alerta == "eliminado"): ?>
            Swal.fire('Eliminado', 'Trabajador eliminado correctamente', 'success');

        <?php elseif ($alerta == "no_eliminado"): ?>
            Swal.fire('Error', 'No se puede eliminar: tiene mantenimientos', 'error');
        <?php endif; ?>
    </script>

</body>

</html>