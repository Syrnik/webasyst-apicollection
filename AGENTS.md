# AGENTS.md — apicollection

Руководство для AI-агентов, работающих с приложением **apicollection** в рамках Webasyst Framework.

---

## Что делает приложение

Менеджер Swagger/OpenAPI коллекций в бэкенде Webasyst. Позволяет сохранять ссылки на `swagger.json` / `openapi.json` **или загружать файлы спецификаций**, просматривать дерево endpoints и выполнять HTTP-запросы через серверный прокси прямо из браузера.

- Коллекции бывают **личными** (видит только владелец) и **общими** (видят все пользователи бэкенда)
- Источник спецификации: **URL** или **загруженный JSON-файл**
- Аутентификация поддерживается: `none`, `bearer`, `basic`, `apikey`
- Все запросы к внешним API проходят через серверный прокси `proxyFetchAction` (SSRF-защита встроена)
- История запросов сохраняется в БД

---

## Файловая структура

```
wa-apps/apicollection/
├── lib/
│   ├── actions/backend/
│   │   ├── apicollectionBackend.action.php   # Главная страница (SPA-оболочка)
│   │   └── apicollectionBackend.actions.php  # JSON API — все AJAX-эндпоинты
│   ├── models/
│   │   ├── apicollectionCollection.model.php       # Модель коллекций
│   │   └── apicollectionRequestHistory.model.php   # Модель истории запросов
│   ├── config/
│   │   ├── app.php       # Метаданные приложения
│   │   ├── db.php        # Схема таблиц
│   │   ├── rights.php    # Права доступа
│   │   └── routing.php   # Пустой (бэкенд не нуждается в routing.php)
│   ├── updates/
│   │   └── 1700000000.php  # Миграция: CREATE TABLE
│   └── apicollectionRightConfig.class.php  # Конфигурация прав
├── src/                       # Исходники фронтенда (Vue 3 + TypeScript + Vite)
│   ├── api/
│   │   └── index.ts           # apiFetch() — обёртка над $.ajax
│   ├── types/
│   │   └── index.ts           # Все TypeScript-типы и интерфейсы
│   ├── utils/
│   │   ├── formatters.ts      # methodBadgeClass, statusClass, timeAgo, truncate, extractBaseUrl
│   │   ├── swagger.ts         # Парсинг Swagger/OpenAPI спецификаций
│   │   └── validation.ts      # Валидация форм
│   ├── composables/
│   │   ├── useCollections.ts     # CRUD коллекций
│   │   ├── useEnvironments.ts    # CRUD окружений
│   │   ├── useSwaggerSpec.ts     # Загрузка и разбор спецификации
│   │   ├── useApiRequest.ts      # Выполнение запросов через прокси
│   │   ├── useRequestHistory.ts  # История запросов
│   │   └── useEndpointCache.ts   # Кеш параметров по endpoint'ам
│   ├── components/
│   │   ├── collections/
│   │   │   ├── CollectionList.vue
│   │   │   └── CollectionForm.vue
│   │   ├── environments/
│   │   │   ├── EnvironmentSelector.vue
│   │   │   ├── EnvironmentManager.vue
│   │   │   └── EnvironmentForm.vue
│   │   └── api-tester/
│   │       ├── ApiTester.vue        # Родительский компонент, владеет состоянием
│   │       ├── EndpointTree.vue
│   │       ├── RequestForm.vue      # Получает params через props, emit при изменении
│   │       ├── RequestBodyDrawer.vue
│   │       ├── ResponseViewer.vue
│   │       └── RequestHistory.vue
│   ├── styles/
│   │   └── main.css           # BEM-стили с префиксом .apic-
│   ├── App.vue                # Корневой компонент
│   └── main.ts                # Точка входа, createApp + mount
├── js/
│   └── apicollection.js       # Скомпилированный бандл (Vite → IIFE)
├── css/
│   └── apicollection.css      # Скомпилированный CSS (Vite)
├── vite.config.ts             # Конфигурация сборки
├── tsconfig.json              # Конфигурация TypeScript
├── package.json
└── img/
    └── app-icon.svg
```

---

## База данных

