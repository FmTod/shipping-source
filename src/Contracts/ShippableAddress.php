<?php

namespace FmTod\Shipping\Contracts;

interface ShippableAddress
{
    /**
     * Get the first name to which the package is addressed to.
     *
     * @return string
     */
    public function getFirstName(): string;

    /**
     * Get the last name to which the package is addressed to.
     *
     * @return string
     */
    public function getLastName(): string;

    /**
     * Get the full name to which the package is addressed to.
     *
     * @return string
     */
    public function getFullName(): string;

    /**
     * Get the company to which the package is addressed to if any.
     *
     * @return string|null
     */
    public function getCompanyName(): ?string;

    /**
     * Get email address for the shipment.
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Get phone number for the shipment.
     *
     * @return string
     */
    public function getPhoneNumber(): string;

    /**
     * Get line 1 of the address, usually used for street.
     *
     * @return string
     */
    public function getStreetAddress1(): string;

    /**
     * Get line 2 of the address, usually used for apartment/suite number.
     *
     * @return string|null
     */
    public function getStreetAddress2(): ?string;

    /**
     * Get the address' city
     *
     * @return string
     */
    public function getCity(): String;

    /**
     * Get the address' state (abbreviated if inside US or CA).
     *
     * @return ?string
     */
    public function getState(): ?string;

    /**
     * Get address' postal code
     *
     * @return string|null
     */
    public function getPostalCode(): ?string;

    /**
     * Get address country code as 2-digit alphanumeric code.
     *
     * @return string
     */
    public function getCountryCode(): string;

    /**
     * Get whether the address is a residential address.
     *
     * @return bool
     */
    public function getIsResidential(): bool;
}
