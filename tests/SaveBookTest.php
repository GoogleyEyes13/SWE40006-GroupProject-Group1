<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/books.php";

//Mock unit test with database
class SaveBookTest extends TestCase
{
    public function testGetBook(): void {
        //variables retrieved for test
        $key = "1";
        $title = "Percy Jackson's Greek Gods";
        $author = "Rick Riordan";
        $year = 2006;
        $cover = 5;

        //Create mock database
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $mockdb = $this->createMock(PDO::class);
        $mockdb->expects($this->once())
            ->method('prepare')
            ->willReturn($statement);

        $result = getBook($mockdb, $key);

        //test that a book can be found in the database (check that the database exists)
        $this->assertSame($title, $result);
        //if a book can be found, check that it can be fetched
    }


}