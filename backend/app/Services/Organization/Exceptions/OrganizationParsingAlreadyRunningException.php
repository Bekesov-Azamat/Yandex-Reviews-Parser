<?php

namespace App\Services\Organization\Exceptions;

use RuntimeException;

class OrganizationParsingAlreadyRunningException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Organization parsing is already running.');
    }
}
