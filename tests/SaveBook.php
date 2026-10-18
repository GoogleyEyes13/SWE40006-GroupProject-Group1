<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ ."/../database/books.php";

class Test_Book extends TestCase
{
    public function TestSaveBook(): void {
        $s = new Search();
        $this->assure("", $s);
    }
}