### `apicollection_collection`
| Поле        | Тип                                     | Описание                            |
|-------------|-----------------------------------------|-------------------------------------|
| `id`        | INT PK AUTO_INCREMENT                   |                                     |
| `contact_id`| INT                                     | Владелец (ссылка на `wa_contact.id`)|
| `is_shared` | TINYINT(1)                              | 1 = общая, 0 = личная               |
| `title`     | VARCHAR(255)                            |                                     |
| `spec_url`  | TEXT                                    | URL до swagger.json / openapi.json  |
| `auth_type` | ENUM('none','bearer','basic','apikey')  |                                     |
| `auth_data` | TEXT                                    | JSON: токен / логин-пароль / apikey |
| `custom_headers` | TEXT                            | JSON: массив `[{name, value}, ...]` для произвольных заголовков |
| `created`   | DATETIME                                |                                     |
| `updated`   | DATETIME                                |                                     |

### `apicollection_request_history`
| Поле              | Тип         | Описание                        |
|-------------------|-------------|---------------------------------|
| `id`              | INT PK      |                                 |
| `collection_id`   | INT         | FK → apicollection_collection   |
| `contact_id`      | INT         | Кто выполнил запрос             |
| `method`          | VARCHAR(10) | GET / POST / PUT / …            |
| `path`            | VARCHAR(1000)|                                |
| `request_data`    | MEDIUMTEXT  | JSON: query_params, headers, body |
| `response_status` | INT         | HTTP-код ответа (0 = ошибка)    |
| `response_body`   | MEDIUMTEXT  |                                 |
| `executed_at`     | DATETIME    |                                 |

### `apicollection_environment` (v1.1.0+)
| Поле        | Тип                                     | Описание                            |
|-------------|-----------------------------------------|-------------------------------------|
| `id`        | INT PK AUTO_INCREMENT                   |                                     |
| `contact_id`| INT                                     | Владелец (ссылка на `wa_contact.id`)|
| `is_shared` | TINYINT(1)                              | 1 = общее, 0 = личное               |
| `name`      | VARCHAR(255)                            | Название окружения (Production, Staging и т.д.) |
| `base_url`  | VARCHAR(1000)                           | Base URL API (переопределяет URL из коллекции) |
| `auth_type` | ENUM('none','bearer','basic','apikey')  | Тип аутентификации                  |
| `auth_data` | TEXT                                    | JSON: токен / логин-пароль / apikey |
| `custom_headers` | TEXT                            | JSON: массив `[{name, value}, ...]` произвольных заголовков |
| `sort`      | INT(11)                                 | Порядок сортировки                  |
| `created`   | DATETIME                                |                                     |
| `updated`   | DATETIME                                |                                     |

### `apicollection_environment_selected` (v1.1.0+)
| Поле            | Тип     | Описание                                    |
|-----------------|---------|---------------------------------------------|
| `contact_id`    | INT PK  | Пользователь                                |
| `collection_id` | INT PK  | Коллекция                                   |
| `environment_id`| INT     | Выбранное окружение (NULL = без окружения)  |

---

## PHP-слой

### Диспетчеризация (важно!)

Webasyst`waFrontController::getController()` ищет классы в порядке:
1. `{appid}{Module}{Action}Controller`
2. `{appid}{Module}{Action}Action` — одиночный action
3. `{appid}{Module}Actions` — мульти-action; `$action` из URL передаётся как `$params` в `run()`

Для URL `?module=backend&action=collections`:
- Находит `apicollectionBackendActions` (файл `apicollectionBackend.actions.php`)
- Вызывает `run('collections')` → `collectionsAction()`

Для URL `?module=backend` (без action):
- Находит `apicollectionBackendAction` (файл `apicollectionBackend.action.php`)
- Рендерит шаблон `Backend.html`

### `apicollectionBackend.action.php` — `apicollectionBackendAction`
Единственная задача: передать `contactId` в Smarty и зарегистрировать CSS через `wa()->getResponse()->addCss()`. JS подключается прямо в шаблоне.

```php
wa()->getResponse()->addCss('css/apicollection.css', 'apicollection');
```

### `apicollectionBackend.actions.php` — `apicollectionBackendActions extends waJsonActions`

Все методы вызываются через `$.ajax` из Vue, Webasyst автоматически проверяет CSRF через `$.ajaxSetup` — **не добавляй ручные checkCsrf()**.

