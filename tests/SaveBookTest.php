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
        $key = "";

        //Create mock database statement (for testing), says that the statement will be true
        $mockstatement = $this->createMock(PDOStatement::class);
        $mockstatement->expects($this->once())
            ->method('execute')
            ->willReturn(true);


        //Create fake database connection -- returns the mock statement above instead of a flase connection
        $mockdb = $this->createMock(Connection::class);
        $mockdb->expects($this->once())
        ->method('prepare')
        ->willReturn($mockstatement);

        //Fill parameters for getBook
        $result = getBook($mockdb, $key);

        //Assert that test should result in a failure/null
        $this->assertNull($result);
    }
}