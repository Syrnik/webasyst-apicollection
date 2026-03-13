# API Collection — Менеджер Swagger/OpenAPI коллекций

<p class="wi-intro">
<strong>API Collection</strong> — это инструмент для работы со Swagger/OpenAPI спецификациями прямо в бэкенде Webasyst. Сохраняйте коллекции API, выполняйте HTTP-запросы через встроенный прокси и тестируйте endpoints без сторонних инструментов вроде Postman.
</p>

<div class="wi-columns fixed space-30">
    <div class="wi-column">
        <div class="wi-section">
            <div class="wi-section-icon">
                <span class="wi-icon gray size-52"><i class="fas fa-cloud-download-alt"></i></span>
            </div>
            <h4 class="wi-section-title">Импорт из URL или файла</h4>
            <p><strong>Загружайте спецификации</strong> по ссылке на <code>swagger.json</code>/<code>openapi.json</code> или загружайте JSON-файлы напрямую с диска.</p>
        </div>
        <div class="wi-section">
            <div class="wi-section-icon">
                <span class="wi-icon gray size-52"><i class="fas fa-layer-group"></i></span>
            </div>
            <h4 class="wi-section-title">Профили окружения</h4>
            <p><strong>Создавайте профили</strong> Production, Staging, Local для быстрого переключения между серверами без изменения коллекции.</p>
        </div>
    </div>
    <div class="wi-column">
        <div class="wi-section">
            <div class="wi-section-icon">
                <span class="wi-icon gray size-52"><i class="fas fa-shield-alt"></i></span>
            </div>
            <h4 class="wi-section-title">Безопасный прокси</h4>
            <p><strong>Все запросы</strong> к внешним API проходят через серверный прокси с защитой от SSRF и ограничением размера ответа 5 МБ.</p>
        </div>
        <div class="wi-section">
            <div class="wi-section-icon">
                <span class="wi-icon gray size-52"><i class="fas fa-history"></i></span>
            </div>
            <h4 class="wi-section-title">История запросов</h4>
            <p><strong>Сохраняйте историю</strong> всех выполненных запросов с параметрами, ответами и статусами для последующего анализа.</p>
        </div>
    </div>
</div>

<h3>Возможности приложения</h3>

<ul class="wi-list disc">
    <li><strong>Два источника спецификаций</strong> — ссылка на URL или загрузка JSON-файла с диска</li>
    <li><strong>Просмотр дерева endpoints</strong> — навигация по тегам и методам API в удобном древовидном интерфейсе</li>
    <li><strong>Выполнение HTTP-запросов</strong> — GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS прямо из браузера</li>
    <li><strong>Аутентификация</strong> — поддержка <code>none</code>, <code>bearer</code>, <code>basic</code>, <code>apikey</code></li>
    <li><strong>Произвольные заголовки</strong> — добавление кастомных HTTP-заголовков к каждому запросу</li>
    <li><strong>Профили окружения</strong> — переключение между Production, Staging, Development без редактирования коллекции</li>
    <li><strong>Просмотр схемы запроса</strong> — для body-параметров отображается JSON Schema и пример структуры</li>
    <li><strong>История запросов</strong> — полная история с параметрами, ответами и возможностью повторного выполнения</li>
    <li><strong>Личные и общие коллекции</strong> — делитесь коллекциями с командой или храните приватно</li>
    <li><strong>Webasyst UI 2.0</strong> — нативный интерфейс в стиле бэкенда Webasyst</li>
</ul>

<h3>Два способа загрузки спецификаций</h3>

<p>Приложение поддерживает <strong>два формата источников</strong> для Swagger/OpenAPI спецификаций:</p>

