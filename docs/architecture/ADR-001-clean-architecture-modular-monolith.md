# ADR-001: Clean Architecture + Modular Monolith

**Status:** Accepted

## Context
El sistema SENTAI es una solución que requiere alta mantenibilidad, trazabilidad y adaptabilidad en el tiempo para la gestión logística (WMS) y financiera básica. La dirección tecnológica base define el uso de PHP y Laravel, pero acoplar la lógica de negocio directamente al framework presenta riesgos a largo plazo para la mantenibilidad y evolución del dominio. La decisión ya fue aprobada a nivel de proyecto (Product Truth).

## Decision
Se adoptará una aproximación combinada de **Clean Architecture** (para el desacoplamiento interno) y **Modular Monolith** (para la estructuración física y lógica a nivel macro).

### Layer Responsibilities (Clean Architecture)
- **Domain**: Contiene Entities, Value Objects, Domain Services y reglas de negocio puras. Es completamente independiente de Laravel, Eloquent, HTTP, bases de datos o UI.
- **Application**: Coordina los Casos de Uso (Orchestration), define los transaction boundaries conceptuales e interfaces (Ports). Gestiona la intención de autorización delegada desde Presentation, coordinando módulos cuando es necesario. No contiene detalles de Laravel/Eloquent.
- **Infrastructure**: Implementa los adapters concretos (MySQL persistence, repositories, external services, durable audit persistence y logging técnico). Aquí viven los detalles acoplados a frameworks.
- **Presentation**: Adapters de entrada (HTTP/API), integración del boundary de autenticación/sesión, request validation, mapping de requests y respuestas, y autorización.

### Module Isolation (Modular Monolith)
- El sistema se dividirá lógicamente en módulos funcionales de alto nivel con alta cohesión y bajo acoplamiento (Identity, Master Data, Inventory, Orders, Fulfillment, Shipping, Finance).
- Todo el código se desplegará como una unidad única (monolito) aprovechando la infraestructura simple, sin microservicios separados.

### Dependency Direction
- La regla de dependencia estricta es hacia adentro (Presentation -> Application -> Domain, Infrastructure -> Application -> Domain).
- Domain y Application **no conocen** a Infrastructure ni Presentation.
- Las dependencias entre módulos se realizarán a nivel de Application Contracts (Ports) o mediante Domain Events asíncronos en-memoria.

### Laravel Boundary
- Laravel es un detalle de implementación (Infrastructure/Presentation). Se usará su Dependency Injection Container para conectar las implementaciones de Infrastructure con los Ports de Application.
- Eloquent Models son modelos de persistencia, no Entities de Dominio. Se deberá realizar un mapeo explícito donde se justifique la separación.

## Consequences
- **Positivas**: Altísima testabilidad de la lógica de negocio; resiliencia a cambios tecnológicos o actualizaciones del framework; el dominio será un fiel reflejo de las reglas de negocio descritas en EVD-REQ-001.
- **Negativas**: Aumenta la complejidad técnica percibida y requiere mayor cantidad de código boilerplate (mapeos, DTOs, interfaces) frente al patrón Active Record por defecto de Laravel. Curva de aprendizaje para desarrolladores acostumbrados al Laravel tradicional.

## Rejected Alternatives
- **Laravel Standard (MVC/Active Record)**: Rechazado por acoplar fuertemente la lógica de dominio al ORM y framework, dificultando el aislamiento de reglas de negocio logísticas complejas.
- **Microservices**: Rechazado por introducir complejidad distribuida innecesaria, problemas de observabilidad, redespliegues complicados y latencia, no justificados por la escala técnica esperada del MVP.

## Implementation Conformance Expectations
Esta decisión es vinculante. Posteriormente, en la fase de implementación de API (o en cualquier etapa de validación de código), el control de calidad verificará que la implementación física de SENTAI respeta estos límites.
`api.architecture_implementation_conformance` deberá demostrar obligatoriamente que el código implementado cumple con este contrato arquitectónico.
