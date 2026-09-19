# ADR-002: Domain Module Boundaries

**Status:** Proposed

## Context
Tras la adopción de Clean Architecture y Modular Monolith (ADR-001), es necesario definir los límites cohesivos (Bounded Contexts o Módulos lógicos) del dominio para SENTAI. El sistema debe gestionar aspectos operacionales logísticos (WMS) y generar transacciones financieras asociadas. Separar correctamente las responsabilidades desde el principio es vital para evitar el "Big Ball of Mud", aislando la logística física de la facturación y los datos maestros transversales. El diseño debe estar fundamentado en las reglas de negocio descritas en EVD-REQ-001.

## Proposed Decision

Se propone la siguiente partición en módulos, reglas de dependencia cruzada y trazabilidad a los requerimientos base:

### Module Map & Responsibilities

1. **Identity & Access**
   - Propósito: Seguridad, resolución de permisos.
   - Trazabilidad: FR-022, NFR-001, UC-001.
2. **Master Data**
   - Propósito: Catálogos transversales (Product, Customer, Location).
   - Trazabilidad: FR-001, UC-002.
3. **Inventory / Warehouse Operations**
   - Propósito: Control de stock físico y sus movimientos, invariantes de cantidad.
   - Trazabilidad: FR-002, FR-003, FR-004, FR-005, UC-003, UC-004, UC-005, UC-006, BR-001, BR-003, BR-004, FR-019 (cuando corresponda a auditoría de cambios críticos).
4. **Commercial Orders**
   - Propósito: Ciclo de vida de órdenes del cliente.
   - Trazabilidad: FR-007, FR-008, UC-007, UC-008, BR-009.
5. **Fulfillment**
   - Propósito: Logística de preparación interna (Allocation, Picking, Packing).
   - Trazabilidad: FR-006, FR-009, FR-010, UC-009, UC-010, UC-011, BR-001, BR-002, BR-003, BR-004 (cuando corresponda).
6. **Shipping / Dispatch**
   - Propósito: Salida y entrega final (Dispatch).
   - Trazabilidad: FR-011, FR-012, UC-012, UC-013.
7. **Finance**
   - Propósito: Cuentas por cobrar/pagar y aplicación de pagos.
   - Trazabilidad: FR-013, FR-014, FR-015, FR-016, FR-017, FR-018, UC-014, UC-015, UC-016, UC-017, UC-018, BR-005, BR-006, BR-007, BR-008 (cuando corresponda), BR-009 (únicamente donde crédito comercial interactúe con Finance/Orders).
8. **Audit / Compliance**
   - Propósito: Registro histórico auditable transversal.
   - Trazabilidad: FR-019, NFR-003, NFR-012, AC-017.

### Dependency Rules

- **Independencia Operacional:** Ningún módulo de operaciones (Inventory, Fulfillment, Shipping) dependerá del módulo Finance.
- **Topología Jerárquica Lógica:**
  - `Master Data` y `Identity` forman la base (no dependen de otros).
  - `Commercial Orders` e `Inventory` dependen de `Master Data`.
  - `Fulfillment` depende de `Inventory` y `Commercial Orders`.
  - `Shipping` depende de `Fulfillment`.
  - `Finance` depende de `Commercial Orders` y `Master Data`.
- **Integración y Coordinación:** Los módulos pueden coordinarse mediante Application Contracts y, cuando sea apropiado, Domain Events internos. La semántica síncrona, deferred/after-commit o eventual se decidirá según las garantías de consistencia de cada caso de uso. No se usarán eventos para debilitar invariantes o postcondiciones obligatorias.

## Trade-offs
- **Acoplamiento vs. Duplicidad:** Referenciar datos maestros desde otros módulos acopla dependencias lógicas, pero evita duplicar masivamente los catálogos en este MVP, reduciendo la complejidad inicial.
- **Transaccionalidad Cruzada:** El patrón de modular monolith sobre la misma base de datos física tentará a los desarrolladores a usar transacciones globales cruzadas, ignorando los límites de los módulos, lo cual requerirá disciplina férrea en Code Reviews.

## Alternatives Considered
1. **Un Módulo de "Logistics" Único:** Se consideró unir Inventory, Fulfillment y Shipping. Se rechazó porque mezclar invariantes estrictas físicas (Stock) con intenciones logísticas humanas (Picking/Packing) causa acoplamiento rígido de conceptos que evolucionan diferente.
2. **Finance totalmente agnóstico mediante eventos:** Rechazado temporalmente para simplificar la lectura inicial de clientes. Finance apuntará a Master Data de Customer, balanceando pureza con simplicidad práctica en el MVP.

## Unresolved Questions
Se mantienen explicitamente documentados (y sin resolver en A1):
- **UNRES-001, UNRES-002, UNRES-003**: Impactarán interacciones futuras.
- **UNRES-004, UNRES-005**: No afectan directamente los module boundaries de A1 y se resolverán en la fase Interface Inventory.
- **UNRES-006**: Permanece pendiente para la fase A2 Security/Auth.

## Traceability
Documento derivado y anclado estrictamente en:
- `EVD-REQ-001` (Requirements Domain Baseline)
- Lógica descrita en `EVD-ARCH-001` (Architecture Domain Model)
