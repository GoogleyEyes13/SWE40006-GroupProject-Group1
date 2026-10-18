<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/library.php";

class Test_Library extends TestCase
{
    public function TestGetUserLibrary(): void {
        //Test that user can access their library
    }

    public function TestSaveUserBook(): void {
        //Test that a book can be saved to the users library
    }

    public function TestUpdateUserLibrary(): void {
        //Test that a book can be updated from a users library
    }

    public function TestRemoveBookFromLibrary(): void {
        //Test that a book can be removed from a users library
    }
}