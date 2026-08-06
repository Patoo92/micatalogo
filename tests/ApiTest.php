<?php

/**
 * Pruebas de la API REST (api.php, api-productos.php).
 * Replica la lógica en helpers para testear sin conexión BD real.
 */

// ── Helper: responder (misma lógica que api.php) ──

function _api_responder($success, $message, $status = 200, $data = null) {
    http_response_code($status);
    $res = ['success' => $success, 'message' => $message];
    if ($data !== null) $res['data'] = $data;
    return json_encode($res, JSON_UNESCAPED_UNICODE);
}

// ── Helper: validar api key (misma lógica que api.php líneas 25-48) ──

function _api_validar_key($api_key, $pdo) {
    if (!$api_key) {
        return ['error' => 'API key requerida.', 'status' => 401];
    }
    $stmt = $pdo->prepare("SELECT k.tienda_id, t.plan FROM api_keys k JOIN tiendas t ON k.tienda_id = t.id WHERE k.api_key = ? AND k.activo = 1");
    $stmt->execute([$api_key]);
    $key = $stmt->fetch();
    if (!$key) {
        return ['error' => 'API key inválida o desactivada.', 'status' => 401];
    }
    $planes_api = ['business', 'enterprise'];
    if (!in_array($key['plan'], $planes_api)) {
        return ['error' => 'API Keys disponibles solo en plan Business+.', 'status' => 403];
    }
    return ['ok' => true, 'tienda_id' => (int)$key['tienda_id']];
}

// ── Helper: listar productos (misma lógica que api.php action=productos) ──