<div class="wi-columns fixed space-30">
    <div class="wi-column">
        <div class="wi-section wi-bordered wi-rounded">
            <h4><span class="wi-icon black size-22"><i class="fas fa-link"></i></span>&nbsp;URL-источник</h4>
            <p>Укажите прямую ссылку на <code>swagger.json</code> или <code>openapi.json</code>. Спецификация загружается при каждом обращении к коллекции.</p>
            <ul class="wi-list checklist">
                <li>Актуальная версия спецификации</li>
                <li>Не занимает место на сервере</li>
                <li>Требуется доступность URL</li>
            </ul>
        </div>
    </div>
    <div class="wi-column">
        <div class="wi-section wi-bordered wi-rounded">
            <h4><span class="wi-icon black size-22"><i class="fas fa-file-upload"></i></span>&nbsp;Загрузка файла</h4>
            <p>Загрузите JSON-файл спецификации напрямую с диска. Файл сохраняется в <code>wa-data/apicollection/specs/</code>.</p>
            <ul class="wi-list checklist">
                <li>Работает без доступа к интернету</li>
                <li>Для локальных и внутренних API</li>
                <li>Максимальный размер — 10 МБ</li>
            </ul>
        </div>
    </div>
</div>

<h3>Профили окружения</h3>

<blockquote class="wi-columns space-20 align-left">
    <div class="wi-image">
        <span class="wi-icon size-52"><i class="fas fa-lightbulb"></i></span>
    </div>
    <div class="wi-text middle">
        <p><strong>Зачем нужны профили?</strong> Один API часто развёрнут на нескольких серверах: продакшен, тестовый стенд, локальная разработка. Профили окружения позволяют переключаться между ними без создания дубликатов коллекции.</p>
    </div>
</blockquote>

<p>Для каждой коллекции можно создать <strong>несколько профилей окружения</strong>:</p>

<ul class="wi-list decimal">
    <li><strong>Production</strong> — боевой сервер <code>https://api.example.com</code></li>
    <li><strong>Staging</strong> — тестовый стенд <code>https://staging-api.example.com</code></li>
    <li><strong>Local</strong> — локальная разработка <code>http://localhost:8080</code></li>
</ul>

<p>Каждый профиль хранит:</p>

<ul class="wi-list disc">
    <li><strong>Base URL</strong> — базовый адрес API (переопределяет URL из коллекции)</li>
    <li><strong>Аутентификацию</strong> — отдельный токен/логин-пароль для каждого окружения</li>
    <li><strong>Произвольные заголовки</strong> — уникальные заголовки для конкретного окружения</li>
</ul>

<p>Переключение между профилями происходит <strong>в один клик</strong> через выпадающий список в панели инструментов.</p>

<h3>Просмотр схемы и примера body-параметров</h3>

<p>Для endpoints, принимающих данные в теле запроса (POST, PUT, PATCH), приложение автоматически отображает:</p>

<div class="wi-columns fixed space-30">
    <div class="wi-column">
        <div class="wi-section">
            <h4><span class="wi-icon black size-22"><i class="fas fa-book"></i></span>&nbsp;Вкладка «Модель»</h4>
            <p>Описание JSON Schema с типами полей, обязательными параметрами и вложенными объектами. Поддерживаются <code>$ref</code> ссылки на компоненты спецификации.</p>
        </div>
    </div>
    <div class="wi-column">
        <div class="wi-section">
            <h4><span class="wi-icon black size-22"><i class="fas fa-code"></i></span>&nbsp;Вкладка «Пример»</h4>
            <p>Автоматически сгенерированный пример структуры запроса на основе схемы. Используйте как шаблон для заполнения реальных данных.</p>
        </div>
    </div>
</div>

<p>Это особенно полезно при работе со <strong>сложными вложенными объектами</strong> и массивами — вы сразу видите ожидаемую структуру без изучения документации API.</p>

<h3>Безопасность и прокси</h3>

<p>Все запросы к внешним API выполняются через <strong>серверный прокси</strong> <code>proxyFetchAction</code>:</p>

<ul class="wi-list checklist">
    <li>Разрешены только <code>http://</code> и <code>https://</code> URL</li>
    <li>Защита от SSRF-атак встроена</li>
    <li>Максимальный размер ответа — 5 МБ (с маркером <code>[TRUNCATED]</code>)</li>
    <li>Чувствительные заголовки ответа (<code>Server</code>, <code>X-Powered-By</code>) удаляются</li>
    <li>История запросов пишется всегда, даже при ошибке</li>
