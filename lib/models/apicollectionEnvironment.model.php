<?php

declare(strict_types=1);

class apicollectionEnvironmentModel extends waModel
{
    protected $table = 'apicollection_environment';

    /**
     * Возвращает все окружения, доступные пользователю:
     * собственные + общие (is_shared = 1).
     */
    public function getForUser(int $contactId): array
    {
        return $this->query(
            "SELECT * FROM {$this->table}
             WHERE contact_id = i:cid OR is_shared = 1
             ORDER BY is_shared DESC, sort ASC, name ASC",
            ['cid' => $contactId]
        )->fetchAll();
    }

    /**
     * Создаёт новое окружение, возвращает id.
     */
    public function add(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $data['created'] = $now;
        $data['updated'] = $now;
        return $this->insert($data);
    }

    /**
     * Обновляет окружение (только для владельца).
     *
     * @throws waException
     */
    public function updateEnvironment(int $id, array $data): void
    {
        $this->checkOwner($id);
        $data['updated'] = date('Y-m-d H:i:s');
        $this->updateByField('id', $id, $data);
    }

    /**
     * Удаляет окружение (только для владельца).
     * Также очищает записи в apicollection_environment_selected.
     *
     * @throws waException
     */
    public function deleteEnvironment(int $id): void
    {
        $this->checkOwner($id);

        // Очищаем выбор этого окружения у всех пользователей
        $selectedModel = new apicollectionEnvironmentSelectedModel();
        $selectedModel->deleteByEnvironment($id);

        $this->deleteByField('id', $id);
    }

    /**
     * Возвращает окружение, если пользователь — владелец или оно общее.
     */
    public function getEnvironment(int $id, int $contactId): ?array
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
            throw new waException('Нет прав для изменения этого окружения', 403);
        }
    }
}
