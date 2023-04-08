<?php

namespace datagutten\nrk\exceptions;

use Exception;

class NRKException extends Exception
{
    public function setMessage($message)
    {
        $this->message = $message;
    }
}