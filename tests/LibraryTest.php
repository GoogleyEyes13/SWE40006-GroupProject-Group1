<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../database/library.php';

final class LibraryTest extends TestCase
{
    public function testGetUserLibrary(): void
    {
        $expected = [
            [
                'id' => 10,
                'title' => 'Dune',
                'status' => 'reading',
                'quantity' => 1
            ]
        ];

        $statement = $this->createMock(PDOStatement::class);

        $statement->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 5])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('fetchAll')
            ->willReturn($expected);

        $db = $this->createMock(PDO::class);

        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE ub.user_id = :user_id'))
            ->willReturn($statement);

        $this->assertSame($expected, getUserLibrary($db, 5));
    }

    public function testSaveUserBook(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $statement->expects($this->once())
            ->method('execute')
            ->with([
                'user_id' => 5,
                'book_id' => 10,
                'status' => 'reading',
                'quantity' => 1
            ])
            ->willReturn(true);

        $db = $this->createMock(PDO::class);

        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO user_books'))
            ->willReturn($statement);

        saveUserBook($db, 5, 10, 'reading', 1);
    }

    public function testUpdateUserLibrary(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $statement->expects($this->once())
            ->method('execute')
            ->with([
                'status' => 'read',
                'quantity' => 2,
                'user_id' => 5,
                'book_id' => 10
            ])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $db = $this->createMock(PDO::class);

        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE user_books'))
            ->willReturn($statement);

        $this->assertTrue(
            updateUserBook($db, 5, 10, 'read', 2)
        );
    }

    public function testRemoveBookFromLibrary(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $statement->expects($this->once())
            ->method('execute')
            ->with([
                'user_id' => 5,
                'book_id' => 10
            ])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $db = $this->createMock(PDO::class);

        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM user_books'))
            ->willReturn($statement);

        $this->assertTrue(
            removeUserBook($db, 5, 10)
        );
    }

    public function testRejectInvalidReadingStatus(): void
    {
        $db = $this->createMock(PDO::class);

        $db->expects($this->never())
            ->method('prepare');

        $this->expectException(InvalidArgumentException::class);

        saveUserBook($db, 5, 10, 'invalid_status', 1);
    }

    public function testRejectInvalidQuantity(): void
    {
        $db = $this->createMock(PDO::class);

        $db->expects($this->never())
            ->method('prepare');

        $this->expectException(InvalidArgumentException::class);

        updateUserBook($db, 5, 10, 'reading', -5);
    }
}