# Shipping

## Installation

You can install the package via composer:

```bash
composer require fmtod/shipping
```

You can publish the config file with:
```bash
php artisan vendor:publish --tag="shipping-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="example-views"
```

## Usage

```php
$shipping = new FmTod\Shipping([
    'Shippo' => ['access_token' => 'abc123']
]);
echo $shipping->carriers();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [FmTod](https://github.com/FmTod)
- [Victor](https://github.com/viicslen)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
