# Сервис магазина (Phalcon + MySQL)

REST API и простой веб-интерфейс для управления товарами и иерархическими
категориями. Бэкенд: PHP 8.2, Phalcon 5.10, MySQL 8, Nginx. Интерфейс: шаблоны
Volt и одностраничное приложение на Vue 3 (Composition API).

**Стек по заданию:** используется **PHP + Phalcon** (другой PHP-фреймворк не
выбирался — отдельное обоснование не требуется).

## Соответствие требованиям ТЗ

| Требование | Реализация |
| ---------- | ---------- |
| Список товаров с **постраничной пагинацией** | `GET /api/products` — параметры `page`, `per_page` |
| **Иерархия категорий** | Таблица `categories` (`parent_id`, материализованный `path`), фильтр по поддереву по `category_id` |
| Поля в списке: **название, содержание, цена, категория, наличие** | В JSON: `name`, `content`, `price`, объект `category` (`id`, `name`, `path`), `in_stock` |
| **Фильтры:** категория, `in_stock` | Query: `category_id` (поддерево), `in_stock` |
| **Агрегаты** по товарам в наличии: количество и сумма | `meta.aggregates.in_stock_count`, `in_stock_total_price`; учитываются те же фильтры, считаются только `in_stock = true` (см. раздел «Семантика агрегатов») |
| **CRUD товаров** | `GET/POST/PUT/DELETE /api/products`, `GET /api/products/{id}` |
| **CRUD категорий** (в т.ч. подкатегории) | `GET/POST/PUT/DELETE /api/categories`, `parent_id` при создании/смене родителя |
| **Bearer Token** | Заголовок `Authorization: Bearer <token>`, таблица `api_tokens` |
| **Ошибки и HTTP-коды**, ответы **JSON** | Единый формат `error`, коды 401/404/409/422 и др.; `Content-Type: application/json` |
| **MySQL**, **индексы** под фильтры и пагинацию | См. раздел «База данных и индексы», миграция [`db/migrations/1.0.0/shop.php`](db/migrations/1.0.0/shop.php) |
| **Веб-интерфейс:** Volt + **Vue 3** | `app/Views/index.volt` + `public/assets/*.js`; запросы списка товаров — **Composition API** (`setup` в `app.js` / `ProductsPanel.js`) |
| **Документация** | Этот README: запуск, возможности, curl, агрегаты, индексы; OpenAPI в `/api/docs` |

## Открытие в браузере

После запуска (см. ниже) откройте в браузере:

| Назначение | URL (по умолчанию) |
| ---------- | ------------------ |
| **Главная страница — админка (Volt + Vue): товары, категории, ввод токена** | **http://localhost:8080/** |
| **Swagger UI — интерактивная документация API** | **http://localhost:8080/api/docs** |
| OpenAPI 3 (JSON) | http://localhost:8080/api/docs/openapi.json |
| Проверка жизнеспособности (без авторизации) | http://localhost:8080/api/health |

Порты настраиваются в `.env`: `APP_HOST_PORT` (по умолчанию `8080` для
приложения) и `DB_HOST_PORT` для внешнего доступа к MySQL. Если порт 8080 занят,
измените `APP_HOST_PORT` и открывайте, например, `http://localhost:9090/`.

**Авторизация в Swagger UI:** на странице `/api/docs` нажмите кнопку **Authorize**
(вверху справа). В диалоге для схемы **bearerAuth** введите сам токен (как в
`Authorization: Bearer <токен>`, **без** слова `Bearer` — его подставляет Swagger).
После этого «Try it out» на защищённых методах будет отправлять заголовок с токеном.

## Возможности

- Список товаров с пагинацией, фильтрами по категории (с учётом поддерева) и
  признаку `in_stock`.
- В каждом ответе списка — агрегаты: количество и суммарная стоимость **только
  товаров в наличии**, с теми же фильтрами, что и у выборки.
- CRUD по товарам и по дереву категорий; удаление категории с детьми или
  товарами — отказ с кодом 409.
- Защита API заголовком `Authorization: Bearer <token>`.
- Ошибки в JSON с корректными HTTP-кодами (`401`, `404`, `409`, `422`, …).
- OpenAPI 3: атрибуты на ручках, схема по адресу `/api/docs/openapi.json`, UI —
  по адресу `/api/docs`.

## Запуск

### Вариант A: Docker (рекомендуется)

Нужны Docker и Docker Compose v2.

