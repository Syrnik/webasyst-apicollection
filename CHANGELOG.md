# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Fatal `TypeError` when sending a POST/PUT/PATCH request with an empty body. The proxy
  passed `null` as the request body, and `waNet::encodeRequest()` fed it to
  `http_build_query()`, which throws on PHP 8. The body now defaults to an empty string
  so requests with no payload go through (#77.3).

## [1.2.2]

### Fixed

- Fatal `TypeError` in the environment list when an environment's Base URL is empty
  (the backend stores `NULL` for an empty value). `truncate()` dereferenced the value
  unconditionally, so the environment manager dialog crashed and closed immediately
  when trying to edit such an environment (#77.2).

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

[Unreleased]: https://github.com/Syrnik/webasyst-apicollection/compare/1.2.2...HEAD
[1.2.2]: https://github.com/Syrnik/webasyst-apicollection/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/Syrnik/webasyst-apicollection/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/Syrnik/webasyst-apicollection/releases/tag/1.2.0
