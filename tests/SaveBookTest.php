<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/books.php";

//Mock unit test with database
class SaveBookTest extends TestCase
{
    public function testGetBook(): void {
        //Create mock database
        $key = "1";
        $statement = $this->createMock(PDOStatement::class);

        $author = "";
        $key = "";
        $title = "";
        //test that a book can be found in the database (check that the database exists)
        $this->assertTrue(true);
        //if a book can be found, check that it can be fetched
    }

    public function testNullCheck(): void {
        //Create mock database
        $key = "";
        $book = "Harry Potter"
        $statement = $this->createMock(PDOStatement::class);

        //assign how sql calls are handled
        $statement->expects($this->once())
            ->method('execute')
            ->with(['key' => $key])
        
        $statement->expects($this->once())
            ->method('fetch')
            ->willReturn(['key' => $key, 'title' => null])

        //Create fake database connection
        $db = $this->createMock(PDO::class);

        //Fill parameters for getBook
        $result = getBook($db, $statement);

        //Assert that test should result in a failure/null
        $this->assertNull($result)
    }
}