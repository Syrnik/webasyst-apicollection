<?php

declare(strict_types=1);

class apicollectionBackendActions extends waJsonActions
{
    /** @var apicollectionCollectionModel */
    private $collectionModel;

    /** @var apicollectionRequestHistoryModel */
    private $historyModel;

    /** @var apicollectionEnvironmentModel */
    private $environmentModel;

    /** @var apicollectionEnvironmentSelectedModel */
    private $envSelectedModel;

    protected function preExecute(): void
    {
        $this->collectionModel = new apicollectionCollectionModel();
        $this->historyModel = new apicollectionRequestHistoryModel();
        $this->environmentModel = new apicollectionEnvironmentModel();
        $this->envSelectedModel = new apicollectionEnvironmentSelectedModel();
    }

    // ─── GET: список коллекций для текущего пользователя ───────────────────────

    public function collectionsAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $collections = $this->collectionModel->getForUser($contactId);

            // Не возвращаем auth_data в списке, приводим типы
            foreach ($collections as &$c) {
                unset($c['auth_data']);
                $c['id'] = (int)$c['id'];
                $c['contact_id'] = (int)$c['contact_id'];
                $c['is_shared'] = (int)$c['is_shared'];
            }
            unset($c);

            $this->response = $collections;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── POST: создать или обновить коллекцию ───────────────────────────────────

    public function collectionSaveAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $id = (int)waRequest::post('id');
            $title = trim((string)waRequest::post('title'));
            $specSource = waRequest::post('spec_source', 'url');  // 'url' или 'file'
            $specUrl = trim((string)waRequest::post('spec_url', ''));
            $specFile = trim((string)waRequest::post('spec_file', ''));
            $isShared = (int)(bool)waRequest::post('is_shared');
            $authType = waRequest::post('auth_type', 'none');
            $authData = waRequest::post('auth_data', '');
            $customHeaders = waRequest::post('custom_headers');

            // Валидация
            if (!$title) {
                throw new waException('Поле «Название» обязательно');
            }
            if (mb_strlen($title) > 255) {
                throw new waException('Название не должно превышать 255 символов');
            }

            // Валидация источника спецификации
            if ($specSource === 'url') {
                if (!$specUrl) {
                    throw new waException('Поле «URL спецификации» обязательно');
                }
                if (!preg_match('#^https?://#i', $specUrl)) {
                    throw new waException('URL спецификации должен начинаться с http:// или https://');
                }
            } elseif ($specSource === 'file') {
                if (!$specFile) {
                    throw new waException('Файл спецификации не загружен');
                }
            } else {
                throw new waException('Неверный источник спецификации');
            }

            // Проверка права на создание общих коллекций
            if ($isShared && !wa()->getUser()->getRights('apicollection', 'manage_shared')) {
                throw new waException('Нет прав для создания общих коллекций', 403);
            }

            // auth_data: нормализуем в JSON-строку
            if (is_array($authData)) {
                $authData = json_encode($authData, JSON_UNESCAPED_UNICODE);
            }

            // custom_headers: нормализуем в JSON-строку
            if (is_string($customHeaders)) {
                try {
                    $customHeaders = waUtils::jsonDecode($customHeaders, true);
                } catch (waException $e) {
                    $customHeaders = null;
                }
            }

            if (is_array($customHeaders)) {
                // Фильтруем пустые заголовки
                $customHeaders = array_filter($customHeaders, function ($h) {
                    return !empty($h['name']) || !empty($h['value']);
                });
                $customHeaders = $customHeaders ? json_encode($customHeaders, JSON_UNESCAPED_UNICODE) : null;
            } else {
                $customHeaders = null;
            }

            $data = [
                'title'          => $title,
                'spec_source'    => $specSource,
                'spec_url'       => $specSource === 'url' ? $specUrl : null,
                'spec_file'      => $specSource === 'file' ? $specFile : null,
                'is_shared'      => $isShared,
                'auth_type'      => in_array($authType, ['none', 'bearer', 'basic', 'apikey']) ? $authType : 'none',
                'auth_data'      => $authData ?: null,
                'custom_headers' => $customHeaders,
            ];

