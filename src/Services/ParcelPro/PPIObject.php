<?php

namespace FmTod\Shipping\Services\ParcelPro;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Serializable;

abstract class PPIObject implements Serializable, Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    protected array $data = [];

    /**
     * Initiate a new instance of the object.
     *
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if (! is_null($data)) {
            $attributes = array_intersect_key($data, $this->data);

            $this->data = array_replace($this->data, $attributes);
        }
    }

    /**
     * Get specified property.
     *
     * @param $name
     * @return mixed
     *
     * @throws \Throwable
     */
    public function __get($name)
    {
        throw_if(! isset($this->data[$name]), "Property [$name] does not exists.");

        return $this->data[$name];
    }

    /**
     * Set specified property.
     *
     * @param $name
     * @param $value
     *
     * @throws \Throwable
     */
    public function __set($name, $value)
    {
        throw_if(! isset($this->data[$name]), "Property [$name] does not exists.");

        $this->data[$name] = $value;
    }

    /**
     * Determine if property is set.
     *
     * @param $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Whether an offset exists.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * Offset to set.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @throws \Throwable
     */
    public function offsetSet($offset, $value): void
    {
        throw_if(! isset($this->data[$offset]), "Offset [$offset] does not exists.");

        $this->data[$offset] = $value;
    }

    /**
     * Offset to unset.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Return array representation of the object.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * String representation of object.
     *
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string The string representation of the object or null
     */
    public function serialize(): string
    {
        return $this->toJson();
    }

    /**
     * Constructs the object.
     *
     * @link https://php.net/manual/en/serializable.unserialize.php
     * @param string $data The string representation of the object.
     * @return void
     */
    public function unserialize($data): void
    {
        $this->data = json_decode($data, true);
    }
}
