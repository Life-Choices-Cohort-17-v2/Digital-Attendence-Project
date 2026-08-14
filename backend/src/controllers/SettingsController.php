<?php
namespace Controllers;

use Models\Settings;

class SettingsController
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        $settingsModel = new Settings($this->pdo);
        $settings = $settingsModel->getAllSettings();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
    }
}