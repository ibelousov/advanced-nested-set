<?php

namespace Ibelousov\AdvancedNestedSet\Exceptions;

use Exception;
use Throwable;

class UnsupportedDatabaseException extends Exception
{
    public function __construct($driver, $code = 0, Throwable $previous = null)
    {
        parent::__construct("Unknown database driver: $driver", $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__.": [{$this->code}]: {$this->message}\n";
    }
}
