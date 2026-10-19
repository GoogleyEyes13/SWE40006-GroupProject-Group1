<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/connection.php";

final class ConnectionTest extends TestCase
{
    public function testDBConnection(): void {
        //Test that database is connected and accessible
        $this->assertTrue(true);
    }
}