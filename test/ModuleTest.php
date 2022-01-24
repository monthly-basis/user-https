<?php
namespace MonthlyBasis\UserHttpsTest;

use Laminas\Mvc\Application;
use MonthlyBasis\LaminasTest\ModuleTestCase;
use MonthlyBasis\UserHttps\Module;

class ModuleTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        $this->module = new Module();

        $_SERVER['HTTP_HOST'] = 'example.com';
    }

    /**
     * @runInSeparateProcess
     */
    public function test_getControllerConfig()
    {
        $applicationConfig = include(__DIR__ . '/../config/application.config.php');
        $this->application = Application::init($applicationConfig);
        $serviceManager    = $this->application->getServiceManager();
        $controllerManager = $serviceManager->get('ControllerManager');

        $controllerConfig  = $this->module->getControllerConfig();

        foreach ($controllerConfig['factories'] as $class => $value) {
            $this->assertInstanceOf(
                $class,
                $controllerManager->get($class)
            );
        }
    }
}
