# Europarcel Modal Architecture

## File Structure

### Core Files
- **`europarcel-modal-core.js`** - Main modal controller (show/hide, coordination)
- **`europarcel-map-handler.js`** - Leaflet map and marker management  
- **`europarcel-ui-components.js`** - UI rendering and user interactions
- **`europarcel-modal-utils.js`** - Helper functions and utilities
- **`locker-selector.js`** - Button integration and AJAX handling

### Styles
- **`europarcel-modal.css`** - All modal styles (extracted from JS)
- **`locker-delivery.css`** - Legacy styles (maintained for compatibility)

## Loading Order
```
1. Leaflet CSS/JS (external)
2. europarcel-modal.css
3. europarcel-modal-utils.js (no dependencies)
4. europarcel-map-handler.js (requires: leaflet-js)
5. europarcel-ui-components.js (requires: europarcel-modal-utils)
6. europarcel-modal-core.js (requires: map-handler, ui-components)
7. locker-selector.js (requires: jquery, modal-core)
```

## Component Responsibilities

### `EuroparcelModal` (Core)
- Modal lifecycle (show/hide)
- Component coordination
- State management

### `EuroparcelMapHandler` (Map)  
- Map initialization
- Marker management
- Pin rendering

### `EuroparcelUIComponents` (UI)
- Carrier filtering
- Locker list rendering
- Selection handling

### `EuroparcelUtils` (Utilities)
- Form integration
- Data formatting
- Helper functions

## WordPress Plugin Standards
- ✅ Modular architecture
- ✅ Single responsibility principle
- ✅ Proper dependency management
- ✅ Clean separation of concerns
- ✅ Maintainable code structure
