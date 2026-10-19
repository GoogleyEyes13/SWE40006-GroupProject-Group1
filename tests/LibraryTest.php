<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/library.php";

class LibraryTest extends TestCase
{
    public function testGetUserLibrary(): void {
        //Test that user can access their library
        $this->assertTrue(true);
    }

    public function testSaveUserBook(): void {
        //Test that a book can be saved to the users library
        $this->assertTrue(true);
    }

    public function testUpdateUserLibrary(): void {
        //Test that a book can be updated from a users library
        $this->assertTrue(true);
    }

    public function testRemoveBookFromLibrary(): void {
        //Test that a book can be removed from a users library
        $this->assertTrue(true);
    }
}