# Stable list keys

Objective: address the ten Biome `noArrayIndexKey` findings without changing UI behavior.
Scope: the seven affected frontend components and focused tests; preserve unrelated work.
Delivery strategy: ask-on-risk; estimated under 400 authored changed lines; no publishing.
Mirror: pending when Engram is unavailable.

- [ ] T1 — delegated direct: eight of ten findings addressed; two remain because breadcrumb and recovery-code uniqueness is not established. Focused tests 2 passed, frontend suite 42 passed, TypeScript passed; targeted Biome fails on the two remaining findings. Independent verification found no correctness defect, but noted limited reconciliation coverage. Native assessment unavailable with undeclared untracked files; no review receipt or work-unit commit yet.

Acceptance: no unsafe invented uniqueness; fixed slots retain positional semantics; no unrelated edits.
