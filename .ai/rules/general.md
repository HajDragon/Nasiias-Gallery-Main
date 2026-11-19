---
paths:
    - '**/*'
---

# General

## Branch and PR base is Dev, not master

Create every feature/task branch from Dev (local `Dev`, tracking `origin/Dev`) and target Dev when opening pull requests. Never branch from or target master; it only advances via releases. Keep local Dev fast-forwarded to origin/Dev before cutting a new branch.

Always run npm run check --fix && vp check --fix before each commit
Always run php Stan and pint before each commit

## Tests

comment before each test and make its role clear according to AAA convention.
Arrange: Set up the test environment.
Act: Execute the code to test.
Assert: Verify the results.

## Git Commit Behavior

- **Granular, Atomic Commits:** Never bundle unrelated changes into a single large commit at the end of a task. Create as many commits as necessary so that each commit represents one clean, self-contained logical change.
- **Commit Triggers:** Automatically stage and commit your changes immediately after completing any of the following:
    - Creating or modifying a data model, schema, or configuration file.
    - Implementing a standalone function, module, or API endpoint.
    - Refactoring existing code (keep refactors in separate commits from new features).
    - Adding or updating unit/integration tests.
- **Verification:** Before each commit, verify there are no syntax errors or broken imports introduced by that step.
- **Message Format:** Use Conventional Commits (e.g., `feat:`, `fix:`, `refactor:`, `test:`, `chore:`) with a concise summary of _why_ the change was made.
