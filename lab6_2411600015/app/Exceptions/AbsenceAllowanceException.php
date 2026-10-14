<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a transaction would push a course's remaining absences
 * below zero or above the allowed maximum.
 */
class AbsenceAllowanceException extends RuntimeException
{
}
