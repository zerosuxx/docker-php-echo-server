<?php
declare(strict_types=1);

namespace App;

use Swoole\HTTP\Server;
use Swoole\HTTP\Request;
use Swoole\HTTP\Response;

class RequestHandler
{
    private Server $server;
    private Encoder $encoder;
    private array $env;
    private string $version;

    public function __construct(Server $server, Encoder $encoder, array $env, string $version)
    {
        $this->server = $server;
        $this->encoder = $encoder;
        $this->env = $env;
        $this->version = $version;
    }

    public function handle(Request $request, Response $response): void
    {
        $response->header('Server', "EchoServer ($this->version)");
        $response->header('Content-Type', 'application/json');

        $this->handleActions($request, $response);
    }

    private function handleActions(Request $request, Response $response): void
    {
        $requestUri = $request->server['request_uri'];
        if ($requestUri === '/healthcheck') {
            $response->end($this->encoder->json(['success' => true]));
        } else if (preg_match('#/status/(.+)#', $requestUri, $statusMatches)) {
            if ($statusMatches[1] === 'random') {
                $availableStatuses = [200, 401, 500];
                $statusCode = $availableStatuses[array_rand($availableStatuses)];
            } else {
                $statusCode = (int)$statusMatches[1];
            }
            $response->status($statusCode);
        } else if (preg_match('#/redirect/(.+)#', $requestUri, $redirectMatches)) {
            $response->redirect($redirectMatches[1]);
        } else {
            echo $this->encoder->json([
                'version' => $this->version,
                'request' => $request,
                'server' => $this->server,
                'stats' => $this->server->stats(),
                'client_info' => $this->server->getClientInfo($request->fd),
                'env' => $this->env
            ]) . PHP_EOL;
            
            $hostName = gethostname();
            $responseBody = [
                'VERSION' => $this->version,
                'HOST_NAME' => $hostName,
                'NODE_NAME' => $this->env['NODE_NAME'] ?? $hostName,
                'USER_AGENT' => $request->header['user-agent'] ?? 'Unknown',
                'REMOTE_IP' => $request->server['remote_addr'],
                'PROTOCOL' => $request->server['server_protocol'],
                'URI' => $request->server['request_uri'],
                'METHOD' => $request->server['request_method'],
                'HEADER' => $request->header,
                'COOKIE' => $request->cookie ?? [],
                'QUERY' => $request->get ?? [],
                'PARSED_BODY' => $request->post ?? [],
                'RAW_BODY' => $request->getContent()
            ];

            $debugMode = isset($request->header['debug']) || isset($request->get['debug']);
            if ($debugMode) {
                $responseBody['SERVER'] = $request->server;
                $responseBody['ENV'] = $this->env;
            }

            $response->end($this->encoder->json($responseBody));
        }
    }
}
