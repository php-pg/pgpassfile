<?php

declare(strict_types=1);

namespace PhpPg\PgPassFile;

use Amp\ByteStream\ReadableResourceStream;
use InvalidArgumentException;

use function Amp\ByteStream\splitLines;
use function count;
use function explode;
use function fopen;
use function str_replace;
use function trim;

/**
 * @see https://www.postgresql.org/docs/current/libpq-pgpass.html
 */
final class PgPassFile
{
    /**
     * @param array<Entry> $entries
     */
    public function __construct(
        private array $entries = [],
    ) {
    }

    /**
     * @return array<Entry>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Finds the password for the provided hostname, port, database, and username.
     * Unix domain socket hostname must be set to "localhost".
     * An empty string will be returned if no match is found.
     *
     * @see https://www.postgresql.org/docs/current/libpq-pgpass.html
     * for more password file information.
     *
     * @param string $host
     * @param string $port
     * @param string $database
     * @param string $username
     * @return string
     */
    public function findPassword(string $host, string $port, string $database, string $username): string
    {
        foreach ($this->entries as $entry) {
            if (
                ($entry->host === '*' || $entry->host === $host) &&
                ($entry->port === '*' || $entry->port === $port) &&
                ($entry->database === '*' || $entry->database === $database) &&
                ($entry->username === '*' || $entry->username === $username)
            ) {
                return $entry->password;
            }
        }

        return '';
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function open(string $path): PgPassFile
    {
        $entries = [];

        $fp = @fopen($path, 'rb');
        if (false === $fp) {
            $lastErr = \error_get_last()['message'] ?? 'Unknown  error';
            \error_clear_last();

            throw new InvalidArgumentException("Unable to open .pgpass file at path {$path}: {$lastErr}");
        }

        $stream = new ReadableResourceStream($fp);

        foreach (splitLines($stream) as $line) {
            if (null !== ($entry = self::parseLine($line))) {
                $entries[] = $entry;
            }
        }

        return new PgPassFile($entries);
    }

    protected static function parseLine(string $line): ?Entry
    {
        // Cut excess chars
        $line = trim($line);

        // Skip comments
        if ($line === '' || $line[0] === '#') {
            return null;
        }

        $line = str_replace(["\\:", "\\\\"], ["\r", "\n"], $line);

        $parts = explode(':', $line);
        if (count($parts) !== 5) {
            return null;
        }

        // Unescape parts
        foreach ($parts as $idx => $part) {
            $parts[$idx] = str_replace(["\r", "\n"], [':', "\\"], $part);
        }

        return new Entry(
            host: $parts[0],
            port: $parts[1],
            database: $parts[2],
            username: $parts[3],
            password: $parts[4],
        );
    }
}
