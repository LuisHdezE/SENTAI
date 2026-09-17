---
name: Modern Industrial Logistics
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#3f4a3d'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#6f7a6c'
  outline-variant: '#becab9'
  surface-tint: '#006e20'
  primary: '#006e20'
  on-primary: '#ffffff'
  primary-container: '#53b559'
  on-primary-container: '#00420f'
  inverse-primary: '#79dc7b'
  secondary: '#565e74'
  on-secondary: '#ffffff'
  secondary-container: '#dae2fd'
  on-secondary-container: '#5c647a'
  tertiary: '#006398'
  on-tertiary: '#ffffff'
  tertiary-container: '#48a8ed'
  on-tertiary-container: '#003b5c'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#94f994'
  primary-fixed-dim: '#79dc7b'
  on-primary-fixed: '#002205'
  on-primary-fixed-variant: '#005316'
  secondary-fixed: '#dae2fd'
  secondary-fixed-dim: '#bec6e0'
  on-secondary-fixed: '#131b2e'
  on-secondary-fixed-variant: '#3f465c'
  tertiary-fixed: '#cce5ff'
  tertiary-fixed-dim: '#93ccff'
  on-tertiary-fixed: '#001d31'
  on-tertiary-fixed-variant: '#004b73'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
  canvas-bg: '#F8FAFC'
  surface-card: '#FFFFFF'
  border-subtle: '#E2E8F0'
  border-strong: '#CBD5E1'
  text-primary: '#0F172A'
  text-secondary: '#475569'
  text-muted: '#94A3B8'
  status-available: '#16A34A'
  status-reserved: '#2563EB'
  status-quarantine: '#D97706'
  status-damaged: '#DC2626'
  status-blocked: '#475569'
  status-expired: '#9333EA'
  status-sync-pending: '#F59E0B'
  status-sync-error: '#E11D48'
  accent-focus: '#53B559'
typography:
  display-hero:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.015em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 22px
    fontWeight: '700'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  title-sm:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  caption:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
  code-data:
    fontFamily: JetBrains Mono
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
    letterSpacing: -0.01em
  badge-status:
    fontFamily: JetBrains Mono
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.04em
  metric-stat:
    fontFamily: Inter
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-desktop: 1.5rem
  margin: 1rem
  margin-desktop: 1.5rem
  space-2xs: 0.125rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 0.75rem
  space-lg: 1rem
  space-xl: 1.5rem
  space-2xl: 2rem
  space-3xl: 3rem
---

## Brand & Style
The design system powers an enterprise-grade warehouse and supply chain execution suite spanning Backoffice Web, Customer Portal, and Rugged Operator Mobile interfaces. It targets logistics directors, warehouse operators, and commercial clients who require real-time velocity, absolute data integrity, and microsecond operational clarity.

The visual style is **Corporate / Modern** inflected with **Precision Industrial Minimalism**. It eliminates ornamental clutter in favor of high-density tabular rigor, crisp structural framing, and deliberate chromatic cues. The aesthetic projects authority, physical efficiency, and technical resilience—communicating that physical goods are securely tracked, allocated, and dispatched with zero margin for error. Visual balance is achieved by pairing high-contrast graphite typography against sterile slate canvases, reserving vibrant brand green strictly for operational truth, primary commitment actions, and positive state changes.

## Colors
The color architecture enforces a high-density, low-fatigue environment suitable for long operational shifts under warehouse fluorescent lighting or standard office monitors.

- **Primary (`#53B559`)**: Reserved for system confirmations, primary CTAs (`Confirm Pick`, `Dispatch Shipment`), active navigation markers, and positive states (`AVAILABLE`, `ON TIME`). It is intentionally kept isolated to preserve visual priority.
- **Secondary (`#0F172A`)**: Deep industrial slate-900. Anchors headers, primary numbers, metrics, and high-priority structural frames.
- **Tertiary (`#0284C7`)**: Precision cyan/sky. Powers system notifications, active allocations, dock schedule links, and informative callouts.
- **Neutral (`#64748B`)**: Balanced slate gray used for metadata labels, subtle separators, and inactive indicators.

