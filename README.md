# Central Data API

**Master Data Management (MDM) Centralizado para Entidades Gubernamentales y Proyectos Digitales**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://docker.com)

---

## 📋 Descripción del Proyecto

**Central Data API** es un **Data Center centralizado** que actúa como fuente única de verdad (Single Source of Truth) para información básica de personas.

Permite que múltiples sistemas (Vive Digital, Votaciones, Salubridad, etc.) consulten, sincronicen y actualicen datos de personas de forma segura, trazable y con control de calidad de datos.

Diseñado siguiendo estándares enterprise, arquitectura limpia (Repository + Service + DTO), y preparado para escalar a millones de registros y múltiples microservicios.

---

## 🏗️ Arquitectura

- **API REST** versionada (`/api/v1`)
- **Repository Pattern** + **Service Layer**
- **DTOs** para transferencia de datos
- **UUIDs** como identificadores primarios (nunca IDs incrementales expuestos)
- **Trazabilidad completa** por proyecto cliente (`api_clients`)
- **Auditoría inmutable** de todos los cambios
- **Data Quality Score** por registro y por fuente
- **Sincronización idempotente** vía `/sync`

### Modelos Principales

- `Person` - Registro maestro
- `PersonContact` - Emails, teléfonos, WhatsApp...
- `PersonAddress` - Direcciones con división político-administrativa colombiana
- `ApiClient` - Proyectos autorizados + tokens Sanctum
- `PersonProjectRelation` - Trazabilidad por proyecto
- `AuditLog` - Historial completo de cambios

---

## 🚀 Instalación Rápida (Docker Recomendado)

```bash
# 1. Clonar / entrar al proyecto
cd Api-conexion-servicios-digitales

# 2. Copiar variables de entorno
cp .env.example .env

# 3. Levantar todo con Docker
docker-compose up -d --build

# 4. Instalar dependencias y migrar (dentro del contenedor app)
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed

# 5. Acceder
# API: http://localhost:8001/api/v1
# Swagger: http://localhost:8001/api/documentation
# phpMyAdmin: http://localhost:8081
```

---

## 🔐 Autenticación

Todos los endpoints protegidos usan **Laravel Sanctum** con tokens por proyecto.

### Obtener Token

```http
POST /api/v1/auth/token
{
  "slug": "vive-digital",
  "secret": "change-this-to-a-very-long-random-string-in-productionvive-digital"
}
```

Respuesta:
```json
{
  "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxx",
  "client": { "slug": "vive-digital", ... }
}
```

Usar como `Authorization: Bearer {token}`

---

## 📡 Endpoints Principales

| Método | Endpoint                        | Descripción                          |
|--------|----------------------------------|--------------------------------------|
| POST   | `/api/v1/auth/token`            | Obtener token de acceso             |
| GET    | `/api/v1/persons/search`        | Buscar persona (documento, nombre...) |
| POST   | `/api/v1/persons/sync`          | Crear o actualizar (idempotente)    |
| GET    | `/api/v1/persons/{uuid}`        | Obtener detalle completo            |
| GET    | `/api/v1/health`                | Health check                        |

---

## 🧪 Testing

```bash
# Ejecutar tests
composer test

# O con sail
./vendor/bin/sail test
```

---

## 📊 Swagger / OpenAPI

Visita `/api/documentation` después de levantar el proyecto.

Las anotaciones están en los controladores usando atributos PHP 8.

---

## 🛡️ Seguridad Implementada

- Sanctum API Tokens por cliente
- Rate limiting por cliente (configurable)
- Validación fuerte con Form Requests
- CORS configurable
- Headers de seguridad
- Auditoría completa de cambios
- Soft deletes + trazabilidad de fuente

---

## 📁 Estructura Enterprise

```
app/
├── Actions/
├── DTOs/
├── Enums/
├── Exceptions/
├── Http/
│   ├── Controllers/API/v1/
│   ├── Requests/API/v1/
│   └── Resources/API/v1/
├── Interfaces/Repositories/
├── Jobs/
├── Models/
├── Repositories/
├── Services/
├── Traits/
└── Providers/
```

---

## 🔄 Flujo de Sincronización Recomendado

1. Cliente consulta `/search?document=...`
2. Si existe → autocompleta formulario
3. Usuario finaliza registro → `POST /sync`
4. API crea o actualiza + registra trazabilidad + recalcula calidad

---

## 📈 Escalabilidad

- Preparado para sharding futuro (UUIDs)
- Redis para cache y colas
- Jobs para procesos pesados
- Arquitectura lista para extraer a microservicios

---

## ✅ Buenas Prácticas Aplicadas

- SOLID
- Repository + Service + DTO
- API versioning
- Typed properties + strict types
- Inmutabilidad donde aplica (DTOs readonly)
- Logging estructurado
- Validación centralizada

---

## 📝 Licencia

Propiedad de la entidad que implemente el sistema.

---

**Desarrollado siguiendo estándares de arquitectura de software senior para sistemas gubernamentales de alto impacto.**

Cualquier contribución o mejora es bienvenida.
