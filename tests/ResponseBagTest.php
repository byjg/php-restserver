<?php

namespace Tests;

use ByJG\RestServer\ResponseBag;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/ModelSample.php';

class ResponseBagTest extends TestCase
{
    /**
     * @var ResponseBag
     */
    private $object;

    public function setup(): void
    {
        $this->object = new ResponseBag();
    }

    public function tearDown(): void
    {
        $this->object = null;
    }

    public function testAddStringAutomatic()
    {
        $this->object->add('Test1');
        $this->assertEquals(
            ['Test1'],
            $this->object->process()
        );

        $this->object->add('Test2');
        $this->assertEquals(
            [
                ['Test1'],
                ['Test2']
            ],
            $this->object->process()
        );
    }

    public function testAddStringArray()
    {
        $this->object->setSerializationRule(ResponseBag::OBJECT_LIST);
        $this->object->add('Test1');
        $this->assertEquals(
            [
                ['Test1']
            ],
            $this->object->process()
        );

        $this->object->add('Test2');
        $this->assertEquals(
            [
                ['Test1'],
                ['Test2']
            ],
            $this->object->process()
        );
    }

    public function testAddStringSingleObject()
    {
        $this->object->setSerializationRule(ResponseBag::SINGLE_OBJECT);
        $this->object->add('Test1');
        $this->assertEquals(
            'Test1',
            $this->object->process()
        );

        $this->object->add('Test2');
        $this->assertEquals(
            [
                'Test1',
                'Test2'
            ],
            $this->object->process()
        );
    }

    public function testAddArrayAutomatic()
    {
        $this->object->add(['key1' => 'Test1']);
        $this->assertEquals(
            ['key1' => 'Test1'],
            $this->object->process()
        );

        $this->object->add(['key2' => 'Test2']);
        $this->assertEquals(
            [
                ['key1' => 'Test1'],
                ['key2' => 'Test2']
            ],
            $this->object->process()
        );
    }

    public function testAddArrayArray()
    {
        $this->object->setSerializationRule(ResponseBag::OBJECT_LIST);
        $this->object->add(['key1' => 'Test1']);
        $this->assertEquals(
            [
                ['key1' => 'Test1']
            ],
            $this->object->process()
        );

        $this->object->add(['key2' => 'Test2']);
        $this->assertEquals(
            [
                ['key1' => 'Test1'],
                ['key2' => 'Test2']
            ],
            $this->object->process()
        );
    }

    public function testAddArraySingleObject()
    {
        $this->object->setSerializationRule(ResponseBag::SINGLE_OBJECT);
        $this->object->add(['key1' => 'Test1']);
        $this->assertEquals(
            ['key1' => 'Test1'],
            $this->object->process()
        );

        $this->object->add(['key2' => 'Test2']);
        $this->assertEquals(
            [
                'key1' => 'Test1',
                'key2' => 'Test2'
            ],
            $this->object->process()
        );
    }

    public function testAddObjectAutomatic()
    {
        $obj1 = new \stdClass();
        $obj1->MyField = [ "teste1" => "value1", "test2" => [ "3", "4"]];
        $obj1->OtherField = "OK";

        $obj2 = new ModelSample('value3', 'value4');

        $this->object->add($obj1);
        $this->assertEquals(
            [
                'MyField' => [ "teste1" => "value1", "test2" => [ "3", "4"]],
                'OtherField' => 'OK'
            ],
            $this->object->process()
        );

        $this->object->add($obj2);
        $this->assertEquals(
            [
                [
                    'MyField' => [ "teste1" => "value1", "test2" => [ "3", "4"]],
                    'OtherField' => 'OK'
                ],
                [
                    'prop1' => 'value3',
                    'prop2' => 'value4'
                ],
            ],
            $this->object->process()
        );
    }

    public function testAddObjectArray()
    {
        $obj1 = new \stdClass();
        $obj1->MyField = [ "teste1" => "value1", "test2" => [ "3", "4"]];
        $obj1->OtherField = "OK";

        $obj2 = new ModelSample('value3', 'value4');

        $this->object->setSerializationRule(ResponseBag::OBJECT_LIST);
        $this->object->add($obj1);
        $this->assertEquals(
            [
                [
                    'MyField' => [ "teste1" => "value1", "test2" => [ "3", "4"]],
                    'OtherField' => 'OK'
                ],
            ],
            $this->object->process()
        );

        $this->object->add($obj2);
        $this->assertEquals(
            [
                [
                    'MyField' => [ "teste1" => "value1", "test2" => [ "3", "4"]],
                    'OtherField' => 'OK'
                ],
                [
                    'prop1' => 'value3',
                    'prop2' => 'value4'
                ],
            ],
            $this->object->process()
        );
    }

    public function testAddObjectSingleObject()
    {
        $obj1 = new \stdClass();
        $obj1->MyField = [ "teste1" => "value1", "test2" => [ "3", "4"]];
        $obj1->OtherField = "OK";

        $obj2 = new ModelSample('value3', 'value4');

        $this->object->setSerializationRule(ResponseBag::SINGLE_OBJECT);
        $this->object->add($obj1);
        $this->assertEquals(
            [
                'MyField' => [ "teste1" => "value1", "test2" => [ "3", "4"]],
                'OtherField' => 'OK'
            ],
            $this->object->process()
        );

        $this->object->add($obj2);
        $this->assertEquals(
            [
                [
                    'MyField' => [ "teste1" => "value1", "test2" => [ "3", "4"]],
                    'OtherField' => 'OK',
                ],
                [
                    'prop1' => 'value3',
                    'prop2' => 'value4'
                ]
            ],
            $this->object->process()
        );
    }


    public function testRaw()
    {
        $this->object->setSerializationRule(ResponseBag::RAW);
        $this->object->add('Test1');
        $this->assertEquals(
            'Test1',
            $this->object->process()
        );

        $this->object->add('Test2');
        $this->assertEquals(
            'Test1Test2',
            $this->object->process()
        );
    }

    public function testRawInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->object->setSerializationRule(ResponseBag::RAW);
        $this->object->add(['Test1']);
    }
}
