<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Exceptions;

use Daika7ana\DragonStar\DTOs\Common\Errors\DragonStarError;
use Exception;
use Psr\Http\Message\ResponseInterface;

class ValidationException extends DragonStarException
{
    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(
        public readonly array $errors,
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        public readonly ?ResponseInterface $response = null,
        public readonly ?int $retryAfter = null,
        public readonly ?DragonStarError $error = null,
    ) {
        parent::__construct($message ?: 'Validation failed', $response?->getStatusCode() ?? $code, $previous);
    }
}
