<?php

namespace App\Http\Controllers\AI;

use App\Ai\Mcp\ToolRegistry;
use App\Ai\Security\AssistantAccessPolicy;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class McpController extends Controller
{
    public function __invoke(Request $request, ToolRegistry $registry): JsonResponse|Response
    {
        app(AssistantAccessPolicy::class)->markServiceRequest();

        if (! $this->authorized($request)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => ['code' => -32001, 'message' => 'Unauthorized MCP request.'],
                'id' => null,
            ], 401);
        }

        if ($request->isMethod('get')) {
            return response()->json([
                'name' => config('assistant.mcp.server_name', 'veestore-backoffice'),
                'version' => config('assistant.mcp.server_version', '1.0.0'),
                'protocolVersion' => '2024-11-05',
                'tools' => $registry->definitions(),
            ]);
        }

        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return $this->rpcError(null, -32700, 'Parse error.');
        }

        if (! isset($payload['method'])) {
            return $this->rpcError($payload['id'] ?? null, -32600, 'Invalid Request.');
        }

        if (! isset($payload['id'])) {
            return response()->noContent(202);
        }

        try {
            return match ($payload['method']) {
                'initialize' => $this->rpcResult($payload['id'], [
                    'protocolVersion' => $payload['params']['protocolVersion'] ?? '2024-11-05',
                    'capabilities' => [
                        'tools' => ['listChanged' => false],
                    ],
                    'serverInfo' => [
                        'name' => config('assistant.mcp.server_name', 'veestore-backoffice'),
                        'version' => config('assistant.mcp.server_version', '1.0.0'),
                    ],
                ]),
                'ping' => $this->rpcResult($payload['id'], new \stdClass),
                'tools/list' => $this->rpcResult($payload['id'], ['tools' => $registry->definitions()]),
                'tools/call' => $this->callTool($registry, $payload),
                'resources/list' => $this->rpcResult($payload['id'], ['resources' => []]),
                'prompts/list' => $this->rpcResult($payload['id'], ['prompts' => []]),
                default => $this->rpcError($payload['id'], -32601, 'Method not found.'),
            };
        } catch (Throwable $exception) {
            report($exception);

            return $this->rpcError($payload['id'], -32603, 'Internal error.');
        }
    }

    private function callTool(ToolRegistry $registry, array $payload): JsonResponse
    {
        $id = $payload['id'] ?? null;
        $name = $payload['params']['name'] ?? null;
        $arguments = $payload['params']['arguments'] ?? [];

        if (! is_string($name) || $name === '') {
            return $this->rpcError($id, -32602, 'Tool name is required.');
        }

        if (! is_array($arguments)) {
            return $this->rpcError($id, -32602, 'Tool arguments must be an object.');
        }

        $result = $registry->call($name, $arguments);
        $isError = isset($result['error']);

        return $this->rpcResult($id, [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}',
            ]],
            'isError' => $isError,
        ]);
    }

    private function authorized(Request $request): bool
    {
        $expected = trim((string) config('assistant.mcp.token'));

        if ($expected === '') {
            return false;
        }

        $provided = trim((string) $request->bearerToken());

        return $provided !== '' && hash_equals($expected, $provided);
    }

    private function rpcResult(mixed $id, mixed $result): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ]);
    }

    private function rpcError(mixed $id, int $code, string $message): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => ['code' => $code, 'message' => $message],
        ]);
    }
}
