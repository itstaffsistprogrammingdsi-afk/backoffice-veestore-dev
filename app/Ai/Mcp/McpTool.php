<?php

namespace App\Ai\Mcp;

interface McpTool
{
    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function run(array $arguments): array;
}
