# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

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
