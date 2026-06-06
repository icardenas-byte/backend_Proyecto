# Backend - Microservicios LogiTrans Express

Proyecto de un sistema web para gestionar rutas y transporte de carga usando 5 microservicios independientes.

## Servicios

Tengo 5 microservicios, cada uno con su propia base de datos:

- **ms-auth**: Para autenticación (login/logout)
- **ms-conductores**: Gestión de conductores
- **ms-vehiculos**: Gestión de vehículos
- **ms-rutas**: Rutas y programación de viajes
- **ms-viajes**: Seguimiento de viajes

## Tecnologías

- PHP 8+
- Slim Framework
- Eloquent ORM
- MySQL

## Estructura

Cada microservicio tiene:
- `/app/Controllers` - La lógica
- `/app/Models` - Los modelos de Eloquent
- `/app/Middleware` - Validación de tokens y CORS
- `/app/Routes` - Las rutas
- `/app/Config/db.php` - Conexión a la BD

## Instalación

1. Crear las 5 bases de datos en MySQL
2. En cada carpeta de microservicio: `composer install`
3. Ver ENDPOINTS.md para los endpoints

## Validaciones

Implementé validaciones en:
- Duplicados de documentos, licencias, placas
- Licencias vencidas
- Disponibilidad de conductores y vehículos
- Estados válidos
- Campos obligatorios

## Tokens

Cada usuario que hace login recibe un token. Ese token se usa en el header Authorization para usar los otros endpoints.

Ejemplo:
```
Authorization: Bearer <token>
```