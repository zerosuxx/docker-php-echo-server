<?php
declare(strict_types=1);

namespace App;

use Swoole\HTTP\Server;
use Swoole\HTTP\Request;
use Swoole\HTTP\Response;

class RequestHandler
{
    private Server $server;
    private array $env;
    private string $version;

    public function __construct(Server $server, array $env, string $version)
    {
        $this->server = $server;
        $this->env = $env;
        $this->version = $version;
    }

    public function handle(Request $request, Response $response): void
    {
        echo $this->jsonEncode($request);

        $hostName = gethostname();
        $responseBody = [
            'VERSION' => $this->version,
            'HOST_NAME' => $hostName,
            'NODE_NAME' => $this->env['NODE_NAME'] ?? $hostName,
            'USER_AGENT' => $request->header['user-agent'],
            'REMOTE_IP' => $request->server['remote_addr'],
            'PROTOCOL' => $request->server['server_protocol'],
            'URI' => $request->server['request_uri'],
            'METHOD' => $request->server['request_method'],
            'COOKIE' => $request->cookie ?? [],
            'QUERY' => $request->get ?? [],
            'PARSED_BODY' => $request->post ?? [],
            'RAW_BODY' => $request->getContent()
        ];

        $debugMode = (bool)$request->header['debug'];
        if ($debugMode) {
            $responseBody['SERVER'] = $request->server;
            $responseBody['ENV'] = $this->env;
        }

        $response->header('Content-Type', 'application/json');

        $this->handleActions($request, $response);

        $response->end($this->jsonEncode($responseBody));
    }

    private function handleActions(Request $request, Response $response): void
    {
        $requestUri = $request->server['request_uri'];
        if (preg_match('#/status/(.+)#', $requestUri, $statusMatches)) {
            if ($statusMatches[1] === 'random') {
                $availableStatuses = [200, 401, 500];
                $statusCode = $availableStatuses[array_rand($availableStatuses)];
            } else {
                $statusCode = (int)$statusMatches[1];
            }
            $response->status($statusCode);
        } else if (preg_match('#/redirect/(.+)#', $requestUri, $redirectMatches)) {
            $response->redirect($redirectMatches[1]);
        }
    }

    private function jsonEncode(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS);
    }
}