<?php
/**
 * Webhook de Stripe — recibe eventos de Stripe y actualiza la BD.
 *
 * Configurar en Stripe Dashboard → Webhooks → Agregar endpoint:
 *   https://tudominio.com/micatalogo/webhook-stripe.php
 *   Eventos: checkout.session.completed, customer.subscription.updated,
 *            customer.subscription.deleted, invoice.paid, invoice.payment_failed
 */
require_once __DIR__ . '/stripe_helper.php';

$config = stripe_config();
if (!$config) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe no configurado']);
    exit;
}

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $config['webhook_secret']
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit;
}

require_once __DIR__ . '/conexion.php';

$event_type = $event->type;
$data = $event->data->object;

switch ($event_type) {
    case 'checkout.session.completed':
        $tienda_id = (int)($data->metadata->tienda_id ?? 0);
        $plan = $data->metadata->plan ?? 'starter';
        $periodo = $data->metadata->periodo ?? 'mensual';
        $customer_id = $data->customer;
        $subscription_id = $data->subscription;
        $customer_email = $data->customer_details->email ?? '';

        if ($tienda_id && $customer_id && $subscription_id) {
            $stmt = $pdo->prepare("UPDATE tiendas SET stripe_customer_id = ?, stripe_subscription_id = ? WHERE id = ?");
            $stmt->execute([$customer_id, $subscription_id, $tienda_id]);

            // Registrar factura inicial
            $monto = $data->amount_total / 100;
            $moneda = strtoupper($data->currency ?? 'eur');
            $numero = generar_numero_factura();
            $stmtF = $pdo->prepare("INSERT INTO facturas (tienda_id, numero_factura, plan, periodo, monto, moneda, estado, metodo_pago, fecha_emision, fecha_pago) VALUES (?, ?, ?, ?, ?, ?, 'pagada', 'Stripe', CURDATE(), CURDATE())");
            $stmtF->execute([$tienda_id, $numero, $plan, $periodo, $monto, $moneda]);
        }
        break;

    case 'customer.subscription.updated':
        $subscription_id = $data->id;
        $status = $data->status;
        $items = $data->items->data ?? [];

        // Buscar la tienda por subscription_id
        $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE stripe_subscription_id = ?");
        $stmt->execute([$subscription_id]);
        $tienda = $stmt->fetch();

        if ($tienda && in_array($status, ['active', 'trialing', 'past_due'])) {
            // Si el status es active pero no es trialing, ya está pagando
            // Extraer el plan del item de suscripción
            foreach ($items as $item) {
                $price_id = $item->price->id;
                // Buscar el plan correspondiente al price_id
                foreach ($config['prices'] as $plan_name => $periodos) {
                    foreach ($periodos as $p => $pid) {
                        if ($pid === $price_id) {
                            $stmtU = $pdo->prepare("UPDATE tiendas SET plan = ? WHERE id = ?");
                            $stmtU->execute([$plan_name, $tienda['id']]);
                            break 3;
                        }
                    }
                }
            }
        }

        if ($tienda && $status === 'canceled') {
            $stmtU = $pdo->prepare("UPDATE tiendas SET plan = 'starter', stripe_subscription_id = NULL WHERE id = ?");
            $stmtU->execute([$tienda['id']]);
        }
        break;

    case 'customer.subscription.deleted':
        $subscription_id = $data->id;
        $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE stripe_subscription_id = ?");
        $stmt->execute([$subscription_id]);
        $tienda = $stmt->fetch();

        if ($tienda) {
            $stmtU = $pdo->prepare("UPDATE tiendas SET plan = 'starter', stripe_subscription_id = NULL, stripe_customer_id = NULL WHERE id = ?");
            $stmtU->execute([$tienda['id']]);
        }
        break;

    case 'invoice.paid':
        $subscription_id = $data->subscription;
        $customer_id = $data->customer;
        $monto = $data->amount_paid / 100;
        $moneda = strtoupper($data->currency ?? 'eur');
        $invoice_url = $data->hosted_invoice_url ?? '';

        if ($subscription_id) {
            $stmt = $pdo->prepare("SELECT id, plan FROM tiendas WHERE stripe_subscription_id = ?");
            $stmt->execute([$subscription_id]);
            $tienda = $stmt->fetch();

            if ($tienda) {
                $numero = generar_numero_factura();
                $stmtF = $pdo->prepare("INSERT INTO facturas (tienda_id, numero_factura, plan, periodo, monto, moneda, estado, metodo_pago, fecha_emision, fecha_pago, notas) VALUES (?, ?, ?, 'mensual', ?, ?, 'pagada', 'Stripe', CURDATE(), CURDATE(), ?)");
                $stmtF->execute([$tienda['id'], $numero, $tienda['plan'], $monto, $moneda, "Invoice: $invoice_url"]);
            }
        }
        break;

    case 'invoice.payment_failed':
        $subscription_id = $data->subscription;
        $attempts = $data->attempt_count ?? 1;

        if ($subscription_id) {
            $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE stripe_subscription_id = ?");
            $stmt->execute([$subscription_id]);
            $tienda = $stmt->fetch();

            if ($tienda) {
                $stmtF = $pdo->prepare("INSERT INTO facturas (tienda_id, numero_factura, plan, periodo, monto, moneda, estado, notas) VALUES (?, ?, (SELECT plan FROM tiendas WHERE id = ?), 'mensual', 0, 'EUR', 'vencida', ?)");
                $stmtF->execute([$tienda['id'], generar_numero_factura(), $tienda['id'], "Pago fallido (intento $attempts)"]);
            }
        }
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);
