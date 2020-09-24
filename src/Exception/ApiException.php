<?php

namespace App\Exception;

use RuntimeException;

class ApiException extends RuntimeException
{
    const VALIDATION_ERROR = 0;
    const ROOT_CHANGE_ERROR = 1;

    const MESSAGES = [
        self::VALIDATION_ERROR => 'Validation Failed.',
        self::ROOT_CHANGE_ERROR => 'Root role cannot be modified.',
    ];

    private string $type = '';

    private $context;

    public function __construct($type, $context = null, $previous = null)
    {
        $this->type = $type;
        $this->context = $context;

        parent::__construct(
            self::MESSAGES[$type],
            0,
            $previous
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    public function getStatusCode(): int
    {
        return 400;
    }
}