| Action метод           | HTTP | URL (`?module=backend&action=`) | Описание                                   |
|------------------------|------|---------------------------------|--------------------------------------------|
| `collectionsAction`    | GET  | `collections`                   | Список коллекций текущего пользователя     |
| `collectionSaveAction` | POST | `collectionSave`                | Создать или обновить коллекцию             |
| `collectionDeleteAction`| POST| `collectionDelete`              | Удалить коллекцию (только владелец)        |
| `collectionGetAction`  | GET  | `collectionGet`                 | Получить одну коллекцию (с `auth_data`)    |
| `collectionUploadFileAction` | POST | `collectionUploadFile`     | Загрузить JSON-файл спецификации; возвращает `{file, name}` |
| `collectionGetSpecAction` | GET | `collectionGetSpec`            | Получить загруженный JSON-файл спецификации по имени |
| `proxyFetchAction`     | POST | `proxyFetch`                    | Серверный прок��и — выполнить HTTP-запрос; принимает опциональный `base_url` для переопределения хоста; поддерживает `environment_id` (v1.1.0+) |
| `historyAction`        | GET  | `history`                       | История запросов по `collection_id`        |
| `environmentsAction`   | GET  | `environments`                  | Список окружений текущего пользователя (v1.1.0+) |
| `environmentSaveAction` | POST | `environmentSave`              | Создать или обновить окружение (v1.1.0+)  |
| `environmentDeleteAction` | POST | `environmentDelete`            | Удалить окружение (только владелец) (v1.1.0+) |
| `environmentGetAction` | GET  | `environmentGet`                | Получить одно окружение (с `auth_data` для владельца) (v1.1.0+) |
| `environmentSelectAction` | POST | `environmentSelect`            | Выбрать окружение для коллекции (v1.1.0+) |
| `environmentSelectedAction` | GET | `environmentSelected`         | Получить текущее выбранное окружение для коллекции (v1.1.0+) |

Ответ всегда: `{ status: 'ok', data: ... }` или `{ status: 'fail', errors: { message: '...' } }`.

Для установки ошибки используй переопределённый `setError(string $message)`:
```php
$this->setError($e->getMessage());
```

### `apicollectionCollectionModel`
Расширяет `waModel`. **Не используй** имена методов `getById`, `updateById`, `deleteById` — они зарезервированы в `waModel` и вызовут Fatal error. Текущие имена:

| Метод                                         | Описание                                       |
|-----------------------------------------------|------------------------------------------------|
| `getForUser(int $contactId): array`           | Свои + общие, ORDER BY is_shared DESC, title   |
| `getCollection(int $id, int $contactId): ?array` | Одна коллекция (своя или общая)             |
| `add(array $data): int`                       | Вставка с auto-заполнением created/updated     |
| `updateCollection(int $id, array $data): void`| Обновление (проверяет владельца)               |
| `deleteCollection(int $id): void`             | Удаление + каскадное удаление истории          |

Внутренний метод `checkOwner(int $id)` бросает `waException(403)` если текущий пользователь не владелец.

### `apicollectionRequestHistoryModel`
| Метод                                              | Описание               |
|----------------------------------------------------|------------------------|
| `addEntry(array $data): int`                       | Пишет запись истории   |
| `getByCollection(int $collectionId, int $limit): array` | История по коллекции |
| `getByUser(int $contactId, int $limit): array`     | История по пользователю|
| `deleteByCollection(int $collectionId): void`      | Каскадное удаление     |

---

## Шаблон и загрузка JS

`Backend.html` — **полноценный HTML-документ** (не фрагмент). Обязательная структура:

```html
<!DOCTYPE html>
<html>
<head>
    {$wa->css()}                          <!-- CSS Webasyst + зарегистрированный addCss -->
    <script src="{$wa_url}wa-content/js/jquery/jquery-3.6.0.min.js"></script>
    <script src="{$wa_url}wa-content/js/jquery-wa/wa.js?v=..."></script>
    <!-- jQuery нужен ДО {$wa->header()}, иначе $ is not defined -->
</head>
<body>
<div id="wa">
    {$wa->header()}                       <!-- Шапка с навигацией Webasyst -->
    <div id="wa-app">
        <div id="apicollection-app">…</div>
    </div>
</div>
{$wa->js()}                               <!-- Системные JS Webasyst (без параметра!) -->
<script>
(function() {
    var s = document.createElement('script');
    s.src = '{$wa_app_static_url}js/apicollection.js?v=…';
    document.body.appendChild(s);         <!-- Injект после {$wa->js()}, так SPA не ломает jQuery -->
})();
</script>
</body>
</html>
```

