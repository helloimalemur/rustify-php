<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Rustify\Json;

final class JsonTest extends TestCase
{
    public function testDecode(): void
    {
        $ok = Json::decode('{"a":1}');
        $this->assertTrue($ok->isOk());
        $this->assertSame(['a' => 1], $ok->unwrap());

        $err = Json::decode('{');
        $this->assertTrue($err->isErr());

        $empty = Json::decode('');
        $this->assertTrue($empty->isErr());
    }

    public function testDecodeObject(): void
    {
        $ok = Json::decodeObject('{"a":1}');
        $this->assertTrue($ok->isOk());
        $this->assertSame(['a' => 1], $ok->unwrap());

        $scalar = Json::decodeObject('123');
        $this->assertTrue($scalar->isErr());
    }
}