### Operational Stock Status Logic
Stock states map to unambiguous functional hues across badges, row outlines, and shelf location markers:
- `AVAILABLE`: Forest Green (`#16A34A`) on light mint tint.
- `RESERVED`: Cobalt Blue (`#2563EB`) on soft ice tint.
- `QUARANTINE`: Deep Amber (`#D97706`) on light amber tint.
- `DAMAGED`: Signal Red (`#DC2626`) on soft rose tint.
- `BLOCKED`: Heavy Slate (`#475569`) on cool gray tint.
- `EXPIRED`: Deep Purple (`#9333EA`) on pale violet tint.

### Network & Sync States (Operator Mobile)
- `ONLINE`: Emerald green indicator dot.
- `OFFLINE`: Neutral slate slash icon with explicit warning banner.
- `SYNC PENDING`: Pulsing amber-500 sync icon.
- `SYNC ERROR`: High-visibility rose-600 block with immediate retry trigger.

## Typography
The system uses **Inter** for all prose, navigational labels, and data display, chosen for its tall x-height, neutral construction, and tabular figure support (`tnum`). 

For alphanumeric logistics codes (e.g., SKUs `SKU-100245`, lot numbers `LOT-2026-0916`, warehouse bin addresses `A-03-02-04`, and operational task IDs `PICK-000932`), the system switches strictly to **JetBrains Mono**. This eliminates character ambiguity between `0` and `O`, `1` and `I`, reducing warehouse picking errors and entry mistakes during scanning verification.

All table numeric cells use monospaced tabular figures to guarantee vertical column alignment across stock counts (`On Hand`, `Reserved`, `Available`) and financial balances (`Overdue`, `Total Due`).

## Layout & Spacing
The layout uses an 8px modular baseline system with a 4px sub-grid for dense technical controls.

### Desktop Shell (Backoffice & Portal, 1440px Baseline)
- **Persistent Sidebar**: Width fixed at `260px` (`68px` when collapsed to icon-only mode). Dark graphite surface (`#0F172A`) or ultra-light slate (`#F8FAFC`) with crisp right boundary border (`1px solid #E2E8F0`).
- **Top Utility Bar**: Height `56px`. Sticky header containing the dynamic Warehouse Selector (`Montevideo DC-01`), global SKU/Document search box (`380px` wide), system notification beacon, and user profile drawer.
- **Main Canvas**: Fluid width bounded to `1440px` min/standard desktop viewports, with `margin-desktop: 1.5rem` and `gutter-desktop: 1.5rem`.
- **Structural Workspaces**: Split panels for fulfillment planners and dock scheduling employ persistent 2-to-3 column layouts (e.g., dock slot timelines taking `65%` flex, right inspection inspector pane fixed at `35%`).

### Mobile Shell (Operator App, 390x844px Reference)
- Standard margins at `1rem`. Layout prioritizes thumb zones: high-frequency confirmation actions (`Scan Next`, `Confirm Pick`) are pinned to bottom sheets with minimum `52px` target touch heights.
- Dense lists convert into card stacks with explicit `space-md` gaps.

## Elevation & Depth
The design relies on **Tonal Layers** combined with **Low-Contrast Outlines** rather than heavy blurred drop shadows, maximizing perceived performance and screen contrast on industrial screens.

- **Level 0 (Canvas Base)**: `#F8FAFC`. The foundational backdrop for the Backoffice and mobile shells.
- **Level 1 (Cards, Workspaces, Tables)**: `#FFFFFF` surfaced over Level 0, bounded by `1px solid #E2E8F0`. No drop shadow in standard state.
- **Level 2 (Dropdowns, Popovers, Warehouse Selector Menu)**: `#FFFFFF` with `1px solid #CBD5E1` and shadow: `0 4px 12px -2px rgba(15, 23, 42, 0.08)`.
- **Level 3 (Modals, Overlays, Slide-in Drawers)**: `#FFFFFF` bounded by `#CBD5E1` with shadow: `0 12px 32px -4px rgba(15, 23, 42, 0.16)`. Background scrim uses `#0F172A` at `45%` opacity.
- **Interactive Drag-and-Drop (Shipment Dock Slots)**: During active pointer drag, shipment cards raise to Level 2 with a `2px solid #53B559` outline and `0 8px 20px -2px rgba(83, 181, 89, 0.25)` green-tinted shadow.

