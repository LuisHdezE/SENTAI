# SENTAI - API Authentication Contract

**Artifact ID:** EVD-API-003
**Blueprint Phase:** API Contract Design (C2)
**Status:** READY_FOR_REVIEW

---

## 1. Auth Contract Strategy

SENTAI define dos contratos de autenticación separados estructuralmente y semánticamente, reflejando ciclos de vida (lifecycles) diferentes entre clientes Web (browser-based) y clientes Mobile (operadores offline).

### 1.1 Web Auth Contract (Browser Sessions)
Las superficies Web (Backoffice y Customer Portal) emplean sesiones administradas en el backend (backend-managed browser sessions).

**Endpoints Contractuales:**
- `POST /api/v1/auth/web/login`: Establece la sesión en el servidor. El identificador de sesión es regenerado. El response retornará contexto seguro del principal autenticado si es requerido para el bootstrap del cliente, o HTTP 204 si no lo es.
- `POST /api/v1/auth/web/logout`: Invalida la sesión backend y purga las cookies del cliente.
- `GET /api/v1/auth/web/csrf`: Endpoint versionado propio cuya semántica contractual es proporcionar o preparar material CSRF. (Su implementación concreta queda diferida; no se prescribe Sanctum contractualmente).

**Reglas Web (Browser):**
- **Cookies:** Todo identificador de sesión se transporta exclusivamente en cookies `HttpOnly` y `Secure`.
- **SameSite:** El valor exacto se define según la topología final Web/API y la política de despliegue como deployment/security configuration.
- **CSRF:** Requerido para toda operación state-changing (POST, PUT, DELETE) web.
- **Expiración:** Idle expiration y absolute expiration administradas por el backend.
- **Storage:** No se exponen credenciales, identificadores de sesión ni secretos en `localStorage` o código JS.
- **Revocación:** La sesión Web es server-revocable.

### 1.2 Mobile Auth Contract (Tokens)
La superficie Mobile emplea credenciales de acceso de corta duración complementadas por sesiones de refresh rotativas.

**Endpoints Contractuales:**
- `POST /api/v1/auth/mobile/login`: Retorna un payload JSON con credencial de acceso (short-lived access credential) y material de refresh.
- `POST /api/v1/auth/mobile/refresh`: Recibe el material de refresh vigente, emite un nuevo par de credenciales (access + refresh). El material de refresh anterior queda inválido tras una rotación exitosa.
- `POST /api/v1/auth/mobile/logout`: La sesión móvil server-revocable queda terminada y el material de refresh deja de ser válido. Las credenciales posteriores deben ser rechazadas según el mecanismo de revocación elegido en implementación.

**Reglas Mobile:**
- **Almacenamiento:** Secure platform storage local.
- **Implementación Diferida:** Este contrato no prescribe JWT, algoritmos físicos criptográficos concretos, bibliotecas de storage, uso de Redis/denylists o el mecanismo físico de revocación.

---

## 2. Sync Re-Authorization Contract

La autenticación del envelope `/api/v1/sync` no reemplaza la autorización individual de sus operaciones.

Cada operación offline sincronizada debe individualmente:
1. Resolver/revalidar la identidad del actor.
2. Comprobar que la sesión no esté revocada.
3. Reautorizar la capability de la operación original.
4. Aplicar ownership/context policy (ej. cliente consultando pedidos propios).
5. Verificar idempotency.
6. Ejecutar su transaction boundary individual.
7. Registrar outcome/audit correspondiente.

---

## 3. Auth Error Contract

Los errores de autenticación y autorización se modelan de acuerdo al estándar RFC 9457 (EVD-ARCH-API-001).

- **401 Unauthorized (`authentication`):** Faltan credenciales, la sesión fue revocada o el token/sesión expiró.
- **403 Forbidden (`authorization`):** Identidad verificada, pero el actor carece de la capability requerida.
- **422 Unprocessable Entity (`validation`):** Problemas de formato en el payload de auth.

**Obligaciones:** 
- `correlation_id` obligatorio.
- Sin enumeración de cuentas.
- Sin stack traces, SQL errors, credentials o internal paths.

---

## 4. Auth Audit Events

Se registran los eventos auditables canónicos (según aplique):

- `auth.login.success` (Login Web o Mobile exitoso).
- `auth.login.failure` (Login fallido Web o Mobile).
- `auth.logout` (Cierre de sesión voluntario explícito).
- `auth.session.revoked` (Revocación administrativa o de session system).
- `auth.token.revoked` (Revocación específica de credenciales móviles/token, si aplica).
