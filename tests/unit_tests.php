<?php
namespace Tests;

use BookManger\backend\Search;
use PHPUnit\Framework\TestCase;

class Test_Auth extends TestCase
{
    public function Test_Search(): void {
        $s = new Search();
        $this->test_Auth();
    }
}