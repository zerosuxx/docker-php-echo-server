<?php

declare(strict_types=1);

const VERSION = '0.1.0';

function logger(string $level, string $message, array $context = [])
{
    file_put_contents('php://stderr', json_encode(array_merge(['level' => $level, 'message' => $message], $context), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (preg_match('#/status/(\d+)#', $requestUri, $statusMatches)) {
    http_response_code($statusMatches[1]);
} else if (preg_match('#/redirect/(.+)#', $requestUri, $redirectMatches)) {
    header("Location {$redirectMatches[1]}", true, 302);
}

$context = [
    'VERSION' => VERSION,
    'HOST' => gethostname(),
    'SERVER' => $_SERVER,
    'GET' => $_GET,
    'POST' => $_POST,
    'php://input' => file_get_contents('php://input')
];

if (isset($_GET['debug'])) {
    echo '<pre>';
    var_dump($context);
}

logger('INFO', 'incoming request', $context);

