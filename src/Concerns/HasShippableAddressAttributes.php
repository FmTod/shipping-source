<?php

namespace FmTod\Shipping\Concerns;

trait HasShippableAddressAttributes
{

    /**
     * Get an address attribute from the object using the trait.
     *
     * @param string $attribute
     * @return mixed
     */
    protected function getAttributeForShippingAddress(string $attribute): mixed
    {
        return $this->getAttribute($attribute);
    }

    /**
     * Get the first name to which the package is addressed to.
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->getAttributeForShippingAddress('first_name');
    }

    /**
     * Get the last name to which the package is addressed to.
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->getAttributeForShippingAddress('last_name');
    }

    /**
     * Get the full name to which the package is addressed to.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->getAttributeForShippingAddress('full_name');
    }

    /**
     * Get the company to which the package is addressed to if any.
     *
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->getAttributeForShippingAddress('company_name');
    }

    /**
     * Get email address for the shipment.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->getAttributeForShippingAddress('email');
    }

    /**
     * Get phone number for the shipment.
     *
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->getAttributeForShippingAddress('phone_number');
    }

    /**
     * Get line 1 of the address, usually used for street.
     *
     * @return string
     */
    public function getStreetAddress1(): string
    {
        return $this->getAttributeForShippingAddress('street_address1');
    }

    /**
     * Get line 2 of the address, usually used for apartment/suite number.
     *
     * @return string|null
     */
    public function getStreetAddress2(): ?string
    {
        return $this->getAttributeForShippingAddress('street_address2');
    }

    /**
     * Get the address' city
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->getAttributeForShippingAddress('city');
    }

    /**
     * Get the address' state (abbreviated if inside US or CA).
     *
     * @return ?string
     */
    public function getState(): ?string
    {
        return $this->getAttributeForShippingAddress('state');
    }

    /**
     * Get address' postal code
     *
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->getAttributeForShippingAddress('postal_code');
    }

    /**
     * Get address country code as 2-digit alphanumeric code.
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->getAttributeForShippingAddress('country_code');
    }

    /**
     * Get whether the address is a residential address.
     *
     * @return bool
     */
    public function getIsResidential(): bool
    {
        return $this->getAttributeForShippingAddress('is_residential');
    }
}
