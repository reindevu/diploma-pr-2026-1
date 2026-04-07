# Flour and Fire

Веб-приложение пиццерии на `PHP + PostgreSQL`.

Сейчас в проекте реализованы:

- главная страница и меню из базы данных
- страница товара с выбором размера и количества
- регистрация и вход
- корзина
- промокоды
- оформление заказа
- личный кабинет
- админка для товаров и заказов

## Стек

- `PHP 8.1+`
- `PostgreSQL 14+`
- `Docker Compose`
- `Apache` внутри контейнера приложения

## Структура проекта

```text
app/                бизнес-логика, репозитории, авторизация
bootstrap/          загрузка приложения
config/             конфигурация
database/           миграции и seed-данные
public/             web root, страницы и статика
resources/views/    общие шаблоны
scripts/            CLI-скрипты
entrypoint.sh       старт контейнера приложения
router.php          роутер для встроенного PHP-сервера
верстка/            исходные HTML-макеты
```

## Быстрый запуск через Docker

Основной способ запуска:

```bash
docker compose up --build
```

После старта приложение будет доступно по адресу:

- [http://127.0.0.1:10001](http://127.0.0.1:10001)
- [http://127.0.0.1:10001/admin](http://127.0.0.1:10001/admin)

Что делает запуск:

- поднимает `PostgreSQL`
- поднимает контейнер с `PHP + Apache`
- ждёт готовности базы
- на пустой базе применяет миграцию
- загружает демо-данные
- создаёт или обновляет администратора

Демо-доступ в админку по умолчанию:

- email: `admin@example.com`
- пароль: `Admin123!`

Полезные команды:

```bash
docker compose down
docker compose down -v
```

`down -v` удалит том Postgres и полностью сбросит базу.

## Локальный запуск без Docker

### 1. Подготовить переменные окружения

Перед запуском выставь переменные:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=5432
export DB_NAME=flour_and_fire
export DB_USER=postgres
export DB_PASSWORD=postgres
```

Опционально можно использовать DSN:

```bash
export DB_DSN="pgsql:host=127.0.0.1;port=5432;dbname=flour_and_fire"
```

### 2. Применить миграции и seed

```bash
php scripts/migrate.php
php scripts/seed.php
php scripts/make_admin.php admin@example.com Admin123!
```

`scripts/migrate.php` рассчитан на пустую базу и накатывает базовую схему целиком.

### 3. Поднять локальный сервер

```bash
php -S 127.0.0.1:8000 -t public router.php
```

После этого открой:

- [http://127.0.0.1:8000](http://127.0.0.1:8000)
- [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)

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

## База данных

В проекте уже есть:

- миграция схемы: [database/migrations/001_init_schema.sql](/Users/reindevu/PhpstormProjects/diploma-pr-2026-1/database/migrations/001_init_schema.sql)
- демо-данные: [database/seeds/001_demo_seed.sql](/Users/reindevu/PhpstormProjects/diploma-pr-2026-1/database/seeds/001_demo_seed.sql)

## Что важно знать

- загрузка изображений из админки пока не реализована, используется строковый путь к файлу
- стоимость доставки сейчас зашита в коде как `200 ₽`
- карта и контакты пока статические
- продуктовые карточки `margarita-card.html`, `spinachi-card.html` и другие заменены одним динамическим маршрутом `/product/{slug}`
- папка `верстка/` используется как эталон исходной HTML-верстки

## Если проект не стартует

Проверь:

- что Docker запущен
- что порт `10001` не занят
- что порт `5432` не занят другим Postgres
- что контейнеры собираются без ошибки

Логи можно посмотреть так:

```bash
docker compose logs -f
```
