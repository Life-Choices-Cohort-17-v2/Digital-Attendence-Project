<?php

namespace Controllers;

use Models\SyncError;

class SyncErrorController
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        $syncErrorModel = new SyncError($this->pdo);
        $errors = $syncErrorModel->getAllSyncErrors();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $errors
        ]);
    }
}