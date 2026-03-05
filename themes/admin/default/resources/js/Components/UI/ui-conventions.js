/**
 * UI Conventions - Centralized token definitions for Pagify Admin UI primitives
 *
 * This file establishes standardized prop values across all UI components.
 * It provides runtime validation and ensures design consistency.
 *
 * **Canonical vs Alias Props**
 *
 * All UI primitives now use CANONICAL prop names with LEGACY ALIASES for backwards compatibility:
 *
 * - `tag` (canonical) ← `as` (alias): Component root HTML element
 * - `tone` (canonical) ← `variant` (alias): Semantic color/meaning (primary|neutral|danger|success|warning|info)
 * - `radius` (canonical) ← `rounded` (alias): Border radius preset
 * - `fullWidth` (canonical) ← `block` (alias): Full width display
 * - `padding` (canonical) ← `noPadding` (alias): Padding control
 * - `for` (canonical) ← `forId` (alias): Label for attribute
 * - `editLabel`/`deleteLabel` (canonical) ← `editText`/`deleteText` (alias): Action button labels
 *
 * **Usage Guidelines**
 *
 * 1. **Prefer canonical props** in new code for clarity and consistency
 * 2. **Legacy aliases remain supported** - old code continues working without changes
 * 3. **Prop validators** reject invalid token values at runtime
 * 4. **Computed resolvers** internally normalize aliases to canonical values
 *
 * **Example**
 *
 * ```vue
 * <!-- Canonical (recommended for new code) -->
 * <UiButton tag="a" tone="danger" radius="lg" full-width>Delete</UiButton>
 *
 * <!-- Legacy alias (still valid, backwards compatible) -->
 * <UiButton as="a" variant="danger" rounded="lg" block>Delete</UiButton>
 * ```
 *
 * @module ui-conventions
 * @since 1.0.0
 */

/**
 * Allowed HTML tag names for polymorphic components
 * @type {string[]}
 * @constant
 */
export const UI_TAGS = ['div', 'section', 'article', 'button', 'a', 'input', 'textarea', 'select', 'form', 'span']

/**
 * Semantic tone/variant values for buttons, alerts, badges
 * Maps to color schemes (primary=purple, danger=rose, success=emerald, etc.)
 * @type {string[]}
 * @constant
 */
export const UI_TONES = ['primary', 'neutral', 'danger', 'outline', 'success', 'warning', 'info']

/**
 * Button size presets
 * @type {string[]}
 * @constant
 */
export const UI_BUTTON_SIZES = ['xs', 'sm', 'md', 'lg']

/**
 * Border radius presets for buttons
 * @type {string[]}
 * @constant
 */
export const UI_BUTTON_RADII = ['full', 'lg']

/**
 * Card padding control values
 * @type {string[]}
 * @constant
 */
export const UI_CARD_PADDING = ['default', 'none']

/**
 * Badge tone/variant values (subset of UI_TONES for status indicators)
 * @type {string[]}
 * @constant
 */
export const UI_BADGE_TONES = ['neutral', 'success', 'warning', 'danger', 'info']

/**
 * Input component polymorphic tag values
 * @type {string[]}
 * @constant
 */
export const UI_INPUT_TAGS = ['input', 'textarea', 'select']

/**
 * Prop validator factory - creates runtime validation functions
 *
 * Returns a validator that checks if a prop value exists in allowed tokens.
 * Used in Vue prop definitions to prevent typos and enforce conventions.
 *
 * @param {Array} allowed - Array of valid token values
 * @returns {Function} Validator function that returns boolean
 *
 * @example
 * defineProps({
 *   tone: {
 *     type: String,
 *     default: 'primary',
 *     validator: oneOf(UI_TONES) // Rejects values not in UI_TONES
 *   }
 * })
 */
export const oneOf = (allowed) => (value) => allowed.includes(value)

