# Changelog

All notable changes to `shipping` will be documented in this file.

## 0.7.8 - 2021-12-28

**Full Changelog**: https://github.com/FmTod/shipping-source/compare/0.7.7...0.7.8

## 0.7.7 - 2021-12-24

## What's Changed

- Updated laravel-money package to v8

**Full Changelog**: https://github.com/FmTod/shipping-source/compare/0.7.6...0.7.7

## 0.7.6 - 2021-12-16

**Full Changelog**: https://github.com/FmTod/shipping-source/compare/0.7.5...0.7.6

## 0.7.5 - 2021-11-30

### Changes

Update the construct method in for the Rate class so that it can be hydrated correctly from its array representation.

## 0.7.3 - 2021-11-29

### Changes

- Added delay in calls to Shippo to prevent request test failure due to throttling.

## 0.7.2 - 2021-11-24

## What's Changed

- Minor bug fixes

## 0.6.9 - 2021-11-24

Revert change where carrier name was appended to the beginning of the service name

## 0.6.8 - 2021-11-24

Small bug fix for cases where shippo is being initialized statically.

## 0.6.7 - 2021-11-24

- Allow shipping facade to be initialized statically and added a few more helper methods.
- Fix exception in ShippoTest.php caused by a parenthesis left behind from previous code.
- Added carrier at the beginning of every service name and normalized carrier names.

## 0.6.5 - 2021-11-24

Allow shipping providers to be called statically

## 0.6.0 - 2021-11-24

Simplify creation of shipments by just requiring consignor, consignee, and package

## 1.0.0 - 202X-XX-XX

- initial release
