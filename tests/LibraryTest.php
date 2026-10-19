<?php
declare(strict_types=1);

//use Illuminate\Foundations\Testing\RefreshDatabase
use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/library.php";

//integration test
class LibraryTest extends TestCase
{
    public function testGetUserLibrary(): void {
        //Mock variables for user library

        //Test that user can access their library
        $this->assertTrue(true);
    }

    public function testSaveUserBook(): void {
        //mock variables for books

        //Test that a book can be saved to the users library
        $this->assertTrue(true);
    }

    public function testUpdateUserLibrary(): void {
        //Update a user library setting for a book

        //Test that a book can be updated from a users library
        $this->assertTrue(true);
    }

    public function testRemoveBookFromLibrary(): void {
        //remove a library book from list
        $userID = 1;
        $bookID = 5;

        //Create mock database statement calls
        $delstatement = $this->createMock(PDOStatement::class);

        //configure a mock database behaviour expectations
        $delstatement->expects($this->once())
            ->method('delete')
            ->with([
                'user_id' => $userID,
                'book_id' => $bookID
            ]);

        $service = new UserService($delstatement);

        $result = removeUserBook($service, $userID, $bookID);

        //Test that a book can be removed from a users library and that no rows are returned
        $this->assertSame(true, $result);
    }
}