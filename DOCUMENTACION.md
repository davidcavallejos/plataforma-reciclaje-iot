# Documentación Oficial del Proyecto: EcoRecicla IoT

**Plataforma Integral de Multi-Reciclaje, Jabón Saponificado y Telemetría IoT**

---

## 1. Visión General del Proyecto

**EcoRecicla IoT** es una plataforma web y PWA de economía circular orientada a la recolección, intercambio y valorización de materiales reciclables (plásticos, aluminio, papel/cartón, vidrio, electrónicos) y aceite de cocina usado para su transformación en jabón líquido o sólido mediante saponificación automatizada con telemetría IoT.

La plataforma conecta a tres actores clave:
1. **Recicladores Ciudadanos (`user`)**: Publican residuos, ofrecen o solicitan insumos, participan en el marketplace social y localizan centros de acopio.
2. **Centros de Acopio y Negocios (`business`)**: Registran sus instalaciones, muestran materiales aceptados y coordinan retiros en el mapa interactivo.
3. **Administradores del Sistema (`admin`)**: Supervisan las lecturas de las máquinas procesadoras IoT (ESP32), gestionan la base de usuarios (con baja segura mediante `SoftDeletes`) y moderan las publicaciones con borrado multimedia en la nube (Cloudinary).

---

## 2. Arquitectura de Tecnologías

| Capa | Tecnología Utilizada | Función Principal |
| :--- | :--- | :--- |
| **Framework Backend** | Laravel 11 (PHP 8.2+) | Rutas RESTful, Eloquent ORM, Autenticación, Middleware por Roles. |
| **Frontend Reactivo** | Livewire 3 + Alpine.js | Interfaz dinámica en tiempo real sin recarga de página. |
| **Estilos & UI** | Tailwind CSS (Tema Oscuro Eco) + FontAwesome | Diseño Glassmorphism moderno y adaptable (Mobile-First). |
| **Almacenamiento Cloud** | Cloudinary SDK / CloudinaryService | Gestión, optimización CDN y eliminación segura de imágenes. |
| **Mapas Interactivos** | Leaflet.js + OpenStreetMap API | Geolocalización de publicaciones y puntos de acopio. |
| **Telemetría IoT** | ESP32 + HTTP REST API | Recepción de sensores de aceite/temperatura y cálculo de saponificación. |
| **Progresive Web App** | Web Manifest (`manifest.json`) + Service Worker (`sw.js`) | Instalabilidad en Android/iOS y funcionamiento offline básico. |

---

## 3. Desglose de Hitos y Módulos Desarrollados

### Hito 1: Fundamentos, Base de Datos y Autenticación
- **Esquema de Base de Datos**: Migraciones completas para usuarios, publicaciones, comentarios, me gusta y negocios.
- **Autenticación Multi-Rol**: Soporte para roles `admin`, `user` y `business`.
- **Seeder Inicial**: Creación automática del usuario administrador (`admin@reciclaje.com`) y datos de prueba.

### Hito 2: Integración Multimedia con Cloudinary
- **`CloudinaryService`**: Servicio encapsulado para subir imágenes a carpetas optimizadas en la nube (`plataforma_reciclaje/posts`), transformación automática a WebP/AutoQuality y fallback local.
- **Eliminación Segura (`deleteImage`)**: Función que remueve recursos en la nube mediante `cloudinary_public_id`.

### Hito 3: Marketplace Multi-Material y Mapa Ecológico
- **Marketplace Social (`MarketplaceFeed`)**: Feed con filtros por tipo de material, precio/donación, comentarios y me gusta.
- **Directorio de Negocios (`BusinessRegistry`)**: Registro de centros de acopio con geolocalización.
- **Mapa Interactivo Leaflet (`InteractiveMap`)**: Renderizado de marcadores dinámicos con popups interactivos.

### Hito 4: Telemetría IoT y Calculadora de Saponificación
- **API Rest IoT (`/api/v1/machine/telemetry`)**: Endpoint seguro con `Authorization: Bearer <token>` para microcontroladores ESP32.
- **Calculadora Química de Saponificación**: Algoritmo que calcula la masa de KOH/NaOH y agua requerida en base al volumen y calidad de aceite de cocina usado.
- **Panel de Control Máquina IoT (`MachineDashboard`)**: Monitorización en tiempo real de temperatura, nivel de tanque, agitación y estado de bomba.

