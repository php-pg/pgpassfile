<?php

declare(strict_types=1);

namespace PhpPg\PgPassFile;

class Entry
{
    public function __construct(
        public string $host,
        public string $port,
        public string $database,
        public string $username,
        public string $password,
    ) {
    }
}