</ul>

<h3>Произвольные заголовки (Custom Headers)</h3>

<p>Для случаев, когда требуется <strong>несколько методов аутентификации одновременно</strong> (например, Basic Auth + API-ключ в заголовке), каждая коллекция поддерживает список произвольных HTTP-заголовков:</p>

<pre class="prettyprint">[
  { "name": "X-API-Key", "value": "secret123" },
  { "name": "X-Request-ID", "value": "uuid-123" },
  { "name": "X-Tenant-ID", "value": "tenant123" }
]</pre>

<p>Заголовки автоматически добавляются ко всем запросам коллекции. Приоритет (последний побеждает):</p>

<ol class="wi-list decimal">
    <li>Заголовки аутентификации</li>
    <li>Произвольные заголовки коллекции</li>
    <li>Заголовки из текущего запроса (UI)</li>
</ol>

<h3>Технические требования</h3>

<ul class="wi-list disc">
    <li><strong>Webasyst Framework</strong> — версия 2.6+</li>
    <li><strong>PHP</strong> — 7.4+</li>
    <li><strong>Браузер</strong> — современный браузер с поддержкой ES6+ (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)</li>
    <li><strong>Vue 3</strong> — встроен в сборку приложения (не требуется установка)</li>
</ul>

<h3>Структура базы данных</h3>

<p>Приложение создаёт 4 таблицы в БД Webasyst:</p>

<ul class="wi-list disc">
    <li><code>apicollection_collection</code> — коллекции спецификаций</li>
    <li><code>apicollection_environment</code> — профили окружения</li>
    <li><code>apicollection_environment_selected</code> — выбранные профили для коллекций</li>
    <li><code>apicollection_request_history</code> — история выполненных запросов</li>
</ul>

<h3>Права доступа</h3>

<ul class="wi-list checklist">
    <li><strong>backend</strong> — доступ к приложению (стандартное право Webasyst)</li>
    <li><strong>manage_shared</strong> — создание и редактирование общих коллекций</li>
</ul>

<h3>Установка и обновление</h3>

<ol class="wi-list decimal">
    <li>Установите приложение через <strong>Инсталлер</strong> или скопируйте в <code>wa-apps/apicollection/</code></li>
    <li>Откройте бэкенд Webasyst — приложение автоматически создаст таблицы БД</li>
    <li>Включите право <code>manage_shared</code> для групп пользователей, которым разрешено создавать общие коллекции</li>
    <li>Для обновления сборки фронтенда выполните:<br>
    <code>cd wa-apps/apicollection && npm install && npm run build</code></li>
</ol>

<h3>Примеры использования</h3>

<div class="wi-section wi-bordered wi-rounded">
    <h4><span class="wi-icon black size-22"><i class="fas fa-shopping-cart"></i></span>&nbsp;Разработка интеграций</h4>
    <p>Тестируйте API сторонних сервисов (платёжные системы, службы доставки, CRM) без переключения между окнами браузера. Сохраняйте коллекции с готовыми настройками аутентификации.</p>
</div>

<div class="wi-section wi-bordered wi-rounded">
    <h4><span class="wi-icon black size-22"><i class="fas fa-code-branch"></i></span>&nbsp;Отладка собственного API</h4>
    <p>Создайте коллекцию для вашего API на основе Swagger-спецификации. Переключайтесь между Production и Staging окружениями для проверки исправлений.</p>
</div>

<div class="wi-section wi-bordered wi-rounded">
    <h4><span class="wi-icon black size-22"><i class="fas fa-users"></i></span>&nbsp;Командная работа</h4>
    <p>Поделитесь общей коллекцией с командой разработчиков. Все участники видят историю запросов и могут воспроизвести проблемы.</p>
</div>

<blockquote>
<strong>API Collection</strong> — это Postman прямо в бэкенде Webasyst. Импортируйте Swagger/OpenAPI спецификации, выполняйте запросы и отлаживайте интеграции без установки дополнительного софта.
</blockquote>