**Ключевые правила:**
- `{$wa->css()}` и `{$wa->js()}` вызываются **без параметров** — они выводят то, что зарегистрировано через `addCss`/`addJs` в PHP
- `{$wa->js('filename')}` с параметром — **не подключает файл**, параметр трактуется как `$strict` (bool)
- JS приложения инжектируется через `document.createElement('script')` после `{$wa->js()}`, чтобы избежать конфликтов с jQuery
- `{$wa_app_static_url}` — автоматическая Smarty-переменная, URL статики текущего приложения (`/wa-apps/apicollection/`)

---

## Vue 3 SPA — Фронтенд

Фронтенд собран с помощью **Vite** из исходников в `src/`. Точка входа — `src/main.ts`. Итоговый бандл: `js/apicollection.js` (IIFE формат для совместимости с Webasyst).

**Стек:** Vue 3.4+, TypeScript (strict), Vite 5+, `<script setup>`, Composition API. Options API отключён (`__VUE_OPTIONS_API__: false`).

### Команды сборки

```bash
cd wa-apps/apicollection

# Development с watch (HMR)
npm run dev

# Production бандл
npm run build

# Проверка типов
npm run type-check
```

### API-слой (`src/api/index.ts`)

`apiFetch<T>()` — типизированная обёртка над `$.ajax`. CSRF-токен добавляется автоматически через `$.ajaxSetup` Webasyst.

```ts
apiFetch<Collection[]>('collections')
apiFetch<void>('collectionSave', { method: 'POST', data: payload })
```

**Важно: `cache: true`** — без него jQuery добавляет `?_=timestamp`, что вызывает 400 от Webasyst.

### Composables (`src/composables/`)

| Файл | Экспортирует | Назначение |
|------|-------------|------------|
| `useCollections.ts` | `useCollections()` | CRUD коллекций, загрузка списка |
| `useEnvironments.ts` | `useEnvironments()` | CRUD окружений, выбор активного |
| `useSwaggerSpec.ts` | `useSwaggerSpec(collectionRef)` | Загрузка и парсинг Swagger/OpenAPI, построение дерева тегов |
| `useApiRequest.ts` | `useApiRequest()` | Состояние params/body/response, выполнение запроса через прокси |
| `useRequestHistory.ts` | `useRequestHistory(collectionIdRef)` | Загрузка истории, `load()`, `addItem()` |
| `useEndpointCache.ts` | `useEndpointCache()` | Кеш `pathParams/queryParams/headerParams/bodyContent` по ключу `METHOD:PATH` |

**Важно:** каждый вызов composable создаёт **новый экземпляр**. Если дочернему компоненту нужен доступ к тому же состоянию — передавать через props/emits, а не вызывать composable повторно.

### Компоненты (`src/components/`)

| Компонент | Путь | Назначение |
|-----------|------|------------|
| `App.vue` | `src/` | Корневой: лейаут `.flexbox`, сайдбар с коллекциями, основная область |
| `CollectionList.vue` | `collections/` | `ul.menu.ellipsis`, кнопки действий в слоте `.count` |
| `CollectionForm.vue` | `collections/` | Модальное окно `.dialog`, форма на `.fields` |
| `EnvironmentSelector.vue` | `environments/` | Dropdown выбора окружения в тулбаре |
| `EnvironmentManager.vue` | `environments/` | Модальное окно управления окружениями |
| `EnvironmentForm.vue` | `environments/` | Форма создания/редактирования окружения |
| `ApiTester.vue` | `api-tester/` | **Владеет состоянием:** `useApiRequest`, `useEndpointCache`, `useRequestHistory`. Передаёт params в `RequestForm` через props |
| `EndpointTree.vue` | `api-tester/` | Двухуровневый `ul.menu`: теги → endpoints |
| `RequestForm.vue` | `api-tester/` | Поля параметров; **не вызывает** composables — получает данные через props, изменения через emits |
| `RequestBodyDrawer.vue` | `api-tester/` | Drawer: вкладки «Модель» (JSON Schema + `$ref`) и «Пример» (автогенерированный JSON) |
| `ResponseViewer.vue` | `api-tester/` | Отображение ответа: статус, заголовки, тело |
| `RequestHistory.vue` | `api-tester/` | Таблица истории запросов с кнопкой повтора |

