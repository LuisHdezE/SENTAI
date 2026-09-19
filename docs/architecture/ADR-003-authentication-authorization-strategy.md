# ADR 003: Authentication and Authorization Strategy

**Date:** 2026-09-19
**Status:** Accepted
**Artifact ID:** EVD-ARCH-ADR-003

## Context
SENTAI requires a robust security model to protect its authoritative boundary (the backend API), particularly given its multiple interfaces: Backoffice (Web), Customer Portal (Web), and Operator Mobile (Android/iOS). We need to establish an authentication and authorization strategy that aligns with Blueprint 0.5.4 constraints, our Modular Monolith architecture, and specific business rules regarding offline sync and strict capability isolation.

## Decision

### Authentication
We will implement distinct, platform-appropriate authentication mechanisms without substituting authorization:
- **Web (Backoffice & Customer Portal):** We will use secure, backend-managed HTTP sessions leveraging `HttpOnly` and `Secure` cookies with strict `SameSite` policies. We mandate CSRF protection for state-changing requests and explicitly forbid storing authentication secrets in browser `localStorage`. Session lifecycle mandates include: regenerating session identifiers after authentication/privilege changes, server-side invalidation on logout or revocation, and enforcing both idle and absolute expirations. Authorization is strictly evaluated against current server-side privileges, independent of stale client state.
- **Mobile (Operator Mobile):** We will use short-lived access credentials/tokens supported by server-revocable, rotating refresh sessions. Tokens will be stored in secure platform storage (Android Keystore/iOS Keychain).

### Authorization
We adopt a **capability-oriented authorization model**.
- **Role/Actor Mapping:** Actors (ACT-001 to ACT-007) map strictly to explicit capabilities (e.g., `warehouse.picking`, `finance.payment.apply`).
- **No Implicit Superusers:** The Administrator (ACT-007) does not bypass commercial or operational controls.
- **Customer Isolation:** Contextual policies enforce strict server-side ownership checks for Customer Portal data (Orders, Financials).
- **Offline Sync Revalidation:** The server remains the Single Source of Truth (SSOT). While offline work is provisionally authorized, all synchronized mutations must be re-authenticated and re-authorized against current server state upon reconnection to prevent revoked access from committing invalid work.
- **Audit Logging (UNRES-006):** We introduce `AuditViewer` as a dedicated security role with the `audit.global.read` capability, distinct from domain actors.

## Consequences
- **Positive:** Clear, testable security boundaries. Web clients are protected against XSS-based token theft. Mobile clients have a robust revocation mechanism. Global audit logs are protected from implicit internal snooping.
- **Negative:** Increased complexity in the mobile sync layer to handle deferred authorization rejections.
- **Constraints:** The authorization implementation must not rely on third-party frameworks that violate the existing A1 architectural decisions (e.g., no external caching via Redis).
