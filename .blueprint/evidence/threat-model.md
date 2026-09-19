# SENTAI - Threat Model

**Blueprint Phase:** Architecture & Security Data (A2)
**Status:** COMPLETE
**Artifact ID:** EVD-ARCH-THREAT-001
**Type:** architecture_threat_model

---

## Overview
This document outlines the threat model for the SENTAI architecture, addressing the risk profile across the Web Backoffice, Web Customer Portal, and Operator Mobile surfaces.

## Threat Records

| Asset / Boundary | Threat | Affected Surface | Mitigation Principle | Residual Consideration | Related Requirement |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Authentication System** | Credential attacks, Brute force, Credential stuffing | Web (BO/CP), Mobile | Enforce strong password policies, rate limiting, and account lockout mechanisms at the authentication boundary. | Dedicated credential stuffing networks might bypass simple IP rate limiting. | NFR-001 |
| **Session State** | Session or token theft | Web (BO/CP), Mobile | Web: `HttpOnly`, `Secure` cookies. No `localStorage`. Mobile: Secure enclave storage, short-lived tokens, rotating refresh tokens. | Device-level malware capturing cookies/tokens in memory. | FR-022, NFR-001 |
| **API Endpoints** | Broken Object-Level Authorization (IDOR) | Web (CP), Mobile, Web (BO) | Strict server-side ownership checks and contextual policies on all resource access. | Complex business logic might omit ownership checks on nested resources. | BR-005, NFR-001 |
| **Authorization Logic** | Horizontal Privilege Escalation | Web (CP) | A customer cannot access another customer's data. Server-side validation of customer IDs against session identity. | Shared/corporate accounts might require complex delegation. | ACT-001 |
| **Authorization Logic** | Vertical Privilege Escalation | Web (BO), Mobile | Capability-oriented authorization. Explicit checks for operations. No implicit superusers. | Misconfiguration of capabilities to roles. | NFR-001, FR-022 |
| **Web Sessions** | Cross-Site Request Forgery (CSRF) | Web (BO/CP) | CSRF tokens enforced for all state-changing requests, `SameSite` cookie policies. | CORS misconfigurations bypassing SameSite protections. | NFR-001 |
| **Mobile Sync API** | Replay of mobile/offline operations | Mobile, Backend Sync | Idempotency keys included in sync payloads and validated server-side. | Exhaustion of idempotency key storage if not garbage collected. | NFR-005, BR-008 |
| **Mobile Storage** | Tampering with queued offline operations | Mobile | Payload signing or strict server-side validation of synchronized data against invariants. | A compromised device can generate seemingly valid but fraudulent tasks. | NFR-007, BR-010 |
| **Mobile Device** | Stolen/lost mobile device risk | Mobile | Server-revocable refresh sessions. Access tokens are short-lived. | Window of vulnerability before revocation is processed. | NFR-001 |
| **Offline State** | Stale authorization during offline periods | Mobile, Backend Sync | Offline tasks are provisional. All synced tasks are reauthorized at the moment of sync against current server state. | Rejected syncs lead to complex conflict resolution UX. | FR-021, BR-010 |
| **Sync Logic** | Sync conflicts used to bypass invariants | Backend Sync | Server SSOT. The server strictly enforces invariants (e.g., `Reserved <= OnHand`) regardless of offline state. | Race conditions during concurrent syncs. | BR-001, NFR-002 |
| **Financial API** | Unauthorized financial operations | Web (BO) | Strict capability checks (`finance.payment.register`, `finance.payment.apply`) isolated to ACT-006. | Social engineering of finance personnel. | FR-014, FR-015 |
| **Audit Log** | Audit-event tampering | Backend | Audit logs are append-only. Immutable storage patterns for critical events. | Database-level admin access bypassing application controls. | NFR-003, FR-019 |
| **Logging System** | Sensitive data/secrets leaking to logs/audit | Backend | Scrubbing mechanisms in logger middleware. Explicitly exclude PII and credentials from serialization. | Third-party dependencies logging full request objects. | NFR-001 |
| **API Endpoints** | User/identifier enumeration | Backend, Web (CP/BO) | Generic error messages for failed logins, consistent response times for existence checks. | Timing attacks on computationally heavy endpoints. | NFR-001 |
| **Admin Panel** | Administrative permission abuse | Web (BO) | ACT-007 capabilities restricted to identity/master data. Explicitly deny commercial/warehouse capabilities to admins. | Admins resetting passwords of powerful users to gain access. | ACT-007 |
