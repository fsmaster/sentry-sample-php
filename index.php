<?php
// php -S 0.0.0.0:8080 index.php   -> POST /trigger (dashboard)
// php index.php error             -> one error from the command line
require __DIR__ . '/vendor/autoload.php';

\Sentry\init([
    'dsn' => getenv('SENTRY_DSN') ?: null,
    'environment' => getenv('SENTRY_ENVIRONMENT') ?: 'local',
    'release' => getenv('RELEASE') ?: 'sample-php@dev',
    'send_default_pii' => false,
]);

function load_cart(string $id): array
{
    $carts = ['C-1' => ['total' => 10]];
    if (!isset($carts[$id])) {
        throw new RuntimeException("Cart $id not found");
    }
    return $carts[$id];
}

function generate(string $run, bool $fresh): ?string
{
    $eventId = null;
    \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($run, $fresh, &$eventId) {
        $scope->setTag('run_id', $run);
        if ($fresh) {
            $scope->setFingerprint(['{{ default }}', $run]);
        }
        try {
            load_cart("C-$run");
        } catch (Throwable $e) {
            $eventId = (string) \Sentry\captureException($e);
        }
    });
    return $eventId;
}

if (PHP_SAPI === 'cli') {
    echo 'event id: ' . generate(date('His'), true) . PHP_EOL;
    exit;
}

$body = json_decode(file_get_contents('php://input') ?: '{}', true);
header('Content-Type: application/json');
echo json_encode(['event_id' => generate($body['run'] ?? 'manual', $body['fresh'] ?? true)]);
