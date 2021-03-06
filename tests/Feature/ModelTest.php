<?php

use FmTod\Shipping\Exceptions\MassAssignmentException;
use FmTod\Shipping\Tests\stubs\ModelStub;
use Illuminate\Support\Collection;

it('can be constructed', function () {
    $model = new ModelStub(['name' => 'john']);
    $this->assertEquals('john', $model->name);
});

it('can manipulate attributes', function () {
    $model = new ModelStub();
    $model->name = 'foo';

    $this->assertEquals('foo', $model->name);
    $this->assertTrue(isset($model->name));
    unset($model->name);
    $this->assertFalse(isset($model->name));

    $model['name'] = 'foo';
    $this->assertTrue(isset($model['name']));
    unset($model['name']);
    $this->assertFalse(isset($model['name']));
});

it('can create a new instance with attributes', function () {
    $model = new ModelStub();
    $instance = $model->newInstance(['name' => 'john']);

    $this->assertEquals('john', $instance->name);
});

it('can make attributes hidden', function () {
    $model = new ModelStub();
    $model->password = 'secret';

    $attributes = $model->attributesToArray();
    $this->assertFalse(isset($attributes['password']));
    $this->assertEquals(['password'], $model->getHidden());
});

it('can make attributes visible', function () {
    $model = new ModelStub();
    $model->setVisible(['name']);
    $model->name = 'John Doe';
    $model->city = 'Paris';

    $attributes = $model->attributesToArray();
    $this->assertEquals(['name' => 'John Doe'], $attributes);
});

it('can be converted to array', function () {
    $model = new ModelStub();
    $model->name = 'foo';
    $model->bar = null;
    $model->password = 'password1';
    $model->setHidden(['password']);
    $array = $model->toArray();

    $this->assertIsArray($array);
    $this->assertEquals('foo', $array['name']);
    $this->assertFalse(isset($array['password']));
    $this->assertEquals($array, $model->jsonSerialize());

    $model->addHidden(['name']);
    $model->addVisible('password');
    $array = $model->toArray();
    $this->assertIsArray($array);
    $this->assertFalse(isset($array['name']));
    $this->assertTrue(isset($array['password']));
});

it('can be converted to JSON', function () {
    $model = new ModelStub();
    $model->name = 'john';
    $model->foo = 10;

    $object = new stdClass();
    $object->name = 'john';
    $object->foo = 10;

    $this->assertEquals(json_encode($object), $model->toJson());
    $this->assertEquals(json_encode($object), (string) $model);
});

it('uses attribute mutators', function () {
    $model = new ModelStub();
    $model->list_items = ['name' => 'john'];
    $this->assertEquals(['name' => 'john'], $model->list_items);
    $attributes = $model->getAttributes();
    $this->assertEquals(json_encode(['name' => 'john']), $attributes['list_items']);

    $birthday = strtotime('245 months ago');

    $model = new ModelStub();
    $model->birthday = '245 months ago';

    $this->assertEquals(date('Y-m-d', $birthday), $model->birthday);
    $this->assertEquals(20, $model->age);
});

it('uses attribute mutators in array conversion', function () {
    $model = new ModelStub();
    $model->list_items = [1, 2, 3];
    $array = $model->toArray();

    $this->assertEquals([1, 2, 3], $array['list_items']);
});

it('can replicate instance', function () {
    $model = new ModelStub();
    $model->name = 'John Doe';
    $model->city = 'Paris';

    $clone = $model->replicate();
    $this->assertEquals($model, $clone);
    $this->assertEquals($model->name, $clone->name);
});

it('can make attributes appended', function () {
    $model = new ModelStub();
    $array = $model->toArray();
    $this->assertFalse(isset($array['test']));

    $model = new ModelStub();
    $model->setAppends(['test']);
    $array = $model->toArray();
    $this->assertTrue(isset($array['test']));
    $this->assertEquals('test', $array['test']);
});

