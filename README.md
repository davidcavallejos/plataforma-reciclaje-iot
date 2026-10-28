# Panel de control local — Máquina de jabón

Aplicación web local para operar y auditar una máquina automatizada de fabricación
de jabón, controlada por un ESP32 con cuatro módulos L298N.

**Institución:** Instituto Técnico Nacional de Comercio "Federico Álvarez Plata" (Nocturno)

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12 · PHP 8.2 |
| Base de datos | PostgreSQL 17 (relacional estricto, con FK y CHECK) |
| Frontend | Blade + JavaScript (actualizaciones sin recargar) |
| Hardware | ESP32 + 4 × L298N, sensores IR1/IR2 y DS18B20 |

## Estructura

```
.
├── app/                  Código de la aplicación (modelos, controladores)
├── config/               Configuración (timezone America/La_Paz)
├── database/migrations/  Esquema completo de la base de datos
├── hardware/             Firmware del ESP32 + contrato web ↔ placa
│   ├── Maquina_jabon_sin_servo_con_28BYJ48.ino
│   └── README.md
├── resources/            Vistas Blade
├── routes/               Rutas web y API
└── .env                  Configuración local (no se versiona)
```

## Puesta en marcha

```bash
# 1. Dependencias
composer install
npm install

# 2. Configuración: copia .env.example y ajusta la contraseña de PostgreSQL
cp .env.example .env
php artisan key:generate

# 3. Base de datos
#    Crea la base una sola vez:
#    psql -U postgres -c "CREATE DATABASE maquina_jabon;"
php artisan migrate

# 4. Servidor local
php artisan serve
```

Variables relevantes de `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=maquina_jabon
DB_USERNAME=postgres
DB_PASSWORD=

ESP32_BASE_URL=http://192.168.1.50
ESP32_USERNAME=admin
ESP32_PASSWORD=jabon123
```

## Roles

| Rol | Alcance |
|---|---|
| `administrador` | Único. Control total, CRUD de usuarios, reportes y registros. |
| `operador` | Control total, reportes y registros. No gestiona usuarios. |
| `visualizador` | Solo Inicio y Proyecto/Registros. |

El administrador único no se controla solo en PHP: un índice único parcial en
PostgreSQL impide que existan dos administradores activos a la vez.

## Esquema de datos

| Tabla | Propósito |
|---|---|
| `users` | Usuarios, rol y baja lógica. |
| `recetas` | Formulación con cálculo proporcional; `calculos` y `aditivos` en `jsonb`. |
| `perfiles` | Configuración de máquina (PWM, IR, pulsos de aceite) en `jsonb`. |
| `lotes` | Producción real; guarda la configuración aplicada de forma inmutable. |
| `pedidos` | Pedidos de clientes en peso o unidades. |
| `pedido_lote` | Pivote muchos a muchos pedidos ↔ lotes. |
| `logs_acciones` | Auditoría de comandos; distingue origen web / ESP32 / automatización. |
| `historial_temperaturas` | Serie temporal del DS18B20. |
| `reportes_generados` | Trazabilidad de los PDF/Excel descargados. |
| `chatbot_historial` | Preguntas y respuestas del asistente. |
| `programaciones` | Temporizadores y programación diaria de comandos *(tabla añadida)*. |

Reglas de integridad implementadas en la base de datos:

- **Un solo administrador** (índice único parcial sobre `rol`).
- **Un solo perfil predeterminado por usuario** (índice único parcial).
- Recetas con `peso > 0`; pedidos con al menos una cantidad; lotes con
  `fecha_fin >= fecha_inicio`; temperaturas dentro del rango del DS18B20.
- Borrado `RESTRICT` en datos de autoría (no se destruye historial) y
  `SET NULL` / `CASCADE` solo donde la relación es prescindible.

## Modelos y enums

`app/Models` expone los diez modelos con sus relaciones y casts. Los valores
cerrados (rol, estados, tipo de producto, ingrediente base y origen) son enums
PHP respaldados por string: un valor inválido falla en el cast y nunca llega a
la base de datos.

