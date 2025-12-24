# Contributing to Author Post Guard

Thank you for your interest in contributing! This document describes how to contribute to the project in a way that makes review and maintenance easier for everyone.

## Ways to contribute

- Report bugs by opening an **issue** with steps to reproduce and expected vs actual behavior.
- Suggest features by opening an **issue** describing the problem and proposed solution.
- Submit a **pull request (PR)** to fix issues or add features.

## Pull Request Guidelines

1. Fork the repository and create a branch named: `fix/short-description` or `feat/short-description`.
2. Keep PRs small and focused on a single change.
3. Add tests if applicable and ensure existing tests pass.
4. Provide a clear PR description linking related issues.
5. Follow code style and include inline comments for complex logic.

## Coding Standards

- PHP code should follow common WordPress PHP coding standards.
- Escape and sanitize user input. Use `esc_html`, `esc_url`, `sanitize_text_field`, etc., as appropriate.
- Nonce verification required for form and AJAX requests.

## Testing

- Run `php -l` to check PHP syntax.
- Run any available test suites (if present).

## Commit messages

- Use concise messages and prefix with `fix:`, `feat:`, `chore:`, or `docs:`.

## Communication

- Be respectful and constructive in discussions. If unsure, open an issue first to propose changes.

---

Thanks again — your contributions are welcome and appreciated! 🎉
