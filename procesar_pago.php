<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['username']) || !isset($_SESSION['correo'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar el pago.']);
    exit;
}

$correo_usuario = $_SESSION['correo']; // Correo del usuario autenticado
$data = json_decode(file_get_contents('php://input'), true); // Leer datos del carrito y de pago

// Verificar que el carrito tenga artículos
if (empty($data['articulos'])) {
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío.']);
    exit;
}

$articulos = $data['articulos'];
$cardNumber = $data['cardNumber'];
$expiryDate = $data['expiryDate'];
$name = $data['name'];

// Enmascarar el número de tarjeta para mayor seguridad (solo mostrar últimos 4 dígitos)
$maskedCardNumber = '**** **** **** ' . substr($cardNumber, -4);

// Preparar detalles del correo con el recibo de compra y datos de la tarjeta
$mensaje = "<div style='font-family: Arial, sans-serif; color: #333;'>
                <h1 style='color: #5a523a; text-align: center;'>Detalles de tu compra en Vieux Bois</h1>
                <p style='text-align: center;'>Gracias por tu compra. A continuación te mostramos los detalles de la transacción:</p>";

// Resumen de la compra
$mensaje .= "<h2 style='color: #333;'>Resumen de la compra</h2>";
$mensaje .= "<table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;'>
                <tr style='background-color: #f4f4f4; color: #5a523a;'>
                    <th style='padding: 10px; border: 1px solid #ddd;'>Imagen</th>
                    <th style='padding: 10px; border: 1px solid #ddd;'>Artículo</th>
                    <th style='padding: 10px; border: 1px solid #ddd;'>Precio</th>
                </tr>";

$total = 0;
foreach ($articulos as $articulo) {
    $nombre = htmlspecialchars($articulo['nombre']);
    $precio = htmlspecialchars($articulo['precio']);
    $imagen = htmlspecialchars($articulo['imagen']);

    $mensaje .= "<tr>
                    <td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>
                        <img src='{$imagen}' alt='{$nombre}' style='width: 80px; border-radius: 5px;'>
                    </td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$nombre}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>$ {$precio}</td>
                </tr>";
    $total += $precio;
}

$mensaje .= "</table>";
$mensaje .= "<p style='font-size: 16px;'><strong>Total de la compra:</strong> $ {$total}</p>";

// Detalles del pago
$mensaje .= "<h2 style='color: #333;'>Detalles del Pago</h2>";
$mensaje .= "<p><strong>Tarjeta:</strong> {$maskedCardNumber}</p>";
$mensaje .= "<p><strong>Fecha de expiración:</strong> {$expiryDate}</p>";
$mensaje .= "<p><strong>Nombre del titular:</strong> {$name}</p>";
$mensaje .= "<p style='color: red; font-size: 12px;'>*Nota: El CVV no se incluye por motivos de seguridad.</p>";
$mensaje .= "</div>";

// Configuración del correo usando PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'victoriasalasluisangel@gmail.com'; // Cambia esto por tu correo
    $mail->Password = 'jbbb ylmx jkid dqpg'; // Contraseña de aplicación de Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('tu_correo@gmail.com', 'Vieux Bois');
    $mail->addAddress($correo_usuario);

    $mail->isHTML(true);
    $mail->Subject = 'Confirmación de Compra - Vieux Bois';
    $mail->Body = $mensaje;

    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
}
?>




    $mail->Username = 'victoriasalasluisangel@gmail.com'; // Cambia esto por tu correo
    $mail->Password = 'jbbb ylmx jkid dqpg'; // Contraseña de aplicación de Gmail
