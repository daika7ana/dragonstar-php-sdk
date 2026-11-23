<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Exceptions;

use Daika7ana\DragonStar\DTOs\Common\Errors\DragonStarError;
use Psr\Http\Message\ResponseInterface;

class UnexpectedStatusException extends DragonStarException
{
    public function __construct(
        public readonly ?ResponseInterface $response = null,
        string $message = '',
        int $code = 0,
        public readonly ?int $retryAfter = null,
        public readonly ?DragonStarError $error = null,
    ) {
        if ($message === '') {
            $message = $response === null
                ? 'Unexpected HTTP status'
                : "Unexpected HTTP status {$response->getStatusCode()}";
        }

        // getCode() is the HTTP status whenever a response exists; $code only covers response-less failures.
        parent::__construct($message, $response?->getStatusCode() ?? $code);
    }
}
