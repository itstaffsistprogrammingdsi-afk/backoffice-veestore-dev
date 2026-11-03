<?php

namespace App\Ai\Providers;

use App\Ai\Gateway\NineRouterGateway;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Providers\OpenAiCompatibleProvider;

class NineRouterProvider extends OpenAiCompatibleProvider
{
    public function textGateway(): StepTextGateway
    {
        return $this->textGateway ??= new NineRouterGateway($this->events);
    }
}
