<?php

declare(strict_types=1);

class apicollectionRequestHistoryModel extends waModel
{
    protected $table = 'apicollection_request_history';

    public function addEntry(array $data): int
    {
        $data['executed_at'] = date('Y-m-d H:i:s');
        return $this->insert($data);
    }

    public function getByCollection(int $collectionId, int $limit = 50): array
    {
        return $this->query(
            "SELECT * FROM {$this->table}
             WHERE collection_id = i:cid
             ORDER BY executed_at DESC
             LIMIT i:lim",
            ['cid' => $collectionId, 'lim' => $limit]
        )->fetchAll();
    }

    public function getByUser(int $contactId, int $limit = 100): array
    {
        return $this->query(
            "SELECT * FROM {$this->table}
             WHERE contact_id = i:cid
             ORDER BY executed_at DESC
             LIMIT i:lim",
            ['cid' => $contactId, 'lim' => $limit]
        )->fetchAll();
    }

    public function deleteByCollection(int $collectionId): void
    {
        $this->deleteByField('collection_id', $collectionId);
    }
}
