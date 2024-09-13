<?php

namespace Tests;

use ByJG\RestServer\ResponseBag;
use ByJG\RestServer\SerializationRuleEnum;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Model\ModelSample;

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

    public function testAddStringAutomatic(): void
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

    public function testAddStringArray(): void
    {
        $this->object->setSerializationRule(SerializationRuleEnum::ObjectList);
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

    public function testAddStringSingleObject(): void
    {
        $this->object->setSerializationRule(SerializationRuleEnum::SingleObject);
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

    public function testAddArrayAutomatic(): void
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

    public function testAddArrayArray(): void
    {
        $this->object->setSerializationRule(SerializationRuleEnum::ObjectList);
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

    public function testAddArraySingleObject(): void
    {
        $this->object->setSerializationRule(SerializationRuleEnum::SingleObject);
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

    public function testAddObjectAutomatic(): void
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

    public function testAddObjectArray(): void
    {
        $obj1 = new \stdClass();
        $obj1->MyField = [ "teste1" => "value1", "test2" => [ "3", "4"]];
        $obj1->OtherField = "OK";

        $obj2 = new ModelSample('value3', 'value4');

        $this->object->setSerializationRule(SerializationRuleEnum::ObjectList);
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

    public function testAddObjectSingleObject(): void
    {
        $obj1 = new \stdClass();
        $obj1->MyField = [ "teste1" => "value1", "test2" => [ "3", "4"]];
        $obj1->OtherField = "OK";

        $obj2 = new ModelSample('value3', 'value4');

        $this->object->setSerializationRule(SerializationRuleEnum::SingleObject);
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


    public function testRaw(): void
    {
        $this->object->setSerializationRule(SerializationRuleEnum::Raw);
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

    public function testRawInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->object->setSerializationRule(SerializationRuleEnum::Raw);
        $this->object->add(['Test1']);
    }
}
