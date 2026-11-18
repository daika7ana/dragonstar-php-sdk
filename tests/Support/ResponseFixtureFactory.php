<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Support;

final class ResponseFixtureFactory
{
    /**
     * @param array<string, list<string>> $errors
     * @return array<string, mixed>
     */
    public static function validationError(array $errors, string $detail = 'The request has invalid fields.'): array
    {
        return [
            'type' => 'https://api.curierdragonstar.ro/errors/validation-failed',
            'title' => 'Validation failed',
            'status' => 422,
            'code' => 'validation_failed',
            'detail' => $detail,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function problem(string $code, int $status, string $title, string $detail): array
    {
        return [
            'type' => sprintf('https://api.curierdragonstar.ro/errors/%s', str_replace('_', '-', $code)),
            'title' => $title,
            'status' => $status,
            'code' => $code,
            'detail' => $detail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function awbCreated(int $awb = 94348148): array
    {
        return [
            'awb' => $awb,
            'totalAmount' => 9.83,
            'weight' => 5.0,
            'pieces' => 1,
            'codAmount' => null,
            'codPaymentType' => null,
        ];
    }
}
