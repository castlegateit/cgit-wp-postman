# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 3.4.1 (2025-04-15)

### Fixed

*   Fixed cURL requests made over non-secure connections.

## 3.4.0 (2025-03-03)

### Changed

*   Moved remote request methods to dedicated class.
*   Moved captcha and anti-spam methods to traits.
*   Separate properties for ReCaptcha 2 and ReCaptcha 3.

### Removed

*   Removed external ReCaptcha library to avoid issues with multiple Composer installations.

## 3.3.3 (2024-08-20)

### Added

*   Added minimum PHP version.

### Removed

*   Removed legacy plugin file constant.

## 3.3.2 (2024-07-24)

### Changed

*   Reworked Turnstile implementation to use cURL and vanilla PHP for greater PHP version compatibility.

### Removed

*   Removed unnecessary composer dependencies and Turnstile library.

## 3.3.1 (2024-05-23)

### Added

*   Added alias for Cloudflare Turnstile error key.

## 3.3.0 (2023-01-10)

### Added

*   Added support for Cloudflare Turnstile.

## 3.2.1 (2023-10-13)

### Fixed

*   Log downloads are listed in alphabetical order.
*   Log entries are sorted by submission date.

## 3.2.0 (2023-06-30)

### Added

*   Added support for ReCaptcha v3.
*   Added separate method to enable ReCaptcha v2 and ReCaptcha v3.

### Changed

*   Migrated to Composer autoloader.
*   Migrated `Postman` class to standard namespace. A copy of the class in the original namespace still exists for backward compatibility.
*   Migrated to standard Google ReCaptcha library instead of using bespoke code to make API calls.
*   Generic ReCaptcha methods are now deprecated aliases of dedicated ReCaptcha v2 methods for backward compatibility.

### Removed

*   Remove site key and secret methods from `Postman` class. Keys are now set exclusively by the `enableReCaptcha2` and `enableReCaptcha3` methods.

## 3.0.0 (2021-09-10)

*   ReCaptcha support has been completely rewritten and is no longer compatible with v2.x. Please review the documentation and adapt your code accordingly.
*   You can no longer replace the mailer, log spooler, and validation classes.

## 2.0.0 (2016-07-13)

*   Forms now require a unique identifier, set in the constructor. This makes submission detection automatic and allows accurate logging.
*   Mailer settings have been moved from individual properties, such as `mailTo` to a single property called `mailerSettings` that consists of an array of mailer settings.
*   The `detect()` method has been removed. Form identification is now automatic, based on the unique form ID set in the constructor.
*   The new `exclude` field option prevents fields appearing in email messages or log downloads.
*   You can now download log files from the WordPress admin panel.
*   You can now change or replace the mailer and validator classes.
