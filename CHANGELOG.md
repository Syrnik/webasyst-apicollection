# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.1]

### Added

- JetBrains Mono font for monospace text in the request/response panels.

### Changed

- Consolidated all CSS into a single `apicollection.css` file and improved the response panel.

### Fixed

- Correctly handle base URL with embedded query parameters in the request proxy.
- Endpoint sidebar scroll — replaced the `.sidebar` Webasyst class with a custom flex layout.
- Fatal `TypeError` in `proxyFetchAction()` when sending a request with an environment
  selected on a collection imported from a file (`spec_url` is `null` for such
  collections). A missing base URL now raises a clear error instead of crashing (#77.1).

### Security

- Bumped `vite` and transitive dev dependencies (`picomatch`, `brace-expansion`, `js-yaml`,
  `postcss`) to patch 5 Dependabot advisories (2 high, 3 moderate). Dev-only, no runtime impact.

## [1.2.0]

### Added

- Support for YAML-formatted OpenAPI specifications.

### Changed

- Improved base URL handling in the API Tester to support the OpenAPI `servers` field.

### Fixed

- Uploaded files are now stored in a protected directory instead of a public one.

[Unreleased]: https://github.com/Syrnik/webasyst-apicollection/compare/1.2.1...HEAD
[1.2.1]: https://github.com/Syrnik/webasyst-apicollection/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/Syrnik/webasyst-apicollection/releases/tag/1.2.0
