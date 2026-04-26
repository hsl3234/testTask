# Сервис магазина (Phalcon + MySQL)

REST API и простой веб-интерфейс для управления товарами и иерархическими
категориями. Бэкенд: PHP 8.2, Phalcon 5.10, MySQL 8, Nginx. Интерфейс: шаблоны
Volt и одностраничное приложение на Vue 3 (Composition API).

**Стек по заданию:** используется **PHP + Phalcon** (другой PHP-фреймворк не
выбирался — отдельное обоснование не требуется).

## Соответствие требованиям ТЗ

| Требование | Реализация |
| ---------- | ---------- |
| Список товаров с **постраничной пагинацией** | `GET /api/products` — `page`, `perPage` (также `per_page`) |
| **Иерархия категорий** | В БД: `parent_id`, `path`; в JSON — `parentId` и `path`, фильтр по поддереву `categoryId` |
| Поля в списке: **название, содержание, цена, категория, наличие** | В JSON (camelCase): `name`, `content`, `price`, `inStock`, `category` { `id`, `name`, `path` } |
| **Фильтры:** категория, наличие | Query: `categoryId` или `category_id`, `inStock` или `in_stock` |
| **Агрегаты** по товарам в наличии: количество и сумма | `meta.aggregates.inStockCount`, `inStockTotalPrice`; те же фильтры, в подсчёте только `inStock = true` (см. «Семантика агрегатов») |
| **CRUD товаров** | `GET/POST/PUT/DELETE /api/products`, `GET /api/products/{id}` |
| **CRUD категорий** (в т.ч. подкатегории) | `GET/POST/PUT/DELETE /api/categories`, в JSON — `parentId` (алиас: `parent_id`) |
| **Bearer Token** | Заголовок `Authorization: Bearer <token>`. Источники токенов: вход админа в SPA через `POST /api/auth/login` (+ refresh-ротация) либо статический ключ из `api_tokens` для curl/демо. |
| **Ошибки и HTTP-коды**, ответы **JSON** | Единый формат `error`, коды 401/404/409/422 и др.; `Content-Type: application/json` |
| **MySQL**, **индексы** под фильтры и пагинацию | См. раздел «База данных и индексы», миграции [`db/migrations/1.0.0/shop.php`](db/migrations/1.0.0/shop.php) и [`db/migrations/1.0.1/auth.php`](db/migrations/1.0.1/auth.php) |
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
  признаку наличия (`inStock` в query/ответе).
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

### Авторизация

В системе два способа получить Bearer-токен:

1. **Логин админа (для SPA).** При открытии `/` показывается экран входа.
   После `POST /api/auth/login` с парой `login` + `password` сервер возвращает
   `accessToken` (короткоживущий, по умолчанию 15 мин) и одноразовый
   `refreshToken` (по умолчанию 14 дней). SPA хранит их в `localStorage`,
   подставляет access в `Authorization`, а на любую `401` от защищённой
   ручки автоматически вызывает `POST /api/auth/refresh` и повторяет запрос.
2. **Статический демо-токен `demo-token-please-change`** — строка в
   `api_tokens` с `user_id IS NULL` и `expires_at IS NULL`. Удобен для curl
   и Swagger UI; для админ-панели не используется. В продакшене удалите его
   или замените на свой.

Сидируемая учётка задаётся через `.env`: `ADMIN_LOGIN`, `ADMIN_PASSWORD`
(дефолт `admin` / `admin`). Миграция `1.0.1` хеширует пароль `password_hash`
и кладёт строку в `users`. Сроки жизни токенов — `ACCESS_TOKEN_TTL` и
`REFRESH_TOKEN_TTL` (секунды).

```bash
# Получить пару
curl -s -X POST -H 'Content-Type: application/json' \
  -d '{"login":"admin","password":"admin"}' \
  http://localhost:8080/api/auth/login
# {"accessToken":"…","refreshToken":"…","tokenType":"Bearer","expiresIn":900}

# Защищённый запрос с access
curl -s -H "Authorization: Bearer <accessToken>" \
  http://localhost:8080/api/products

# Ротация (старый refresh уничтожается)
curl -s -X POST -H 'Content-Type: application/json' \
  -d '{"refreshToken":"<refreshToken>"}' \
  http://localhost:8080/api/auth/refresh
```

