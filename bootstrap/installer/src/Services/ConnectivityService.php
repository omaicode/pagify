<?php

namespace Pagify\Installer\Services;

use PDO;
use Throwable;

class ConnectivityService
{
    /**
     * @param array<string, mixed> $db
     * @return array<string, mixed>
     */
    public function testDatabase(array $db): array
    {
        $connection = (string) ($db['connection'] ?? 'mysql');

        if ($connection === 'sqlite') {
            $database = (string) ($db['database'] ?? '');
            if ($database === '') {
                return ['ok' => false, 'message' => 'SQLite database path is required.'];
            }

            if (! is_file($database)) {
                return ['ok' => false, 'message' => 'SQLite database file does not exist.'];
            }

            return ['ok' => true, 'message' => 'SQLite database file is available.'];
        }

        if ($connection !== 'mysql' && $connection !== 'pgsql') {
            return ['ok' => false, 'message' => 'Only mysql, pgsql, and sqlite are supported by installer connectivity test.'];
        }

        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (string) ($db['port'] ?? ($connection === 'pgsql' ? '5432' : '3306'));
        $database = (string) ($db['database'] ?? '');
        $username = (string) ($db['username'] ?? '');
        $password = (string) ($db['password'] ?? '');

        if ($database === '' || $username === '') {
            return ['ok' => false, 'message' => 'Database name and username are required.'];
        }

        $dsn = $connection === 'pgsql'
            ? sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database)
            : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            new PDO($dsn, $username, $password, [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Database connection established successfully.',
        ];
    }

    /**
     * @param array<string, mixed> $mail
     * @return array<string, mixed>
     */
    public function testMail(array $mail): array
    {
        $mailer = (string) ($mail['mailer'] ?? 'smtp');

        if (in_array($mailer, ['log', 'array'], true)) {
            return [
                'ok' => true,
                'message' => sprintf('Mailer "%s" does not require remote connectivity test.', $mailer),
            ];
        }

        if ($mailer !== 'smtp') {
            return [
                'ok' => false,
                'message' => 'Installer currently supports mail connectivity validation for smtp, log, and array mailers only.',
            ];
        }

        $host = (string) ($mail['host'] ?? '');
        $port = (int) ($mail['port'] ?? 0);

        if ($host === '' || $port <= 0) {
            return [
                'ok' => false,
                'message' => 'Mail host and port are required for SMTP validation.',
            ];
        }

        $errno = 0;
        $errstr = '';

        $connection = @fsockopen($host, $port, $errno, $errstr, 5.0);

        if (! is_resource($connection)) {
            return [
                'ok' => false,
                'message' => sprintf('Unable to connect to SMTP server: %s (%d)', $errstr, $errno),
            ];
        }

        fclose($connection);

        return [
            'ok' => true,
            'message' => 'SMTP server is reachable.',
        ];
    }
}
