<?php
// Inicia la sesión para poder verificar el usuario logueado
session_start();

// Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

// Validación: si no hay sesión activa, redirige al login
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

// Consulta SQL para obtener todos los productos con sus categorías y variaciones
$sql = "
    SELECT 
        p.Id_Producto,           -- ID del producto
        p.Nombre,                -- Nombre del producto
        p.Color,                 -- Color del producto
        p.Precio_Venta,          -- Precio de venta principal
        p.Cantidad,              -- Cantidad total en inventario
        c.Nombre_Categoria,      -- Nombre de la categoría
        v.Medida,                -- Nombre de la variación (si existe)
        v.Precio AS Precio_Medida,  -- Precio de la variación
        v.Cantidad AS Stock_Medida   -- Stock de la variación
    FROM productos p
    INNER JOIN categorias c 
        ON p.Id_Categoria = c.Id_Categoria
    LEFT JOIN variaciones_producto v
        ON p.Id_Producto = v.Id_Producto
    ORDER BY c.Nombre_Categoria ASC, p.Id_Producto ASC
";

// Ejecuta la consulta
$resultado = $conexion->query($sql);

// Arreglo para organizar los productos y sus variaciones
$productos = [];

// Recorre cada fila obtenida de la base de datos
while ($row = $resultado->fetch_assoc()) {

    $id = $row['Id_Producto'];  // ID del producto actual

    // Si aún no se ha agregado este producto al arreglo principal
    if (!isset($productos[$id])) {
        $productos[$id] = [
            'Id_Producto' => $row['Id_Producto'],       // ID del producto
            'Nombre' => $row['Nombre'],                 // Nombre
            'Color' => $row['Color'],                   // Color
            'Precio_Venta' => $row['Precio_Venta'],     // Precio de venta
            'Cantidad' => $row['Cantidad'],             // Stock general
            'Nombre_Categoria' => $row['Nombre_Categoria'], // Categoría
            'variaciones' => []                          // Array de variaciones (inicialmente vacío)
        ];
    }

    // Si la fila tiene variaciones (Medida no está vacía)
    if (!empty($row['Medida'])) {
        $productos[$id]['variaciones'][] = [
            'medida' => $row['Medida'],                 // Nombre de la variación
            'precio' => $row['Precio_Medida'],         // Precio de la variación
            'stock' => $row['Stock_Medida']            // Stock de la variación
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inventario Admin</title>

    <!-- Bootstrap para estilos y responsividad -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS personalizado para la interfaz de administración -->
    <link rel="stylesheet" href="../CSS/admin.css">
</head>

<body>

    <div class="container mt-5">
        <div class="card-kaiu-admin">

            <!-- Título principal -->
            <h3 class="kaiu-title-admin">👁️ Inventario General • Kaiu Home</h3>

            <!-- Buscador de productos -->
            <div class="mb-4 buscador-container-admin">
                <input type="text" id="buscador" class="buscador-admin"
                    placeholder="Buscar producto por nombre o color...">

                <!-- Mensaje cuando no se encuentra ningún producto -->
                <div id="mensajeNoEncontrado" class="mensaje-no mt-2 d-none">
                    ❌ No se encontró ningún producto
                </div>
            </div>

            <!-- Contenedor tipo acordeón para mostrar productos por categoría -->
            <div class="accordion" id="accordionInventario">

                <?php
                $categoria_actual = ""; // Variable para rastrear categoría actual
                $index = 0;             // Índice para IDs únicos de acordeón

                // Array de iconos para cada categoría
                $iconos = [
                    "Base Cama" => "https://cdn-icons-png.flaticon.com/128/2286/2286069.png",
                    "Camas" => "https://cdn-icons-png.flaticon.com/128/3168/3168626.png",
                    "Carpas" => "https://cdn-icons-png.flaticon.com/128/5987/5987625.png",
                    "Combos Con Colchon" => "https://cdn-icons-png.flaticon.com/128/11646/11646037.png",
                    "Combos Sin Colchon" => "https://cdn-icons-png.flaticon.com/128/5059/5059805.png",
                    "Poltronas" => "https://cdn-icons-png.flaticon.com/128/2944/2944104.png",
                    "Puff" => "https://cdn-icons-png.flaticon.com/128/7214/7214591.png",
                    "Salas En L y Esquineros" => "https://cdn-icons-png.flaticon.com/128/4897/4897529.png",
                    "Sillas" => "https://cdn-icons-png.flaticon.com/128/2944/2944135.png",
                    "Sillas Bar" => "https://cdn-icons-png.flaticon.com/128/3822/3822733.png",
                    "Sofá Cama" => "https://cdn-icons-png.flaticon.com/128/7378/7378037.png",
                    "Sofás" => "https://cdn-icons-png.flaticon.com/128/2361/2361657.png",
                    "Cabeceros" => "https://cdn-icons-png.flaticon.com/128/11931/11931883.png",
                    "Cama Nido" => "https://cdn-icons-png.flaticon.com/128/1698/1698762.png",
                    "Colchones" => "https://cdn-icons-png.flaticon.com/128/3821/3821521.png",
                    "Juegos De Sala" => "https://cdn-icons-png.flaticon.com/128/2398/2398481.png",
                    "Mesas De Centro" => "https://cdn-icons-png.flaticon.com/128/15837/15837972.png",
                    "Sillas Reclinables" => "https://cdn-icons-png.flaticon.com/128/11935/11935874.png",
                    "Sofás Camas y Sofás En Combo" => "https://cdn-icons-png.flaticon.com/128/6009/6009570.png"
                ];

                // Recorre todos los productos organizados previamente
                foreach ($productos as $p):

                    // Si la categoría cambia, cerramos la categoría anterior (si existe)
                    if ($categoria_actual != $p['Nombre_Categoria']):

                        if ($categoria_actual != ""):
                            echo "</tbody></table></div></div></div>"; // Cierra tabla y acordeón anterior
                        endif;

                        $index++;  // Incrementa índice para ID único de acordeón
                        $categoria_actual = $p['Nombre_Categoria'];

                        // Obtiene el icono correspondiente a la categoría, si no existe, usa icono por defecto
                        $icono = isset($iconos[$categoria_actual]) ? $iconos[$categoria_actual] : "bi-box";
                ?>

                        <!-- Inicio de ítem del acordeón para la nueva categoría -->
                        <div class="accordion-item">

                            <!-- Cabecera del acordeón -->
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed categoria-header"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse<?= $index ?>">
                                    <!-- Icono de categoría -->
                                    <img src="<?= $icono ?>" class="icono-cat">
                                    <?= $categoria_actual ?> <!-- Nombre de la categoría -->
                                </button>
                            </h2>

                            <!-- Contenido colapsable de la categoría -->
                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse">
                                <div class="accordion-body table-responsive">

                                    <!-- Tabla de productos de la categoría -->
                                    <table class="table table-hover admin-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Colores</th>
                                                <th>Precio / Medidas</th>
                                                <th>Cantidad por medida</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i = 1; ?> <!-- 🔥 Contador visual de productos -->

                                        <?php endif; ?> <!-- Cierre del if que detecta cambio de categoría -->

                                        <!-- Fila de producto -->
                                        <tr class="fila-producto">
                                            <td><strong><span class="badge-admin">#<?= $i++ ?></span></strong></td>
                                            <td class="nombre"><strong><?= $p['Nombre'] ?></strong></td>

                                            <!-- Colores del producto -->
                                            <td class="color-text">
                                                <?php
                                                if (!empty($p['Color'])) {
                                                    // Si hay colores, los separamos por coma
                                                    $colores = explode(",", $p['Color']);
                                                    foreach ($colores as $color):
                                                        $partes = explode("|", $color); // Formato: "nombre|hex"
                                                        $nombre = trim($partes[0] ?? '');
                                                        $hex = trim($partes[1] ?? '#ccc');
                                                ?>
                                                        <span class="color-circle" style="background:<?= $hex ?>;" data-title="<?= $nombre ?>"></span>
                                                <?php endforeach;
                                                } else {
                                                    // Si no hay color, mostramos un mensaje
                                                    echo '<span class="text-muted">Sin color</span>';
                                                }
                                                ?>
                                            </td>

                                            <!-- 💰 Precio del producto o de sus variaciones -->
                                            <td>
                                                <?php if (!empty($p['variaciones'])): ?>
                                                    <?php foreach ($p['variaciones'] as $v): ?>
                                                        <div>
                                                            <strong><?= $v['medida'] ?></strong> :
                                                            <span class="text-success fw-bold">
                                                                $<?= number_format($v['precio'], 0, ',', '.') ?>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-success fw-bold">
                                                        $<?= number_format($p['Precio_Venta'], 0, ',', '.') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 📦 Stock por medida -->
                                            <td>
                                                <?php if (!empty($p['variaciones'])): ?>
                                                    <?php foreach ($p['variaciones'] as $v): ?>
                                                        <?php if ($v['stock'] > 0): ?>
                                                            <div class="badge-disponible" style="margin-bottom:4px;">
                                                                <?= $v['medida'] ?> → <?= $v['stock'] ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="badge-inactivo" style="margin-bottom:4px;">
                                                                <?= $v['medida'] ?> → 0
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <?php if ($p['Cantidad'] > 0): ?>
                                                        <span class="badge-disponible"><?= $p['Cantidad'] ?></span>
                                                    <?php else: ?>
                                                        <span class="badge-inactivo">0</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Estado general del producto -->
                                            <td>
                                                <?php if ($p['Cantidad'] > 1): ?>
                                                    <span class="badge-disponible">Disponible</span>
                                                <?php elseif ($p['Cantidad'] > 0): ?>
                                                    <span class="badge-stock-bajo">Stock bajo</span>
                                                <?php else: ?>
                                                    <span class="badge-inactivo">Agotado</span>
                                                <?php endif; ?>
                                            </td>

                                        </tr> <!-- Fin de fila de producto -->

                                    <?php endforeach; ?> <!-- Fin de foreach productos -->

                                        </tbody>
                                    </table>

                                </div>
                            </div> <!-- Fin de accordion-collapse -->

                        </div> <!-- Fin de accordion-item -->

            </div> <!-- Fin de accordion -->

            <!-- Botón para volver al dashboard -->
            <div class="text-center mt-4">
                <a href="../DASHBOARD/dashboard.php" class="btn btn-admin">⬅ Volver</a>
            </div>

        </div> <!-- Fin de card-kaiu-admin -->
    </div> <!-- Fin de container -->

    <script>
        // 🔍 Escucha el evento "keyup" en el input de búsqueda
        document.getElementById("buscador").addEventListener("keyup", function() {

            let filtro = this.value.toLowerCase().trim(); // Convertimos el texto a minúsculas y quitamos espacios
            let filas = document.querySelectorAll(".fila-producto"); // Todas las filas de productos
            let acordeones = document.querySelectorAll(".accordion-item"); // Todos los acordeones de categorías
            let encontrado = false; // Bandera para mostrar mensaje "No encontrado"

            // 🔹 Iterar sobre cada fila de producto
            filas.forEach(fila => {

                let nombre = fila.querySelector(".nombre").innerText.toLowerCase(); // Nombre del producto
                let colores = fila.querySelector(".color-text").innerText.toLowerCase(); // Colores del producto

                if (filtro === "") {
                    // Si el input está vacío, mostramos todas las filas
                    fila.style.display = "";
                    fila.classList.remove("resaltado");
                } else if (nombre.includes(filtro) || colores.includes(filtro)) {
                    // Si el nombre o los colores incluyen el texto buscado
                    fila.style.display = "";
                    fila.classList.add("resaltado"); // Resalta la fila encontrada
                    encontrado = true;
                } else {
                    // Si no coincide, ocultamos la fila
                    fila.style.display = "none";
                    fila.classList.remove("resaltado");
                }

            });

            // 🔹 Ajustar acordeones según filas visibles
            acordeones.forEach(item => {

                let collapse = item.querySelector(".accordion-collapse"); // Contenedor de tabla de la categoría
                let button = item.querySelector(".accordion-button"); // Botón de la categoría
                let visibles = item.querySelectorAll(".fila-producto:not([style*='display: none'])"); // Filas visibles en la categoría

                if (filtro === "") {
                    // Si no hay filtro, colapsamos todo
                    item.style.display = "";
                    collapse.classList.remove("show");
                    button.classList.add("collapsed");
                } else if (visibles.length > 0) {
                    // Si hay filas visibles en la categoría, expandimos el acordeón
                    item.style.display = "";
                    collapse.classList.add("show");
                    button.classList.remove("collapsed");
                } else {
                    // Si no hay filas visibles, ocultamos toda la categoría
                    item.style.display = "none";
                }

            });

            // 🔹 Mostrar mensaje "No se encontró ningún producto" si no hay coincidencias
            let mensaje = document.getElementById("mensajeNoEncontrado");

            if (filtro !== "" && !encontrado) {
                mensaje.classList.remove("d-none"); // Mostramos mensaje
            } else {
                mensaje.classList.add("d-none"); // Ocultamos mensaje
            }

        });
    </script>

    <!-- Bootstrap JS para funcionamiento del acordeón -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>