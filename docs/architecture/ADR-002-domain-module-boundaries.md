# ADR-002: Domain Module Boundaries

**Status:** Proposed

## Context
Tras la adopción de Clean Architecture y Modular Monolith (ADR-001), es necesario definir los límites cohesivos (Bounded Contexts o Módulos lógicos) del dominio para SENTAI. El sistema debe gestionar aspectos operacionales logísticos (WMS) y generar transacciones financieras asociadas. Separar correctamente las responsabilidades desde el principio es vital para evitar el "Big Ball of Mud", aislando la logística física de la facturación y los datos maestros transversales. El diseño debe estar fundamentado en las reglas de negocio descritas en EVD-REQ-001.

## Proposed Decision

Se propone la siguiente partición en módulos, reglas de dependencia cruzada y trazabilidad a los requerimientos base:

### Module Map & Responsibilities

1. **Identity & Access**
   - Propósito: Seguridad, resolución de permisos.
   - Trazabilidad: FR-011, BR-010.
2. **Master Data**
   - Propósito: Catálogos transversales (Product, Customer, Location).
   - Trazabilidad: Catálogos requeridos por BR-002, FR-001, etc.
3. **Inventory / Warehouse Operations**
   - Propósito: Control de stock físico y sus movimientos, invariantes de cantidad.
   - Trazabilidad: FR-001, FR-002, UC-003, Invariante de Stock (BR).
4. **Commercial Orders**
   - Propósito: Ciclo de vida de órdenes del cliente.
   - Trazabilidad: UC-008.
5. **Fulfillment**
   - Propósito: Logística de preparación interna (Allocation, Picking, Packing).
   - Trazabilidad: UC-009, UC-010, BR-003, BR-004.
6. **Shipping / Dispatch**
   - Propósito: Salida y entrega final (Dispatch).
   - Trazabilidad: BR-005.
7. **Finance**
   - Propósito: Cuentas por cobrar/pagar y aplicación de pagos.
   - Trazabilidad: FR-009, BR-008, BR-009.
8. **Audit / Compliance**
   - Propósito: Registro histórico auditable transversal.
   - Trazabilidad: FR-019, AC-017.

### Dependency Rules

- **Independencia Operacional:** Ningún módulo de operaciones (Inventory, Fulfillment, Shipping) dependerá del módulo Finance. Finance puede observar los eventos de operaciones y Orders para generar obligaciones, pero la logística física nunca debe fallar porque falle la facturación.
- **Topología Jerárquica Lógica:**
  - `Master Data` y `Identity` forman la base (no dependen de otros).
  - `Commercial Orders` e `Inventory` dependen de `Master Data`.
  - `Fulfillment` depende de `Inventory` y `Commercial Orders`.
  - `Shipping` depende de `Fulfillment`.
  - `Finance` depende de `Commercial Orders` y `Master Data`.
- **Integración Asíncrona (Domain Events):** Los efectos cruzados donde no se necesite consistencia fuerte (transaccional inmediata) se resolverán con Domain Events asíncronos en-proceso.

## Trade-offs
- **Acoplamiento vs. Duplicidad:** Referenciar datos maestros desde otros módulos acopla dependencias lógicas, pero evita duplicar masivamente los catálogos en este MVP, reduciendo la complejidad inicial que exigiría Event Sourcing y replicación de datos en un contexto distribuido.
- **Transaccionalidad Cruzada:** El patrón de modular monolith sobre la misma base de datos física tentará a los desarrolladores a usar transacciones globales cruzadas, ignorando los límites de los módulos, lo cual requerirá disciplina férrea en Code Reviews.

## Alternatives Considered
1. **Un Módulo de "Logistics" Único:** Se consideró unir Inventory, Fulfillment y Shipping. Se rechazó porque mezclar invariantes estrictas físicas (Stock) con intenciones logísticas humanas (Picking/Packing) causa acoplamiento rígido de conceptos que evolucionan diferente.
2. **Finance totalmente agnóstico mediante eventos:** Rechazado temporalmente para simplificar la lectura inicial de clientes. Finance apuntará a Master Data de Customer, balanceando pureza con simplicidad práctica en el MVP.

## Unresolved Questions
1. **UNRES-001, UNRES-002, UNRES-003, UNRES-006:** Aún persisten necesidades no resueltas heredadas de la fase Requirements que impactarán eventualmente estos límites (Ej: origen de creación del ASN o la autorización sobre la consulta de Auditoría). Su resolución dictaminará cómo evolucionarán las interacciones entre los módulos mencionados.

## Traceability
Documento derivado y anclado estrictamente en:
- `EVD-REQ-001` (Requirements Domain Baseline)
- Lógica descrita en `EVD-ARCH-001` (Architecture Domain Model)
