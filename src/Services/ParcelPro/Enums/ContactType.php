<?php

namespace FmTod\Shipping\Services\ParcelPro\Enums;

use BenSampo\Enum\Enum;

final class ContactType extends Enum
{
    public const Null = 0;

    public const CorporateContact = 1;

    public const MailingContact = 2;

    public const Location = 3;

    public const APContact = 4;

    public const AddressBook = 11;

    public const BillingAddress = 12;
}
