<?php
session_start();

$imagen_perfil = 'default.jpg'; // Imagen predeterminada
$mensaje = ''; // Variable para el mensaje
$color_clase = ''; // Clase CSS para el estilo del mensaje

// Ruta de los archivos
$archivo_usuarios = 'usuarios.txt';
$archivo_articulos = 'articulos_usu.txt';

// Verificar si el usuario ha iniciado sesión
if (isset($_SESSION['username'])) {
    $usuarios = file($archivo_usuarios, FILE_IGNORE_NEW_LINES);

    foreach ($usuarios as $usuario) {
        list($id, $nombre, $apellido, $fecha_nac, $sexo, $correo, $telefono, $username_existente, $password, $imagen) = explode('|', $usuario);

        if ($username_existente == $_SESSION['username']) {
            $imagen_perfil = $imagen; // Usar la imagen del usuario logueado
            $_SESSION['id_usuario'] = $id; // Guardar el ID del usuario en la sesión
            $_SESSION['correo'] = $correo; // Guardar el correo para futuras acciones (como envío de correo)
            break;
        }
    }

    $mensaje = 'Ya puedes acceder a tu perfil y añadir artículos al carrito.';
    $color_clase = 'mensaje-verde'; // Clase CSS para mensaje verde
} else {
    $mensaje = 'Inicia sesión para acceder a tu perfil y añadir artículos al carrito.';
    $color_clase = 'mensaje-rojo'; // Clase CSS para mensaje rojo
}

// Manejar peticiones POST para el carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id_usuario = $_SESSION['id_usuario'] ?? null;

    if (!$id_usuario) {
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para gestionar el carrito.']);
        exit;
    }

    $nombre = $_POST['nombre'] ?? '';
    $precio = $_POST['precio'] ?? '';
    $imagen = $_POST['imagen'] ?? '';

    if ($accion === 'añadir') {
        // Añadir artículo al archivo
        $linea = "$id_usuario|$nombre|$precio|$imagen\n";
        file_put_contents($archivo_articulos, $linea, FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Artículo añadido al carrito.']);
    } elseif ($accion === 'eliminar') {
        // Eliminar artículo del archivo
        $contenido = file($archivo_articulos, FILE_IGNORE_NEW_LINES);
        $contenido_actualizado = array_filter($contenido, function ($linea) use ($id_usuario, $nombre) {
            return !str_starts_with($linea, "$id_usuario|$nombre");
        });
        file_put_contents($archivo_articulos, implode("\n", $contenido_actualizado) . "\n");
        echo json_encode(['success' => true, 'message' => 'Artículo eliminado del carrito.']);
    }
    exit;
}

