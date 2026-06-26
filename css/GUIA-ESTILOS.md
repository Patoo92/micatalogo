# Guía de Estilos — miCatálogo

Framework: **Tabler 1.0.0** (Bootstrap 5.3 compatible)

## Colores

### Paleta principal (Tabler defaults)
| Rol | Variable CSS | Color | Hex |
|-----|-------------|-------|-----|
| Primario (acción principal) | `--tblr-primary` | Azul Tabler | `#206bc4` |
| Éxito (stock OK, pagado) | `--tblr-success` | Verde | `#2fb344` |
| Peligro (stock crítico, error) | `--tblr-danger` | Rojo | `#d63939` |
| Advertencia (pendiente) | `--tblr-warning` | Amarillo | `#f59f00` |
| Información (neutro) | `--tblr-info` | Cian | `#17a2b8` |
| Secundario (etiquetas, badges) | `--tblr-secondary` | Gris | `#667382` |

### Grises / Texto
| Uso | Hex |
|-----|-----|
| Texto principal | `#1e293b` |
| Texto secundario | `#64748b` |
| Texto muted | `#94a3b8` |
| Bordes / separadores | `#e2e8f0` |
| Fondo de página (admin) | `#f5f7fb` |
| Fondo de página (public) | `#f8fafc` |

### Dark mode (admin)
Se activa con `data-bs-theme="dark"` en `<html>`. Tabler maneja todos los colores automáticamente.

### Color de tienda (catálogo público)
Variable CSS: `--color-principal` — se define inline en cada página pública según `$tienda['color_tema']`.

## Tipografía
- **Font**: Inter (Google Fonts)
- `body { font-family: 'Inter', sans-serif; }`
- Tamaños: `h1`=`1.75rem`, `h2`=`1.4rem`, `h3`=`1.2rem`, `h4`=`1.1rem`

## Botones
| Clase | Cuándo usarlo |
|-------|--------------|
| `btn btn-primary` | Acción principal (guardar, enviar) |
| `btn btn-success` | Acción positiva (nuevo producto, activar) |
| `btn btn-danger` | Acción destructiva (eliminar, cancelar) |
| `btn btn-outline-secondary` | Acción secundaria (volver, cancelar) |
| `btn btn-outline-danger` | Botón de eliminar (outline) |
| `btn btn-icon` | Botón con icono + texto |
| `btn-sm` | Botón pequeño (tablas, acciones inline) |

## Cards
- Admin: `<div class="card">` sin clases extra
- Formularios: `<div class="card"><div class="card-body">`
- Tarjetas de producto en admin: `<div class="card p-3">`
- Sin glassmorphism, sin sombras excesivas

## Tablas
- `<table class="table table-vcenter align-middle">`
- `<thead><tr><th>...</th></tr></thead>`
- `<tbody><tr>...</tr></tbody>`

## Formularios
- `<label class="form-label">`
- `<input class="form-control">`
- `<select class="form-select">`
- `<textarea class="form-control">`
- Switch: `form-check form-switch`

## Badges / Stock
| Clase | Cuándo |
|-------|--------|
| `badge bg-success` | Stock normal |
| `badge bg-danger` | Stock crítico o agotado |
| `badge bg-secondary` | Categoría, etiqueta neutra |

## Layout admin
```
<body>
  <aside class="navbar navbar-vertical">...</aside>
  <div class="page-wrapper">
    <div class="container">
      <!-- contenido -->
    </div>
  </div>
</body>
```

## Reglas importantes
1. NO usar `!important` en clases de Tabler
2. NO sobrescribir `.btn`, `.card`, `.table` globalmente
3. NO usar glassmorphism (`backdrop-filter`, `rgba(255,255,255,0.x)` backgrounds)
4. Dark mode admin: `data-bs-theme="dark"` en `<html>`, **no** clases en `<body>`
5. Dark mode público: clase `public-dark-mode` en `<body>`, CSS inline en cada página
6. Los iconos usan `<iconify-icon icon="mdi:...">` (no ocultos tras CSP)
7. `btn-icon` solo para botones con icono + texto, no para iconos solos