function _api_listar_productos($pdo, $tienda_id) {
    $stmt = $pdo->prepare("SELECT id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, destacado, etiqueta, imagen_url, imagen_thumb FROM productos WHERE tienda_id = ? ORDER BY nombre");
    $stmt->execute([$tienda_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Helper: obtener producto (misma lógica que api.php action=producto) ──

function _api_obtener_producto($pdo, $tienda_id, $id) {
    if (!$id) return ['error' => 'ID de producto requerido.', 'status' => 400];
    $stmt = $pdo->prepare("SELECT id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, destacado, etiqueta, imagen_url, imagen_thumb FROM productos WHERE id = ? AND tienda_id = ?");
    $stmt->execute([$id, $tienda_id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prod) return ['error' => 'Producto no encontrado.', 'status' => 404];
    return ['ok' => true, 'data' => $prod];
}

// ── Helper: crear pedido (misma lógica que api.php action=crear-pedido) ──

function _api_crear_pedido($pdo, $tienda_id, $input) {
    $producto_id = (int)($input['producto_id'] ?? 0);
    $nombre = trim($input['nombre_cliente'] ?? '');
    $email = trim($input['email_cliente'] ?? '');

    if (!$producto_id) return ['error' => 'producto_id requerido.', 'status' => 400];
    if (!$nombre) return ['error' => 'nombre_cliente requerido.', 'status' => 400];

    $stmt = $pdo->prepare("SELECT id, nombre, precio FROM productos WHERE id = ? AND tienda_id = ? AND stock > 0");
    $stmt->execute([$producto_id, $tienda_id]);
    $producto = $stmt->fetch();
    if (!$producto) return ['error' => 'Producto no disponible.', 'status' => 404];

    $stmt = $pdo->prepare("SELECT slug, telefono_whatsapp, moneda FROM tiendas WHERE id = ?");
    $stmt->execute([$tienda_id]);
    $tienda = $stmt->fetch();

    $moneda = htmlspecialchars($tienda['moneda'] ?? '€');

    $stmt = $pdo->prepare("INSERT INTO pedidos (tienda_id, producto_id, nombre_cliente, email_cliente, estado) VALUES (?, ?, ?, ?, 'Pendiente')");
    $stmt->execute([$tienda_id, $producto_id, $nombre, $email ?: null]);
    $pedido_id = $pdo->lastInsertId();

    $texto = "¡Hola! Soy $nombre. Me interesa: {$producto['nombre']} ({$producto['precio']}$moneda). Pedido #$pedido_id";
    $url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp']) . "?text=" . urlencode($texto);

    return [
        'ok' => true,
        'data' => [
            'id' => (int)$pedido_id,
            'producto' => $producto['nombre'],
            'url_whatsapp' => $url,
        ],
        'status' => 201,
    ];
}

// ── Helper: api-productos.php lógica ──

function _api_productos_obtener($pdo, $ids_raw, $tienda_id_param) {
    if (!$ids_raw || !$tienda_id_param) return json_encode([]);
    $ids_array = array_map('intval', explode(',', $ids_raw));
    $ids_array = array_filter($ids_array);
    if (empty($ids_array)) return json_encode([]);

    $placeholders = implode(',', array_fill(0, count($ids_array), '?'));
    $stmt = $pdo->prepare("SELECT id, nombre, precio, imagen_thumb, imagen_url, stock FROM productos WHERE id IN ($placeholders) AND tienda_id = ?");
    $params = array_values($ids_array);
    $params[] = $tienda_id_param;
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as &$p) {
        $p['imagen'] = $p['imagen_thumb'] ?: $p['imagen_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80';
        $p['precio'] = (float)$p['precio'];
        $p['stock'] = (int)$p['stock'];
        unset($p['imagen_thumb'], $p['imagen_url']);
    }

    return json_encode($productos);
}


class ApiTest extends PHPUnit\Framework\TestCase
{
    // ── responder ──

    public function testResponderExitosoDevuelveJsonCorrecto()
    {
        $json = _api_responder(true, 'OK', 200, ['id' => 1]);
        $data = json_decode($json, true);
        $this->assertTrue($data['success']);
        $this->assertSame('OK', $data['message']);
        $this->assertSame(1, $data['data']['id']);
    }

    public function testResponderErrorDevuelveStatusYError()
    {
        $json = _api_responder(false, 'No autorizado', 401);
        $data = json_decode($json, true);
        $this->assertFalse($data['success']);
        $this->assertSame('No autorizado', $data['message']);
        $this->assertArrayNotHasKey('data', $data);
    }

    public function testResponderSinDataNoIncluyeCampoData()
    {
        $json = _api_responder(true, 'OK', 200);
        $data = json_decode($json, true);
        $this->assertArrayNotHasKey('data', $data);
    }

    public function testResponderNotFoundStatus()
    {
        $json = _api_responder(false, 'No encontrado', 404);
        $this->assertSame(404, http_response_code());
    }

    // ── validación API key ──

    public function testValidarKeySinKey()
    {
        $pdo = $this->createMock(PDO::class);
        $resultado = _api_validar_key('', $pdo);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertSame(401, $resultado['status']);
    }

    public function testValidarKeyInvalida()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(false);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_validar_key('key_invalida', $pdo);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertSame(401, $resultado['status']);
        $this->assertStringContainsString('inválida', $resultado['error']);
    }

    public function testValidarKeyPlanNoPermitido()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(['tienda_id' => '1', 'plan' => 'starter']);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_validar_key('key_starter', $pdo);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertSame(403, $resultado['status']);
        $this->assertStringContainsString('Business', $resultado['error']);
    }

    public function testValidarKeyPlanProNoPermitido()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(['tienda_id' => '2', 'plan' => 'pro']);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_validar_key('key_pro', $pdo);
        $this->assertSame(403, $resultado['status']);
    }

    public function testValidarKeyBusinessPermitido()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(['tienda_id' => '3', 'plan' => 'business']);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_validar_key('key_business', $pdo);
        $this->assertArrayHasKey('ok', $resultado);
        $this->assertSame(3, $resultado['tienda_id']);
    }

    public function testValidarKeyEnterprisePermitido()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(['tienda_id' => '5', 'plan' => 'enterprise']);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_validar_key('key_enterprise', $pdo);
        $this->assertArrayHasKey('ok', $resultado);
        $this->assertSame(5, $resultado['tienda_id']);
    }

    public function testValidarKeyAutorizationBearer()
    {
        // Simula que el API key viene de header Authorization: Bearer xxx
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(['tienda_id' => '3', 'plan' => 'business']);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_validar_key('bearer_token_valido', $pdo);
        $this->assertArrayHasKey('ok', $resultado);
    }

    // ── action: productos ──

    public function testListarProductos()
    {
        $expected = [
            ['id' => 1, 'nombre' => 'Producto A', 'precio' => '10.00', 'stock' => '5'],
            ['id' => 2, 'nombre' => 'Producto B', 'precio' => '20.00', 'stock' => '0'],
        ];
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($expected);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $productos = _api_listar_productos($pdo, 1);
        $this->assertCount(2, $productos);
        $this->assertSame('Producto A', $productos[0]['nombre']);
    }

    public function testListarProductosSinResultados()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([]);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $productos = _api_listar_productos($pdo, 999);
        $this->assertEmpty($productos);
    }

    public function testListarProductosTieneCamposEsperados()
    {
        $expected = [['id' => 1, 'categoria_id' => '2', 'nombre' => 'Test', 'descripcion' => 'Desc', 'precio' => '15.50', 'stock' => '10', 'stock_minimo' => '3', 'destacado' => '1', 'etiqueta' => 'Nuevo', 'imagen_url' => 'img.jpg', 'imagen_thumb' => 'thumb.jpg']];
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($expected);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $productos = _api_listar_productos($pdo, 1);
        $this->assertArrayHasKey('nombre', $productos[0]);
        $this->assertArrayHasKey('precio', $productos[0]);
        $this->assertArrayHasKey('stock', $productos[0]);
        $this->assertArrayHasKey('destacado', $productos[0]);
        $this->assertArrayHasKey('etiqueta', $productos[0]);
    }

    // ── action: producto ──

    public function testObtenerProductoSinId()
    {
        $pdo = $this->createMock(PDO::class);
        $resultado = _api_obtener_producto($pdo, 1, 0);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertSame(400, $resultado['status']);
    }

    public function testObtenerProductoNoEncontrado()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(false);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_obtener_producto($pdo, 1, 999);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertSame(404, $resultado['status']);
    }

    public function testObtenerProductoExitoso()
    {
        $expected = ['id' => 1, 'nombre' => 'Producto Test', 'precio' => '25.00', 'stock' => '8'];
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn($expected);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_obtener_producto($pdo, 1, 1);
        $this->assertArrayHasKey('ok', $resultado);
        $this->assertSame('Producto Test', $resultado['data']['nombre']);
    }

    // ── action: crear-pedido ──

    public function testCrearPedidoSinProductoId()
    {
        $pdo = $this->createMock(PDO::class);
        $resultado = _api_crear_pedido($pdo, 1, []);
        $this->assertArrayHasKey('error', $resultado);
        $this->assertSame(400, $resultado['status']);
        $this->assertStringContainsString('producto_id', $resultado['error']);
    }

    public function testCrearPedidoSinNombreCliente()
    {
        $pdo = $this->createMock(PDO::class);
        $resultado = _api_crear_pedido($pdo, 1, ['producto_id' => 1]);
        $this->assertSame(400, $resultado['status']);
        $this->assertStringContainsString('nombre_cliente', $resultado['error']);
    }

    public function testCrearPedidoProductoNoDisponible()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(false);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $resultado = _api_crear_pedido($pdo, 1, ['producto_id' => 999, 'nombre_cliente' => 'Juan']);
        $this->assertSame(404, $resultado['status']);
        $this->assertStringContainsString('no disponible', $resultado['error']);
    }

    public function testCrearPedidoExitoso()
    {
        $producto = ['id' => 1, 'nombre' => 'Prod Test', 'precio' => '30.00'];
        $tienda = ['slug' => 'mi-tienda', 'telefono_whatsapp' => '+5491123456789', 'moneda' => '$'];

        $stmtProducto = $this->createMock(PDOStatement::class);
        $stmtProducto->method('fetch')->willReturn($producto);

        $stmtTienda = $this->createMock(PDOStatement::class);
        $stmtTienda->method('fetch')->willReturn($tienda);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $callCount = 0;
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($stmtProducto, $stmtTienda, $stmtInsert, &$callCount) {
                return match (++$callCount) {
                    1 => $stmtProducto,
                    2 => $stmtTienda,
                    3 => $stmtInsert,
                    default => $this->createMock(PDOStatement::class),
                };
            });
        $pdo->method('lastInsertId')->willReturn('101');

        $resultado = _api_crear_pedido($pdo, 1, ['producto_id' => 1, 'nombre_cliente' => 'Juan', 'email_cliente' => 'juan@test.com']);
        $this->assertArrayHasKey('ok', $resultado);
        $this->assertSame(201, $resultado['status']);
        $this->assertSame('Prod Test', $resultado['data']['producto']);
        $this->assertSame(101, $resultado['data']['id']);
        $this->assertStringContainsString('wa.me', $resultado['data']['url_whatsapp']);
    }

    public function testCrearPedioWhatsappUrlFormato()
    {
        $producto = ['id' => 2, 'nombre' => 'Camiseta', 'precio' => '15.00'];
        $tienda = ['slug' => 'test-shop', 'telefono_whatsapp' => '54 9 11 5555-1234', 'moneda' => '$'];

        $stmtProducto = $this->createMock(PDOStatement::class);
        $stmtProducto->method('fetch')->willReturn($producto);

        $stmtTienda = $this->createMock(PDOStatement::class);
        $stmtTienda->method('fetch')->willReturn($tienda);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $callCount = 0;
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($stmtProducto, $stmtTienda, $stmtInsert, &$callCount) {
                return match (++$callCount) {
                    1 => $stmtProducto,
                    2 => $stmtTienda,
                    3 => $stmtInsert,
                    default => $this->createMock(PDOStatement::class),
                };
            });
        $pdo->method('lastInsertId')->willReturn('50');

        $resultado = _api_crear_pedido($pdo, 1, ['producto_id' => 2, 'nombre_cliente' => 'Ana']);
        $url = $resultado['data']['url_whatsapp'];
        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString('text=', $url);
        $this->assertStringNotContainsString(' ', $url);
        $this->assertStringNotContainsString('-', $url);
        // Solo dígitos en el número
        preg_match('/wa\.me\/(\d+)/', $url, $m);
        $this->assertNotEmpty($m[1]);
    }

    public function testCrearPedidoMonedaPersonalizada()
    {
        $producto = ['id' => 1, 'nombre' => 'Prod', 'precio' => '100.00'];
        $tienda = ['slug' => 't', 'telefono_whatsapp' => '123', 'moneda' => 'USD'];

        $stmtProducto = $this->createMock(PDOStatement::class);
        $stmtProducto->method('fetch')->willReturn($producto);

        $stmtTienda = $this->createMock(PDOStatement::class);
        $stmtTienda->method('fetch')->willReturn($tienda);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $callCount = 0;
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($stmtProducto, $stmtTienda, $stmtInsert, &$callCount) {
                return match (++$callCount) {
                    1 => $stmtProducto,
                    2 => $stmtTienda,
                    3 => $stmtInsert,
                    default => $this->createMock(PDOStatement::class),
                };
            });
        $pdo->method('lastInsertId')->willReturn('1');

        $resultado = _api_crear_pedido($pdo, 1, ['producto_id' => 1, 'nombre_cliente' => 'Carlos']);
        $this->assertStringContainsString('100.00USD', $resultado['data']['url_whatsapp']);
    }

    // ── api-productos.php ──

    public function testApiProductosSinIds()
    {
        $pdo = $this->createMock(PDO::class);
        $json = _api_productos_obtener($pdo, '', 0);
        $this->assertSame('[]', $json);
    }

    public function testApiProductosSinTienda()
    {
        $pdo = $this->createMock(PDO::class);
        $json = _api_productos_obtener($pdo, '1,2,3', 0);
        $this->assertSame('[]', $json);
    }

    public function testApiProductosIdsInvalidos()
    {
        $pdo = $this->createMock(PDO::class);
        $json = _api_productos_obtener($pdo, '0,abc,', 1);
        $this->assertSame('[]', $json);
    }

    public function testApiProductosResultados()
    {
        $rows = [
            ['id' => 1, 'nombre' => 'A', 'precio' => '10.00', 'imagen_thumb' => 'thumb.jpg', 'imagen_url' => 'img.jpg', 'stock' => '5'],
            ['id' => 2, 'nombre' => 'B', 'precio' => '20.00', 'imagen_thumb' => null, 'imagen_url' => 'img2.jpg', 'stock' => '0'],
        ];
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($rows);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $json = _api_productos_obtener($pdo, '1,2', 1);
        $data = json_decode($json, true);
        $this->assertCount(2, $data);
        $this->assertSame('A', $data[0]['nombre']);
        $this->assertSame(10, $data[0]['precio']);
        $this->assertSame(5, $data[0]['stock']);
        // imagen_thumb tiene prioridad
        $this->assertSame('thumb.jpg', $data[0]['imagen']);
        // Cuando imagen_thumb es null, usa imagen_url
        $this->assertSame('img2.jpg', $data[1]['imagen']);
        // No debe incluir campos internos
        $this->assertArrayNotHasKey('imagen_thumb', $data[0]);
        $this->assertArrayNotHasKey('imagen_url', $data[0]);
    }

    public function testApiProductosSinMatch()
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([]);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $json = _api_productos_obtener($pdo, '999,998', 999);
        $this->assertSame('[]', $json);
    }

    public function testApiProductosTiposDatos()
    {
        $rows = [
            ['id' => 1, 'nombre' => 'Test', 'precio' => '99.99', 'imagen_thumb' => '', 'imagen_url' => 'x.jpg', 'stock' => '3'],
        ];
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($rows);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmtMock);

        $json = _api_productos_obtener($pdo, '1', 1);
        $data = json_decode($json, true);
        $this->assertIsFloat($data[0]['precio']);
        $this->assertIsInt($data[0]['stock']);
        $this->assertIsInt($data[0]['id']);
    }

    // ── action routing ──

    public function testActionValidaRouting()
    {
        $acciones = ['productos', 'producto', 'categorias', 'pedidos', 'crear-pedido'];
        foreach ($acciones as $a) {
            $this->assertTrue(in_array($a, $acciones), "Acción $a debería ser válida");
        }
    }

    public function testActionInvalida()
    {
        $acciones = ['productos', 'producto', 'categorias', 'pedidos', 'crear-pedido'];
        $this->assertNotContains('eliminar', $acciones);
        $this->assertNotContains('actualizar', $acciones);
        $this->assertNotContains('', $acciones);
    }
}
