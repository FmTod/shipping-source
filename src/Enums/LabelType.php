<?php

namespace FmTod\Shipping\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Url()
 * @method static static File()
 * @method static static Base64()
 */
class LabelType extends Enum
{
    public const Url = 'url';

    public const File = 'file';

    public const Base64 = 'base64';
}
