# Modernize the application experience
Modernize the existing local HelpDeskAgent UI, retaining branch-first operational focus and existing workflows.
Branch: refactor/project-foundations. Delivery: integrated single feature branch, local work-unit commits only (existing user decision “todo en un rama”, memory202); PR slicing deferred, no size exception or publication. Initial forecast1,400 authored lines; current approximately2,700. Checks: Pest, Bun SSR, TypeScript, Vite.

## Specs
- S1: "vamos con el dashboard" — operational dashboard with the accepted proposal "resumen operativo del equipo: tickets por estado, sucursales online/offline y actividad reciente".
- S2: "si que lo llamativo es las sucursales" — give branch connectivity primary visual hierarchy; show recorded states honestly.
- S3: "moderniza toda la UX/UI" — modernize the existing application shell, branch screens, project/ticket workspaces, account/auth/team screens and entry page. Preserve routes, authorization, form payloads and existing workflows. Assumption: extend the slate/teal visual language across the existing stack without dependencies or publication.

- S4: "continua puliendo la UX/UI" — continue bounded polish of empty/loading/navigation/accessibility states without changing existing backend workflows.

## Tasks
- T1 [S1,S2] inline, implemented/verified: branch-first dashboard, 14 focused tests/132 assertions; independent verifier correction preserves missing-team403; no commit yet.
- T2 [S3] inline, implemented/verified: shared theme, branding and application navigation; root owns shared surfaces.
- T3 [S2,S3] delegated, implemented/verified: branch listing/detail UX, independent parallel behavior unit with disjoint surfaces.
- T4 [S3] delegated, implemented/verified: project/ticket workspace UX, independent parallel behavior unit with disjoint surfaces.
- T5 [S3] delegated, implemented/verified: account/auth/team/entry UX, independent parallel behavior unit with disjoint surfaces.
- T6 [S1-S3] inline plus independent verifier for shared contracts/security, pending: seam checks, scoped review consent and delivery decision.

- T7 [S4] inline, implemented/verified: honest shared pagination, empty-table feedback, keyboard skip navigation and error contrast.

## Log
- L1 Original user request: "vamos con el dashboard". Assistant proposal: "¿Lo enfocamos en un resumen operativo del equipo: tickets por estado, sucursales online/offline y actividad reciente?" User: "si que lo llamativo es las sucursales".
- L2 2026-10-28 User expanded scope verbatim: "moderniza toda la UX/UI".
- L3 Baseline5596a2c43f92dbf537f56678eaae96aff5fd06cd. Existing unrelated dirty backend/dependency/config/project-refactor files are protected. No remote access or publication.
- L4 Dashboard RED: missing overview fields (3 failures), frontend missing summary component; GREEN: focused14/132, suite189/800 before final missing-team regression, frontend7/36, TypeScript and build passed, targeted PHPStan0 errors. Independent baseline differential identified implicit Team binding changed missing-team403 to404; correction removes implicit binding and uses middleware-selected currentTeam, regression RED then GREEN; independent recheck output/data hashes match baseline.
- L5 Read-only map found23 screens with shared semantic tokens and layouts. Route helpers and team scoping must remain; no fabricated monitoring or new integrations. Visual-only work has no meaningful behavioral RED; add runnable SSR assertions for changed navigation/fields/empty states. Browser QA unavailable; never claim it.
- L6 Writer reason: three independent units launched together, each several files/several minutes and disjoint edit surfaces; root owns shared styles, primitives and git. Workers do not commit or run global formatters. Delivery-chain choice must be resolved before first modernization work-unit commit if authored changes exceed400 lines.

- L7 2026-10-28 User: "continua puliendo la UX/UI".
- L8 T2-T5 complete: unit SSR RED/GREEN plus seam20 frontend/155 assertions,190 backend/801, tsc0, build0, targeted PHPStan0. Independent verifier S1-S3 passes:121 security/account/ticket/project tests535 assertions; AST26 files handlers and credential attributes unchanged, Input SSR4 fixtures, team permission visibility equals baseline. Browser QA unavailable.
- L9 Only38 UX/dashboard files staged (2535 authored lines); prior unrelated dirty files remain unstaged. No commits/publication. Native staged preflight target524fcfa1d6b49b3c59e59c9fd5ba2bf784497cd3326f5d4657f5a9882838a2d4, no START yet; current polishing invalidates that preflight and requires fresh staged snapshot after checks. Managed assets synchronized through provider-owned sync after documented stop. Reconciled existing user single-branch decision from memory202: no additional delivery branch or PR is authorized; local reviewable commits remain authorized.
- L10 T7 RED4 -> GREEN4/17; final24 frontend/172,190 backend/801, tsc0, build0. Scoped independent S4 passes9/37 and8 actual-page SSR differential renders: pagination href/query params unchanged for branch/ticket populated/empty fixtures; disabled controls cannot navigate or submit; table fields preserved; skip target unique; destructive alert tokens visible. Browser focus and measured contrast not tested.
- L11 Parent removed unnecessary dashboard test Inertia mock after nonfatal DOM warning; final frontend24/172 has no mock warning. Shared error text must use destructive token (not white button text); destructive buttons use their own white text. Previous native preflight was never started; final candidate must be assessed per committed work-unit boundary.
- L12 Existing delivery decision verified in full memory202 and project-refactor.md L7: “todo en un rama”. Reuse refactor/project-foundations; PR slicing stays deferred, no oversized-PR consent implied. Dashboard is one cohesive local work unit above400 lines because data payload, primary UI and regressions belong together; do not omit tests or compress styling to hit an advisory count.
