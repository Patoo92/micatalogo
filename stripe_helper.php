<?php
if (defined('STRIPE_HELPER_LOADED')) return;
define('STRIPE_HELPER_LOADED', true);

require_once __DIR__ . '/helpers.php';

/**
 * Carga la config de Stripe y retorna el array.
 */
function stripe_config() {
    $paths = [
        __DIR__ . '/../../micatalogo-config/stripe.php',
        'C:\\xampp\\micatalogo-config\\stripe.php',
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) {
            return require $p;
        }
    }
    error_log("stripe_helper.php: No se encontró stripe.php en las rutas de config");
    return null;
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
 * Genera un número de factura único.
 */
function generar_numero_factura() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}
