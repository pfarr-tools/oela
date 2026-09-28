# SDD ledger — plan: docs/superpowers/plans/2026-09-25-docker-src-restructure.md
Pre-flight: Task 1 produces environment precedence and test path behavior consumed by Tasks 3-4; current App gives .env precedence, so implementation must change that.
Task 1: complete — explicit environment values now override .env; AppTest passes locally and in Docker.
Task 2: complete — sources are under src; Docker image and Compose mount validation passed.
Task 3: complete — root ./oela wrapper provides Docker, init, admin-links and isolated test commands.
Task 4: complete — PHP lint, Compose config, HTTP smoke test, init secret preservation and test isolation passed.
Final review: self-review (no subagent tool); no Critical or Important findings.
