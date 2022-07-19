<?php

declare(strict_types=1);

const VERSION = '0.4.2';

function jsonEncode(mixed $data): string
{
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS);
}

function logger(string $level, string $message, array $context = []): void
{
    file_put_contents('php://stderr', jsonEncode(array_merge(['level' => $level, 'message' => $message], $context)) . PHP_EOL);
}

function getInput(int $inputType, string $key, string $default = null): ?string
{
    $value = filter_input($inputType, $key);

    if ($value === null) {
        return $default;
    }

    return (string)$value;
}

function getServerParam(string $key, string $default = null): ?string
{
    return getInput(INPUT_SERVER, $key, $default);
}

function getEnvParam(string $key, string $default = null): ?string
{
    return getInput(INPUT_ENV, $key, $default);
}

function getProxyHeaders(): array
{
    $proxyHeaders = [];
    $proxyKeys = [
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED_HOST',
        'HTTP_X_FORWARDED_PORT',
        'HTTP_X_FORWARDED_PROTO'
    ];
    foreach ($proxyKeys as $proxyKey) {
        $proxyValue = getServerParam($proxyKey);
        if ($proxyValue === null) {
            continue;
        }
        $key = str_replace('HTTP_X_', '', $proxyKey);
        $proxyHeaders[$key] = $proxyValue;
    }

    return $proxyHeaders;
}

$requestUri = getServerParam('REQUEST_URI') ?: '/';
if (preg_match('#/status/(.+)#', $requestUri, $statusMatches)) {
    if ($statusMatches[1] === 'random') {
        $availableStatuses = [200, 401, 500];
        $statusCode = $availableStatuses[array_rand($availableStatuses)];
    } else {
        $statusCode = (int)$statusMatches[1];
    }
    http_response_code($statusCode);
} else if (preg_match('#/redirect/(.+)#', $requestUri, $redirectMatches)) {
    header("Location: $redirectMatches[1]", true, 302);
}

$hostName = gethostname();
$userAgent = getServerParam('HTTP_USER_AGENT', 'N/A');
$response = [
    'VERSION' => VERSION,
    'HOST_NAME' => $hostName,
    'NODE_NAME' => getEnvParam('NODE_NAME', $hostName),
    'USER_AGENT' => $userAgent,
    'REMOTE_IP' => getServerParam('REMOTE_ADDR'),
    'PROTOCOL' => getServerParam('SERVER_PROTOCOL'),
    'URI' => $requestUri,
    'METHOD' => getServerParam('REQUEST_METHOD'),
    'COOKIE' => $_COOKIE,
    'QUERY' => $_GET,
    'PARSED_BODY' => $_POST,
    'RAW_BODY' => file_get_contents('php://input')
];

$proxyHeaders = getProxyHeaders();
if (count($proxyHeaders) > 0) {
    $response['PROXY'] = $proxyHeaders;
}

$debugMode = (bool)getServerParam('HTTP_DEBUG');
if ($debugMode) {
    $response['SERVER'] = $_SERVER;
    $response['ENV'] = $_ENV;
}

if (str_starts_with($userAgent, 'curl')
    || str_contains(getServerParam('HTTP_CONTENT_TYPE', ''), 'application/json')) {
    header('Content-Type: application/json');
    echo jsonEncode($response);
} else {
    echo '<pre>';
    var_dump($response);
}

logger('INFO', 'incoming request', $response);
