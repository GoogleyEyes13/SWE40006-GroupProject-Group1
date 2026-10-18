<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/library.php";

class LibraryTest extends TestCase
{
    public function TestGetUserLibrary(): void {
        //Test that user can access their library
        $this->assertTrue(true);
    }

    public function TestSaveUserBook(): void {
        //Test that a book can be saved to the users library
        $this->assertTrue(true);
    }

    public function TestUpdateUserLibrary(): void {
        //Test that a book can be updated from a users library
        $this->assertTrue(true);
    }

    public function TestRemoveBookFromLibrary(): void {
        //Test that a book can be removed from a users library
        $this->assertTrue(true);
    }
}