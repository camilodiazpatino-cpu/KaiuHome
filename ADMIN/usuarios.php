<?php
// Inicia la sesión para poder usar variables de sesión
session_start();

// Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

/* 🔐 Validar sesión */

// Verifica que exista la sesión del usuario
if (!isset($_SESSION['Cedula'])) {

    // Si no hay sesión activa, redirige al login
    header("Location: ../LOGIN/login.php");
    exit; // Detiene la ejecución
}

/* 🔐 SOLO ADMIN */

// Verifica que el usuario sea ADMINISTRADOR
if (strtoupper($_SESSION['Nombre_tipo']) !== 'ADMINISTRADOR') {

    // Si no es administrador, lo envía al dashboard
    header("Location: ../DASHBOARD/dashboard.php");
    exit; // Detiene la ejecución
}

// Variable para mostrar alertas visuales (registrado, actualizado, etc.)
$alerta = "";

// Variable para saber si estamos en modo edición
$editar = false;

// Arreglo que almacenará los datos del usuario a editar
$usuario_edit = [];


/* =========================
   🟢 REGISTRAR USUARIO
========================= */

// Verifica si se presionó el botón "registrar"
if (isset($_POST['registrar'])) {

    // Inserta un nuevo usuario en la base de datos
    $conexion->query("
        INSERT INTO usuarios
        (Cedula, Nombre, Apellido, Correo, Usuario, Contraseña, Id_tipo_usuario, Estado, Fecha_Creacion)
        VALUES (
            '{$_POST['Cedula']}',
            '{$_POST['Nombre']}',
            '{$_POST['Apellido']}',
            '{$_POST['Correo']}',
            '{$_POST['Usuario']}',
            '{$_POST['Contrasena']}',
            '{$_POST['Id_tipo_usuario']}',
            'ACTIVO',         -- El usuario se crea activo por defecto
            NOW()             -- Guarda fecha y hora actual
        )
    ");

    // Activa alerta de registro exitoso
    $alerta = "registrado";
}


/* =========================
   🔁 ACTIVAR / INACTIVAR
========================= */

// Verifica si viene parámetro GET llamado "estado"
if (isset($_GET['estado'])) {

    // Cambia el estado del usuario:
    // Si está ACTIVO → lo pone INACTIVO
    // Si está INACTIVO → lo pone ACTIVO
    $conexion->query("
        UPDATE usuarios 
        SET Estado = IF(Estado='ACTIVO','INACTIVO','ACTIVO')
        WHERE Cedula='{$_GET['estado']}'
    ");

    // Activa alerta de cambio de estado
    $alerta = "estado";
}


/* =========================
   ✏️ OBTENER USUARIO
========================= */

// Verifica si se quiere editar un usuario
if (isset($_GET['editar'])) {

    // Consulta el usuario específico por su cédula
    $res = $conexion->query("
        SELECT * FROM usuarios 
        WHERE Cedula='{$_GET['editar']}'
    ");

    // Guarda los datos del usuario en un arreglo
    $usuario_edit = $res->fetch_assoc();

    // Activa modo edición
    $editar = true;
}


/* =========================
   💾 ACTUALIZAR USUARIO
========================= */

// Verifica si se presionó el botón "actualizar"
if (isset($_POST['actualizar'])) {

    // Actualiza los datos del usuario en la base de datos
    $conexion->query("
        UPDATE usuarios SET
            Nombre='{$_POST['Nombre']}',
            Apellido='{$_POST['Apellido']}',
            Correo='{$_POST['Correo']}',
            Usuario='{$_POST['Usuario']}',
            Id_tipo_usuario='{$_POST['Id_tipo_usuario']}'
        WHERE Cedula='{$_POST['Cedula']}'
    ");

    // Activa alerta de actualización
    $alerta = "actualizado";
}


/* =========================
   📄 LISTAR USUARIOS
========================= */

// Consulta todos los usuarios junto con su tipo de usuario
$res = $conexion->query("
    SELECT u.*, t.Nombre_tipo
    FROM usuarios u
    INNER JOIN tipo_usuario t 
        ON u.Id_tipo_usuario = t.Id_tipo_usuario
    ORDER BY u.Fecha_Creacion ASC
");
// Ordena los usuarios por fecha de creación (más antiguos primero)
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8"> <!-- Codificación UTF-8 para soportar tildes y ñ -->
    <title>Gestión de Usuarios • Kaiu Home</title> <!-- Título de la pestaña -->

    <!-- Bootstrap para estilos rápidos y responsivos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos personalizados del panel administrador -->
    <link rel="stylesheet" href="../CSS/admin.css">

    <!-- Librería SweetAlert2 para alertas bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="container mt-4 admin-panel">

        <!-- TÍTULO PRINCIPAL -->
        <h3 class="admin-main-title">👤 Gestión de Usuarios • Kaiu Home</h3>

        <!-- 🟢 FORMULARIO DE REGISTRO / EDICIÓN -->
        <div class="card admin-form-card mb-4">

            <!-- Título dinámico según si está editando o registrando -->
            <div class="card-header admin-card-header">
                <?= $editar ? 'Editar usuario' : 'Registrar usuario' ?>
            </div>

            <div class="card-body">

                <!-- Formulario que envía datos por método POST -->
                <form method="POST" class="row g-2">

                    <!-- Campo Cédula -->
                    <!-- Si está editando → readonly -->
                    <!-- Si está registrando → required -->
                    <input type="number" name="Cedula"
                        class="form-control col"
                        value="<?= $usuario_edit['Cedula'] ?? '' ?>"
                        <?= $editar ? 'readonly' : 'required' ?>
                        placeholder="Cédula">

                    <!-- Campo Nombre -->
                    <input type="text" name="Nombre" class="form-control col"
                        value="<?= $usuario_edit['Nombre'] ?? '' ?>" required placeholder="Nombre">

                    <!-- Campo Apellido -->
                    <input type="text" name="Apellido" class="form-control col"
                        value="<?= $usuario_edit['Apellido'] ?? '' ?>" required placeholder="Apellido">

                    <!-- Campo Correo -->
                    <input type="email" name="Correo" class="form-control col"
                        value="<?= $usuario_edit['Correo'] ?? '' ?>" required placeholder="Correo">

                    <!-- Campo Usuario -->
                    <input type="text" name="Usuario" class="form-control col"
                        value="<?= $usuario_edit['Usuario'] ?? '' ?>" required placeholder="Usuario">

                    <!-- Campo Contraseña solo aparece si se está registrando -->
                    <?php if (!$editar): ?>
                        <input type="password" name="Contrasena" class="form-control col" required placeholder="Contraseña">
                    <?php endif; ?>

                    <!-- Selector de Rol -->
                    <select name="Id_tipo_usuario" class="form-control col" required>
                        <option value="">Rol</option>

                        <!-- Se selecciona automáticamente si coincide con el usuario en edición -->
                        <option value="2" <?= ($usuario_edit['Id_tipo_usuario'] ?? '') == 2 ? 'selected' : '' ?>>Logística</option>
                        <option value="3" <?= ($usuario_edit['Id_tipo_usuario'] ?? '') == 3 ? 'selected' : '' ?>>Mantenimiento</option>
                        <option value="4" <?= ($usuario_edit['Id_tipo_usuario'] ?? '') == 4 ? 'selected' : '' ?>>Administrador</option>
                        <option value="5" <?= ($usuario_edit['Id_tipo_usuario'] ?? '') == 5 ? 'selected' : '' ?>>Jefe de Logística</option>
                        <option value="6" <?= ($usuario_edit['Id_tipo_usuario'] ?? '') == 6 ? 'selected' : '' ?>>Jefe de Mantenimiento</option>
                    </select>

                    <!-- Botón dinámico: Registrar o Actualizar -->
                    <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                        class="btn <?= $editar ? 'btn-warning' : 'btn-success' ?>">
                        <?= $editar ? 'Actualizar' : 'Registrar' ?>
                    </button>

                    <!-- Botón cancelar solo aparece en modo edición -->
                    <?php if ($editar): ?>
                        <a href="usuarios.php" class="btn btn-cancelar">Cancelar</a>
                    <?php endif; ?>

                </form>
            </div>
        </div>

        <!-- 📋 TABLA DE USUARIOS -->
        <div class="table-responsive">

            <!-- Tabla con efecto hover -->
            <table class="table table-hover admin-table">

                <!-- ENCABEZADOS -->
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php $i = 1; ?> <!-- Contador visual -->

                    <!-- Recorre todos los usuarios obtenidos en la consulta -->
                    <?php while ($u = $res->fetch_assoc()): ?>

                        <tr>

                            <!-- Número consecutivo visual -->
                            <td><strong><span class="badge-admin">#<?= $i++ ?></span></strong></td>

                            <!-- Nombre completo -->
                            <td><strong><?= $u['Nombre'] ?> <?= $u['Apellido'] ?></strong></td>

                            <!-- Correo -->
                            <td><strong><?= $u['Correo'] ?></strong></td>

                            <!-- Usuario -->
                            <td><strong><?= $u['Usuario'] ?></strong></td>

                            <!-- Rol con clase dinámica -->
                            <td>
                                <strong>
                                    <span class="rol <?= strtolower(str_replace(' ', '-', $u['Nombre_tipo'])) ?>">
                                        <?= $u['Nombre_tipo'] ?>
                                    </span>
                                </strong>
                            </td>

                            <!-- Estado ACTIVO o INACTIVO -->
                            <td>
                                <strong>
                                    <?= $u['Estado'] == 'ACTIVO'
                                        ? '<span class="estado-activo">ACTIVO</span>'
                                        : '<span class="estado-inactivo">INACTIVO</span>' ?>
                                </strong>
                            </td>

                            <!-- Fecha de creación -->
                            <td><strong><?= $u['Fecha_Creacion'] ?></strong></td>

                            <!-- Acciones -->
                            <td>
                                <div class="acciones-admin">

                                    <!-- Botón editar -->
                                    <a href="?editar=<?= $u['Cedula'] ?>" class="btn-editar">✏️</a>

                                    <!-- Botón cambiar estado -->
                                    <button
                                        class="<?= $u['Estado'] == 'ACTIVO' ? 'btn-estado-inactivar' : 'btn-estado-reactivar' ?>"
                                        onclick="cambiarEstado(<?= $u['Cedula'] ?>)">
                                        <?= $u['Estado'] == 'ACTIVO' ? '🚫 Inactivar' : '✅ Reactivar' ?>
                                    </button>

                                </div>
                            </td>

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

    <!-- SCRIPT DE CONFIRMACIÓN Y ALERTAS -->
    <script>
        // Función para confirmar cambio de estado
        function cambiarEstado(cedula) {
            Swal.fire({
                title: '¿Cambiar estado del usuario?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar'
            }).then((r) => {
                if (r.isConfirmed) {
                    // Redirige enviando parámetro GET estado
                    window.location = '?estado=' + cedula;
                }
            });
        }

       // <!--ALERTAS AUTOMÁTICAS SEGÚN ACCIÓN REALIZADA-- >

        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Usuario registrado', 'success');
        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Usuario actualizado', 'success');
        <?php elseif ($alerta == "estado"): ?>
            Swal.fire('Hecho', 'Estado actualizado', 'info');
        <?php endif; ?>
    </script>

</body>

</html>