```bash
cp .env.example .env
docker compose up --build
```

### Вариант B: локально без Docker (по желанию)

1. Установите **PHP 8.2+** с расширениями **phalcon**, **pdo_mysql**, **json** и
   **Composer**.
2. Поднимите **MySQL 8**, создайте пустую БД и пользователя (как в `.env.example`).
3. `cp .env.example .env`, задайте `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
   `DB_PASSWORD` на ваш MySQL.
4. `composer install` в корне проекта, затем `composer run migrate` — это
   `vendor/bin/phalcon-migrations run --directory=.`; настройки БД и
   `application.migrationsDir` берутся из [`app/config/config.php`](app/config/config.php)
   (отдельный `--config` не нужен: так CLI не склеивает путь к файлу дважды).
5. Настройте **Nginx** + **php-fpm** с `root` на каталог `public/` и правилом
   `try_files` как в [`docker/nginx/default.conf`](docker/nginx/default.conf), либо
   эквивалент для вашего веб-сервера.

**Адреса (по умолчанию) при Docker:**

- Приложение (браузер): http://localhost:8080/
- База API: http://localhost:8080/api
- MySQL с хоста: `localhost:3307` (логин/пароль `shop` / `shop`, БД `shop`)

При старте контейнера PHP после `composer install` выполняются миграции через
**phalcon-migrations** (`run --directory=.`, конфиг — тот же
[`app/config/config.php`](app/config/config.php), что и у приложения); накатываются
таблицы и демо-данные (категории, товары, токен). Чтобы полностью
сбросить данные MySQL: `docker compose down -v` и снова `docker compose up`
(том `mysql_data` удаляется, миграции накатываются на пустую БД).

### Демо-токен

Для быстрого старта в БД уже есть токен:

```text
demo-token-please-change
```

Вставьте его в поле в боковой панели веб-интерфейса или передавайте в
заголовке `Authorization: Bearer …` в HTTP-запросах. В продакшене замените на
свой секрет.

## API

Все ответы — в формате JSON. Тело ошибки:

```json
{ "error": { "message": "…", "errors": { "поле": "причина" } } }
```

### Маршруты

| Метод  | Путь | Описание |
| ------ | ---- | -------- |
| GET    | `/api/health` | Проверка жизнеспособности (без токена). |
| GET    | `/api/docs/openapi.json` | Спецификация OpenAPI 3. |
| GET    | `/api/docs` | Swagger UI. |
| GET    | `/api/products` | Список: пагинация, фильтры, агрегаты. |
| GET    | `/api/products/{id}` | Один товар. |
| POST   | `/api/products` | Создание товара. |
| PUT    | `/api/products/{id}` | Обновление. |
| DELETE | `/api/products/{id}` | Удаление. |
| GET    | `/api/categories` | Список категорий (дерево: `?tree=1`). |
| POST   | `/api/categories` | Создание (корень или подкатегория). |
| PUT    | `/api/categories/{id}` | Переименование / перенос. |
| DELETE | `/api/categories/{id}` | Удаление (нельзя, если есть дети/товары). |

### Примеры запросов

```bash
TOKEN=demo-token-please-change
BASE=http://localhost:8080/api

# Список ноутбуков в наличии
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE/products?category_id=3&in_stock=1&page=1&per_page=10"

# Фильтр по родительской категории (поддерево): "Electronics" (id=1)
curl -s -H "Authorization: Bearer $TOKEN" "$BASE/products?category_id=1&per_page=50"

# Создать товар
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"New phone","content":"Latest model","price":799.0,"in_stock":true,"category_id":5}' \
  "$BASE/products"

# Частичное обновление
curl -s -X PUT -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"price":749.5,"in_stock":false}' \
  "$BASE/products/1"

# Удалить товар
curl -s -X DELETE -H "Authorization: Bearer $TOKEN" "$BASE/products/1" -o /dev/null -w "%{http_code}\n"

# Категории деревом
curl -s -H "Authorization: Bearer $TOKEN" "$BASE/categories?tree=1"

# Подкатегория
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"Gaming laptops","parent_id":3}' \
  "$BASE/categories"

