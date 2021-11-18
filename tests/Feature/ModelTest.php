<?php

use FmTod\Shipping\Exceptions\MassAssignmentException;
use FmTod\Shipping\Tests\stubs\ModelStub;
use Illuminate\Support\Collection;

test('Constructor', function () {
    $model = new ModelStub(['name' => 'john']);
    $this->assertEquals('john', $model->name);
});

test('AttributeManipulation', function () {
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

test('NewInstanceWithAttributes', function () {
    $model = new ModelStub();
    $instance = $model->newInstance(['name' => 'john']);

    $this->assertEquals('john', $instance->name);
});

test('Hidden', function () {
    $model = new ModelStub();
    $model->password = 'secret';

    $attributes = $model->attributesToArray();
    $this->assertFalse(isset($attributes['password']));
    $this->assertEquals(['password'], $model->getHidden());
});

test('Visible', function () {
    $model = new ModelStub();
    $model->setVisible(['name']);
    $model->name = 'John Doe';
    $model->city = 'Paris';

    $attributes = $model->attributesToArray();
    $this->assertEquals(['name' => 'John Doe'], $attributes);
});

test('ToArray', function () {
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

test('ToJson', function () {
    $model = new ModelStub();
    $model->name = 'john';
    $model->foo = 10;

    $object = new stdClass();
    $object->name = 'john';
    $object->foo = 10;

    $this->assertEquals(json_encode($object), $model->toJson());
    $this->assertEquals(json_encode($object), (string) $model);
});

test('Mutator', function () {
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

test('ToArrayUsesMutators', function () {
    $model = new ModelStub();
    $model->list_items = [1, 2, 3];
    $array = $model->toArray();

    $this->assertEquals([1, 2, 3], $array['list_items']);
});

test('Replicate', function () {
    $model = new ModelStub();
    $model->name = 'John Doe';
    $model->city = 'Paris';

    $clone = $model->replicate();
    $this->assertEquals($model, $clone);
    $this->assertEquals($model->name, $clone->name);
});

test('Appends', function () {
    $model = new ModelStub();
    $array = $model->toArray();
    $this->assertFalse(isset($array['test']));

    $model = new ModelStub();
    $model->setAppends(['test']);
    $array = $model->toArray();
    $this->assertTrue(isset($array['test']));
    $this->assertEquals('test', $array['test']);
});

test('ArrayAccess', function () {
    $model = new ModelStub();
    $model->name = 'John Doen';
    $model['city'] = 'Paris';

    $this->assertEquals($model->name, $model['name']);
    $this->assertEquals($model->city, $model['city']);
});

test('Serialize', function () {
    $model = new ModelStub();
    $model->name = 'john';
    $model->foo = 10;

    $serialized = serialize($model);
    $this->assertEquals($model, unserialize($serialized));
});

test('Casts', function () {
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

test('Guarded', function () {
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

test('GuardedCallback', function () {
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

test('TotallyGuarded', function () {
    $this->expectException(MassAssignmentException::class);

    $model = new ModelStub();
    $model->guard(['*']);
    $model->fillable([]);
    $model->fill(['name' => 'John Doe']);
});

test('Fillable', function () {
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

test('Hydrate', function () {
    $models = ModelStub::hydrate([['name' => 'John Doe']]);
    $this->assertEquals('John Doe', $models[0]->name);
});

test('Validate', function () {
    $model = new ModelStub(['name' => 'bar']);
    $model->setRules(['name' => 'required|string']);
    $this->assertTrue($model->validate());

    $model->addRule('bar', 'required|string');
    $this->assertFalse($model->validate());
});