            if ($id) {
                $this->collectionModel->updateCollection($id, $data);
            } else {
                $data['contact_id'] = $contactId;
                $id = $this->collectionModel->add($data);
            }

            $this->response = ['id' => $id];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── POST: удалить коллекцию ────────────────────────────────────────────────

    public function collectionDeleteAction(): void
    {
        try {
            $id = (int)waRequest::post('id');
            if (!$id) {
                throw new waException('Не передан id');
            }
            $this->collectionModel->deleteCollection($id);
            $this->response = [];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── GET: одна коллекция (включая auth_data — только владельцу) ────────────

    public function collectionGetAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $id = (int)waRequest::get('id');
            $collection = $this->collectionModel->getCollection($id, $contactId);

            if (!$collection) {
                throw new waException('Коллекция не найдена', 404);
            }

            // auth_data показываем только владельцу
            if ((int)$collection['contact_id'] !== (int)$contactId) {
                unset($collection['auth_data']);
            }

            $collection['id'] = (int)$collection['id'];
            $collection['contact_id'] = (int)$collection['contact_id'];
            $collection['is_shared'] = (int)$collection['is_shared'];

            $this->response = $collection;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── GET: получить загруженный файл спецификации ──────────────────────────────

    public function collectionGetSpecAction(): void
    {
        try {
            $file = waRequest::get('file');
            if (!$file) {
                throw new waException('Файл не указан');
            }

            // Безопасность: проверяем, что это валидное имя файла
            if (preg_match('#[/\\\\]#', $file) || !preg_match('#^spec_\d+_[a-f0-9]+\.(json|yaml|yml)$#', $file)) {
                throw new waException('Неверное имя файла');
            }

            $filePath = wa()->getDataPath('specs/' . $file, false);
            if (!file_exists($filePath)) {
                throw new waException('Файл не найден', 404);
            }

            // Читаем и возвращаем JSON
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new waException('Не удалось прочитать файл');
            }

            // Удаляем BOM (Byte Order Mark) если присутствует
            if (substr($content, 0, 3) === "\xef\xbb\xbf") {
                $content = substr($content, 3);
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === 'json') {
                $parsed = json_decode($content, true);
                if ($parsed === null) {
                    throw new waException('Файл содержит некорректный JSON');
                }
                $this->response = ['content' => $content, 'format' => 'json'];
            } else {
                // YAML — отдаём строку, фронтенд распарсит через js-yaml
                $this->response = ['content' => $content, 'format' => 'yaml'];
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── POST: загрузить спецификацию с внешнего URL через прокси ────────────────

    public function proxyLoadSpecAction(): void
    {
        try {
            $collectionId = (int)waRequest::post('collection_id');
            $specUrl = trim((string)waRequest::post('spec_url', ''));
            $contactId = (int)wa()->getUser()->getId();

            if (!$specUrl) {
                throw new waException('URL спецификации не указан');
            }

            if (!preg_match('#^https?://#i', $specUrl)) {
                throw new waException('URL должен начинаться с http:// или https://');
            }

            // Проверяем доступ к коллекции (если передана)
            if ($collectionId) {
                $collection = $this->collectionModel->getCollection($collectionId, $contactId);
                if (!$collection) {
                    throw new waException('Коллекция не найдена', 404);
                }
            }

            // Загружаем спецификацию через waNet (обходит CORS)
            $net = new waNet(
                [
                    'timeout'            => 30,
                    'format'             => waNet::FORMAT_RAW,
                    'request_format'     => waNet::FORMAT_RAW,
                    'verify'             => false,
                    'expected_http_code' => null,
                ]
            );

            try {
                $responseBody = $net->query($specUrl, null, 'GET');
            } catch (waNetException $e) {
                throw new waException('Не удалось загрузить спецификацию: ' . $e->getMessage());
            }

            // Проверяем HTTP статус код
            $httpCode = (int)$net->getResponseHeader('http_code');
            if ($httpCode >= 400) {
                $errorMsg = "HTTP {$httpCode}";
                if ($httpCode === 404) {
                    $errorMsg = 'Спецификация не найдена (HTTP 404)';
                } elseif ($httpCode === 403) {
                    $errorMsg = 'Доступ запрещён (HTTP 403)';
                } elseif ($httpCode === 401) {
                    $errorMsg = 'Требуется аутентификация (HTTP 401)';
                } elseif ($httpCode >= 500) {
                    $errorMsg = "Ошибка сервера (HTTP {$httpCode})";
                }
                throw new waException($errorMsg);
            }

            // Удаляем BOM (Byte Order Mark) если присутствует
            if (substr($responseBody, 0, 3) === "\xef\xbb\xbf") {
                $responseBody = substr($responseBody, 3);
            }

            // Определяем формат по Content-Type или расширению URL
            $contentTypeHeader = $net->getResponseHeader('content-type') ?? '';
            $isYaml = preg_match('#(yaml|yml)#i', $contentTypeHeader)
                || preg_match('#\.(yaml|yml)(\?|$)#i', $specUrl);

            if ($isYaml) {
                // YAML — отдаём строку, фронтенд распарсит через js-yaml
                $this->response = ['content' => $responseBody, 'format' => 'yaml'];
            } else {
                $json = json_decode($responseBody, true);
                if ($json === null) {
                    // Если JSON не распарсился, показываем первые 200 символов ответа для отладки
                    $preview = substr($responseBody, 0, 200);
                    if (strlen($responseBody) > 200) {
                        $preview .= '...';
                    }
                    throw new waException('Ответ содержит некорректный JSON. Ответ: ' . $preview);
                }
                if (!isset($json['paths']) && !isset($json['swagger']) && !isset($json['openapi'])) {
                    throw new waException(
                        'Ответ не похож на OpenAPI/Swagger спецификацию (отсутствуют поля paths, swagger или openapi)'
                    );
                }
                $this->response = ['content' => $responseBody, 'format' => 'json'];
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── POST: загрузить файл спецификации ──────────────────────────────────────

    public function collectionUploadFileAction(): void
    {
        try {
            if (empty($_FILES['file'])) {
                throw new waException('Файл не загружен');
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new waException('Ошибка загрузки файла: ' . $this->getUploadErrorMessage($file['error']));
            }

            // Проверяем расширение файла
            $filename = basename($file['name']);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['json', 'yaml', 'yml'])) {
                throw new waException('Допускаются только файлы .json, .yaml, .yml');
            }

            // Проверяем размер (максимум 10 МБ)
            $maxSize = 10 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                throw new waException('Размер файла не должен превышать 10 МБ');
            }

            // Читаем и валидируем JSON
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                throw new waException('Не удалось прочитать файл');
            }

            // Удаляем BOM (Byte Order Mark) если присутствует
            if (substr($content, 0, 3) === "\xef\xbb\xbf") {
                $content = substr($content, 3);
            }

            if ($ext === 'json') {
                $parsed = json_decode($content, true);
                if ($parsed === null) {
                    throw new waException('Файл содержит некорректный JSON');
                }
                if (!isset($parsed['paths']) && !isset($parsed['swagger']) && !isset($parsed['openapi'])) {
                    throw new waException(
                        'Файл не похож на OpenAPI/Swagger спецификацию (отсутствует поле paths, swagger или openapi)'
                    );
                }
            }
            // YAML валидируется на фронтенде при парсинге

            // Сохраняем файл в защищённое хранилище (не публичное)
            $uploadDir = wa()->getDataPath('specs', false);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Генерируем уникальное имя файла
            $timestamp = time();
            $random = bin2hex(random_bytes(4));
            $savedExt = ($ext === 'json') ? 'json' : 'yaml';
            $newFilename = "spec_{$timestamp}_{$random}.{$savedExt}";
            $newPath = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;

            if (!move_uploaded_file($file['tmp_name'], $newPath)) {
                throw new waException('Не удалось сохранить файл');
            }

            $this->response = [
                'file' => $newFilename,
                'name' => $filename,
            ];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    /**
     * Возвращает описание ошибки загрузки файла.
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'Размер файла превышает максимально допустимый',
            UPLOAD_ERR_FORM_SIZE  => 'Размер файла превышает максимально допустимый',
            UPLOAD_ERR_PARTIAL    => 'Файл был загружен только частично',
            UPLOAD_ERR_NO_FILE    => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION  => 'Загрузка файла остановлена расширением',
        ];
        return $messages[$errorCode] ?? 'Неизвестная ошибка';
    }

    // ─── POST: серверный прокси для HTTP-запросов ───────────────────────────────

    public function proxyFetchAction(): void
    {
        $contactId = (int)wa()->getUser()->getId();
        $collectionId = 0;
        $historyData = [];

        try {
            $collectionId = (int)waRequest::post('collection_id');
            $method = strtoupper(trim((string)waRequest::post('method', 'GET')));
            $path = (string)waRequest::post('path', '');
            $queryParams = waRequest::post('query_params', []);
            $extraHeaders = waRequest::post('headers', []);
            $bodyRaw = waRequest::post('body', '');

            if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'])) {
                throw new waException("Недопустимый метод: {$method}");
            }

            $collection = $this->collectionModel->getCollection($collectionId, $contactId);
            if (!$collection) {
                throw new waException('Коллекция не найдена', 404);
            }

            // ── Окружение ──────────────────────────────────────────────────────
            $environmentId = (int)waRequest::post('environment_id', 0);
            $environment = null;

            if ($environmentId) {
                $environment = $this->environmentModel->getEnvironment($environmentId, $contactId);
            }

            // Base URL: ручной ввод > окружение > коллекция
            $baseUrlOverride = trim((string)waRequest::post('base_url'));
            if ($baseUrlOverride) {
                $rawBaseUrl = $baseUrlOverride;
            } elseif ($environment && !empty($environment['base_url'])) {
                $rawBaseUrl = $environment['base_url'];
            } else {
                $rawBaseUrl = $this->extractBaseUrl($collection['spec_url']);
            }

            // Разбираем base URL: он может содержать собственные query-параметры
            $parsedBase = parse_url($rawBaseUrl);
            $baseOrigin  = ($parsedBase['scheme'] ?? 'https') . '://' . ($parsedBase['host'] ?? '');
            if (!empty($parsedBase['port'])) {
                $baseOrigin .= ':' . $parsedBase['port'];
            }
            $basePath  = rtrim($parsedBase['path'] ?? '', '/');
            $baseQuery = [];
            if (!empty($parsedBase['query'])) {
                parse_str($parsedBase['query'], $baseQuery);
            }

            $url = $baseOrigin . $basePath . '/' . ltrim($path, '/');

            // Параметры base URL + параметры эндпойнта (эндпойнт имеет приоритет)
            $mergedQuery = array_merge($baseQuery, is_array($queryParams) ? $queryParams : []);
            if (!empty($mergedQuery)) {
                $url .= '?' . http_build_query($mergedQuery);
            }

            if (!preg_match('#^https?://#i', $url)) {
                throw new waException('Недопустимый URL');
            }

            // Auth: окружение (если auth_type != 'none') > коллекция
            if ($environment && $environment['auth_type'] !== 'none') {
                $authType = $environment['auth_type'];
                $authDataRaw = $environment['auth_data'] ?? '';
            } else {
                $authType = $collection['auth_type'] ?? 'none';
                $authDataRaw = $collection['auth_data'] ?? '';
            }
            $authData = $authDataRaw ? (json_decode($authDataRaw, true) ?? []) : [];
            $authHeaders = $this->buildAuthHeaders($authData, $authType);

            // Custom headers: коллекция + окружение (окружение переопределяет одноимённые)
            $collectionHeadersRaw = $collection['custom_headers'] ?? '';
            $collectionCustomHeaders = $collectionHeadersRaw ? (json_decode($collectionHeadersRaw, true) ?? []) : [];
            $collectionHeadersFormatted = [];
            if (is_array($collectionCustomHeaders)) {
                foreach ($collectionCustomHeaders as $h) {
                    if (!empty($h['name']) && !empty($h['value'])) {
                        $collectionHeadersFormatted[$h['name']] = $h['value'];
                    }
                }
            }

            $envHeadersFormatted = [];
            if ($environment) {
                $envHeadersRaw = $environment['custom_headers'] ?? '';
                $envCustomHeaders = $envHeadersRaw ? (json_decode($envHeadersRaw, true) ?? []) : [];
                if (is_array($envCustomHeaders)) {
                    foreach ($envCustomHeaders as $h) {
                        if (!empty($h['name']) && !empty($h['value'])) {
                            $envHeadersFormatted[$h['name']] = $h['value'];
                        }
                    }
                }
            }

            // Порядок приоритета: auth < коллекция custom < окружение custom < UI headers
            $headers = array_merge(
                $authHeaders,
                $collectionHeadersFormatted,
                $envHeadersFormatted,
                is_array($extraHeaders) ? $extraHeaders : []
            );

            // Тело запроса
            $postData = null;
            if (in_array($method, ['POST', 'PUT', 'PATCH']) && $bodyRaw !== '') {
                $postData = $bodyRaw;
                if (!isset($headers['Content-Type'])) {
                    $headers['Content-Type'] = 'application/json';
                }
            }

            $historyData = [
                'collection_id' => $collectionId,
                'contact_id'    => $contactId,
                'method'        => $method,
                'path'          => $path,
                'request_data'  => json_encode([
                    'query_params' => $queryParams,
                    'headers'      => $extraHeaders,
                    'body'         => $bodyRaw,
                ], JSON_UNESCAPED_UNICODE),
            ];

            // Выполняем запрос
            $net = new waNet(
                [
                    'timeout'            => 30,
                    'format'             => waNet::FORMAT_RAW,
                    'request_format'     => waNet::FORMAT_RAW,
                    'verify'             => false,
                    'expected_http_code' => null,  // Не бросать исключения при любых HTTP-кодах
                ],
                $headers  // Кастомные заголовки передаются вторым параметром
            );

            $responseBody = '';
            $responseStatus = 0;
            $responseHeaders = [];

            try {
                $responseBody = $net->query($url, $postData, $method);
            } catch (waNetException $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'timed out') !== false || stripos($msg, 'timeout') !== false) {
                    $responseBody = 'Request timeout';
                } else {
                    $responseBody = 'Connection failed: ' . $msg;
                }
            }

            // Получаем заголовки и статус ВСЕГДА, даже при ошибке
            $responseStatus = (int)$net->getResponseHeader('http_code');
            $allHeaders = $net->getResponseHeader();

            // Нормализуем заголовки в ассоциативный массив
            if (is_array($allHeaders)) {
                foreach ($allHeaders as $key => $value) {
                    if (is_string($key) && $key !== 'http_code') {
                        $responseHeaders[$key] = $value;
                    }
                }
            }

            // Удаляем чувствительные заголовки
            $sensitiveHeaders = ['server', 'x-powered-by', 'x-aspnet-version'];
            foreach ($sensitiveHeaders as $h) {
                unset($responseHeaders[strtolower($h)]);
            }

            // Ограничиваем размер тела — 5 МБ
            $maxSize = 5 * 1024 * 1024;
            if (strlen($responseBody) > $maxSize) {
                $responseBody = substr($responseBody, 0, $maxSize) . "\n\n[TRUNCATED: response exceeded 5MB]";
            }

            // Сохраняем в историю
            $historyData['response_status'] = $responseStatus;
            $historyData['response_body'] = $responseBody;
            $this->historyModel->addEntry($historyData);

            $this->response = [
                'response_status'  => $responseStatus,
                'response_headers' => $responseHeaders,
                'response_body'    => $responseBody,
            ];
        } catch (Exception $e) {
            // Сохраняем ошибку в историю, если коллекция была определена
            if ($collectionId && $historyData) {
                $historyData['response_status'] = 0;
                $historyData['response_body'] = $e->getMessage();
                try {
                    $this->historyModel->addEntry($historyData);
                } catch (Exception $ignored) {
                }
            }
            $this->setError($e->getMessage());
        }
    }

    // ─── GET: список окружений для текущего пользователя ───────────────────────

    public function environmentsAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $environments = $this->environmentModel->getForUser($contactId);

            foreach ($environments as &$env) {
                unset($env['auth_data']); // Не возвращаем секреты в списке
                $env['id'] = (int)$env['id'];
                $env['contact_id'] = (int)$env['contact_id'];
                $env['is_shared'] = (int)$env['is_shared'];
                $env['sort'] = (int)$env['sort'];
            }
            unset($env);

            $this->response = $environments;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── POST: создать или обновить окружение ───────────────────────────────────

    public function environmentSaveAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $id = (int)waRequest::post('id');
            $name = trim((string)waRequest::post('name'));
            $baseUrl = trim((string)waRequest::post('base_url', ''));
            $isShared = (int)(bool)waRequest::post('is_shared');
            $authType = waRequest::post('auth_type', 'none');
            $authData = waRequest::post('auth_data', '');
            $customHeaders = waRequest::post('custom_headers', []);
            $sort = (int)waRequest::post('sort', 0);

            if (!$name) {
                throw new waException('Поле «Название» обязательно');
            }
            if (mb_strlen($name) > 255) {
                throw new waException('Название не должно превышать 255 символов');
            }
            if ($baseUrl && !preg_match('#^https?://#i', $baseUrl)) {
                throw new waException('Base URL должен начинаться с http:// или https://');
            }

            // Проверка права на создание общих окружений
            if ($isShared && !wa()->getUser()->getRights('apicollection', 'manage_shared')) {
                throw new waException('Нет прав для создания общих окружений', 403);
            }

            if (is_array($authData)) {
                $authData = json_encode($authData, JSON_UNESCAPED_UNICODE);
            }

            if (is_array($customHeaders)) {
                $customHeaders = array_filter($customHeaders, function ($h) {
                    return !empty($h['name']) || !empty($h['value']);
                });
                $customHeaders = $customHeaders ? json_encode(
                    array_values($customHeaders),
                    JSON_UNESCAPED_UNICODE
                ) : null;
            } else {
                $customHeaders = null;
            }

            $data = [
                'name'           => $name,
                'base_url'       => $baseUrl ?: null,
                'is_shared'      => $isShared,
                'auth_type'      => in_array($authType, ['none', 'bearer', 'basic', 'apikey']) ? $authType : 'none',
                'auth_data'      => $authData ?: null,
                'custom_headers' => $customHeaders,
                'sort'           => $sort,
            ];

            if ($id) {
                $this->environmentModel->updateEnvironment($id, $data);
            } else {
                $data['contact_id'] = $contactId;
                $id = $this->environmentModel->add($data);
            }

            $this->response = ['id' => (int)$id];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── POST: удалить окружение ────────────────────────────────────────────────

    public function environmentDeleteAction(): void
    {
        try {
            $id = (int)waRequest::post('id');
            if (!$id) {
                throw new waException('Не передан id');
            }
            $this->environmentModel->deleteEnvironment($id);
            $this->response = [];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── GET: одно окружение (включая auth_data — только владельцу) ────────────

    public function environmentGetAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $id = (int)waRequest::get('id');
            $environment = $this->environmentModel->getEnvironment($id, $contactId);

            if (!$environment) {
                throw new waException('Окружение не найдено', 404);
            }

            // auth_data показываем только владельцу
            if ((int)$environment['contact_id'] !== $contactId) {
                unset($environment['auth_data']);
            }

            $environment['id'] = (int)$environment['id'];
            $environment['contact_id'] = (int)$environment['contact_id'];
            $environment['is_shared'] = (int)$environment['is_shared'];
            $environment['sort'] = (int)$environment['sort'];

            $this->response = $environment;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── POST: выбрать окружение для коллекции ──────────────────────────────────

    public function environmentSelectAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $collectionId = (int)waRequest::post('collection_id');
            $environmentId = waRequest::post('environment_id');

            if (!$collectionId) {
                throw new waException('Не передан collection_id');
            }

            // Проверяем доступ к коллекции
            $collection = $this->collectionModel->getCollection($collectionId, $contactId);
            if (!$collection) {
                throw new waException('Коллекция не найдена', 404);
            }

            // Если environment_id передан — проверяем доступ к окружению
            $envId = $environmentId !== null && $environmentId !== '' ? (int)$environmentId : null;
            if ($envId) {
                $env = $this->environmentModel->getEnvironment($envId, $contactId);
                if (!$env) {
                    throw new waException('Окружение не найдено', 404);
                }
            }

            $this->envSelectedModel->setSelected($contactId, $collectionId, $envId);
            $this->response = ['environment_id' => $envId];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── GET: получить текущее выбранное окружение для коллекции ─────────────────

    public function environmentSelectedAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $collectionId = (int)waRequest::get('collection_id');

            if (!$collectionId) {
                throw new waException('Не передан collection_id');
            }

            $envId = $this->envSelectedModel->getSelected($contactId, $collectionId);

            $environment = null;
            if ($envId) {
                $environment = $this->environmentModel->getEnvironment($envId, $contactId);
                if ($environment) {
                    $environment['id'] = (int)$environment['id'];
                    $environment['contact_id'] = (int)$environment['contact_id'];
                    $environment['is_shared'] = (int)$environment['is_shared'];
                    $environment['sort'] = (int)$environment['sort'];
                }
            }

            $this->response = [
                'environment_id' => $envId,
                'environment'    => $environment,
            ];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── GET: история запросов по коллекции ────────────────────────────────────

    public function historyAction(): void
    {
        try {
            $contactId = (int)wa()->getUser()->getId();
            $collectionId = (int)waRequest::get('collection_id');

            if (!$collectionId) {
                throw new waException('Не передан collection_id');
            }
            // Проверяем доступ к коллекции
            $collection = $this->collectionModel->getCollection($collectionId, $contactId);
            if (!$collection) {
                throw new waException('Коллекция не найдена', 404);
            }

            $this->response = $this->historyModel->getByCollection($collectionId);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    // ─── Вспомогательные методы ─────────────────────────────────────────────────

    /**
     * Извлекает базовый URL из URL спецификации (без пути /swagger.json и т.п.)
     */
    private function extractBaseUrl(string $specUrl): string
    {
        $parsed = parse_url($specUrl);
        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        if (!empty($parsed['port'])) {
            $base .= ':' . $parsed['port'];
        }
        return $base;
    }

    /**
     * Строит заголовки аутентификации по типу.
     */
    private function buildAuthHeaders(array $authData, string $authType): array
    {
        switch ($authType) {
            case 'bearer':
                $token = $authData['token'] ?? '';
                return $token ? ['Authorization' => 'Bearer ' . $token] : [];

            case 'basic':
                $user = $authData['username'] ?? '';
                $pass = $authData['password'] ?? '';
                return ($user || $pass)
                    ? ['Authorization' => 'Basic ' . base64_encode("{$user}:{$pass}")]
                    : [];

            case 'apikey':
                $header = $authData['header'] ?? '';
                $key = $authData['key'] ?? '';
                return ($header && $key) ? [$header => $key] : [];

            default:
                return [];
        }
    }

    /**
     * Переопределяем формат ошибки.
     */
    protected function setError(string $message, int $code = 0): void
    {
        $this->errors = ['message' => $message];
    }
}
