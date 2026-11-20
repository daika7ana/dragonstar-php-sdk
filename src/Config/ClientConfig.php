<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Config;

final class ClientConfig
{
    public const BASE_URL_PRODUCTION = 'https://api.curierdragonstar.ro';

    public function __construct(
        public string $baseUrl = self::BASE_URL_PRODUCTION,
        public string $apiKey = '',
        public ?float $timeout = null,
    ) {}

    public function withBaseUrl(string $baseUrl): self
    {
        $config = clone $this;
        $config->baseUrl = rtrim($baseUrl, '/');

        return $config;
    }

    public function withApiKey(string $apiKey): self
    {
        $config = clone $this;
        $config->apiKey = $apiKey;

        return $config;
    }

    public function withTimeout(?float $timeout): self
    {
        $config = clone $this;
        $config->timeout = $timeout;

        return $config;
    }
}