# Без токена
curl -i "$BASE/products"
# HTTP/1.1 401 Unauthorized
# { "error": { "message": "Missing Authorization header" } }
```

### Пример ответа: список товаров с пагинацией и агрегатами

Запрос: `GET /api/products?page=1&per_page=2` с заголовком `Authorization: Bearer <token>`.

```json
{
  "data": [
    {
      "id": 1,
      "name": "ThinkPad X1",
      "content": "14\" business laptop",
      "price": "1899.00",
      "in_stock": true,
      "category": {
        "id": 3,
        "name": "Laptops",
        "path": "/1/2/3/"
      }
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 2,
    "total": 8,
    "aggregates": {
      "in_stock_count": 6,
      "in_stock_total_price": "5285.89"
    }
  }
}
```

### Коды ответов

| Код | Когда |
| --- | ----- |
| 200 | Успешный GET / PUT. |
| 201 | Успешный POST (создан ресурс). |
| 204 | Успешный DELETE (без тела). |
| 401 | Нет / неверный / неизвестный Bearer-токен. |
| 404 | Ресурс не найден (в т.ч. несуществующий `category_id` в фильтре). |
| 409 | Конфликт (нельзя удалить непустую категорию, некорректный перенос и т.п.). |
| 422 | Ошибка валидации; в `error.errors` — причины по полям. |
| 500 | Внутренняя ошибка (детали — только при `APP_ENV=dev`). |

### Семантика агрегатов

Блок `meta.aggregates` применяет **те же фильтры**, что и список (поддерево
категории и, если передан, `in_stock`), но для подсчёта **учитываются только
строки с `in_stock = true`**.

- Без параметра `in_stock` в агрегатах — только в наличии в рамках остальных
  фильтров.
- С `in_stock=1` список и агрегаты согласованы.
- С `in_stock=0` в списке — товары не в наличии, а `in_stock_count` = 0 и сумма
  `"0.00"` — ожидаемое поведение.

## База данных и индексы

Схема и сиды — в миграции Phalcon
[`db/migrations/1.0.0/shop.php`](db/migrations/1.0.0/shop.php). Назначение индексов:

### `products (category_id, in_stock, id)` — составной

Типичный запрос:

```sql
SELECT ... FROM products p JOIN categories c ON c.id = p.category_id
WHERE p.category_id IN (...) AND p.in_stock = ?
ORDER BY p.id ASC LIMIT ? OFFSET ?
```

Составной индекс на `(category_id, in_stock, id)`:

1. Сужает по категории.
2. Уточняет по наличию.
3. `id` в конце помогает `ORDER BY id` без лишних сортировок на больших объёмах.

### `products (in_stock, id)`

Вариант запроса **без** фильтра по категории, только по наличию: отдельный
индекс на `(in_stock, id)` дешевле, чем опираться только на первую колонку
составного `category_id`.

### `categories (parent_id)`

Поиск прямых потомков и подсчёты при удалении — через index seek.

### `categories (path)` и материализованный путь

Для дерева в каждой строке хранится путь вида `/1/2/3/`. Поддерево выбирается
одним проходом с префиксом `path LIKE '/1/2/%'`.

### `api_tokens.token` — уникальный

Поиск токена в каждом запросе с авторизацией — O(log n), дубликатов нет.

## Структура репозитория

```text
app/
  Bootstrap.php
  config/                  config, services, router
  Controllers/             Index + Api/*
  Exceptions/
  Http/                    JSON-ответы и обработка ошибок
  Models/
  OpenApi/                 схемы и генерация OpenAPI
  Repositories/
  Services/
  Views/index.volt
public/
  index.php
  assets/                  Vue, стили, компоненты
db/migrations/1.0.0/       схема и демо-данные (shop)
docker/
docker-compose.yml
```

Принципы: тонкие контроллеры, валидация и правила в сервисах, SQL в
репозиториях. В коде — только PHPDoc / JSDoc, без «объясняющих» комментариев в
теле логики. Зависимости в [`app/config/services.php`](app/config/services.php).

## Пагинация

Сейчас — offset-страницы на фоне составного индекса. На очень глубоких страницах
курсор `WHERE id > :after` может быть эффективнее; в индексе уже есть `id` для
такого перехода.

## Почему PHP 8.2 и флаги компиляции

- **PHP 8.2** вместо 8.3: сборка Phalcon 5.x с текущим `php:8.3-fpm-alpine` у
  поставщика образа падает из-за сочетания изменений Zend API и жёсткого GCC
  по несовпадению типов указателей. PHP 8.2 — стабильная LTS-ветка.
- В `Dockerfile` задано `CFLAGS=-Wno-incompatible-pointer-types
  -Wno-int-conversion`, чтобы `pecl install phalcon` проходил на свежем Alpine
  с жёстким gcc.
