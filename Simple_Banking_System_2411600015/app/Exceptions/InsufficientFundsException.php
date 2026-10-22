<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a withdrawal is bigger than the money in the account (balance would be negative).
 */
class InsufficientFundsException extends RuntimeException
{
}
