# Endpoints de la API

## Base URLs

```
ms-auth: http://localhost/ms-auth/public
ms-conductores: http://localhost/ms-conductores/public
ms-vehiculos: http://localhost/ms-vehiculos/public
ms-rutas: http://localhost/ms-rutas/public
ms-viajes: http://localhost/ms-viajes/public
```

---

## MS-AUTH (Autenticación)

### POST /api/login
Login de usuario
```
Body: {
  "usuario": "admin",
  "password": "password"
}

Respuesta: {
  "success": true,
  "data": {
    "token": "...",
    "usuario": {"id": 1, "usuario": "admin", "email": "admin@email.com"}
  }
}
```

### POST /api/logout
Cerrar sesión
```
Headers: Authorization: Bearer <token>

Respuesta: { "success": true, "data": { "message": "Sesion cerrada" } }
```

### GET /api/validate
Validar token activo
```
Headers: Authorization: Bearer <token>

Respuesta: {
  "success": true,
  "data": {
    "valid": true,
    "usuario_id": 1,
    "usuario": "admin",
    "email": "admin@email.com"
  }
}
```

---

## MS-CONDUCTORES (Conductores)

**Todos requieren token en Authorization header**

### GET /api/conductores
Listar conductores
```
Query: ?documento=123&licencia=ABC&estado=Disponible (todos opcionales)

Respuesta: { "success": true, "data": [...] }
```

### POST /api/conductores
Crear conductor
```
Body: {
  "nombres": "Juan",
  "apellidos": "Pérez",
  "documento": "12345678",
  "telefono": "3123456789",
  "email": "juan@email.com",
  "numero_licencia": "LIC123",
  "categoria_licencia": "A2",
  "fecha_vencimiento_licencia": "2026-12-31"
}

Status 201
```

### GET /api/conductores/{id}
Obtener un conductor

### PUT /api/conductores/{id}
Editar conductor (mismo body que POST, pero campos opcionales)

### PATCH /api/conductores/{id}/estado
Cambiar estado
```
Body: { "estado": "Disponible" }
Estados: Disponible, En ruta, Inactivo
```

---

## MS-VEHICULOS (Vehículos)

**Todos requieren token en Authorization header**

### GET /api/vehiculos
Listar vehículos
```
Query: ?placa=ABC&estado=Disponible&tipo=Camión (todos opcionales)
```

### POST /api/vehiculos
Crear vehículo
```
Body: {
  "placa": "ABC123",
  "tipo": "Camión",
  "capacidad_carga": 5000,
  "modelo": "2023",
  "marca": "Volvo",
  "estado": "Disponible"
}

Status 201
```

### GET /api/vehiculos/{id}
Obtener un vehículo

### PUT /api/vehiculos/{id}
Editar vehículo (campos opcionales)

### PATCH /api/vehiculos/{id}/estado
Cambiar estado
```
Body: { "estado": "Disponible" }
Estados: Disponible, En ruta, Mantenimiento, Inactivo
```

---

## MS-RUTAS (Rutas y Programación)

**Todos requieren token en Authorization header**

### GET /api/rutas
Listar rutas
```
Query: ?ciudad=Bogota (busca en origen o destino)
```

### POST /api/rutas
Crear ruta
```
Body: {
  "ciudad_origen": "Bogotá",
  "ciudad_destino": "Medellín",
  "distancia": 430,
  "tiempo_estimado": "06:30:00",
  "observaciones": "Ruta por autopista"
}

Status 201
```

### GET /api/rutas/{id}
Obtener una ruta

### PUT /api/rutas/{id}
Editar ruta (campos opcionales)

---

## Programación de Viajes

### GET /api/programaciones
Listar programaciones
```
Query: ?conductor_id=1&vehiculo_id=1&estado=Programado&fecha=2024-06-15
(todos opcionales)
```

### POST /api/programaciones
Programar un viaje
```
Body: {
  "conductor_id": 1,
  "vehiculo_id": 1,
  "ruta_id": 1,
  "fecha_salida": "2024-06-15",
  "hora_salida": "08:00:00",
  "fecha_estimada_llegada": "2024-06-15",
  "observaciones": "Precaución por clima"
}

Status 201
```

### PUT /api/programaciones/{id}
Editar programación (campos opcionales)

---

## MS-VIAJES (Seguimiento de Viajes)

**Todos requieren token en Authorization header**

### GET /api/viajes
Listar viajes
```
Query: ?estado=En%20transito&programacion_id=1 (opcionales)
```

### POST /api/viajes/iniciar
Iniciar un viaje
```
Body: {
  "programacion_id": 1,
  "observaciones": "Viaje iniciado a tiempo"
}

Status 201
```

### PATCH /api/viajes/{id}/estado
Cambiar estado del viaje
```
Body: {
  "estado": "Retrasado",
  "observaciones": "Tráfico en la ruta"
}

Estados: Programado, En transito, Retrasado, Finalizado, Cancelado
```

### POST /api/viajes/{id}/novedades
Registrar una novedad (retraso, incidente, etc)
```
Body: {
  "tipo": "Retraso",
  "descripcion": "Tráfico intenso",
  "observaciones": "Estimado 30 minutos de retraso"
}

Status 201
```

### PATCH /api/viajes/{id}/finalizar
Finalizar un viaje
```
Body: {
  "observaciones": "Viaje completado exitosamente"
}

Solo funciona si el viaje está "En transito" o "Retrasado"
```

### GET /api/viajes/{id}/seguimiento
Ver historial del viaje
```
Retorna: {
  "viaje": {...},
  "programacion": {...}
}
```

---

## Headers

Todos los endpoints (excepto login) necesitan:
```
Authorization: Bearer <token>
Content-Type: application/json
```

---

## Respuestas

### Éxito
```json
{
  "success": true,
  "data": {}
}
```

### Error
```json
{
  "success": false,
  "message": "Mensaje de error"
}
```

---

## Status Codes

- 200: OK
- 201: Creado
- 400: Error en los datos
- 401: No autenticado (token inválido)
- 404: No encontrado
- 409: Conflicto (ej: documento duplicado)
- 422: Validación fallida