| Modelo | Relaciones principales |
|---|---|
| `User` | recetas, perfiles, lotes, pedidos, logs, reportes, conversaciones · `SoftDeletes` |
| `Receta` | user, lotes · `pesoDe($ingrediente)` |
| `Perfil` | user, lotes · `pwm()` completa los valores por defecto |
| `Lote` | user, receta, perfil, pedidos (`belongsToMany`) · `configuracion()` |
| `Pedido` | user, lotes (`belongsToMany`) · `descripcionCantidad()` |
| `LogAccion` | user (nullable) |
| `HistorialTemperatura` | — |
| `ReporteGenerado` | user |
| `ChatbotHistorial` | user (nullable) |

Permisos reutilizables en las vistas y políticas: `$user->puedeControlarMaquina()`,
`$user->puedeVerReportes()` y `$user->puedeGestionarUsuarios()`.

### Datos iniciales

```bash
php artisan db:seed
```

Crea `admin@jabon.local` / `admin1234` y el perfil "Producción estándar" con los
PWM del sistema. **Cambia esa contraseña en cuanto exista la pantalla de login.**

## Estado del proyecto

- [x] Esquema completo de base de datos (13 migraciones, verificadas sobre PostgreSQL 17)
- [x] Modelos Eloquent, enums y relaciones
- [x] Seeder inicial (administrador + perfil por defecto)
- [x] Autenticación: login, registro público y baja lógica
- [x] Middleware de roles (`rol:administrador,operador`) y menú filtrado por permisos
- [x] **Inicio**: métricas, buscador global en vivo y chatbot con datos reales
- [x] **Control de la máquina**: sondeo 2 s, PWM, temporizadores, programación diaria y `ALLSTOP`
- [x] **Formulación y recetas**: cálculo proporcional en vivo y historial
- [x] **Perfiles**: CRUD de PWM, automatización IR e intervalos de aceite
- [x] **Proyecto y registros**: feed de auditoría con filtros
- [x] **Reportes**: PDF con membrete y CSV para Excel
- [x] **Lotes y pedidos**: CRUD, cambios de estado y pivote muchos a muchos
- [x] **Usuarios**: alta, roles, baja lógica y administrador único
- [x] **Desarrolladores**

### Cómo entrar

```bash
php artisan serve
```

Con el seeder ejecutado: `admin@jabon.local` / `admin1234`.
**Cambia esa contraseña** desde la pestaña de usuarios en cuanto puedas.

### Reportes

| Formato | Cómo se genera |
|---|---|
| PDF | `barryvdh/laravel-dompdf`, con el membrete de la institución y resumen del período. |
| Excel | CSV con BOM y separador `;`, que Excel en español abre directamente. |

Los informes quedan guardados en `storage/app/private/reportes` y cada descarga
se registra en `reportes_generados` con su autor.

> El formato `.xlsx` nativo necesita la extensión `zip` de PHP, que no está
> habilitada en este XAMPP. Si la activas (`extension=zip` en `php.ini`) se puede
> añadir `PhpSpreadsheet` para generar libros con varias hojas.

### Pruebas

```bash
php artisan test
```

Cubren redirección de invitados, registro del primer administrador, restricciones
por rol, administrador único (se omite en SQLite) y el relleno de PWM del perfil.

### Integración con el ESP32

El panel actúa de intermediario: el navegador nunca recibe las credenciales de
la placa. `App\Services\Esp32Service` valida cada comando contra una lista
blanca antes de enviarlo a `/cmd` y lee `/status` + `SENSORS` para el monitoreo.
Si la placa está apagada, la interfaz lo indica y el comando queda registrado
como fallido en la auditoría, sin romper la aplicación.

## Nota sobre el firmware

El ESP32 todavía **no** implementa la automatización IR, los pulsos de aceite ni
los temporizadores que pide el panel. Antes de programar esos controladores hay
que decidir si se implementan en el firmware o se orquestan desde Laravel.
Detalles y el protocolo HTTP completo en [hardware/README.md](hardware/README.md).
