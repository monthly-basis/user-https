<?php
namespace MonthlyBasis\UserHttpsTest;

use MonthlyBasis\LaminasTest\ModuleTestCase;
use MonthlyBasis\UserHttps\Module;

class ModuleTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        $this->module = new Module();

        $_SERVER['HTTP_HOST'] = 'example.com';
    }
}
