<?php
require_once 'conexion.php';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f7fafc; padding: 40px 0; }
        .container { max-width: 800px; }
        h1 { font-size: 2rem; font-weight: 700; margin-bottom: 8px; }
        h2 { font-size: 1.25rem; font-weight: 600; margin-top: 32px; margin-bottom: 12px; }
        p, li { color: #475569; line-height: 1.7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow-sm p-4 p-md-5 border-0">
            <h1>Política de Privacidad</h1>
            <p class="text-muted">Última actualización: <?php echo date('d/m/Y'); ?></p>

            <h2>1. Responsable del tratamiento</h2>
            <p>El responsable del tratamiento de tus datos es el propietario de la tienda que opera a través de esta plataforma. micatalogo.app actúa como encargado del tratamiento, proporcionando la infraestructura tecnológica para la gestión del catálogo y pedidos.</p>

            <h2>2. Datos que recogemos</h2>
            <p>Recogemos únicamente los datos necesarios para procesar pedidos y gestionar la tienda:</p>
            <ul>
                <li><strong>Nombre</strong> — proporcionado voluntariamente al realizar un pedido.</li>
                <li><strong>Email</strong> — proporcionado voluntariamente, usado solo para notificaciones del pedido.</li>
                <li><strong>Número de teléfono</strong> — el del negocio, visible para que los clientes contacten por WhatsApp.</li>
                <li><strong>Datos de navegación</strong> — cookies técnicas necesarias para el funcionamiento de la tienda.</li>
            </ul>

            <h2>3. Finalidad del tratamiento</h2>
            <ul>
                <li>Gestionar y procesar pedidos realizados a través del catálogo.</li>
                <li>Permitir la comunicación entre cliente y tienda vía WhatsApp.</li>
                <li>Mantener el inventario actualizado y evitar ventas de productos sin stock.</li>
                <li>Mejorar la experiencia de navegación en la tienda.</li>
            </ul>

            <h2>4. Base legal</h2>
            <p>El tratamiento de tus datos se basa en la ejecución de un contrato (realización de un pedido) y, en su caso, en el consentimiento prestado al aceptar las cookies técnicas.</p>

            <h2>5. Conservación de los datos</h2>
            <p>Los datos se conservan durante el tiempo necesario para cumplir con la finalidad para la que fueron recogidos y para cumplir con obligaciones legales. Los datos de pedidos se mantienen mientras la tienda esté activa.</p>

            <h2>6. Destinatarios de los datos</h2>
            <p>No cedemos datos personales a terceros, salvo obligación legal. Los datos se almacenan en servidores propios.</p>

            <h2>7. Tus derechos</h2>
            <p>Tienes derecho a acceder, rectificar, suprimir tus datos, limitar u oponerte al tratamiento, así como a la portabilidad de los mismos. Para ejercer estos derechos, contacta directamente con la tienda.</p>

            <h2>8. Cookies</h2>
            <p>Esta tienda utiliza cookies técnicas necesarias para su funcionamiento (gestión del carrito, sesión de navegación). No utilizamos cookies de seguimiento ni publicitarias de terceros. Puedes gestionar las cookies desde la configuración de tu navegador.</p>

            <h2>9. Contacto</h2>
            <p>Si tienes dudas sobre esta política de privacidad, puedes contactar con el responsable de la tienda a través de los datos de contacto indicados en el catálogo.</p>

            <hr class="my-4">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">&larr; Volver</a>
        </div>
    </div>
</body>
</html>
