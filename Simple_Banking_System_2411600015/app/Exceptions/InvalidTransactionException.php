<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a transaction itself is invalid (bad amount, unknown account, unsupported type).
 */
class InvalidTransactionException extends RuntimeException
{
}
