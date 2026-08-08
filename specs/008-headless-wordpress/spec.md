# Spec 008: Headless WordPress Architecture (Backend CMS & Lead Management)

## 1. Executive Summary
The objective of this specification is to architect and transform the existing standard WordPress installation (`C:\xampp\htdocs\datanestiq`) into a robust, high-performance, and secure Headless CMS. In this ecosystem, WordPress will function exclusively as an isolated backend content repository and lead management database. The Astro frontend (Spec 006) will serve as the decoupled presentation layer, consuming data via APIs. This architecture guarantees maximum corporate security by eliminating the public attack surface of a traditional WordPress frontend, while providing a seamless, centralized administrative experience for the Datanestiq team.

## 2. Architectural Context & Dependency Graph
- **Solves Spec 002 Debt:** Eliminates the use of `LocalStorage` and replaces it with secure API calls (REST or GraphQL) for data persistence.
- **Extends Spec 006:** The Astro frontend now pushes write data (POST) towards the isolated backend.
- **Target Architecture (Headless):** Astro acts as the single public-facing entity communicating with WordPress through highly secure, server-to-server or authenticated API calls. WordPress processes incoming webhook payloads (Leads) and serves structured content to the Astro build pipeline and dynamic components.

## 3. User Scenarios & Testing (User Stories)

### US-01: Secure Lead Aggregation & Visualization
**As a** C-Level Executive at Datanestiq,
**I want to** log into the `/wp-admin` dashboard and view qualified leads in a private Custom Post Type,
**So that** I can manage prospect data securely without relying on insecure frontend storage.

*Acceptance Scenarios:*
- **Given** a prospect successfully completes the Astro Wizard and submits their data,
- **When** the C-Level executive navigates to the "Leads" menu in the WordPress admin panel,
- **Then** they should see a new Lead entry populated with Industry, Challenge, Role, Urgency, and Email fields.

### US-02: Public Interface Hardening
**As a** Cloud/DevOps Engineer,
**I want to** ensure that standard WordPress public routes are inaccessible to external users,
**So that** attackers cannot exploit traditional WordPress vulnerabilities or brute-force login pages.

*Acceptance Scenarios:*
- **Given** an unauthenticated external user or bot,
- **When** they attempt to access `datanestiq.com/wp-login.php` or `datanestiq.com/wp-admin` directly,
- **Then** the server should intercept the request and redirect them to the Astro frontend or return a 404/403 status code.

### US-03: Decoupled Content Publishing
**As a** Content Manager,
**I want to** publish case studies via the WordPress editor,
**So that** they are automatically fetched and rendered by the Astro frontend without touching frontend code.

*Acceptance Scenarios:*
- **Given** the Content Manager publishes a new Case Study in WordPress,
- **When** the Astro build process runs or a server-side request is made via WPGraphQL/REST,
- **Then** the new content is accurately displayed on the live Astro site with its respective formatting.

## 4. Functional Requirements (FRs)

### FR-01: Custom Data Structures (CPT & ACF)
- Create a private Custom Post Type (CPT) named `Leads` (`datanestiq_lead`).
- Implement Advanced Custom Fields (ACF) to structurally map the incoming payload from the Astro Wizard: `Industry`, `Primary Challenge`, `Role`, `Urgency/Severity`, and `Email Address`.

### FR-02: Secure Ingestion Webhook (REST API)
- Develop a custom WordPress REST API endpoint (e.g., `POST /wp-json/datanestiq/v1/leads`) designed to receive the JSON payload from the Astro Wizard.
- Implement robust server-side validation and sanitization before executing `wp_insert_post()` and `update_field()`.

### FR-03: Security & Infrastructure Configurations
- **CORS Validation:** Configure strict Cross-Origin Resource Sharing (CORS) headers in WordPress to only accept POST/GET API requests originating from the authorized Astro frontend domain.
- **Traffic Routing (`.htaccess`):** Configure the Apache `.htaccess` (and eventually Nginx/CDN) to block standard frontend rendering of WordPress and restrict `/wp-admin` access.
- **Authentication:** Secure read APIs (WPGraphQL) using Application Passwords or JWT.

## 5. Success Criteria & Metrics
- **Performance:** The API latency for ingesting a new Lead via the custom REST endpoint must be `< 200ms`.
- **Security:** Zero exposure of the WordPress PHP rendering engine to the public internet (100% of non-API traffic must return 404/403 or redirect).
- **Data Integrity:** 100% relational persistence of Leads captured by the Wizard (no data loss, fully overriding the old `LocalStorage` method).
- **Decoupling Validation:** The Astro frontend must be able to compile and render successfully even if the WordPress backend is temporarily put into maintenance mode.
