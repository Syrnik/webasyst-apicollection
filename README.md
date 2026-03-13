# API Collection — приложение для Webasyst

Менеджер Swagger/OpenAPI коллекций в бэкенде Webasyst. Позволяет сохранять API-спецификации, просматривать дерево эндпоинтов и выполнять HTTP-запросы через серверный прокси прямо из браузера.

**Версия:** 1.1.0 · **UI:** Webasyst 2.0 · **Стек:** PHP 7.4+, Vue 3, TypeScript, Vite

---

[![PHP Compatibility](https://github.com/Syrnik/webasyst-apicollection/actions/workflows/main.yml/badge.svg)](https://github.com/Syrnik/webasyst-apicollection/actions/workflows/main.yml)

---

## Возможности

- **Коллекции** — сохраняй ссылки на `swagger.json` / `openapi.json` или загружай файлы спецификаций локально
- **Личные и общие коллекции** — личные видишь только ты, общие доступны всем пользователям бэкенда
- **Окружения** — переопределяй Base URL и параметры аутентификации для разных стендов (prod, staging, dev)
- **Интерактивный API-тестер** — выбирай эндпоинт, заполняй параметры, выполняй запрос прямо в браузере
- **Серверный прокси** — все запросы к внешнему API уходят через PHP-прокси, CORS не мешает
- **История запросов** — все выполненные запросы сохраняются, можно повторить одним кликом
- **Аутентификация** — поддержка Bearer, Basic Auth, API Key и произвольных заголовков
- **Swagger 2.0 и OpenAPI 3.x** — оба формата разбираются без сторонних библиотек

---

## Установка

Приложение устанавливается как обычное приложение Webasyst — скопируй папку `apicollection/` в `wa-apps/` и зарегистрируй его в `wa-config/apps.php`:

```php
// wa-config/apps.php
return [
    // ... другие приложения
    'apicollection' => [],
];
```

При первом открытии Webasyst автоматически применит миграцию и создаст необходимые таблицы в БД.

---

## Сборка фронтенда

Исходники фронтенда находятся в `src/` (Vue 3 + TypeScript + Vite). Скомпилированный бандл уже лежит в `js/apicollection.js` и `css/apicollection.css` — для обычного использования пересборка не нужна.

### Требования

- Node.js 18+
- npm

### Команды

```bash
cd wa-apps/apicollection

# Установить зависимости
npm install

# Production-сборка (js/ и css/)
npm run build

# Development-сборка с watch (пересборка при изменениях)
npm run build:dev

# Проверка TypeScript-типов без сборки
npm run type-check
```

Итоговые файлы после сборки:
- `js/apicollection.js` — IIFE-бандл (Vue app)
- `css/apicollection.css` — стили

---

## Структура проекта

```
wa-apps/apicollection/
├── lib/
│   ├── actions/backend/
│   │   ├── apicollectionBackend.action.php    # Главная страница (SPA-оболочка)
│   │   └── apicollectionBackend.actions.php   # JSON API — все AJAX-эндпоинты
│   ├── models/
│   │   ├── apicollectionCollection.model.php
│   │   └── apicollectionRequestHistory.model.php
│   └── config/
│       ├── app.php        # Метаданные приложения
│       ├── db.php         # Схема таблиц
│       └── rights.php     # Права доступа
├── src/                   # Исходники фронтенда
│   ├── api/               # apiFetch() — обёртка над $.ajax
│   ├── composables/       # useCollections, useEnvironments, useApiRequest и др.
│   ├── components/        # Vue-компоненты
│   ├── types/             # TypeScript-типы
│   └── utils/             # Парсинг Swagger, форматирование, валидация
├── js/
│   └── apicollection.js   # Скомпилированный бандл
├── css/
│   └── apicollection.css  # Скомпилированные стили
├── vite.config.ts
├── tsconfig.json
└── package.json
```

---

## Права доступа

| Право            | Описание                                            |
|------------------|-----------------------------------------------------|
| `backend`        | Доступ к приложению                                 |
| `manage_shared`  | Создание и редактирование общих коллекций           |

---

## Для разработчиков

Подробная документация по архитектуре, API-эндпоинтам, структуре БД, компонентам Vue и типичным ловушкам — в файле [`AGENTS.md`](AGENTS.md).
