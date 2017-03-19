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
        $this->object->setModuleAlias([]);
        $this->object->setDefaultHandler(null);
        $this->object->setDefaultOutput(null);
        $this->object->setDefaultRestVersion(null);
        $this->object->setRoutes([]);
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

    public function testDefaultHandler()
    {
        $this->object->setDefaultHandler( '\Some\Handler' );
        $this->assertEquals('\Some\Handler', $this->object->getDefaultHandler());
    }

    public function testDefaulOutput()
    {
        $this->object->setDefaultOutput( \ByJG\RestServer\Output::JSON );
        $this->assertEquals(\ByJG\RestServer\Output::JSON, $this->object->getDefaultOutput());
    }

    public function testDefaultRestVersion()
    {
        $this->object->setDefaultRestVersion( 'v1' );
        $this->assertEquals('v1', $this->object->getDefaultRestVersion());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDefaultMethods()
    {
        $this->object->setRoutes( ['wrongarray' => ''] );
    }
}
