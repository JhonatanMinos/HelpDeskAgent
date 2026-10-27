# Project refactor
Objective: incremental, evidence-driven refactoring across the application, without a speculative rewrite or stored-data changes.
Branch: `refactor/project-foundations`; delivery: integrated feature-branch strategy, local commits only; runners: Pest, Pint, PHPStan, TypeScript, Vite. Forecast: approximately 600 authored changed lines, excluding pre-existing edits. PR slicing is deferred until explicitly requested.

## Specs

- S1 — User request: “puedes refactorizar todo el proyecto”. Audit and refactor the existing project incrementally, then report verified improvements and remaining limitations.
- S2 — Prior authorized direction: “auditoria refactoriza y luego me das las mejoras”. Prioritize reproducible findings over cosmetic changes; keep recommendations distinct from completed changes.
- Assumptions — Preserve existing working-tree edits and externally observable behavior except verified defects; no remote operations, dependency upgrades, production migrations, or invented Telegram services. Unclear monitoring/Telegram behavior requires a product decision rather than guessing.

## Tasks

- T1 [S1,S2] inline — verified: restored deterministic database isolation and replaced manifest creation with `withoutVite()`; 3 focused tests and 111 full-suite tests pass; commit recorded below after creation.
- T2 [S1,S2] inline — pending: verify and repair remaining project/store/ticket team scoping with route-level regression tests; Risk: item 2; commit pending.
- T3 [S1,S2] inline — pending: align frontend response types, generic tables, route links, and Inertia page props with actual contracts; maintain UI semantics; commit pending.
- T4 [S1,S2] inline — pending: classify incomplete Telegram/monitoring behavior and provide explicit follow-up decisions; no speculative implementation; commit N/A unless verified mechanical fixes are safe.
- T5 [S1,S2] inline — pending: focused/full checks, independent verification for security changes, native review when applicable, and concise improvement report; commit pending.

## Log

- L1 — Original request: “puedes refactorizar todo el proyecto”.
- L2 — Existing edits preserved: KanbanController.php, KanbanRoutesTest.php, bun.lock, composer.json, composer.lock, vite.config.ts, .atl/, config/octane.php. Do not stage unrelated existing work.
- L3 — Baseline: previous Pest run had 26 passed, 1 failed, and 81 errors; `tests/Pest.php` disables RefreshDatabase. TestCase creates a Vite manifest in the worktree. TypeScript currently reports errors in branch pages, tables, navigation, project status values, and Inertia props.
- L4 — Route: parent implements understood units inline. Read-only mapping was delegated because project-wide understanding exceeded the inline evidence budget. A writer will be used only if independent, substantial disjoint units are launched together.
- L5 — Delivery forecast exceeds 400 lines. Before the first work-unit commit, obtain one choice of `stacked-to-main` or `feature-branch-chain`; never publish automatically.
- L6 — T1 proof: new TestEnvironmentTest initially produced 2 missing-table errors and 1 Vite output failure. After enabling RefreshDatabase and using withoutVite, 3 focused tests pass (5 assertions), and all 111 tests pass (371 assertions). Targeted Pint and git diff --check pass. Existing build artifacts are neither overwritten nor removed by the new setup. Rollback boundary: tests/Pest.php, tests/TestCase.php, tests/Feature/TestEnvironmentTest.php. Risk: none (test-only bootstrap, no application/database production changes).
- L7 — User delivery decision, verbatim: “todo en un rama”. Keep all work on refactor/project-foundations; no push, PR, merge, or extra delivery branch. This does not approve an oversized PR.
