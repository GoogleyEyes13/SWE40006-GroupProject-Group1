<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../database/connection.php';

final class ConnectionTest extends TestCase
{
    public function testConnectionRejectsMissingConfiguration(): void
    {
        $originalHost = getenv('DB_HOST');

        putenv('DB_HOST=');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Missing database configuration');

            connectToDatabase();
        } finally {
            if ($originalHost === false) {
                putenv('DB_HOST');
            } else {
                putenv('DB_HOST=' . $originalHost);
            }
        }
    }
}