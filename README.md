# Flour and Fire

PHP-приложение пиццерии с PostgreSQL.

## Запуск

```bash
docker compose up --build
```

После запуска:

- сайт: [http://127.0.0.1:10001](http://127.0.0.1:10001)
- админка: [http://127.0.0.1:10001/admin](http://127.0.0.1:10001/admin)

Доступ в админку по умолчанию:

- email: `admin@example.com`
- пароль: `Admin123!`

## Команды

```bash
docker compose logs -f
docker compose down
docker compose down -v
```

`docker compose down -v` полностью удаляет базу данных.

## Что поднимается

- `app` - PHP 8.2 + Apache
- `db` - PostgreSQL 16
- порт приложения: `10001`
- порт PostgreSQL: `5432`

При старте контейнер сам:

- ждет готовности PostgreSQL
- применяет миграции на пустую базу
- применяет обновления схемы на существующую базу
- загружает demo seed на пустую базу
- создает или обновляет администратора

## Основные маршруты

- `/`
- `/menu`
- `/photos`
- `/contact`
- `/login`
- `/register`
- `/cart`
- `/account`
- `/admin`
- `/product/{slug}`

## Важные папки

- `app/` - бизнес-логика и репозитории
- `public/` - web root, страницы и статика
- `resources/views/` - общие шаблоны
- `database/migrations/` - SQL-миграции
- `database/seeds/` - demo-данные
- `public/uploads/products/` - загруженные изображения товаров

## Если нужно сбросить проект

```bash
docker compose down -v
docker compose up --build
```
