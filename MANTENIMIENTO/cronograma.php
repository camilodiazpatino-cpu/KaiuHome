<<?php
    // 🔐 Inicia la sesión para poder usar variables de usuario logueado
    session_start();

    // 🔌 Incluye la conexión a la base de datos
    require_once("../CONEXION/conexion.php");

    // 📌 Obtiene la cédula del usuario que inició sesión
    $cedula = $_SESSION['Cedula'];

    // 📢 Variables para mostrar mensajes en pantalla (alertas)
    $mensaje = "";
    $tipo = "";

    /* ======================================================
   🟢 INSERTAR NUEVO MANTENIMIENTO EN EL CRONOGRAMA
====================================================== */
    if (isset($_POST['guardar'])) {

        // 📥 Captura los datos del formulario
        $equipo = $_POST['equipo'];
        $trabajo = $_POST['trabajo'];
        $fecha = $_POST['fecha'];
        $estado = $_POST['estado'];

        // 🔄 Si el estado es BUENA → Activo = 1, si no → 0
        $activo = ($estado == 'BUENA') ? 1 : 0;

        // 📝 Consulta SQL para insertar el registro
        $sql = "INSERT INTO cronograma_mantenimiento 
    (Cedula, Descripcion_Equipo, Descripcion_Trabajo, Fecha_Registro, Estado, Activo)
    VALUES ('$cedula','$equipo','$trabajo','$fecha','$estado','$activo')";

        // ▶ Ejecuta la consulta
        if ($conexion->query($sql)) {
            // ✅ Mensaje si todo salió bien
            $mensaje = "Mantenimiento registrado correctamente";
            $tipo = "success";
        } else {
            // ❌ Mensaje si hubo error
            $mensaje = "Error al registrar";
            $tipo = "error";
        }
    }

    /* ======================================================
   🔴 ELIMINAR REGISTRO DEL CRONOGRAMA
====================================================== */
    if (isset($_GET['eliminar'])) {

        // 📌 Obtiene el ID del registro a eliminar
        $id = $_GET['eliminar'];

        // 🗑 Ejecuta eliminación directa en la base de datos
        if ($conexion->query("DELETE FROM cronograma_mantenimiento WHERE Id_Cronograma = $id")) {
            $mensaje = "Registro eliminado correctamente";
            $tipo = "success";
        } else {
            $mensaje = "Error al eliminar";
            $tipo = "error";
        }
    }

    /* ======================================================
   🟡 ACTUALIZAR REGISTRO DE MANTENIMIENTO
====================================================== */
    if (isset($_POST['editar'])) {

        // 📥 Captura datos del formulario de edición
        $id = $_POST['id'];
        $equipo = $_POST['equipo'];
        $trabajo = $_POST['trabajo'];
        $fecha = $_POST['fecha'];
        $estado = $_POST['estado'];

        // 🔄 Determina si queda activo según el estado
        $activo = ($estado == 'BUENA') ? 1 : 0;

        // 📝 Consulta SQL para actualizar el registro
        $sql = "UPDATE cronograma_mantenimiento SET
        Descripcion_Equipo='$equipo',
        Descripcion_Trabajo='$trabajo',
        Fecha_Registro='$fecha',
        Estado='$estado',
        Activo='$activo'
        WHERE Id_Cronograma='$id'";

        // ▶ Ejecuta actualización
        if ($conexion->query($sql)) {
            $mensaje = "Mantenimiento actualizado";
            $tipo = "success";
        } else {
            $mensaje = "Error al actualizar";
            $tipo = "error";
        }
    }

    /* ======================================================
   🔎 CONSULTA GENERAL DEL CRONOGRAMA
====================================================== */

    // 📄 Trae todos los registros ordenados por fecha (más reciente primero)
    $sql = "SELECT * FROM cronograma_mantenimiento ORDER BY Fecha_Registro DESC";

    // ▶ Ejecuta la consulta
    $resultado = $conexion->query($sql);
    ?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">

        <!-- 🧾 Título de la página -->
        <title>Cronograma Mantenimiento</title>

        <!-- 🎨 Bootstrap para estilos rápidos -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- 🚨 Librería SweetAlert para alertas bonitas -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- 🎨 CSS personalizado del sistema Kaiu -->
        <link rel="stylesheet" href="../CSS/kaiu.css">

    </head>

    <body>

        <!-- 📦 Contenedor principal -->
        <div class="container mt-5 kaiu-container">

            <!-- 🎴 Tarjeta visual del módulo -->
            <div class="card-kaiu">

                <!-- 🏷️ Título del módulo -->
                <h3 class="kaiu-title">💎 Cronograma de Mantenimiento • Kaiu Home</h3>

                <!-- ======================================================
                 📝 FORMULARIO DE REGISTRO Y EDICIÓN
            ======================================================= -->
                <form method="POST" class="formulario">

                    <!-- 🔐 Campo oculto para guardar el ID cuando se edita -->
                    <input type="hidden" name="id" id="id">

                    <!-- 🏭 Equipo o máquina -->
                    <div>
                        <label>Equipo / Máquina</label>
                        <input type="text" name="equipo" id="equipo" required>
                    </div>

                    <!-- 🛠 Descripción del trabajo -->
                    <div>
                        <label>Descripción del Trabajo</label>
                        <textarea name="trabajo" id="trabajo" required></textarea>
                    </div>

                    <!-- 📅 Fecha del mantenimiento -->
                    <div>
                        <label>Fecha</label>
                        <input type="date" name="fecha" id="fecha" required>
                    </div>

                    <!-- 📌 Estado del equipo -->
                    <div>
                        <label>Estado</label>
                        <select name="estado" id="estado" required>
                            <option value="BUENA">BUENA</option>
                            <option value="MALA">MALA</option>
                        </select>
                    </div>

                    <!-- 💾 Botón guardar (modo registro) -->
                    <button type="submit" name="guardar" id="btnGuardar">💾 Registrar</button>

                    <!-- ✏️ Botón editar (modo actualización) -->
                    <button type="submit" name="editar" id="btnEditar" style="display:none;">Actualizar</button>

                </form>

                <hr>

                <!-- ======================================================
                 📊 TABLA DE MANTENIMIENTOS REGISTRADOS
            ======================================================= -->
                <table class="tablee kaiu-tablee">

                    <thead>
                        <tr>
                            <th>Equipo</th>
                            <th>Trabajo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- 🔁 Recorre todos los registros del cronograma -->
                        <?php while ($row = $resultado->fetch_assoc()): ?>

                            <tr>

                                <!-- 🏭 Nombre del equipo -->
                                <td><strong><?= $row['Descripcion_Equipo'] ?></strong></td>

                                <!-- 🛠 Trabajo realizado -->
                                <td><strong><?= $row['Descripcion_Trabajo'] ?></strong></td>

                                <!-- 📅 Fecha -->
                                <td><strong><?= $row['Fecha_Registro'] ?></strong></td>

                                <!-- 📌 Estado con estilo dinámico -->
                                <td>
                                    <span class="estado-badge 
        <?= $row['Estado'] == 'BUENA' ? 'estado-buena' : 'estado-mala' ?>">
                                        <?= $row['Estado'] ?>
                                    </span>
                                </td>

                                <!-- 🔋 Activo / Inactivo -->
                                <td>
                                    <span class="estado-badge 
        <?= $row['Activo'] == 1 ? 'estado-activo' : 'estado-inactivo' ?>">
                                        <?= $row['Activo'] == 1 ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>

                                <!-- ⚙️ Acciones -->
                                <td>

                                    <!-- ✏️ BOTÓN EDITAR -->
                                    <button onclick="editarRegistro(
