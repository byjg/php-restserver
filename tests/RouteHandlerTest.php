<?php

use ByJG\RestServer\ServerRequestHandler;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class RouteHandlerTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var ServerRequestHandler
     */
    private $object;

    public function setUp()
    {
        $this->object = ServerRequestHandler::getInstance();
    }

    public function tearDown()
    {

    }

    public function testModuleAlias()
    {
        $list = ['teste' => 'Class.Complete', 'teste2' => 'Another.Class'];
        $this->object->setModuleAlias( $list );
        $this->assertEquals($list, $this->object->getModuleAlias());

        $this->object->addModuleAlias('new', 'Other.Class');
        $this->assertEquals(array_merge($list, ['new' => 'Other.Class']), $this->object->getModuleAlias());
    }
}
