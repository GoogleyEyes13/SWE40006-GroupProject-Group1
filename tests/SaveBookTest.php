<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/books.php";

class SaveBookTest extends TestCase
{
    public function TestSaveBook(): void {
        $author = "";
        $key = "";
        $title = "";
        //test that a book can be found in the database (check that the database exists)
        $this->assertTrue(true);
        //if a book can be found, check that it can be fetched
    }
}