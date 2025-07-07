# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2025-07-07

### Added
- `ToArrayInterface` and `ToArray` implementation for flattening objects to flat associative arrays

## [0.1.0] - 2025-07-07

### Added
- Initial release of Ray.InputQuery
- Core `InputQuery` class for transforming flat HTTP data into structured PHP objects
- `#[Input]` attribute for marking query-sourced parameters
- `#[InputFile]` attribute for file upload handling
- Automatic prefix-based nesting (e.g., `assigneeId`, `assigneeName` → `UserInput` object)
- Type-safe scalar conversion leveraging PHP's type system
- Full dependency injection integration with Ray.Di
- Support for arrays and ArrayObject collections
- Optional file upload functionality with `FileUploadFactory`
- `NullUploadFactory` for projects without file upload requirements
- Comprehensive documentation and examples
- 100% test coverage
- Framework integration guide for Laravel, Symfony, CakePHP, Yii, BEAR.Sunday, and Slim
- Demo applications including CSV processing and file upload examples

### Features
- **Declarative Input Handling**: Use attributes to define input structure
- **Automatic Type Conversion**: Converts string inputs to appropriate PHP types
- **Nested Object Support**: Automatically creates nested objects from flat data
- **DI Integration**: Seamlessly integrates with dependency injection containers
- **File Upload Support**: Optional integration with koriym/file-upload package
- **Framework Agnostic**: Works with any PHP framework or standalone

### Requirements
- PHP 8.1 or higher
- ray/di ^2.18
- symfony/polyfill-php83 ^1.28
- koriym/file-upload ^0.2.0 (optional, for file upload support)

[0.1.0]: https://github.com/ray-di/Ray.InputQuery/releases/tag/0.1.0