it('can be accessed as array', function () {
    $model = new ModelStub();
    $model->name = 'John Doen';
    $model['city'] = 'Paris';

    $this->assertEquals($model->name, $model['name']);
    $this->assertEquals($model->city, $model['city']);
});

it('can be serialized', function () {
    $model = new ModelStub();
    $model->name = 'john';
    $model->foo = 10;

    $serialized = serialize($model);
    $this->assertEquals($model, unserialize($serialized));
});

it('can cast attributes', function () {
    $model = new ModelStub();
    $model->score = '0.34';
    $model->data = ['foo' => 'bar'];
    $model->count = 1;
    $model->object_data = ['foo' => 'bar'];
    $model->active = 'true';
    $model->default = 'bar';
    $model->collection_data = [['foo' => 'bar', 'baz' => 'bat']];

    $this->assertIsFloat($model->score);
    $this->assertIsArray($model->data);
    $this->assertIsBool($model->active);
    $this->assertIsInt($model->count);
    $this->assertEquals('bar', $model->default);
    $this->assertInstanceOf(stdClass::class, $model->object_data);
    $this->assertInstanceOf(Collection::class, $model->collection_data);

    $attributes = $model->getAttributes();
    $this->assertIsString($attributes['score']);
    $this->assertIsString($attributes['data']);
    $this->assertIsString($attributes['active']);
    $this->assertIsInt($attributes['count']);
    $this->assertIsString($attributes['default']);
    $this->assertIsString($attributes['object_data']);
    $this->assertIsString($attributes['collection_data']);

    $array = $model->toArray();
    $this->assertIsFloat($array['score']);
    $this->assertIsArray($array['data']);
    $this->assertIsBool($array['active']);
    $this->assertIsInt($array['count']);
    $this->assertEquals('bar', $array['default']);
    $this->assertInstanceOf(stdClass::class, $array['object_data']);
    $this->assertInstanceOf(Collection::class, $array['collection_data']);
});

it('can be guarded', function () {
    $model = new ModelStub(['secret' => 'foo']);
    $this->assertTrue($model->isGuarded('secret'));
    $this->assertNull($model->secret);
    $this->assertContains('secret', $model->getGuarded());

    $model->secret = 'bar';
    $this->assertEquals('bar', $model->secret);

    ModelStub::unguard();

    $this->assertTrue(ModelStub::isUnguarded());
    $model = new ModelStub(['secret' => 'foo']);
    $this->assertEquals('foo', $model->secret);

    ModelStub::reguard();
});

it('call callback when guarded', function () {
    ModelStub::unguard();
    $mock = $this->getMockBuilder('stdClass')
        ->setMethods(['callback'])
        ->getMock();
    $mock->expects($this->once())
        ->method('callback')
        ->willReturn('foo');
    $string = ModelStub::unguarded([$mock, 'callback']);
    $this->assertEquals('foo', $string);
    ModelStub::reguard();
});

it('can be totally guarded', function () {
    $this->expectException(MassAssignmentException::class);

    $model = new ModelStub();
    $model->guard(['*']);
    $model->fillable([]);
    $model->fill(['name' => 'John Doe']);
});

it('can be fillable', function () {
    $model = new ModelStub(['foo' => 'bar']);
    $this->assertFalse($model->isFillable('foo'));
    $this->assertNull($model->foo);
    $this->assertNotContains('foo', $model->getFillable());

    $model->foo = 'bar';
    $this->assertEquals('bar', $model->foo);

    $model = new ModelStub();
    $model->forceFill(['foo' => 'bar']);
    $this->assertEquals('bar', $model->foo);
});

it('can be hydrated', function () {
    $models = ModelStub::hydrate([['name' => 'John Doe']]);
    $this->assertEquals('John Doe', $models[0]->name);
});

it('can be validated', function () {
    $model = new ModelStub(['name' => 'bar']);
    $model->setRules(['name' => 'required|string']);
    $this->assertTrue($model->validate());

    $model->setRule('bar', 'required|string');
    $this->assertFalse($model->validate());
});
