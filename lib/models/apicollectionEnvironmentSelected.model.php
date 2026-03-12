<?php

declare(strict_types=1);

class apicollectionEnvironmentSelectedModel extends waModel
{
    protected $table = 'apicollection_environment_selected';

    /**
     * Получить ID выбранного окружения для пользователя и коллекции.
     */
    public function getSelected(int $contactId, int $collectionId): ?int
    {
        $row = $this->getByField([
            'contact_id'    => $contactId,
            'collection_id' => $collectionId,
        ]);
        return $row ? (int)$row['environment_id'] : null;
    }

    /**
     * Установить выбранное окружение (upsert).
     */
    public function setSelected(int $contactId, int $collectionId, ?int $environmentId): void
    {
        $this->exec(
            "REPLACE INTO {$this->table} (contact_id, collection_id, environment_id)
             VALUES (i:cid, i:colid, i:envid)",
            [
                'cid'   => $contactId,
                'colid' => $collectionId,
                'envid' => $environmentId,
            ]
        );
    }

    /**
     * Удалить все записи для окружения (при удалении окружения).
     */
    public function deleteByEnvironment(int $environmentId): void
    {
        $this->deleteByField('environment_id', $environmentId);
    }
}
