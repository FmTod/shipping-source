# Changelog

All notable changes to `shipping` will be documented in this file.

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
