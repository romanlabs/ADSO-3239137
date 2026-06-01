# Guia De Ejecucion Docker

## Levantar servicios

```bash
docker compose up --build
```

## Ver contenedores

```bash
docker ps
```

Contenedores esperados:

- `sena-frontend`
- `sena-backend`
- `sena-db`

## Reiniciar

```bash
docker compose restart
```

## Detener

```bash
docker compose down
```

## Detener y borrar volumen de MongoDB

```bash
docker compose down -v
```
