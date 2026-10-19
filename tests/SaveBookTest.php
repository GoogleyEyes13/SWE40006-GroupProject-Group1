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

        //Create mock database statement calls
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())
            ->method('execute')
            //->with(['key' => $key])
            ->willReturn(true);

        $statement->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'key' => "1",
                'title' => $title,
                'author' => $author,
                'year' => $year,
                'cover' => $cover
            ]);

        //retrieves the title from the database
        $mockdb = $this->createMock(PDO::class);
        $mockdb->expects($this->once())
            ->method('prepare')
            ->willReturn($statement);

        $result = getBook($mockdb, $key);

        //check that something is returned when the database is searched
        $this->assertNotNull($result, 'database can be searched and isnt null');

        $nullresult = getBook($mockdb, "0");
        $this->assertNull($nullresult);
    }

}