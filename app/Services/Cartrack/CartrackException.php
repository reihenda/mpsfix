<?php

namespace App\Services\Cartrack;

use RuntimeException;

class CartrackException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }
}
