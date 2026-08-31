# Icon Usage Guide

## Current Icon System: Material Symbols ✅

Your application currently uses **Google Material Symbols Rounded** for all icons. This is working perfectly and requires no changes.

### How to Use Material Symbols (Current Standard)

```html
<!-- Basic usage -->
<span class="material-symbols-rounded">home</span>
<span class="material-symbols-rounded">dashboard</span>
<span class="material-symbols-rounded">trending_up</span>
```

### Available Icons
Browse all 3,000+ icons at: https://fonts.google.com/icons

---

## Alternative: ng-icons with Lucide (Available for Future Use)

The `@ng-icons/lucide` package is now installed and available if you want to use Lucide icons in new features.

### Setup (One-time per Component/Module)

```typescript
// In your component file
import { Component } from '@angular/core';
import { NgIconComponent, provideIcons } from '@ng-icons/core';
import { 
  lucideHome, 
  lucideTrendingUp, 
  lucideBell 
} from '@ng-icons/lucide';

@Component({
  selector: 'app-my-component',
  standalone: true,
  imports: [NgIconComponent],
  providers: [
    provideIcons({ 
      lucideHome, 
      lucideTrendingUp, 
      lucideBell 
    })
  ],
  template: `
    <ng-icon name="lucideHome" size="24"></ng-icon>
    <ng-icon name="lucideTrendingUp" size="20"></ng-icon>
    <ng-icon name="lucideBell"></ng-icon>
  `
})
export class MyComponent {}
```

### Global Setup (Application-wide)

```typescript
// In main.ts or app.config.ts
import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideIcons } from '@ng-icons/core';
import { lucideHome, lucideTrendingUp, lucideBell, /* ... */ } from '@ng-icons/lucide';

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideRouter(routes),
    provideIcons({
      lucideHome,
      lucideTrendingUp,
      lucideBell,
      // Add all icons you want to use globally
    })
  ]
};
```

### Template Usage with ng-icons

```html
<!-- Basic icon -->
<ng-icon name="lucideHome"></ng-icon>

<!-- With size -->
<ng-icon name="lucideHome" size="24"></ng-icon>

<!-- With color (CSS) -->
<ng-icon name="lucideHome" style="color: red;"></ng-icon>

<!-- With CSS class -->
<ng-icon name="lucideHome" class="my-icon-class"></ng-icon>
```

### Available Lucide Icons
Browse all icons at: https://lucide.dev/icons

---

## Comparison: Material Symbols vs Lucide

| Feature | Material Symbols | Lucide (ng-icons) |
|---------|------------------|-------------------|
| **Installation** | CSS font (already loaded) | npm package (installed) |
| **Usage** | CSS class | Angular component |
| **Icon count** | 3,000+ | 1,400+ |
| **Bundle size** | External font | Tree-shakeable (only imports used) |
| **Style** | Rounded, sharp, outlined variants | Consistent stroke-based design |
| **Current status** | ✅ In use everywhere | Available for future use |

---

## Recommendation

**Continue using Material Symbols** for consistency across your existing codebase. Only use Lucide icons if:
- You need a specific icon not available in Material Symbols
- You're building a new feature with different design requirements
- You want to reduce font loading for performance (tree-shakeable icons)

---

## Icon Naming Convention Reference

### Material Symbols Examples
```
home, dashboard, trending_up, notifications, 
person_search, folder_open, bolt, campaign,
business_center, bar_chart, extension, settings
```

### Lucide Icon Names (ng-icons)
All Lucide icons are prefixed with `lucide` in PascalCase:
```typescript
lucideHome          // home
lucideTrendingUp    // trending-up
lucideBell          // bell
lucideSearch        // search
lucideSettings      // settings
lucideUser          // user
```

**Note:** Lucide uses hyphens in icon names, converted to PascalCase with `lucide` prefix:
- `trending-up` → `lucideTrendingUp`
- `arrow-right` → `lucideArrowRight`
- `file-text` → `lucideFileText`

---

## Migration Tips (If Needed in Future)

To gradually migrate from Material Symbols to Lucide:

1. **Add global providers** in `app.config.ts` with commonly used icons
2. **Create a helper component** for consistent icon rendering
3. **Replace icons incrementally** per feature/component
4. **Update design system documentation** with new icon usage

Example helper component:
```typescript
@Component({
  selector: 'app-icon',
  standalone: true,
  imports: [NgIconComponent],
  template: `<ng-icon [name]="name()" [size]="size()"></ng-icon>`
})
export class IconComponent {
  name = input.required<string>();
  size = input<string>('20');
}
```

---

**Updated:** August 31, 2026  
**Status:** Material Symbols in use, Lucide available as alternative