### Hito 5: PWA e Integración de Administración General
- **Soporte PWA**: Manifest de aplicación instalable y Service Worker para almacenamiento en caché de assets estáticos.
- **Gestión de Usuarios (`UserManagement`)**:
  - Tabla paginada con búsqueda en tiempo real por nombre/correo.
  - Filtros por roles (`admin`, `user`, `business`).
  - Registro manual y actualización de datos o contraseñas.
  - Baja segura con **`SoftDeletes`** (preservando integridad de publicaciones y registros vinculados).
- **Moderación de Publicaciones (`PostManagement`)**:
  - Supervisión global de todas las publicaciones registradas en la plataforma.
  - Ocultación/Desactivación preventiva de publicaciones inadecuadas.
  - Edición administrativa de título, precio, categoría, texto e imagen.
  - Integración directa con **`CloudinaryService`** para purgar el almacenamiento en la nube al eliminar publicaciones.
- **Aislamiento de Rutas**: Grupo protegido bajo middleware `['auth', 'role:admin']` (`CheckRole.php`).

---

## 4. Estructura de Rutas de la Aplicación

```
/ (Público)                     -> App\Livewire\MarketplaceFeed
/mapa (Público)                 -> App\Livewire\InteractiveMap
/negocios (Público)             -> App\Livewire\BusinessRegistry

/login (Guest)                  -> App\Http\Controllers\AuthController@showLogin
/register (Guest)               -> App\Http\Controllers\AuthController@showRegister

/api/v1/machine/telemetry (API) -> App\Http\Controllers\Api\MachineApiController@storeTelemetry

/admin/* (Protegido: auth + role:admin)
  ├── /admin/maquina           -> App\Livewire\Admin\MachineDashboard
  ├── /admin/usuarios          -> App\Livewire\Admin\UserManagement
  └── /admin/publicaciones     -> App\Livewire\Admin\PostManagement
```

---

## 5. Esquema de Base de Datos (Modelos Principales)

### Tabla `users`
- `id` (PK)
- `name` (string)
- `email` (string, unique)
- `password` (hashed)
- `role` (enum: `admin`, `user`, `business`)
- `avatar_url` (string, nullable)
- `deleted_at` (timestamp, nullable) -> *SoftDeletes*

### Tabla `posts`
- `id` (PK)
- `user_id` (FK -> users)
- `business_id` (FK -> businesses, nullable)
- `title` (string)
- `description` (text, nullable)
- `category` (string) -> *Plástico PET, Vidrio, Cartón, Metal, Aceite Usado, Jabón*
- `quantity` (decimal)
- `price` (decimal)
- `is_donation` (boolean)
- `address` (string, nullable)
- `latitude` / `longitude` (decimal)
- `cloudinary_public_id` (string, nullable)
- `image_url` (string, nullable)
- `status` (string: `active`, `hidden`, `sold`)

### Tabla `businesses`
- `id` (PK)
- `user_id` (FK -> users)
- `name` (string)
- `description` (text)
- `phone` (string)
- `address` (string)
- `latitude` / `longitude` (decimal)
- `accepted_materials` (json/string)

---

## 6. Integración de Hardware IoT (ESP32)

### Ejemplo de Payload JSON enviado por el ESP32:
```json
POST /api/v1/machine/telemetry
Headers:
  Authorization: Bearer ecorecicla_iot_secret_token_2026
  Content-Type: application/json

{
  "machine_id": "ESP32_ECO_01",
  "oil_volume_liters": 4.5,
  "oil_quality_score": 85,
  "temperature_celsius": 42.5,
  "mixer_active": true,
  "pump_active": false
}
```

### Respuesta del Servidor:
```json
{
  "status": "success",
  "message": "Telemetría registrada y fórmula de saponificación calculada.",
  "saponification_recipe": {
    "target_base": "KOH (Jabón Líquido Ecológico)",
    "oil_liters": 4.5,
    "koh_required_grams": 945.0,
    "water_required_liters": 1.35,
    "estimated_reaction_minutes": 45
  }
}
```

---

## 7. Comandos de Verificación y Mantenimiento

- **Ejecutar Migraciones**:
  ```bash
  php artisan migrate
  ```
- **Ejecutar Pruebas Automatizadas (PHPUnit)**:
  ```bash
  php artisan test
  ```
- **Listar Rutas Administrativas Protegidas**:
  ```bash
  php artisan route:list --path=admin
  ```
