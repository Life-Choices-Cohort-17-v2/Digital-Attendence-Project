<?php
namespace Validators;

class AttendanceValidator {
    public static function validateScanInput(?array $data): array {
        if (!$data || empty($data['employee_id'])) {
            return [
                'is_valid' => false,
                'message'  => 'Employee ID or QR payload is required.'
            ];
        }
        return ['is_valid' => true];
    }
}