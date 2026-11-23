<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Exceptions;

use Daika7ana\DragonStar\DTOs\Common\Errors\DragonStarError;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AuthenticationException extends DragonStarException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?ResponseInterface $response = null,
        public readonly ?int $retryAfter = null,
        public readonly ?DragonStarError $error = null,
    ) {
        parent::__construct($message, $response?->getStatusCode() ?? $code, $previous);
    }
}
