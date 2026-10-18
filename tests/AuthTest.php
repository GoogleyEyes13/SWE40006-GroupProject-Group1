<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../database/users.php';

final class AuthTest extends TestCase
{
    public function testValidCredentialsReturnUserId(): void
    {
        $password = 'TestPassword123!';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $statement = $this->createMock(PDOStatement::class);

        $statement->expects($this->once())
            ->method('execute')
            ->with(['email' => 'reader@example.com'])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id' => 42,
                'password_hash' => $hash
            ]);

        $db = $this->createMock(PDO::class);

        $db->expects($this->once())
            ->method('prepare')
            ->willReturn($statement);

        $result = authenticateUser(
            $db,
            'Reader@Example.com',
            $password
        );

        $this->assertSame(42, $result);
    }
}