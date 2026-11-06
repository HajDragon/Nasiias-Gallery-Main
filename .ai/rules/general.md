---
paths:
    - '**/*'
---

# General

## Branch and PR base is Dev, not master

Create every feature/task branch from Dev (local `Dev`, tracking `origin/Dev`) and target Dev when opening pull requests. Never branch from or target master; it only advances via releases. Keep local Dev fast-forwarded to origin/Dev before cutting a new branch.

Always run npm run check --fix && vp check --fix before each commit
ALways run php stan and pint before each commit
