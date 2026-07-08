# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Fatal `TypeError` in `proxyFetchAction()` when sending a request with an environment
  selected on a collection imported from a file (`spec_url` is `null` for such
  collections). A missing base URL now raises a clear error instead of crashing (#77.1).

## [1.2.0]

### Changed

- Consolidated all CSS into a single `apicollection.css` file and improved the response panel.
- Improved base URL handling in the API Tester to support the OpenAPI `servers` field,
  including base URLs with embedded query parameters.

### Fixed

- Endpoint sidebar scroll — replaced the `.sidebar` Webasyst class with a custom flex layout.
- File storage path now uses the protected directory instead of the public one.

[Unreleased]: https://github.com/Syrnik/webasyst-apicollection/compare/1.2.0...HEAD
[1.2.0]: https://github.com/Syrnik/webasyst-apicollection/releases/tag/1.2.0
