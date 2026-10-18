<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/books.php";

class Test_Book extends TestCase
{
    public function TestSaveBook(): void {
        $author = "";
        $key = "";
        $title = "";
        //test that a book can be found in the database (check that the database exists)

        //if a book can be found, check that it can be fetched
    }
}