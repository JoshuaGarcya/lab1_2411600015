<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a withdrawal would leave the account below its minimum_balance.
 */
class MinimumBalanceException extends RuntimeException
{
}
