<?php

namespace App\Ai\Support;

class AnswerStatus
{
    public const OK = 'ok';

    public const ZERO = 'zero';

    public const NO_DATA = 'no_data';

    public const NOT_YET_OCCURRED = 'not_yet_occurred';

    public const NOT_AVAILABLE = 'not_available';

    public const FORBIDDEN = 'forbidden';

    public const NEEDS_CLARIFICATION = 'needs_clarification';

    public const AGGREGATE_ONLY = 'aggregate_only';

    public const INVALID = 'invalid';
}
