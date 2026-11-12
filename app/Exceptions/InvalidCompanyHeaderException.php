<?php

namespace App\Exceptions;

use Exception;

class InvalidCompanyHeaderException extends Exception
{
    public function __construct(string $company)
    {
        parent::__construct("Invalid company column header for '{$company}'.");
    }
}