### Архитектурные решения фронтенда

- **Параметры endpoint'а** — `ApiTester` владеет `pathParams/queryParams/headerParams/bodyContent` (из `useApiRequest`), передаёт в `RequestForm` как props; `RequestForm` emit'ит изменения обратно через `update:*` события
- **Кеш параметров** — при переключении endpoint'а `ApiTester` сохраняет текущие параметры через `useEndpointCache`, при возврате восстанавливает
- **Обновление истории** — после выполнения запроса `handleExecute()` вызывает `reloadHistory()`
- **Ответ** — очищается при переключении endpoint'а, сохраняется при повторном запросе
- **DevTools** — включены только в development сборке (`__VUE_PROD_DEVTOOLS__: mode === 'development'`)

### UI 2.0 — нативные компоненты

Приложение использует нативные компоненты Webasyst UI 2.0; кастомный CSS сведён к минимуму:

- **Лейаут**: `.flexbox` + `.sidebar` (с модификаторами `.blank`, `.flexbox`, `.width-*`) + `.content`
- **Список коллекций**: `ul.menu.ellipsis` — кнопки редактирования/удаления в слоте `.count` (правый край строки)
- **Бейджи HTTP-методов**: `methodBadgeClass(method)` возвращает `badge squared [color]`; цвета: `green`(GET), `blue`(POST), `orange`(PUT), — (DELETE), `yellow`(PATCH), `purple`(HEAD), `gray`(OPTIONS)
- **Дерево endpoints**: двухуровневый `ul.menu` — первый уровень теги, второй — endpoints; активный элемент: `li.selected`
- **Формы параметров**: `.fields` > `.field` > `.name` + `.value`; заголовки секций: `div.hint.small.uppercase`
- **Диалог**: нативный `.dialog.dialog-opened` с `.dialog-background`, `.dialog-header`, `.dialog-content`, `.dialog-footer`

### Загрузка спецификации
`useSwaggerSpec` загружает и парсит `swagger.json` / `openapi.json` без сторонних библиотек. Поддерживаются Swagger 2.0 и OpenAPI 3.x. Если `spec_source === 'file'` — загружает через серверный action, иначе напрямую по URL.

---

## Права доступа

| Право            | Описание                                          |
|------------------|---------------------------------------------------|
| `backend`        | Доступ к приложению (стандартное право Webasyst)  |
| `manage_shared`  | Создание и редактирование общих коллекций         |

Проверка: `wa()->getUser()->getRights('apicollection', 'manage_shared')`.

---

## Безопасность (proxyFetch)

- Разрешены только `http://` и `https://` URL
- Запросы к любым адресам (включая `localhost`, `127.x`, приватные сети) разрешены — приложение используется только в бэкенде авторизованными пользователями
- Максимальный размер ответа: **5 МБ** (обрезается с маркером `[TRUNCATED]`)
- Чувствительные заголовки ответа (`Server`, `X-Powered-By`) удаляются перед возвратом клиенту
- История пишется всегда, даже при ошибке

## Base URL (переопределение хоста)

`proxyFetchAction` принимает необязательный POST-параметр `base_url`. Если передан — используется вместо хоста из `spec_url` коллекции. Позволяет отправлять запросы на любой тестовый стенд без изменения коллекции.

В `ApiTester` поле Base URL инициализируется из `extractBaseUrl(spec_url)` при открытии коллекции и доступно для редактирования перед выполнением каждого запроса.

## Произвольные заголовки (Custom Headers)

Для решения проблемы с несколькими методами аутентификации одновременно (например, Basic Auth + API-ключ в заголовке), каждая коллекция может содержать список произвольных заголовков, которые автоматически добавляются ко всем запросам.

### Структура данных

Поле `custom_headers` в таблице `apicollection_collection` хранит JSON-массив:
```json
[
  { "name": "X-API-Key", "value": "secret123" },
  { "name": "X-Custom-Header", "value": "custom-value" }
]
```

### Использование в PHP

В `proxyFetchAction`:
```php
// Получаем произвольные заголовки из коллекции
$customHeadersRaw = $collection['custom_headers'] ?? '';
$customHeaders = $customHeadersRaw ? (json_decode($customHeadersRaw, true) ?? []) : [];
$customHeadersFormatted = [];
if (is_array($customHeaders)) {
    foreach ($customHeaders as $h) {
        if (!empty($h['name']) && !empty($h['value'])) {
            $customHeadersFormatted[$h['name']] = $h['value'];
        }
    }
}

// Объединяем с заголовками аутентификации и параметрами запроса
$headers = array_merge($authHeaders, $customHeadersFormatted, is_array($extraHeaders) ? $extraHeaders : []);
```

