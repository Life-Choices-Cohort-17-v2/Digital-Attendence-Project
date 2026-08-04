<?php
namespace Services;

use Config\GoogleSheets;

class GoogleSheetsService {
    private $webhookUrl;

    public function __construct() {
        $this->webhookUrl = GoogleSheets::WEBHOOK_URL;
    }

    public function sendAttendance($record) {
        $payload = [
            'action' => 'add_attendance',
            'data' => [
                'employee_id' => $record['user_id'],
                'name' => $this->getUserName($record['user_id']),
                'type' => $record['type'],
                'timestamp' => $record['timestamp'],
                'location' => $record['location'],
                'qr_code' => $record['qr_code']
            ]
        ];

        $ch = curl_init($this->webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Sync failed: $response");
        }
        $data = json_decode($response, true);
        return $data['row_id'] ?? null;
    }

    private function getUserName($userId) {
        $user = (new \Models\User())->find($userId);
        return $user ? $user['name'] : 'Unknown';
    }
}