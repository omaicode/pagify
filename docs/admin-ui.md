# Pagify Admin UI Primitives - Team Conventions

**Last Updated:** March 2026  
**Status:** ✅ Canonical naming conventions locked and migrated

---

## Overview

This admin interface contains **9 reusable UI primitives** that standardize all Admin interface patterns across Pagify. All components follow **canonical prop naming conventions** with **backwards-compatible legacy aliases** for smooth migration.

---

## Component Inventory

| Component | Purpose | Key Props |
|-----------|---------|-----------|
| **UiButton** | Polymorphic button/link | `tag`, `tone`, `size`, `radius`, `fullWidth` |
| **UiCard** | Content container | `tag`, `padding` |
| **UiInput** | Polymorphic form control | `tag`, `modelValue`, `type`, `rows` |
| **UiField** | Form field label wrapper | `label`, `for`, `labelTone` |
| **UiAlert** | Status/feedback messages | `tone` (danger/warning/success/info) |
| **UiStatusBadge** | Inline status indicators | `tone` (neutral/success/warning/danger) |
| **UiPageHeader** | Page title/subtitle | `title`, `subtitle` |
| **UiTableShell** | Card-wrapped table | `tableClass`, `headClass`, `bodyClass` |
| **UiCrudActions** | Edit/Delete button group | `editLabel`, `deleteLabel` |

---

## Canonical vs Legacy Props

All primitives support **canonical prop names** (recommended) with **legacy aliases** (still valid):

| Canonical (Use This) | Legacy Alias (Still Works) | Purpose |
|----------------------|---------------------------|---------|
| `tag="section"` | `as="section"` | HTML element type |
| `tone="danger"` | `variant="danger"` | Semantic color/meaning |
| `radius="lg"` | `rounded="lg"` | Border radius |
| `full-width` | `block` | Full width display |
| `padding="none"` | `no-padding` | Padding control |
| `for="username"` | `for-id="username"` | Label association |
| `edit-label="Edit"` | `edit-text="Edit"` | Action button labels |
| `delete-label="Delete"` | `delete-text="Delete"` | Action button labels |
| `label-tone="muted"` | `label-class="..."` | Label text color |

---

## Migration Examples

### Before (Legacy Aliases)
```vue
<UiCard as="section" no-padding>
  <UiAlert variant="error">Error message</UiAlert>
  <UiButton as="a" variant="danger" rounded="lg" block>
    Delete
  </UiButton>
  <UiCrudActions
    :edit-text="t.edit"
    :delete-text="t.delete"
    @edit="handleEdit"
    @delete="handleDelete"
  />
</UiCard>
```

### After (Canonical Props)
```vue
<UiCard tag="section" padding="none">
  <UiAlert tone="danger">Error message</UiAlert>
  <UiButton tag="a" tone="danger" radius="lg" full-width>
    Delete
  </UiButton>
  <UiCrudActions
    :edit-label="t.edit"
    :delete-label="t.delete"
    @edit="handleEdit"
    @delete="handleDelete"
  />
</UiCard>
```

**Both versions work!** Canonical props are clearer and self-documenting.

---

## Convention Tokens

All valid prop values are defined in `ui-conventions.js`:

```javascript
import { UI_TAGS, UI_TONES, UI_BUTTON_SIZES, UI_BUTTON_RADII } from './ui-conventions'

// Valid values:
UI_TAGS // ['div', 'section', 'article', 'button', 'a', ...]
UI_TONES // ['primary', 'neutral', 'danger', 'success', 'warning', 'info']
UI_BUTTON_SIZES // ['xs', 'sm', 'md', 'lg']
UI_BUTTON_RADII // ['full', 'lg']
UI_CARD_PADDING // ['default', 'none']
UI_BADGE_TONES // ['neutral', 'success', 'warning', 'danger', 'info']
UI_INPUT_TAGS // ['input', 'textarea', 'select']
```

**Runtime validation** prevents typos:
```vue
<!-- ✅ Valid - renders correctly -->
<UiButton tone="danger" />

<!-- ❌ Invalid - Vue warning: "tone must be one of [primary, neutral, danger, ...]" -->
<UiButton tone="error" />
```

---

## Tone → Color Mapping

| Tone | Colors | Usage |
|------|--------|-------|
| `primary` | Purple (#1e1b4b, #5b21b6) | Default actions, brand elements |
| `neutral` | Gray/Slate | Secondary actions, cancel |
| `danger` | Rose (#be123c, #fda4af) | Delete, errors, destructive actions |
| `success` | Emerald (#059669, #10b981) | Confirmations, success states |
| `warning` | Amber (#d97706, #fbbf24) | Warnings, caution |
| `info` | Blue | Informational messages |
| `outline` | White bg + border | Ghost/minimal buttons |

---

## Best Practices

### ✅ DO
- Use **canonical props** in new code (`tag`, `tone`, `radius`, `fullWidth`)
- Import tokens from `ui-conventions.js` for custom validators
- Leverage `oneOf()` validator in new components
- Let computed resolvers handle alias normalization internally

### ❌ DON'T
- Don't mix canonical and alias for same prop (use one consistently)
- Don't hardcode invalid tone/tag values (trust the validators!)
- Don't create new aliases without updating `ui-conventions.js`

---

## Adding New Primitives

If you create a new UI primitive:

1. **Define canonical props** with validators:
   ```vue
   <script setup>
   import { UI_TONES, oneOf } from './ui-conventions'

   defineProps({
     tone: {
       type: String,
       default: 'primary',
       validator: oneOf(UI_TONES)
     }
   })
   </script>
   ```

2. **Add legacy alias support** (if needed):
   ```javascript
   const props = defineProps({
     tone: { type: String, default: 'primary', validator: oneOf(UI_TONES) },
     variant: { type: String, default: undefined } // Legacy alias
   })

   const resolvedTone = computed(() => props.tone ?? props.variant ?? 'primary')
   ```

3. **Update `ui-conventions.js`** if introducing new token arrays

4. **Document in this README** under Component Inventory

---

## Migration Status

| Page | Status | Notes |
|------|--------|-------|
| Login | ✅ Migrated | All canonical props |
| Dashboard | ✅ Migrated | All canonical props |
| Settings | ✅ Migrated | All canonical props |
| Tokens | ✅ Migrated | All canonical props |
| Profile | ✅ Migrated | All canonical props |
| Modules | ✅ Migrated | All canonical props |
| Updater | ✅ Migrated | All canonical props |
| Audit | ✅ Migrated | All canonical props |
| Permissions | ✅ Migrated | All canonical props |
| Admins | ✅ Migrated | All canonical props |
| AdminGroups | ✅ Migrated | All canonical props |

**All admin pages now use canonical props exclusively.** Legacy aliases remain supported for backwards compatibility in modules/extensions.

---

## Testing

**Build validation:**
```bash
cd themes/admin/default
npm run build
# ✓ built in 1.09s
```

**Runtime validation:**
- All prop validators active in development mode
- Invalid prop values trigger Vue warnings in console

---

## Questions?

- **See examples:** Browse `Pages/Admin/**/Index.vue` for real-world usage
- **Check conventions:** Review `ui-conventions.js` for token definitions
- **Inspect components:** Read JSDoc comments in each primitive's `<script setup>`

**Convention locked ✅** - Safe for team-wide adoption.