## API

Все ответы — JSON; **имена полей в ответах — camelCase** (например `inStock`, `perPage`, `inStockCount`). В запросе (query и body) принимаются **camelCase**; для обратной совместимости также **snake_case** (`in_stock`, `per_page`, `category_id` и т.д.).

Тело ошибки:

```json
{ "error": { "message": "…", "errors": { "полеCamelCase": "причина" } } }
```

### Маршруты

| Метод  | Путь | Описание |
| ------ | ---- | -------- |
| GET    | `/api/health` | Проверка жизнеспособности (без токена). |
| GET    | `/api/docs/openapi.json` | Спецификация OpenAPI 3. |
| GET    | `/api/docs` | Swagger UI. |
| POST   | `/api/auth/login` | Логин админа: `login`, `password` → пара токенов. |
| POST   | `/api/auth/refresh` | Ротация: `refreshToken` → новая пара. |
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
  "$BASE/products?categoryId=3&inStock=1&page=1&perPage=10"

# Фильтр по родительской категории (поддерево): "Electronics" (id=1)
curl -s -H "Authorization: Bearer $TOKEN" "$BASE/products?categoryId=1&perPage=50"

# Создать товар
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"New phone","content":"Latest model","price":799.0,"inStock":true,"categoryId":5}' \
  "$BASE/products"

# Частичное обновление
curl -s -X PUT -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"price":749.5,"inStock":false}' \
  "$BASE/products/1"

# Удалить товар
curl -s -X DELETE -H "Authorization: Bearer $TOKEN" "$BASE/products/1" -o /dev/null -w "%{http_code}\n"

# Категории деревом
curl -s -H "Authorization: Bearer $TOKEN" "$BASE/categories?tree=1"

# Подкатегория
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"name":"Gaming laptops","parentId":3}' \
  "$BASE/categories"

# Без токена
curl -i "$BASE/products"
# HTTP/1.1 401 Unauthorized
# { "error": { "message": "Missing Authorization header" } }
```

### Пример ответа: список товаров с пагинацией и агрегатами

Запрос: `GET /api/products?page=1&perPage=2` с заголовком `Authorization: Bearer <token>`.

```json
{
  "data": [
    {
      "id": 1,
      "name": "ThinkPad X1",
      "content": "14\" business laptop",
      "price": "1899.00",
      "inStock": true,
      "category": {
        "id": 3,
        "name": "Laptops",
        "path": "/1/2/3/"
      }
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 2,
    "total": 8,
    "aggregates": {
      "inStockCount": 6,
      "inStockTotalPrice": "5285.89"
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
| 404 | Ресурс не найден (в т.ч. несуществующий `categoryId` в фильтре). |
| 409 | Конфликт (нельзя удалить непустую категорию, некорректный перенос и т.п.). |
| 422 | Ошибка валидации; в `error.errors` — причины по полям. |
| 500 | Внутренняя ошибка (детали — только при `APP_ENV=dev`). |

### Семантика агрегатов

Блок `meta.aggregates` применяет **те же фильтры**, что и список (поддерево
категории и, если передан, `inStock` / `in_stock`), но для подсчёта **учитываются только
строки с `inStock = true` в ответе** (в БД — `in_stock = 1`).

- Без параметра наличия в агрегатах — только в наличии в рамках остальных
  фильтров.
- С `inStock=1` список и агрегаты согласованы.
- С `inStock=0` в списке — товары не в наличии, а `inStockCount` = 0 и сумма
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
Дополнительно есть индекс по `expires_at` (учётный) и FK на `users.id`,
чтобы выдача и ревокация access-токенов попадали в индекс.

### `users.login` и `refresh_tokens.token` — уникальные

`users.login` обеспечивает однократность аккаунтов, `refresh_tokens.token` —
поиск активного refresh за один index seek; `expires_at` индексирован для
фильтрации актуальных строк, `user_id` — для каскадного удаления.

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
db/migrations/1.0.1/       пользователи, refresh_tokens, расширение api_tokens
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
