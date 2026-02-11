# Development Guide

Detailed documentation for developing with the Metis Bootstrap 5 Admin Template.

## Table of Contents

- [Getting Started](#getting-started)
- [Project Structure](#project-structure)
- [Architecture](#architecture)
- [Adding New Features](#adding-new-features)
- [Component Patterns](#component-patterns)
- [Sidebar & Responsive Layout](#sidebar--responsive-layout)
- [Styling Guide](#styling-guide)
- [Build Configuration](#build-configuration)

## Getting Started

### Prerequisites

- Node.js 18+
- npm or yarn

### Installation

```bash
npm install
npm run dev      # Start dev server at http://localhost:3000
npm run build    # Production build to dist-modern/
npm run preview  # Preview production build
npm run lint     # Run ESLint
npm run format   # Format with Prettier
```

## Project Structure

```text
src-modern/
├── *.html                      # Page templates (each requires data-page attribute)
├── scripts/
│   ├── main.js                 # Application entry point
│   ├── components/             # Page-specific components
│   │   ├── analytics.js
│   │   ├── calendar.js
│   │   ├── dashboard.js
│   │   ├── files.js
│   │   ├── forms.js
│   │   ├── help.js
│   │   ├── messages.js
│   │   ├── orders.js
│   │   ├── products.js
│   │   ├── reports.js
│   │   ├── security.js
│   │   ├── settings.js
│   │   ├── sidebar.js          # SidebarManager (desktop collapse + mobile overlay)
│   │   └── users.js
│   └── utils/
│       ├── theme-manager.js    # Dark/light mode handling
│       ├── notifications.js    # SweetAlert2 wrapper
│       └── icon-manager.js     # Icon library abstraction
├── styles/
│   └── scss/
│       ├── abstracts/          # Variables, mixins, functions
│       ├── components/         # UI component styles
│       ├── layout/             # Header, sidebar, footer
│       ├── pages/              # Page-specific styles
│       ├── themes/             # Theme variants
│       └── main.scss           # Main entry point
└── assets/                     # Static assets (images, icons)
```

## Architecture

### Application Bootstrap

The `AdminApp` class in `main.js` orchestrates initialization:

1. **Core Managers** - ThemeManager, NotificationManager, IconManager
2. **Bootstrap Components** - Dropdowns, modals, tooltips, popovers
3. **Page Detection** - Routes to correct component via `data-page` attribute
4. **Alpine.js** - Registers global data components and starts Alpine

### Page Detection Pattern

Each HTML page must have `data-page="pagename"` on the `<body>` tag:

```html
<body data-page="users" class="admin-layout">
```

This triggers the corresponding component loader in `main.js`:

```javascript
// main.js initPageComponents()
switch (currentPage) {
  case 'users':
    await import('./components/users.js');
    break;
  // ...
}
```

### SidebarManager

The `SidebarManager` class in `scripts/components/sidebar.js` is the single source of truth for all sidebar toggle behavior. It is initialized by `main.js` on every page and handles two distinct modes:

- **Desktop (>=992px):** Toggles between full-width (280px) and collapsed (70px) sidebar via the `sidebar-collapsed` class on `#admin-wrapper`. State is persisted in `localStorage`.
- **Mobile (<992px):** Opens the sidebar as a slide-in overlay with a backdrop. Supports closing via backdrop click, Escape key, and scroll-lock on the body.

**Key elements:**

| Selector | Role |
|----------|------|
| `#admin-wrapper` | Receives `sidebar-collapsed` class on desktop |
| `#admin-sidebar` | The sidebar element; receives `show` class on mobile |
| `[data-sidebar-toggle]` | The hamburger button that triggers `toggle()` |
| `.sidebar-backdrop` | Semi-transparent overlay behind mobile sidebar |

**Important:** Do not add inline `<script>` blocks that also listen for `[data-sidebar-toggle]` clicks. The `SidebarManager` is the only handler needed. Duplicate listeners will cancel each other out on desktop (both toggle the same class on the same click).

### Dynamic Imports

Page components are loaded asynchronously for code splitting:

```javascript
async initUsersPage() {
  try {
    await import('./components/users.js');
    console.log('👥 Users page script loaded successfully');
  } catch (error) {
    console.error('Failed to load users page script:', error);
  }
}
```

### Global Alpine.js Components

Defined in `main.js` and available on all pages:

| Component | Purpose |
|-----------|---------|
| `searchComponent` | Global search with results dropdown |
| `statsCounter` | Auto-incrementing stat displays |
| `themeSwitch` | Theme toggle state management |
| `iconDemo` | Icon provider switching demo |

## Adding New Features

### Adding a New Page

1. **Create HTML file** - `src-modern/newpage.html`

   ```html
   <body data-page="newpage" class="admin-layout">
   ```

2. **Add Vite entry point** - `vite.config.js`

   ```javascript
   rollupOptions: {
     input: {
       // ... existing entries
       newpage: resolve(..., 'src-modern/newpage.html'),
     }
   }
   ```

3. **Create page styles** - `src-modern/styles/scss/pages/_newpage.scss`

   ```scss
   // Page-specific styles
   .newpage-container {
     // ...
   }
   ```

4. **Import in main.scss**

   ```scss
   @import 'pages/newpage';
   ```

5. **Create component** - `src-modern/scripts/components/newpage.js`

   ```javascript
   import Alpine from 'alpinejs';

   document.addEventListener('alpine:init', () => {
     Alpine.data('newpageComponent', () => ({
       // state
       items: [],

       // lifecycle
       init() {
         console.log('Newpage component initialized');
       },

       // methods
       loadItems() {
         // ...
       }
     }));
   });
   ```

6. **Register in main.js**

   ```javascript
   async initNewpagePage() {
     try {
       await import('./components/newpage.js');
       console.log('📄 Newpage script loaded successfully');
     } catch (error) {
       console.error('Failed to load newpage script:', error);
     }
   }

   // Add to switch statement in initPageComponents()
   case 'newpage':
     await this.initNewpagePage();
     break;
   ```

### Creating Reusable Components

For components shared across pages, create in `utils/` and import where needed:

```javascript
// src-modern/scripts/utils/data-table.js
export class DataTable {
  constructor(element, options = {}) {
    this.element = element;
    this.options = { ...this.defaults, ...options };
  }

  defaults = {
    perPage: 10,
    sortable: true
  };

  render() {
    // ...
  }
}
```

## Component Patterns

### Alpine.js Data Component

```javascript
Alpine.data('componentName', () => ({
  // Reactive state
  isLoading: false,
  items: [],
  selectedItem: null,

  // Computed-like getters
  get filteredItems() {
    return this.items.filter(item => item.active);
  },

  // Lifecycle hook
  init() {
    this.loadItems();
  },

  // Methods
  async loadItems() {
    this.isLoading = true;
    try {
      // Simulated API call
      await new Promise(resolve => setTimeout(resolve, 500));
      this.items = [/* data */];
    } finally {
      this.isLoading = false;
    }
  },

  selectItem(item) {
    this.selectedItem = item;
  }
}));
```

### Using in HTML

```html
<div x-data="componentName">
  <template x-if="isLoading">
    <div class="spinner-border"></div>
  </template>

  <template x-for="item in filteredItems" :key="item.id">
    <div @click="selectItem(item)" x-text="item.name"></div>
  </template>
</div>
```

### Notifications

```javascript
// Using NotificationManager (available globally)
window.AdminApp.notificationManager.success('Item saved!');
window.AdminApp.notificationManager.error('Something went wrong');
window.AdminApp.notificationManager.warning('Please review your input');
window.AdminApp.notificationManager.info('Tip: You can drag items to reorder');

// Or use SweetAlert2 directly
Swal.fire({
  title: 'Confirm Delete',
  text: 'This action cannot be undone',
  icon: 'warning',
  showCancelButton: true,
  confirmButtonText: 'Delete',
  confirmButtonColor: '#dc3545'
}).then((result) => {
  if (result.isConfirmed) {
    // Delete logic
  }
});
```

### Charts with ApexCharts

```javascript
const chartOptions = {
  chart: {
    type: 'area',
    height: 350,
    toolbar: { show: false }
  },
  series: [{
    name: 'Revenue',
    data: [31, 40, 28, 51, 42, 109, 100]
  }],
  xaxis: {
    categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  },
  colors: ['#6366f1']
};

const chart = new ApexCharts(document.querySelector('#chart'), chartOptions);
chart.render();
```

## Sidebar & Responsive Layout

The template uses a consistent `lg` breakpoint (992px) across all responsive behavior. Below 992px the layout switches to a mobile-optimized mode.

### Breakpoint Reference

| Breakpoint | Sidebar | Header | Cards & Buttons |
|------------|---------|--------|-----------------|
| >= 992px (desktop) | Fixed left panel, collapsible to 70px mini sidebar | Full navbar with search bar | Standard sizing |
| < 992px (mobile) | Hidden off-screen, slides in as overlay | Compact navbar, hamburger in flow | Compact sizing with smaller padding |

### Desktop Sidebar Collapse

On desktop, clicking the hamburger toggles the `sidebar-collapsed` class on `#admin-wrapper`:

- **Expanded (default):** Sidebar is `var(--sidebar-width)` (280px) with icon + text labels
- **Collapsed:** Sidebar shrinks to `var(--sidebar-mini-width)` (70px) with icons only

The main content area shifts automatically via CSS:

```scss
// layout/_main.scss
.admin-main {
  margin-left: var(--sidebar-width);
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-collapsed .admin-main {
  margin-left: var(--sidebar-mini-width);
}
```

### Mobile Sidebar Overlay

On mobile, the sidebar is positioned off-screen with `transform: translateX(-100%)` and slides in when the `show` class is added:

```scss
// layout/_sidebar.scss
@media (max-width: 991.98px) {
  .admin-sidebar {
    transform: translateX(-100%);
    z-index: 1041; // above backdrop, below modals

    &.show {
      transform: translateX(0);
      box-shadow: 4px 0 16px rgba(0, 0, 0, 0.15);
    }
  }
}
```

The `.sidebar-backdrop` element provides a semi-transparent overlay behind the sidebar. It is created automatically by `SidebarManager` if not present in the HTML.

**Mobile sidebar behaviors:**
- Background scroll is locked (`overflow: hidden` on body) when sidebar is open
- Backdrop click closes the sidebar
- Escape key closes the sidebar
- Resizing from mobile to desktop cleans up overlay state and restores collapsed preference

### Hamburger Button Placement

The hamburger button (`[data-sidebar-toggle]`) lives inside the header navbar, immediately after the `.navbar-brand`. On desktop, it is absolutely positioned at the right edge of the sidebar:

```scss
// components/_hamburger.scss
@media (min-width: 992px) {
  .admin-header .hamburger-menu {
    position: absolute;
    left: calc(var(--sidebar-width) - 40px - 0.5rem);
    top: 50%;
    transform: translateY(-50%);
  }
}
```

On mobile, the hamburger sits in normal document flow within the navbar.

### Z-Index Layering

The template uses a deliberate z-index stack to avoid overlap conflicts:

| Layer | Z-Index | Element |
|-------|---------|---------|
| Header | 1030 | `.admin-header` (Bootstrap `$zindex-fixed`) |
| Sidebar (desktop) | 1035 | `.admin-sidebar` |
| Backdrop | 1040 | `.sidebar-backdrop` (`$zindex-modal-backdrop`) |
| Sidebar (mobile) | 1041 | `.admin-sidebar.show` |
| Modals | 1050+ | Bootstrap modals |

### Responsive Cards & Buttons

Cards and buttons use compact sizing on mobile for better touch usability:

```scss
@media (max-width: 991.98px) {
  .card {
    // Reduced padding and margins
  }

  .btn {
    // Smaller padding for touch targets
  }
}
```

These responsive adjustments are defined in `components/_cards.scss` and `components/_buttons.scss`.

## Styling Guide

### SCSS Variables

Located in `src-modern/styles/scss/abstracts/_variables.scss`:

```scss
// Brand Colors
$primary: #6366f1;
$secondary: #64748b;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$info: #3b82f6;

// Typography
$font-family-sans-serif: "Inter", system-ui, sans-serif;
$font-size-base: 0.9rem;

// Spacing & Layout
$border-radius: 0.75rem;
$box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
```

### Theme Support

Theme switching uses Bootstrap's `data-bs-theme` attribute:

```javascript
// ThemeManager toggles this
document.documentElement.setAttribute('data-bs-theme', 'dark');
```

Custom theme styles in `src-modern/styles/scss/themes/`:

```scss
[data-bs-theme="dark"] {
  --custom-bg: #1e1e2d;
  --custom-text: #a1a5b7;
}
```

### Component Styling Pattern

```scss
// _component-name.scss
.component-name {
  // Base styles
  padding: 1rem;
  border-radius: $border-radius;

  // Element
  &__header {
    font-weight: 600;
  }

  &__body {
    padding: 1rem 0;
  }

  // Modifier
  &--compact {
    padding: 0.5rem;
  }

  // State
  &.is-active {
    border-color: $primary;
  }
}
```

## Build Configuration

### Vite Config (`vite.config.js`)

Key settings:

```javascript
export default defineConfig({
  root: 'src-modern',
  build: {
    outDir: '../dist-modern',
    rollupOptions: {
      input: {
        // Multi-page entries
      }
    }
  },
  server: {
    port: 3000,
    open: true
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler'
      }
    }
  },
  resolve: {
    alias: {
      '@': resolve(..., 'src-modern'),
      '~bootstrap': resolve(..., 'node_modules/bootstrap')
    }
  }
});
```

### Path Aliases

Use in imports:

```javascript
import { something } from '@/scripts/utils/something.js';
```

```scss
@import '~bootstrap/scss/functions';
```

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

IE11 is not supported.