Порядок приоритета заголовков (последний побеждает):
1. Заголовки аутентификации (`Authorization`, `X-API-Key` и т.д.)
2. Произвольные заголовки коллекции
3. Заголовки, переданные в текущем запросе (из UI)

### Использование в Vue

В компоненте `CollectionForm`:
- Поле `form.custom_headers` — массив объектов `{ name, value }`
- При редактировании коллекции заголовки загружаются из `props.initial?.custom_headers`
- При сохранении отправляются как `JSON.stringify(form.custom_headers)`
- UI позволяет добавлять/удалять заголовки через кнопки

### Примеры использования

**Двойная аутентификация (Basic + API-ключ):**
- Аутентификация коллек��ии: `Basic Auth` (username/password)
- Произвольные заголовки: `X-API-Key: secret123`

**Несколько API-ключей:**
- Аутентификация: `none`
- Произвольные заголовки:
  - `X-API-Key-1: key1`
  - `X-API-Key-2: key2`

**Корпоративные требования:**
- Произвольные заголовки:
  - `X-Request-ID: {uuid}`
  - `X-Client-Version: 1.0`
  - `X-Tenant-ID: tenant123`

---

## Загрузка файлов спецификаций

Коллекции поддерживают два источника спецификаций:

### URL-источник (оригинальный)
- Спецификация загружается по HTTP(S) URL
- Поле `spec_url` содержит URL
- Поле `spec_source` = `'url'`
- Поле `spec_file` = `NULL`

### Файловый источник (новое)
- JSON-файл загружается через форму
- Файлы сохраняются в `wa-data/apicollection/specs/` с именами вида `spec_{timestamp}_{random_hex}.json`
- Поле `spec_file` содержит имя файла
- Поле `spec_source` = `'file'`
- Поле `spec_url` = `NULL`
- При удалении коллекции файл автоматически удаляется

### Валидация загруженных файлов
- Расширение: только `.json`
- Максимальный размер: **10 МБ**
- Структура: должна содержать поле `paths`, `swagger` или `openapi`
- Формат: валидный JSON

### Получение спецификации в ApiTester
```javascript
if (collection.spec_source === 'file') {
    // Загружаем из файла
    specUrl = '?module=backend&action=collectionGetSpec&file=' + collection.spec_file;
} else {
    // Загружаем из URL
    specUrl = collection.spec_url;
}
```

---

## Типичные ловушки

| Проблема | Причина | Решение |
|----------|---------|---------|
| `Fatal error: Declaration must be compatible` | Метод перекрывает зарезервированный в `waModel` | Переименовать: `getCollection` вместо `getById`, `updateCollection` вместо `updateById` и т.д. |
| `$ is not defined` | jQuery не загружен до `{$wa->header()}` | Подключать jQuery вручную в `<head>` до header |
| `{$wa->js('file.js')}` не подключает файл | Параметр — это `$strict`, не имя файла | Использовать `wa()->getResponse()->addJs('path', 'appid')` из PHP |
| JS не выполняется при SPA-навигации | Webasyst грузит страницы через AJAX, innerHTML не запускает `<script>` | Инжектировать через `document.createElement('script')` |
| `400 Bad parameters` на GET-запросе | jQuery добавляет `?_=timestamp` | Указывать `cache: true` в `$.ajax` |
| URL `?action=X?param=Y` (двойной `?`) | Параметры склеены с action-строкой | Передавать GET-параметры через `data: {}` в `$.ajax`, jQuery сам добавит `&` |
| Два `<span>` внутри `ul.menu li > a` занимают одинаковую ширину | `li > a` — flex-контейнер, дочерние `<span>` получают `flex:1` | Добавить `style="flex:none"` на span, который должен быть фиксированной ширины (напр. обёртка бейджа) |
| MySQL поле возвращается как строка `"0"`, в JS оно truthy | MySQL всегда отдаёт строки | Кастовать `(int)` в PHP перед `json_encode` для булевых/числовых полей (`is_shared`, `id` и т.п.) |