// Obtener artículos del carrito para mostrar
$carrito = [];
$total = 0;
if (isset($_SESSION['id_usuario'])) {
    $id_usuario = $_SESSION['id_usuario'];
    if (file_exists($archivo_articulos)) {
        $articulos = file($archivo_articulos, FILE_IGNORE_NEW_LINES);
        $carrito = array_filter($articulos, function ($linea) use ($id_usuario) {
            return str_starts_with($linea, "$id_usuario|");
        });
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
    <link rel="icon" type="image/png" href="logo.png">
    <style>
        
        /* Estilos generales del cuerpo */
body {
    font-family: Georgia, sans-serif;
    margin: 20px;
    padding: 0;
    color: black;
    background: #eae9e6;
    box-shadow: 0 0 15px rgb(139, 69, 19);
}

/* Estilo de texto general */
.text {
    margin: 0;
    padding: 0;
    font-family: Georgia, serif;
    color: black;
    min-height: 100%;
    position: relative;
    font-size: 2.0em;
    letter-spacing: 3px;
    line-height: 1;
    text-align: center;

}

/* Header (Encabezado) */
.header {
    background-color: #5a523a;
    color: #fff;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header .logo {
    height: 80px;
}

.header nav {
    display: flex;
}

.header nav a {
    color: #fff;
    margin: 0 15px;
    text-decoration: none;
    position: relative;
}

.header nav a:hover {
    text-decoration: underline;
}

/* Dropdown del Header */
.header nav .dropdown {
    position: relative;
    display: inline-block;
}

.header nav .dropdown-content {
    display: none;
    position: absolute;
    background-color: #5a523a;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
    top: 50px;
}

.header nav .dropdown-content a {
    color: white;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    
}

.header nav .dropdown-content a:hover {
    background-color: #555;
}

.header nav .dropdown:hover .dropdown-content {
    display: block;
}

/* Sección de bienvenida */
.welcome-section {
    background-color: #ffffff;
    padding: 40px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.welcome-section h2 {
    color: #746844;
    margin-bottom: 20px;
}

.welcome-section p {
    line-height: 1.6;
    margin-bottom: 20px;
}

/* Botón de llamada a la acción */
.cta-button {
    background-color: #746844;
    color: #fff;
    padding: 15px 30px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 18px;
    display: inline-block;
}

.cta-button:hover {
    background-color: #5a523a;
}

/* Footer */
.footer, footer {
    background-color: #5a523a;
    color: #fff;
    text-align: center;
    padding: 20px 0;
}

/* Información de contacto en el Footer */
.contact-info {
    font-size: 14px;
}

.contact-columns {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
}

.contact-column {
    flex: 1;
    padding: 10px;
}

.contact-column ul {
    list-style-type: none;
    padding: 0;
}

.contact-column ul li {
    margin-bottom: 10px;
}

/* Estilos para artículos */
/* Contenedor principal para los artículos */
.container {
    display: flex; /* Usamos Flexbox */
    flex-wrap: wrap; /* Permitir que los elementos pasen a nuevas filas */
    gap: 20px; /* Espaciado entre los artículos */
    justify-content: center; /* Centra los elementos horizontalmente */
    padding: 20px;
}

/* Artículos individuales (2x2 por defecto en pantallas grandes) */
.article {
    flex: 1 1 calc(50% - 20px); /* Cada artículo ocupa el 50% del contenedor */
    background-color: #ffffff;
    border: 1px solid transparent;
    box-shadow: 0 0 15px rgb(139, 69, 19); /* Sombra */
    border-radius: 5px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease; /* Transición suave */
    margin-bottom: 20px;
  position: relative; /* Necesario para que el botón se posicione dentro del contenedor */
    padding-bottom: 50px; 
    text-align: center;
           

}
button {
    background: #733D18;
    color: #fff;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    transition: 0.3s;
    margin-top:10px;
    align-self: center;
    
}

.article:hover {
    transform: translate(5px, -5px);
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

.article button {
    margin-top: 15px; /* Espaciado entre el contenido y el botón */
    align-self: center; /* Centra el botón horizontalmente */
    padding: 10px 20px;
    background-color: #5a523a;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
     position: absolute;
    bottom: 0; /* Posiciona el botón en la parte inferior */
    left: 50%; /* Centra el botón horizontalmente */
    transform: translate(-50%,-10px);
    width:70%;


}
/* Ajuste para pantallas más grandes (más columnas) */
@media screen and (min-width: 1024px) {
    .article {
        flex: 1 1 calc(25% - 20px); /* En pantallas más grandes, ocupan el 25% (4x4) */
    }
}

/* Ajuste para pantallas más pequeñas (dispositivos móviles) */
@media screen and (max-width: 768px) {
    .article {
        flex: 1 1 calc(100% - 20px); /* Cada artículo ocupa todo el ancho */
    }
}

/* Imágenes dentro de los artículos */
.article img {
    height: auto;
    display: block;
    margin-left: auto;
    margin-right: auto;
    width: 100%; /* Ajusta el tamaño de las imágenes */
}

.article-title{
font-size:22px;
font-weight: bold;
margin-bottom:16px ;

}
/* Contenido del artículo */
.article-content {
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* Distribuye los elementos uniformemente */
    padding: 15px;
    height: 100%; /* Para asegurarse de que ocupe toda la altura del contenedor */
}

.article-description {
    font-size: 18px;
    color: #666666;
    overflow: hidden; /* Oculta el exceso de texto */
    text-overflow: ellipsis;
    flex-grow: 1;
    max-height: 150px; /* Permite mostrar más texto sin desbordar */
}

.article-price {
    font-size: 18px;
    font-weight: bold;
    color: #5D3215;
    margin-top: 10px; /* Empuja el precio hacia la parte inferior */
    margin-bottom: 20px;
}

.add-to-cart-btn {
    margin-top: 10px; /* Espacio entre el precio y el botón */
    align-self: center; /* Centra el botón horizontalmente */
}

/* Estilos del carrito de compra */
.cart {
    position: fixed;
    top: 0;
    right: -300px;
    background-color: #f4f4f4;
    padding: 20px;
    width: 300px;
    height: auto;
    max-height: 100%;
    overflow-y: auto;
    transition: transform 0.3s ease-in-out, max-height 0.3s ease-in-out;
}

.cart.show {
    transform: translateX(-100%);
}

.cart-count {
    position: absolute;
    top: -10px;
    
    background-color: red;
    color: #fff;
    border-radius: 50%;
    padding: 5px 8px;
    font-size: 14px;
}

.cart ul {
    list-style-type: none;
    padding: 0;
}

.cart li {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.cart-item img {
    width: 50px;
    height: 50px;
    margin-right: 10px;
}

.btn-remove {
    background-color: #ff0000;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}

/* Otros estilos */


button:hover {
    background: #fff;
    color: #733D18;
    box-shadow: 0 0 15px rgb(139, 69, 19);
}

a {
    text-decoration: none;
    color: black;
}

.barra a {
    display: block;
    text-align: center;
    padding: 10px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    margin-top: 20px;
}

/* Perfil del usuario */
.profile-btn {
    width: 100%;
    max-width: 200px;
   
    position: relative;
    margin-right: 10px;
    text-align: center;
}

.profile-btn img {
    background-color: white;
    cursor: pointer;
    border: 3px solid #000000;
    transition: border-color 0.3s ease;
    justify-content: center;
    width: 49px;
    height: 49px;
    border-radius: 50%;
    object-fit: contain;
    margin-top: 10px;
    margin-left: 10px;
}

/* Menú desplegable debajo de la imagen de perfil */
.dropdown-menu {
    display: none;
    position: absolute;
    top: 99%;
    right: 0;
    background-color: white;
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.1);
    border-radius: 5px;
    min-width: 150px;
    padding: 10px;
    z-index: 1;
    border: 2px solid #755405;
}

.dropdown-menu a {
    padding: 10px 20px;
    border-radius: 50px;
    text-decoration: none;
    display: inline-block;
    margin: 6px;
    background-color: #8B4513;
    color: white;
    font-size: 16px;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
    cursor: pointer;
    box-shadow: 0px 0px 10px rgba(139, 69, 19, 0.5);
}

.dropdown-menu a:hover {
    background-color: #A0522D;
    transform: translateY(-5px);
}

.show {
    display: block;
}

.cart-icon {
   
    display: inline-block;
    margin-right: 30px;
}

.cart-icon img {
    width: 48px;
    height: 48px;
    cursor: pointer;
}

/* Barra lateral */


.icons {
    display: flex;
    flex-direction: column; /* Alinear en vertical */
    align-items: center;
    margin-top: 20px;
}

.icons img {
    width: 40px;
    margin-bottom: 10px;
}

.back-button {
    margin-top: auto; /* Mover al final */
    display: block;
    width: 100%;
    padding: 10px;
    background-color: #402d20;
    color: white;
    border: none;
    cursor: pointer;
}
/* Barra lateral oculta por defecto */

.icons {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 20px;
}

.icons img {
    width: 40px;
    margin-bottom: 10px;
}

/* Mostrar la barra lateral y ocultar el menú superior para pantallas pequeñas */




/* Hide logo button on larger screens */
.logo-button {
    display: none;
}

@media screen and (max-width: 768px) {
    .logo-button {
        display: inline;
    }
}

/* Center product text */
.article-title, .article-description {
    text-align: center;
}

/* Cart toggle adjustments */
.cart {
    display: none;
}

.cart.show {
    display: block;
}
body {
    font-family: Georgia, sans-serif;
    margin: 20px;
    padding: 0;
    color: black;
    background: #eae9e6;
    box-shadow: 0 0 15px rgb(139, 69, 19);
}

/* Header (Encabezado) */
.header {
    background-color: #5a523a;
    color: #fff;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header .logo {
    height: 80px;
}

.header nav {
    display: flex;
}

.header nav a {
    color: #fff;
    margin: 0 15px;
    text-decoration: none;
    position: relative;
}

.header nav a:hover {
    text-decoration: underline;
}

/* Barra lateral */

.icons {
    display: flex;
    flex-direction: row; /* Colocar las imágenes en fila */
    justify-content: center;
    align-items: center;
    gap: 10px; /* Espacio entre las imágenes */
    margin-top: 20px;
}

.icons img {
    width: 40px;
    margin-bottom: 10px;
}


.back-button {
    display: block;
    width: 100%;
    padding: 10px;
    background-color: #402d20;
    color: white;
    border: none;
    cursor: pointer;
}

/* Botones de control */
.logo-button {
    display: none; /* Ocultar por defecto */
}

@media screen and (max-width: 768px) {
    .header nav {
        display: none;
    }
    .logo-button {
        display: inline;
        height: 80px;

    }
}

.dropdown-content {
    display: none;
}
.dropdown-content.show {
    display: block;
}

.bajo a{
 top:20px;
 font-size: 25px;
}







.sidebar {
    position: fixed;
    top: 0;
    left: -350px;
    width: 300px;
    height: 100%;
    background-color: #5a523a;
    
    
    z-index: 1000;
    transition: left 0.3s ease-in-out;
    overflow-y: auto;
}

.sidebar.show {
    left: 0;
}

.close-sidebar {
    font-size: 44px;
    color: white;
    cursor: pointer;
    position: absolute;
    top: -1px;
    right: 10px;
    margin-bottom:10px;
}

/* Iconos del carrito y usuario */
.sidebar-icons {
    display: flex;
    gap: 60px;

    
}

#mobile-cart-icon,
#mobile-user-icon {
    cursor: pointer;
    font-size: 18px;
    padding: 10px;
    
    border-radius: 5px;
    text-align: center;
}

/* Contenido del carrito */
.mobile-cart,
.mobile-user-menu {
    display: none;
    background-color: #ffffff;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
}

.mobile-cart.show,
.mobile-user-menu.show {
    display: block;
}

.btn-close {
    background-color: #5a523a;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    margin-top: 10px;
    border-radius: 5px;
}

.mobile-user-menu a {
    display: block;
    color: #5a523a;
    padding: 10px;
    text-decoration: none;
    border-bottom: 1px solid #ddd;
}

.mobile-user-menu a:hover {
    background-color: #eaeaea;
}


@media screen and (max-width: 768px) {
    .mobile-bar {
         margin-top: 40px;
        width: 100%;
        background-color: #5a523a;
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 10px 0;
        z-index: 1001;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.2);
    }

    .mobile-bar-icon {
        color: white;
        font-size: 24px;
        cursor: pointer;
    }

    /* Carrito móvil */
    .mobile-cart {
        
        background-color: #f4f4f4;
        padding: 20px;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.3);
        transform: translateY(100%);
        transition: transform 0.3s ease-in-out;
        z-index: 1002;
        overflow-y: auto;
    }

    .mobile-cart.show {
        transform: translateY(0);
    }

    /* Menú de usuario móvil */
    .mobile-user-menu {
       
       
        height: auto;
        background-color: #fff;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.3);
        padding: 20px;
        transform: translateY(100%);
        transition: transform 0.3s ease-in-out;
        z-index: 1002;
    }

    .mobile-user-menu.show {
        transform: translateY(0);
    }

    .mobile-user-menu a {
        display: block;
        color: #5a523a;
        padding: 10px;
        text-align: center;
        border-bottom: 1px solid #5a523a;
    }

    .profile-btn {
    width: 100%;
    max-width: 50px;
   
    position: relative;
    margin-right: 10px;
    text-align: center;
}

.profile-btn img {
    background-color: white;
    cursor: pointer;
    border: 3px solid #000000;
    transition: border-color 0.3s ease;
    justify-content: center;
    width: 59px;
    height: 59px;
    border-radius: 50%;
    object-fit: contain;
    margin-top: 10px;
    margin-left: 10px;
}
.cart-count {
    position: absolute;
    top: -2px;
    left: 67px;
    background-color: red;
    color: #fff;
    border-radius: 50%;
    padding: 5px 8px;
    font-size: 14px;
}
}




/* Estilo de la barra lateral */
.sidebar-nav {
    background-color: #5b4e3a; /* Color café oscuro */
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

/* Estilo del encabezado */
.bajo h2 {
    color: #fff;
    margin-bottom: 15px;
    font-size: 24px;
    font-weight: bold;
    text-transform: uppercase;
    border-bottom: 1px solid #fff;
    padding-bottom: 10px;
    cursor: pointer;
}


/* Responsivo para móviles */
@media (max-width: 600px) {
    .sidebar-nav {
       
        padding: 10px;
    }

    #dropdown-content li a {
        font-size: 16px;
    }
.dropdown-content {
    display: none; /* Ocultar todos los menús por defecto */
    list-style: none;
    padding: 0;
    margin: 0;
    background-color: #5b4e3a;
    border: 1px solid #fff;
    border-radius: 5px;
}

.dropdown-content li a {
    color: #fff;
    text-decoration: none;
    display: block;
    padding: 10px;
    text-align: center;
}

.dropdown-content li a:hover {
    background-color: #4a4033;
    font-weight: bold;
}

}






img.responsive {
    width: 30%; /* Imagen ocupará el 30% del ancho del contenedor */
    max-width: 100%; /* Asegura que no exceda su tamaño original */
    height: auto; /* Mantiene las proporciones */
}

/* Tamaño para dispositivos más pequeños (por ejemplo, móviles) */
@media screen and (max-width: 768px) {
    img.responsive {
        width: 80%; /* Imagen ocupará el 80% del ancho del contenedor */
    }
}






/** Tamaño predeterminado para pantallas grandes (por ejemplo, PC) 
.container-responsive {
    width: 90%;  El contenedor ocupará el 60% del ancho del viewport 
    max-width: 1200px;  Opcional: Limita el tamaño máximo 
    margin: 0 auto;  Centra el contenedor horizontalmente 
    padding: 20px;  Espaciado interno 
    background-color: #f8f9fa; Color de fondo para visualizarlo mejor 
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); Sombra opcional 
}

 Tamaño para dispositivos más pequeños (por ejemplo, móviles o tablets) 
@media screen and (max-width: 768px) {
    .container-responsive {
        width: 90%; En pantallas pequeñas, el contenedor ocupa el 90% del ancho 
        padding: 10px;  Reduce el espaciado interno 
    }
}

**/

.mensaje-alerta {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border: 1px solid;
            border-radius: 5px;
            z-index: 1000;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        /* Estilo para mensaje verde (sesión iniciada) */
        .mensaje-verde {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        /* Estilo para mensaje rojo (sin sesión) */
        .mensaje-rojo {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        
    </style>
</head>
<body>
<header class="header">
    <h1>Bienvenido a Vieux Bois</h1>
    <p class="<?= htmlspecialchars($color_clase) ?>"><?= htmlspecialchars($mensaje) ?></p>
    <img src="imagenes/<?= htmlspecialchars($imagen_perfil) ?>" alt="Imagen de Perfil">
</header>

<div class="container">
    <h2>Carrito de Compras</h2>
    <ul id="cart-items">
        <?php foreach ($carrito as $articulo): ?>
            <?php list($id, $nombre, $precio, $imagen) = explode('|', $articulo); ?>
            <li class="cart-item" data-name="<?= htmlspecialchars($nombre) ?>" data-price="<?= htmlspecialchars($precio) ?>">
                <img src="<?= htmlspecialchars($imagen) ?>" alt="<?= htmlspecialchars($nombre) ?>" width="50">
                <?= htmlspecialchars($nombre) ?> - $<?= htmlspecialchars($precio) ?>
                <button class="btn-remove">Eliminar</button>
            </li>
        <?php endforeach; ?>
    </ul>
    <p>Total: $<span id="total-price"><?= $total ?></span></p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartItems = document.getElementById('cart-items');
    const totalPrice = document.getElementById('total-price');
    let total = parseFloat(totalPrice.textContent) || 0;

    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const name = btn.getAttribute('data-name');
            const price = parseFloat(btn.getAttribute('data-price'));
            const image = btn.getAttribute('data-image');

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    accion: 'añadir',
                    nombre: name,
                    precio: price,
                    imagen: image
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    cartItems.innerHTML += `
                        <li class="cart-item" data-name="${name}" data-price="${price}">
                            <img src="${image}" alt="${name}" width="50">
                            ${name} - $${price.toFixed(2)}
                            <button class="btn-remove">Eliminar</button>
                        </li>
                    `;
                    total += price;
                    totalPrice.textContent = total.toFixed(2);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    cartItems.addEventListener('click', function(event) {
        if (event.target.classList.contains('btn-remove')) {
            const item = event.target.parentElement;
            const name = item.getAttribute('data-name');
            const price = parseFloat(item.getAttribute('data-price'));

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    accion: 'eliminar',
                    nombre: name
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    item.remove();
                    total -= price;
                    totalPrice.textContent = total.toFixed(2);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
});
</script>
</body>
</html>
