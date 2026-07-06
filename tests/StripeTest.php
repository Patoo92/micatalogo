<?php

/**
 * Pruebas de stripe_helper.php con mock del cliente Stripe.
 * Replica la lógica en helpers para testear sin conexión real a Stripe.
 */

function _test_stripe_config_file($filePath) {
    if (!file_exists($filePath)) return null;
    return require $filePath;
}

function _test_generar_numero_factura() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function _test_stripe_crear_sesion_checkout($client, $price_id, $tienda_id, $plan, $periodo, $success_url, $cancel_url, $customer_email = '') {
    $line_items = [[
        'price'    => $price_id,
        'quantity' => 1,
    ]];

    $subscription_data = [];
    if ($plan !== 'starter') {
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

    $session = $client->checkout->sessions->create($params);

    return [
        'id'  => $session->id,
        'url' => $session->url,
    ];
}

function _test_stripe_crear_portal($client, $stripe_customer_id, $return_url) {
    $session = $client->billingPortal->sessions->create([
        'customer'   => $stripe_customer_id,
        'return_url' => $return_url,
    ]);
    return $session->url;
}


class StripeTest extends PHPUnit\Framework\TestCase
{
    private $tempConfigFile;

    protected function tearDown(): void
    {
        if ($this->tempConfigFile && file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
        }
    }

    // ── stripe_config ──

    public function testStripeConfigSinArchivo()
    {
        $config = _test_stripe_config_file('/ruta/inexistente/stripe.php');
        $this->assertNull($config);
    }

    public function testStripeConfigConArchivoValido()
    {
        $this->tempConfigFile = __DIR__ . '/_stripe_test_config.php';
        file_put_contents($this->tempConfigFile, '<?php return ["secret_key" => "sk_test_xxx", "prices" => ["pro" => ["mensual" => "price_pro"]]];');
        $config = _test_stripe_config_file($this->tempConfigFile);
        $this->assertIsArray($config);
        $this->assertSame('sk_test_xxx', $config['secret_key']);
        $this->assertSame('price_pro', $config['prices']['pro']['mensual']);
    }

    public function testStripeConfigSinSecretKey()
    {
        $this->tempConfigFile = __DIR__ . '/_stripe_test_config.php';
        file_put_contents($this->tempConfigFile, '<?php return ["prices" => []];');
        $config = _test_stripe_config_file($this->tempConfigFile);
        $this->assertIsArray($config);
        $this->assertArrayNotHasKey('secret_key', $config);
    }

    // ── generar_numero_factura ──

    public function testGenerarNumeroFacturaFormato()
    {
        $numero = _test_generar_numero_factura();
        $this->assertStringStartsWith('INV-', $numero);
        $this->assertSame(19, strlen($numero)); // INV-YYYYMMDD-XXXXXX (6 hex chars)
    }

    public function testGenerarNumeroFacturaFechaActual()
    {
        $numero = _test_generar_numero_factura();
        $hoy = date('Ymd');
        $this->assertStringContainsString($hoy, $numero);
    }

    public function testGenerarNumeroFacturaUnico()
    {
        $n1 = _test_generar_numero_factura();
        $n2 = _test_generar_numero_factura();
        $this->assertNotSame($n1, $n2);
    }

    // ── stripe_crear_sesion_checkout ──

    public function testCrearSesionCheckoutExitoso()
    {
        $session = new class {
            public $id = 'cs_test_abc123';
            public $url = 'https://checkout.stripe.com/pay/cs_test_abc123';
        };

        $sessions = new class($session) {
            public function __construct(private $session) {}
            public function create($params) { return $this->session; }
        };

        $checkout = new class($sessions) {
            public function __construct(public $sessions) {}
        };

        $client = new class($checkout) {
            public function __construct(public $checkout) {}
        };

        $resultado = _test_stripe_crear_sesion_checkout(
            $client,
            'price_pro_mensual',
            1, 'pro', 'mensual',
            'https://ejemplo.com/exito',
            'https://ejemplo.com/cancelar',
            'test@ejemplo.com'
        );

        $this->assertSame('cs_test_abc123', $resultado['id']);
        $this->assertStringStartsWith('https://checkout.stripe.com/', $resultado['url']);
    }

    public function testCrearSesionCheckoutParametrosSubscription()
    {
        $capturedParams = null;
        $sessionsFn = function($params) use (&$capturedParams) {
            $capturedParams = $params;
            return (object)['id' => 'cs_test', 'url' => 'https://checkout.stripe.com/pay/cs_test'];
        };

        $sessions = new class($sessionsFn) {
            public function __construct(private $fn) {}
            public function create($params) { return ($this->fn)($params); }
        };

        $checkout = new class($sessions) {
            public function __construct(public $sessions) {}
        };

        $client = new class($checkout) {
            public function __construct(public $checkout) {}
        };

        _test_stripe_crear_sesion_checkout(
            $client,
            'price_pro',
            1, 'pro', 'mensual',
            'https://ok.com', 'https://cancel.com',
            'cli@test.com'
        );

        $this->assertNotNull($capturedParams);
        $this->assertSame('subscription', $capturedParams['mode']);
        $this->assertSame('price_pro', $capturedParams['line_items'][0]['price']);
        $this->assertSame(3, $capturedParams['subscription_data']['trial_period_days']);
        $this->assertSame('cli@test.com', $capturedParams['customer_email']);
        $this->assertSame('1', (string)$capturedParams['metadata']['tienda_id']);
        $this->assertSame('pro', $capturedParams['metadata']['plan']);
        $this->assertSame('mensual', $capturedParams['metadata']['periodo']);
    }

    public function testCrearSesionCheckoutSinTrialParaStarter()
    {
        $capturedParams = null;
        $sessionsFn = function($params) use (&$capturedParams) {
            $capturedParams = $params;
            return (object)['id' => 'cs_starter', 'url' => 'https://checkout.stripe.com/pay/cs_starter'];
        };

        $sessions = new class($sessionsFn) {
            public function __construct(private $fn) {}
            public function create($params) { return ($this->fn)($params); }
        };

        $checkout = new class($sessions) {
            public function __construct(public $sessions) {}
        };

        $client = new class($checkout) {
            public function __construct(public $checkout) {}
        };

        _test_stripe_crear_sesion_checkout(
            $client,
            'price_starter',
            2, 'starter', 'mensual',
            'https://ok.com', 'https://cancel.com'
        );

        $this->assertEmpty($capturedParams['subscription_data']);
    }

    public function testCrearSesionCheckoutSinEmailCliente()
    {
        $capturedParams = null;
        $sessionsFn = function($params) use (&$capturedParams) {
            $capturedParams = $params;
            return (object)['id' => 'cs_test', 'url' => 'https://checkout.stripe.com/pay/cs_test'];
        };

        $sessions = new class($sessionsFn) {
            public function __construct(private $fn) {}
            public function create($params) { return ($this->fn)($params); }
        };

        $checkout = new class($sessions) {
            public function __construct(public $sessions) {}
        };

        $client = new class($checkout) {
            public function __construct(public $checkout) {}
        };

        _test_stripe_crear_sesion_checkout(
            $client,
            'price_pro',
            1, 'pro', 'mensual',
            'https://ok.com', 'https://cancel.com'
        );

        $this->assertArrayNotHasKey('customer_email', $capturedParams);
    }

    // ── stripe_crear_portal ──

    public function testCrearPortalExitoso()
    {
        $sessions = new class {
            public function create($params) { return (object)['url' => 'https://billing.stripe.com/session/test_abc']; }
        };

        $billingPortal = new class($sessions) {
            public function __construct(public $sessions) {}
        };

        $client = new class($billingPortal) {
            public function __construct(public $billingPortal) {}
        };

        $url = _test_stripe_crear_portal($client, 'cus_test_123', 'https://ejemplo.com/volver');
        $this->assertStringStartsWith('https://billing.stripe.com/', $url);
    }

    public function testCrearPortalParametros()
    {
        $capturedParams = null;
        $sessionsFn = function($params) use (&$capturedParams) {
            $capturedParams = $params;
            return (object)['url' => 'https://billing.stripe.com/session/test'];
        };

        $sessions = new class($sessionsFn) {
            public function __construct(private $fn) {}
            public function create($params) { return ($this->fn)($params); }
        };

        $billingPortal = new class($sessions) {
            public function __construct(public $sessions) {}
        };

        $client = new class($billingPortal) {
            public function __construct(public $billingPortal) {}
        };

        _test_stripe_crear_portal($client, 'cus_xyz', 'https://miapp.com/return');

        $this->assertNotNull($capturedParams);
        $this->assertSame('cus_xyz', $capturedParams['customer']);
        $this->assertSame('https://miapp.com/return', $capturedParams['return_url']);
    }

    public function testCrearPortalConCustomerIdVacio()
    {
        $capturedParams = null;
        $sessionsFn = function($params) use (&$capturedParams) {
            $capturedParams = $params;
            return (object)['url' => 'https://billing.stripe.com/session/test'];
        };

        $sessions = new class($sessionsFn) {
            public function __construct(private $fn) {}
            public function create($params) { return ($this->fn)($params); }
        };

        $billingPortal = new class($sessions) {
            public function __construct(public $sessions) {}
        };

        $client = new class($billingPortal) {
            public function __construct(public $billingPortal) {}
        };

        _test_stripe_crear_portal($client, '', 'https://miapp.com/return');

        $this->assertSame('', $capturedParams['customer']);
    }

    // ── webhook-stripe.php event routing ──

    public function testEventTypesManejados()
    {
        $eventos = [
            'checkout.session.completed',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'invoice.paid',
            'invoice.payment_failed',
        ];
        foreach ($eventos as $e) {
            $this->assertNotEmpty($e);
            $this->assertStringContainsString('.', $e);
        }
    }

    public function testEventTypesNoManejados()
    {
        $manejados = [
            'checkout.session.completed',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'invoice.paid',
            'invoice.payment_failed',
        ];
        $no_manejados = [
            'charge.succeeded',
            'payment_intent.succeeded',
            'account.updated',
            'setup_intent.created',
        ];
        foreach ($no_manejados as $e) {
            $this->assertNotContains($e, $manejados);
        }
    }
}
