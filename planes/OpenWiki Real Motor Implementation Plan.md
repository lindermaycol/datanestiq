# OpenWiki Real Motor: Implementation Plan

The objective is to replace the non-existent `openwiki` CLI package with a real, custom Node.js script that implements a dual-provider load balancer (Groq and DashScope) to generate and sync live documentation into the Astro `openwiki` collection.

## User Review Required
> [!IMPORTANT]
> The GitHub Secrets for `GROQ_API_KEY` and `DASHSCOPE_API_KEY` must be manually configured in your GitHub repository settings. The workflow relies on these to function. I will add placeholders in `.env.example` but you must configure them in the repository.

## Open Questions
> [!TIP]
> What specific directory paths should `docs-generator.mjs` monitor by default when executed without arguments? I plan to monitor `specs/` and `src/data/` for now, but let me know if you want to include others (e.g. `src/components/`).

## Proposed Changes

### Configuration and Environment
#### [MODIFY] .env.example
- Add variables: `GROQ_API_KEY`, `GROQ_MODEL`, `DASHSCOPE_API_KEY`, `DASHSCOPE_URL`, `DASHSCOPE_MODEL`, `LLM_WEIGHT_GROQ`, `LLM_WEIGHT_DASHSCOPE`.

### OpenWiki Sync Script
#### [NEW] scripts/docs-generator.mjs
- A pure Node.js (ESM) script using the native `fetch` API.
- Implements `callOpenAICompatible(url, key, model, messages, maxTokens)` equivalent to the one in `chat.php`.
- Implements weighted round-robin load balancing and atomic failover logic.
- Extracts `<!-- OPENWIKI:IGNORE:START -->` blocks before sending to the LLM and restores them afterwards.
- Injects a strict system prompt demanding a JSON response that matches the Astro Zod schema (title, description, author, lastUpdated, tags, seoScore) and the actual markdown content.
- Supports `--dry-run` argument to simulate execution and output the routing logic.
- Checks API keys presence and fails gracefully without corrupting files.

#### [MODIFY] scripts/openwiki-local-sync.sh
- Update the shell script to invoke `node scripts/docs-generator.mjs --dry-run` instead of the fictional CLI.

### GitHub Actions Workflow
#### [MODIFY] .github/workflows/openwiki-audit.yml
- Remove `npm install -g openwiki`.
- Replace `openwiki --sync ...` with `node scripts/docs-generator.mjs`.
- Provide the environment variables mapping to GitHub Secrets (`GROQ_API_KEY`, `DASHSCOPE_API_KEY`).

### Astro Content Integration
#### [NEW] src/content/openwiki/arquitectura.md (Example doc)
- Create a real dummy document conforming to the Zod schema to replace `dummy.md` and prove it renders.
#### [DELETE] src/content/openwiki/dummy.md

## Verification Plan

### Automated Tests
- Run `node scripts/docs-generator.mjs --dry-run` with and without API keys set locally to verify fail-safe routing and lack of API usage.

### Manual Verification
- Execute `node scripts/docs-generator.mjs` locally with valid API keys to confirm files are generated and respect `IGNORE` blocks.
- Check stdout to verify that requests are distributed across Groq and DashScope.
- Intentionally set an invalid `GROQ_API_KEY` to trigger atomic failover to DashScope and observe the logs.
- Run `npm run build` to ensure Astro correctly parses the frontmatter via Zod and builds the site.
