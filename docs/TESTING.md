# Testing

## Commands

```bash
composer ci        # validate -> pint -> stan -> test:unit (exactly what CI runs)
composer pint      # style check only — never fixes
composer stan      # PHPStan level 8 over src/
composer test      # every test, smoke included
composer test:unit # tests/Unit only
vendor/bin/pint    # the only way to actually fix style
```

Run one file or one filter:

```bash
composer test -- tests/Unit/Resources/AwbResourceTest.php
composer test -- --filter=AwbResourceTest
```

CI jobs: Pint and PHPStan on PHP 8.3; unit tests on PHP 8.3, 8.4 and 8.5. The
live smoke test never runs in CI.

## Unit tests

There are no network calls in `tests/Unit`. Every resource test uses
`Daika7ana\DragonStar\Tests\Support\FakeHttpClient`, whose handler returns a
real `GuzzleHttp\Psr7\Response`:

```php
use Daika7ana\DragonStar\Tests\Support\TestClientFactory;
use GuzzleHttp\Psr7\Response;

[$client, $httpClient] = TestClientFactory::create(
    static fn() => new Response(200, [], '{"count":1,"data":{"CJ":"Cluj"}}'),
);

$counties = $client->directory()->counties();

$this->assertSame('/v1/counties', $httpClient->lastRequest?->getUri()->getPath());
```

`$httpClient->lastRequest` and `lastTimeout` let you assert method, path, query,
headers and body. `lastTimeout` is `null` when `ClientConfig::timeout` is left
unset.

`ResponseFixtureFactory` provides problem-document and `AwbCreated` fixtures.

## Smoke test

`tests/Smoke/ClientSmokeTest.php` hits the live API and is skipped unless the
key is configured:

```bash
cp phpunit.xml.dist phpunit.xml
# put the key in DRAGONSTAR_TEST_API_KEY
php vendor/bin/phpunit --group=smoke
```

`phpunit.xml` is gitignored; `phpunit.xml.dist` is the empty template. The key's
IP allowlist must include the machine running the test, otherwise the API
answers `403 ip_not_allowed`.

## Adding a resource test

1. Add the test under `tests/Unit/Resources/`, mirroring `src/Resources/`.
2. Build the client with `TestClientFactory::create()`.
3. Return the exact response your method expects, including status.
4. Assert both the DTO mapping and the outgoing request.
5. Add a failure case for the operation-specific problem code.

DTO factories are covered in `tests/Unit/DTOs`; mapper behavior in
`tests/Unit/Support`.
