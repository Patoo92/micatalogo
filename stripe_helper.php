<?php
if (defined('STRIPE_HELPER_LOADED')) return;
define('STRIPE_HELPER_LOADED', true);

require_once __DIR__ . '/helpers.php';

// Autoloader de Composer: sin él las clases \Stripe\* no existen.
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    error_log("stripe_helper.php: vendor/autoload.php no encontrado — Stripe no disponible");
}

/**
 * Carga la config de Stripe desde variables de entorno y retorna el array.
 */
function stripe_config() {
    $prices = [];
    foreach (['pro', 'business', 'enterprise'] as $plan) {
        foreach (['mensual', 'anual'] as $periodo) {
            $clave = 'STRIPE_PRICE_' . strtoupper($plan) . '_' . strtoupper($periodo);
            $valor = _getenv($clave);
            if ($valor !== '') {
                $prices[$plan][$periodo] = $valor;
            }
        }
    }

    return [
        'secret_key'      => getenv('STRIPE_SECRET_KEY') ?: '',
        'publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
        'prices'          => $prices,
        'webhook_secret'  => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
        'test_mode'       => in_array(strtolower(_getenv('STRIPE_TEST_MODE', 'true')), ['1', 'true', 'on', 'yes']),
    ];
}

/**
 * Retorna una instancia configurada de Stripe\StripeClient.
 */
function stripe_cliente() {
    static $cliente = null;
    if ($cliente === null) {
        $config = stripe_config();
        if (!$config || empty($config['secret_key'])) {
            throw new \RuntimeException("Stripe no configurado: falta secret_key");
        }
        $cliente = new \Stripe\StripeClient($config['secret_key']);
    }
    return $cliente;
}

/**
 * Crea una sesión de Stripe Checkout para un plan/periodo dado.
 *
 * @param int    $tienda_id      ID de la tienda en la BD
 * @param string $plan           starter|pro|business|enterprise
 * @param string $periodo        mensual|anual
 * @param string $success_url    URL de retorno OK
 * @param string $cancel_url     URL de retorno cancelación
 * @param string $customer_email Email para prefijar en Checkout
 * @return array                 ['id' => session_id, 'url' => checkout_url]
 */
function stripe_crear_sesion_checkout($tienda_id, $plan, $periodo, $success_url, $cancel_url, $customer_email = '') {
    $config = stripe_config();
    $price_id = $config['prices'][$plan][$periodo] ?? null;

    if (!$price_id) {
        throw new \InvalidArgumentException("No hay price_id configurado para plan=$plan periodo=$periodo");
    }

    $line_items = [[
        'price'    => $price_id,
        'quantity' => 1,
    ]];

    // Si es un plan pago con trial, se lo pasamos a Stripe para que no cobre hasta el final del trial
    $subscription_data = [];
    if ($plan !== 'starter') {
        // Stripe soporta trial_period_days directamente en la sesión
        $subscription_data['trial_period_days'] = 3;
    }

    $params = [
        'mode'               => 'subscription',
        'line_items'         => $line_items,
        'success_url'        => $success_url,
        'cancel_url'         => $cancel_url,
        'subscription_data'  => $subscription_data,
        'metadata'           => [
            'tienda_id' => (string)$tienda_id,
            'plan'      => $plan,
            'periodo'   => $periodo,
        ],
        'client_reference_id' => (string)$tienda_id,
    ];

    if ($customer_email) {
        $params['customer_email'] = $customer_email;
    }

    $session = stripe_cliente()->checkout->sessions->create($params);

    return [
        'id'  => $session->id,
        'url' => $session->url,
    ];
}

/**
 * Crea una sesión de Stripe Checkout para cobrar productos de un carrito
 * (mode=payment, precios dinámicos). NO descuenta stock: eso lo hace el
 * webhook cuando Stripe confirma el pago (checkout.session.completed).
 *
 * @param int    $tienda_id       ID de la tienda en la BD
 * @param string $codigo_pedido   Código que agrupa las líneas del pedido
 * @param array  $line_items      Items en formato: [['name'=>..., 'unit_amount'=>centavos,
 *                                'currency'=>'eur', 'quantity'=>int, 'images'=>[]], ...]
 * @param string $success_url     URL de retorno OK (incluir {CHECKOUT_SESSION_ID})
 * @param string $cancel_url      URL de retorno cancelación
 * @param string $customer_email  Email para prefijar en Checkout
 * @return array                  ['id' => session_id, 'url' => checkout_url]
 */
function stripe_crear_sesion_pago($tienda_id, $codigo_pedido, $line_items, $success_url, $cancel_url, $customer_email = '') {
    $config = stripe_config();
    if (!$config || empty($config['secret_key'])) {
        throw new \RuntimeException("Stripe no configurado: falta secret_key");
    }

    $stripe_line_items = [];
    foreach ($line_items as $item) {
        $entry = [
            'quantity'   => max(1, (int)$item['quantity']),
            'price_data' => [
                'currency'     => strtolower($item['currency']),
                'unit_amount'  => max(1, (int)$item['unit_amount']),
                'product_data' => [
                    'name' => (string)$item['name'],
                ],
            ],
        ];
        if (!empty($item['images'])) {
            $entry['price_data']['product_data']['images'] = array_slice((array)$item['images'], 0, 8);
        }
        $stripe_line_items[] = $entry;
    }

    $params = [
        'mode'                => 'payment',
        'line_items'          => $stripe_line_items,
        'success_url'         => $success_url,
        'cancel_url'          => $cancel_url,
        'metadata'            => [
            'tienda_id'     => (string)$tienda_id,
            'codigo_pedido' => $codigo_pedido,
        ],
        'client_reference_id' => $codigo_pedido,
    ];

    if ($customer_email) {
        $params['customer_email'] = $customer_email;
    }

    $session = stripe_cliente()->checkout->sessions->create($params);

    return [
        'id'  => $session->id,
        'url' => $session->url,
    ];
}

/**
 * Recupera una sesión de Checkout desde Stripe (para verificar el estado del
 * pago en pago-exito.php). Devuelve null si no existe o falla la llamada.
 */
function stripe_obtener_sesion_checkout($session_id) {
    if (!$session_id || empty(stripe_config()['secret_key'])) {
        return null;
    }
    try {
        return stripe_cliente()->checkout->sessions->retrieve($session_id);
    } catch (\Exception $e) {
        error_log("stripe_obtener_sesion_checkout: " . $e->getMessage());
        return null;
    }
}

/**
 * Genera un número de factura único.
 */
function generar_numero_factura() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Crea un enlace al Customer Portal de Stripe para que el usuario
 * gestione su suscripción (upgrade, downgrade, cancelar, métodos de pago).
 *
 * @param string $stripe_customer_id ID del cliente en Stripe (cus_xxxx)
 * @param string $return_url         URL de retorno después del portal
 * @return string                    URL del portal
 */
function stripe_crear_portal($stripe_customer_id, $return_url) {
    $session = stripe_cliente()->billingPortal->sessions->create([
        'customer'   => $stripe_customer_id,
        'return_url' => $return_url,
    ]);
    return $session->url;
}
