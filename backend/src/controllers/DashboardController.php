<?php
namespace Controllers;

use Services\DashboardService;

class DashboardController {
    private $pdo;
    private $dashboardService;

    public function __construct($pdo = null) {
        $this->pdo = $pdo;
        $this->dashboardService = new DashboardService($this->pdo);
    }

    public function stats() {
        header('Content-Type: application/json');
        try {
            $stats = $this->dashboardService->getStats();
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function onsite() {
        header('Content-Type: application/json');
        try {
            $staff = $this->dashboardService->getOnsiteStaff();
            echo json_encode(['success' => true, 'data' => $staff]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function recent() {
        header('Content-Type: application/json');
        try {
            $activity = $this->dashboardService->getRecentActivity();
            echo json_encode(['success' => true, 'data' => $activity]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}