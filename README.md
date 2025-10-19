# Sistema de Gestión de Inventario

Sistema web desarrollado en PHP para simular y gestionar inventarios utilizando el método de simulación Monte Carlo.

## Características

- ✅ Generación de números aleatorios con método congruencial lineal
- ✅ Simulación de 1000 días de inventario
- ✅ Cálculo de costos (inventario, pedidos, pérdida de prestigio)
- ✅ Interfaz web responsive
- ✅ Sin dependencia de JavaScript

## Tecnologías

- PHP 8.2
- Apache
- Sessions para persistencia de datos

## Despliegue en Railway

1. Sube el proyecto a GitHub
2. Conecta Railway con tu repositorio
3. Railway detectará automáticamente el Dockerfile
4. ¡Listo!

## Uso Local
```bash
docker build -t inventario .
docker run -p 8080:80 inventario
```

Abre: http://localhost:8080