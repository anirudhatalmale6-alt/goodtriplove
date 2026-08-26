<?php

namespace App\Services;

use RuntimeException;

/**
 * Thrown when the daily API budget is spent. The collector treats this as
 * "stop for today", not as a failure worth alerting on.
 */
class QuotaExhaustedException extends RuntimeException
{
}
