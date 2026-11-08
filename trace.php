<?php
declare(strict_types=1);

require __DIR__ . '/src/Algorithms.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $algo  = (string) ($_GET['algo'] ?? 'bubble');
    $class = Algorithms::get($algo);
    if ($class === null) {
        throw new InvalidArgumentException("Unknown algorithm \"$algo\".");
    }
    $input = $class::parseInput((string) ($_GET['data'] ?? ''));
    echo json_encode((new $class())->trace($input), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Tracing failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
