<?php

namespace FmTod\Shipping\Tests\stubs;

use DateTime;
use FmTod\Shipping\Models\Model;

class ModelStub extends Model
{
    protected array $hidden = ['password'];

    protected array $casts = [
        'age'   => 'integer',
        'score' => 'float',
        'data'  => 'array',
        'active' => 'bool',
        'secret' => 'string',
        'count' => 'int',
        'object_data' => 'object',
        'collection_data' => 'collection',
        'foo' => 'bar',
    ];

    protected array $guarded = [
        'secret',
    ];

    protected array $fillable = [
        'name',
        'city',
        'age',
        'score',
        'data',
        'active',
        'count',
        'object_data',
        'default',
        'collection_data',
    ];

    public function getListItemsAttribute($value)
    {
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    public function setListItemsAttribute($value): void
    {
        $this->attributes['list_items'] = json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function setBirthdayAttribute($value): void
    {
        $this->attributes['birthday'] = strtotime($value);
    }

    public function getBirthdayAttribute(): string
    {
        return date('Y-m-d', $this->attributes['birthday']);
    }

    public function getAgeAttribute(): int
    {
        $date = DateTime::createFromFormat('U', $this->attributes['birthday']);

        return $date->diff(new DateTime('now'))->y;
    }

    public function getTestAttribute(): string
    {
        return 'test';
    }
}
