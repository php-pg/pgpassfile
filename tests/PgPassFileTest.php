<?php

namespace PhpPg\PgPassFile\Tests;

use PhpPg\PgPassFile\PgPassFile;
use PHPUnit\Framework\TestCase;

class PgPassFileTest extends TestCase
{
    public function testOpen(): void
    {
        $data = <<<EOT
# A comment
test1:5432:larrydb:larry:whatstheidea
test1:5432:moedb:moe:imbecile
test1:5432:curlydb:curly:nyuknyuknyuk
test2:5432:*:shemp:heymoe
test2:5432:*:*:test\\ing\:
# And opeeeeeen!
localhost:*:*:*:sesam
EOT;

        \file_put_contents('some_pgpass_file.pgpass', $data);

        $pgPassFile = PgPassFile::open('some_pgpass_file.pgpass');

        $entries = $pgPassFile->getEntries();
        self::assertCount(6, $entries);

        self::assertSame("whatstheidea", $pgPassFile->findPassword("test1", "5432", "larrydb", "larry"));
        self::assertSame("imbecile", $pgPassFile->findPassword("test1", "5432", "moedb", "moe"));
        self::assertSame("test\\ing:", $pgPassFile->findPassword("test2", "5432", "something", "else"));
        self::assertSame("sesam", $pgPassFile->findPassword("localhost", "9999", "foo", "bare"));
    
        self::assertSame("", $pgPassFile->findPassword("wrong", "5432", "larrydb", "larry"));
        self::assertSame("", $pgPassFile->findPassword("test1", "wrong", "larrydb", "larry"));
        self::assertSame("", $pgPassFile->findPassword("test1", "5432", "wrong", "larry"));
        self::assertSame("", $pgPassFile->findPassword("test1", "5432", "larrydb", "wrong"));
    }

    public function testOpenFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to open .pgpass file');

        PgPassFile::open('unknownfile.txt');
    }
}