'<?= $row['Id_Cronograma'] ?>',
'<?= $row['Descripcion_Equipo'] ?>',
'<?= $row['Descripcion_Trabajo'] ?>',
'<?= $row['Fecha_Registro'] ?>',
'<?= $row['Estado'] ?>'
)">✏️</button>

                                    <!-- 🗑 BOTÓN ELIMINAR -->
                                    <button onclick="confirmarEliminacion(<?= $row['Id_Cronograma'] ?>)">🗑</button>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>
                </table>

                <!-- 🔙 BOTÓN VOLVER -->
                <div class="text-center mt-4">
                    <a href="../DASHBOARD/dashboard.php" class="btn btn-kaiu">
                        ⬅ Volver
                    </a>
                </div>

            </div>

        </div>

        <!-- ======================================================
         ⚙️ SCRIPTS JS DEL MÓDULO
    ======================================================= -->
        <script>
            /* ======================================================
           ✏️ CARGAR DATOS EN EL FORMULARIO PARA EDITAR
        ======================================================= */
            function editarRegistro(id, equipo, trabajo, fecha, estado) {

                // 📥 Inserta los datos en el formulario
                document.getElementById("id").value = id;
                document.getElementById("equipo").value = equipo;
                document.getElementById("trabajo").value = trabajo;
                document.getElementById("fecha").value = fecha;
                document.getElementById("estado").value = estado;

                // 🔄 Cambia botones: oculta guardar y muestra editar
                document.getElementById("btnGuardar").style.display = "none";
                document.getElementById("btnEditar").style.display = "inline-block";
            }

            /* ======================================================
               🗑 CONFIRMACIÓN DE ELIMINACIÓN (SweetAlert)
            ======================================================= */
            function confirmarEliminacion(id) {
                Swal.fire({
                    title: '¿Eliminar mantenimiento?',
                    text: "No podrás revertir esto",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar'
                }).then((result) => {

                    // ✅ Si confirma → redirige para eliminar
                    if (result.isConfirmed) {
                        window.location = "cronograma.php?eliminar=" + id;
                    }
                })
            }

            /* ======================================================
               🔄 CAMBIO AUTOMÁTICO DE ACTIVO / INACTIVO
            ======================================================= */
            document.getElementById("estado").addEventListener("change", function() {
                let estado = this.value;

                // 🧠 Solo imprime en consola (puedes usarlo para lógica futura)
                console.log(estado === "BUENA" ? "Activo" : "Inactivo");
            });
        </script>

        <!-- ======================================================
         🚨 ALERTA GLOBAL DE MENSAJES (INSERTAR / EDITAR / ELIMINAR)
    ======================================================= -->
        <?php if ($mensaje != ""): ?>
            <script>
                Swal.fire({
                    title: "<?= $mensaje ?>",
                    icon: "<?= $tipo ?>",
                    confirmButtonText: "OK"
                });
            </script>
        <?php endif; ?>

    </body>

</html>