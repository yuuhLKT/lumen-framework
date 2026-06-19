# Project Instructions

This project is a small PHP mini-framework/template for study, APIs, and backend challenges. Keep changes simple, explicit, and easy to understand.

## Core Rules

- Prefer the smallest correct change.
- Do not introduce runtime dependencies unless there is a clear need.
- Keep the framework lightweight: avoid adding framework-like abstractions unless they remove real duplication or clarify the code.
- Keep public behavior stable. Do not change existing business rules unless the task explicitly requires it.
- Do not rewrite existing tests unless the business rule changed. Add new tests instead.
- Restore or preserve developer ergonomics when possible, including CLI commands, Makefile flows, and helpers such as `dd()`.

## Testing And QA

- Every code change must include focused tests when behavior is added, fixed, or refactored.
- Always run `php lumen.php qa` before finishing a task.
- All existing and new tests must pass.
- The QA command runs lint, PHP CS Fixer dry-run, PHPStan, and PHPUnit.
- If QA cannot run in the current environment, explain exactly why and run the narrowest possible alternative.

## PHP Guidelines

- Use `declare(strict_types=1);` in PHP files.
- Follow the existing PSR-12 style and current file organization.
- Prefer native PHP and existing project classes over new packages.
- Validate external input at controller boundaries.
- Keep controllers thin; put rules in services and persistence in repositories.
- Prefer explicit return types and PHPDoc for array shapes where useful.

## Database Guidelines

- Keep the table abstraction simple: each table has an `id` and a `data` payload for SQL drivers.
- Preserve support for `json`, `sqlite`, `mysql`, `pgsql`, and the `postgres` alias.
- Migrations are PHP files and should be reversible when practical.

## Documentation

- The `docs/` directory documents each major part of the project. These files explain what each part is for, how it works, and include usage examples.
- Before changing a subsystem, check the related documentation in `docs/` to understand the intended behavior and examples.
- Update README and docs whenever commands, Makefile behavior, helpers, routes, or database behavior changes.
- Keep documentation in Portuguese to match the project docs.
- Avoid documenting removed or experimental APIs.

## Makefile And CLI

- Keep `make up` usable for both Docker and local workflows.
- Docker options must not require PHP installed on the host.
- Local options may require PHP installed on the host.
- CLI commands live under `app/Console/Commands` and must be registered in `lumen.php`.

## Git

- Write commit messages in English.
- Use Conventional Commits in English, for example `feat(generator): add optional auth scaffolding`.
