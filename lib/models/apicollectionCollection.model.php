<?php

declare(strict_types=1);

class apicollectionCollectionModel extends waModel
{
    protected $table = 'apicollection_collection';

    /**
     * Возвращает все коллекции, доступные пользователю:
     * собственные + общие (is_shared = 1).
     */
    public function getForUser(int $contactId): array
    {
        return $this->query(
            "SELECT * FROM {$this->table}
             WHERE contact_id = i:cid OR is_shared = 1
             ORDER BY is_shared DESC, title ASC",
            ['cid' => $contactId]
        )->fetchAll();
    }

    /**
     * Только коллекции пользователя.
     */
    public function getOwnedByUser(int $contactId): array
    {
        return $this->getByField('contact_id', $contactId, true);
    }

    /**
     * Создаёт новую коллекцию, возвращает id.
     */
    public function add(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $data['created'] = $now;
        $data['updated'] = $now;
        return $this->insert($data);
    }

    /**
     * Обновляет коллекцию (только для владельца).
     *
     * @throws waException
     */
    public function updateCollection(int $id, array $data): void
    {
        $this->checkOwner($id);
        $data['updated'] = date('Y-m-d H:i:s');
        $this->updateByField('id', $id, $data);
    }

    /**
     * Удаляет коллекцию и её историю запросов (только для владельца).
     *
     * @throws waException
     */
    public function deleteCollection(int $id): void
    {
        $this->checkOwner($id);
        
        // Удаляем загруженный файл, если он есть
        $collection = $this->getByField('id', $id);
        if ($collection && $collection['spec_source'] === 'file' && $collection['spec_file']) {
            $this->deleteSpecFile($collection['spec_file']);
        }
        
        $historyModel = new apicollectionRequestHistoryModel();
        $historyModel->deleteByCollection($id);
        $this->deleteByField('id', $id);
    }

    /**
     * Удаляет файл спецификации из защищённого хранилища.
     */
    public function deleteSpecFile(string $filePath): void
    {
        try {
            $fullPath = wa()->getDataPath('specs/' . basename($filePath), false);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        } catch (Exception $e) {
            // Игнорируем ошибки удаления файла
        }
    }

    /**
     * Возвращает коллекцию, если пользователь — владелец или она общая.
     */
    public function getCollection(int $id, int $contactId): ?array
    {
        $row = $this->query(
            "SELECT * FROM {$this->table}
             WHERE id = i:id AND (contact_id = i:cid OR is_shared = 1)
             LIMIT 1",
            ['id' => $id, 'cid' => $contactId]
        )->fetchAssoc();

        return $row ?: null;
    }

    /**
     * @throws waException
     */
    protected function checkOwner(int $id): void
    {
        $contactId = (int) wa()->getUser()->getId();
        $row = $this->getByField('id', $id);
        if (!$row || (int)$row['contact_id'] !== $contactId) {
            throw new waException('Нет прав для изменения этой коллекции', 403);
        }
    }
}
