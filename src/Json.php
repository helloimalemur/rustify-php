<?php

declare(strict_types=1);

namespace Rustify;

use function Rustify\{ok, err};

final class Json
{
    /**
     * Decode JSON into mixed, returning Result<mixed, string>.
     *
     * - Rejects empty string as an error.
     */
    public static function decode(string $raw): Result
    {
        if ($raw === '') {
            return err('Empty JSON body');
        }

        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return err(json_last_error_msg());
        }

        return ok($data);
    }

    /**
     * Decode JSON and ensure it's an array (object / list) not scalar.
     * Good for API request bodies.
     *
     * @return Result<array, string>
     */
    public static function decodeObject(string $raw): Result
    {
        $res = self::decode($raw);

        if ($res->isErr()) {
            return $res;
        }

        $data = $res->unwrap();

        if (!is_array($data)) {
            return err('Expected JSON object or array');
        }

        /** @var Result<array, string> */
        return ok($data);
    }
}
