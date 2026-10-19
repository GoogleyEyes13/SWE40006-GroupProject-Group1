<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../database/books.php';

final class SaveBookTest extends TestCase
{
    public function testSaveValidBook(): void
    {
        $book = [
            'key' => '/works/OL12345W',
            'title' => 'Dune',
            'author_name' => ['Frank Herbert'],
            'first_publish_year' => 1965,
            'cover_i' => 12345
        ];

        $statement = $this->createMock(PDOStatement::class);

        $statement->expects($this->once())
            ->method('execute')
            ->with([
                'key' => '/works/OL12345W',
                'title' => 'Dune',
                'author' => 'Frank Herbert',
                'year' => 1965,
                'cover' => 12345
            ])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(42);

        $db = $this->createMock(PDO::class);

        $db->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO books'))
            ->willReturn($statement);

        $this->assertSame(42, saveBook($db, $book));
    }

    public function testRejectInvalidBookKey(): void
    {
        $db = $this->createMock(PDO::class);

        $db->expects($this->never())
            ->method('prepare');

        $this->expectException(InvalidArgumentException::class);

        saveBook($db, [
            'key' => 'invalid',
            'title' => 'Dune'
        ]);
    }

    public function testRejectEmptyBookTitle(): void
    {
        $db = $this->createMock(PDO::class);

        $db->expects($this->never())
            ->method('prepare');

        $this->expectException(InvalidArgumentException::class);

        saveBook($db, [
            'key' => '/works/OL12345W',
            'title' => ''
        ]);
    }

    public function testRetrieveExistingBook(): void
    {
        $expected = [
            'id' => 42,
            'title' => 'Dune',
            'author' => 'Frank Herbert'
        ];

        $statement = $this->createMock(PDOStatement::class);

        $statement->expects($this->once())
            ->method('execute')
            ->with(['key' => '/works/OL12345W'])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('fetch')
            ->willReturn($expected);

        $db = $this->createMock(PDO::class);

        $db->expects($this->once())
            ->method('prepare')
            ->willReturn($statement);

        $this->assertSame(
            $expected,
            getBook($db, '/works/OL12345W')
        );
    }
}