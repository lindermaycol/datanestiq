---
title: "Skills Overview"
description: "Consolidated overview of all AI-powered skills available in Datanestiq, grouped by domain and purpose — covering infrastructure automation (IONOS), and the f"
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["skills","automation","specification","infrastructure","security","ai-engineering"]
seoScore: 100
---
# 🧠 Datanestiq Skills Overview

Datanestiq provides a curated set of **domain-specialized, production-safe AI skills**, designed to automate high-cognitive-load engineering workflows while enforcing strict security, traceability, and quality guardrails.

---

## 🌐 Infrastructure Automation

### `ionos-deploy` — Secure SSH/SFTP Server Orchestration
- **Purpose**: Fully automated, auditable deployment to IONOS cloud servers.
- **Capabilities**: Deploy `dist/` + PHP backend, upload `.env`, run init/migration scripts, enforce file permissions, verify host keys.
- **Security-first design**:
  - 🔑 Credentials *only* from `.env` (gitignored); SSH key auth preferred; password fallback disabled by default.
  - 🛑 Hardcoded secrets blocked by `pre-commit`; `known_hosts` enforced; PII-sensitive paths (`secure_leads/`, `.git`, `node_modules`) explicitly forbidden.
  - 🧪 Dry-run mode enabled by default — real execution requires explicit opt-in.
- **Tooling**: Built on `paramiko`; scripts live in `scripts/deploy/` (e.g., `deploy_ionos.py`).

---

## 📜 spec-kit Lifecycle Suite
*A unified, constitution-governed framework for human-AI co-authoring of software specifications — where requirements are treated as first-class, testable artifacts.*

| Skill | Purpose | Key Differentiator |
|--------|---------|---------------------|
| **`speckit-specify`** | Generate or update `spec.md` from natural language | Turns fuzzy feature requests into precise, structured, versioned specs. |
| **`speckit-plan`** | Produce architecture/design artifacts (`plan.md`) | Bridges spec → implementation via intentional design decisions (not code). |
| **`speckit-tasks`** | Derive dependency-ordered `tasks.md` | Outputs executable, sequential, non-redundant engineering tasks. |
| **`speckit-taskstoissues`** | Convert tasks into GitHub issues | Auto-generates templated, labeled, linked issues with dependencies. |
| **`speckit-implement`** | Execute tasks in `tasks.md` (with hooks) | Orchestrates tooling, validation, and feedback loops — *not raw coding*. |
| **`speckit-converge`** | Audit code vs. spec & append missing work to `tasks.md` | Ensures spec-compliance drift is automatically captured and scheduled. |
| **`speckit-analyze`** | Cross-artifact consistency check (`spec.md` ↔ `plan.md` ↔ `tasks.md`) | Non-destructive quality gate: detects ambiguity, omission, or misalignment. |
| **`speckit-clarify`** | Identify underspecification & ask up to 5 targeted questions | Proactively surfaces hidden assumptions before implementation begins. |
| **`speckit-checklist`** | Generate unit tests *for the spec itself* | Validates spec quality — clarity, completeness, consistency, edge-case coverage. |
| **`speckit-constitution`** | Create/update project constitution & sync templates | Enforces team-agreed principles (e.g., "no hardcoded secrets") across all artifacts. |

### ✅ Universal Guarantees (All spec-kit Skills)
- **Project-aware**: Require `.specify/` directory and standard `spec.md`/`plan.md`/`tasks.md` layout.
- **Extension-ready**: Respect `.specify/extensions.yml` hooks (before each phase) — optional, conditional, and slash-command compatible.
- **No black-box logic**: All hooks defer condition evaluation to dedicated executors; no unsafe YAML eval.
- **Traceable & auditable**: Every skill outputs structured metadata, references its source template, and preserves provenance.

---

## 🧩 Design Philosophy

- **Requirements as Code**: `speckit-*` treats English specs like source code — tested (`checklist`), linted (`analyze`), clarified (`clarify`), and converged (`converge`).
- **Zero Trust Infrastructure**: `ionos-deploy` enforces constitutional security rules — no exceptions, no bypasses.
- **Human-in-the-Loop by Default**: Clarification, dry-runs, optional hooks, and checklist-driven spec QA ensure AI augments — never replaces — engineering judgment.

> 💡 **Bottom line**: Datanestiq skills don’t just *do work* — they enforce *how work should be done*, turning best practices into immutable, executable constraints.