<?php

use ByJG\RestServer\ResponseBag;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/ModelSample.php';

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class ResponseBagTest extends TestCase
{
    /**
     * @var ResponseBag
     */
    private $object;

    public function setUp()
    {
        $this->object = new ResponseBag();
    }

    public function tearDown()
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
        $this->object->serializationRule(ResponseBag::ARRAY);
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
        $this->object->serializationRule(ResponseBag::SINGLE_OBJECT);
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
        $this->object->serializationRule(ResponseBag::ARRAY);
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
        $this->object->serializationRule(ResponseBag::SINGLE_OBJECT);
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
        $obj1 = new stdClass();
        $obj1->key1 = 'value1';
        $obj1->key2 = 'value2';

        $obj2 = new ModelSample('value3', 'value4');

        $this->object->add($obj1);
        $this->assertEquals(
            [
                'key1' => 'value1',
                'key2' => 'value2'
            ],
            $this->object->process()
        );

        $this->object->add($obj2);
        $this->assertEquals(
            [
                [
                    'key1' => 'value1',
                    'key2' => 'value2'
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
        $obj1 = new stdClass();
        $obj1->key1 = 'value1';
        $obj1->key2 = 'value2';

        $obj2 = new ModelSample('value3', 'value4');

        $this->object->serializationRule(ResponseBag::ARRAY);
        $this->object->add($obj1);
        $this->assertEquals(
            [
                [
                    'key1' => 'value1',
                    'key2' => 'value2'
                ]
            ],
            $this->object->process()
        );

        $this->object->add($obj2);
        $this->assertEquals(
            [
                [
                    'key1' => 'value1',
                    'key2' => 'value2'
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
        $obj1 = new stdClass();
        $obj1->key1 = 'value1';
        $obj1->key2 = 'value2';

        $obj2 = new ModelSample('value3', 'value4');

        $this->object->serializationRule(ResponseBag::SINGLE_OBJECT);
        $this->object->add($obj1);
        $this->assertEquals(
            [
                'key1' => 'value1',
                'key2' => 'value2'
            ],
            $this->object->process()
        );

        $this->object->add($obj2);
        $this->assertEquals(
            [
                [
                    'key1' => 'value1',
                    'key2' => 'value2'
                ],
                [
                    'prop1' => 'value3',
                    'prop2' => 'value4'
                ],
            ],
            $this->object->process()
        );
    }
}