## Shapes
The design system employs **Soft (`1`)** roundedness. Precision supply-chain tooling requires compact geometry to maximize usable grid density:
- Default components (Inputs, Buttons, Segmented Controls, Table Cells): `0.25rem` (4px).
- Container cards, metric boards, and modal sheets: `0.5rem` (8px).
- Badges and status pills: `0.25rem` (4px) with uppercase text, rejecting round pill capsules to maintain technical authority.
- Action sheets and operator bottom sheets (Mobile): Top corners rounded to `0.75rem` (12px).

## Components

### Buttons
- **Primary Button**: Solid `#53B559` background with white text (`#FFFFFF`), `font-weight: 600`, height `36px` (`48px` on Mobile Operator). Hover: `#469E4C`. Active: `#3C8741`.
- **Secondary / Neutral Button**: White background, `1px solid #CBD5E1`, text `#0F172A`. Hover: `#F1F5F9`.
- **Destructive Button**: Crimson `#DC2626` background or ghost border with rose text for non-reversible operations (`Cancel ASN`, `Mark Damaged`).
- **Focus Rings**: `2px solid #53B559` offset by `2px`.

### Badges & Inventory Status Chips
Compact components styled with `JetBrains Mono` at `11px`, letter-spaced uppercase:
- `AVAILABLE`: Text `#15803D`, background `#DCFCE7`, border `1px solid #BBF7D0`.
- `RESERVED`: Text `#1D4ED8`, background `#DBEAFE`, border `1px solid #BFDBFE`.
- `QUARANTINE`: Text `#B45309`, background `#FEF3C7`, border `1px solid #FDE68A`.
- `DAMAGED`: Text `#B91C1C`, background `#FEE2E2`, border `1px solid #FECACA`.
- `BLOCKED`: Text `#334155`, background `#F1F5F9`, border `1px solid #CBD5E1`.
- `EXPIRED`: Text `#7E22CE`, background `#F3E8FF`, border `1px solid #E9D5FF`.

### Structured Data Tables (LIST View Template)
- Column headers: Height `36px`, background `#F8FAFC`, uppercase `#475569`, border bottom `1px solid #CBD5E1`.
- Row cells: Height `44px` (dense) to `52px` (standard), bottom border `1px solid #F1F5F9`. Hover state `#F8FAFC`.
- All monetary and quantity figures right-aligned with tabular numerals.
- Action column sticky on the far right with icon buttons (`View`, `Edit`, `More`).

### Metric Cards (KPI Tiles)
- White card (`#FFFFFF`), `1px solid #E2E8F0`, padding `1rem`.
- Header: 12px uppercase slate title with optional trend pill (`+4.2%` green or `-1.8%` red).
- Primary Value: `30px` bold slate-900 typography.
- Footer: Micro context (`e.g., 42 pallets pending allocation`).

### Form Inputs & Search
- Inputs have a height of `36px` (`44px` on mobile), background `#FFFFFF`, border `1px solid #CBD5E1`, text `#0F172A`. Placeholder: `#94A3B8`.
- Focus: Border shifts to `#53B559` with a subtle green tint shadow ring.

### Domain-Specific Components
- **Warehouse Selector (`Montevideo DC-01`)**: Pill-shaped container in the topbar with a green status dot (`#53B559`), building icon, warehouse code, and caret indicator. Clicking triggers a search-filtered dropdown of available logistics centers.
- **End-to-End Traceability Timeline (`WEB-INV-010`)**: Horizontal-to-vertical node rail depicting stages: `ASN → Receipt → Put-away → Location → Movements → Reservation → Picking → Packing → Shipment → Dispatch`. Active and completed nodes highlighted with solid green checkmarks; non-completed steps display neutral outlines.
- **Dock Slot Scheduler Cards (`WEB-SHP-002`)**: Compact draggable blocks with carrier code, estimated loading time, weight indicators, order count badges, and current dock state (`PLANNED`, `PACKING`, `READY`, `LOADING`, `DISPATCHED`).