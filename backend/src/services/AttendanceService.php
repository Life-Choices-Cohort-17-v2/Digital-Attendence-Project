<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Data\AttendanceRules;
use App\Services\GoogleSheetsService;

/**
 * ATTENDANCE CORE SERVICE ENGINE
 * Owner: Person 3 (Clock Engine Lead)
 * Extra methods for Person 7 (Staff Portal)
 */
class AttendanceService 
{
    private Attendance $attendanceModel;
    private User $userModel;
    private ?GoogleSheetsService $sheetsService;

    public function __construct(Attendance $attendanceModel, User $userModel, ?GoogleSheetsService $sheetsService = null) 
    {
        $this->attendanceModel = $attendanceModel;
        $this->userModel = $userModel;
        $this->sheetsService = $sheetsService;
    }

    /**
     * Smart Processing for QR Scans (Auto Clock-In / Clock-Out)
     */
    public function processScan(string $employeeId, string $location): array 
    {
        // 1. Fetch User Record via Person 1's Model
        $user = $this->userModel->findByEmployeeId($employeeId);

        // 2. Business Rule Check: User Status
        $userGuard = AttendanceRules::canUserClock($user);
        if (!$userGuard['allowed']) {
            return [
                'success' => false,
                'status_code' => $userGuard['status_code'],
                'message' => $userGuard['message']
            ];
        }

        // 3. Check for Active Open Shift
        $activeShift = $this->attendanceModel->findActiveShiftByUserId($user['id']);

        // 4. Toggle Action: Clock Out if active shift exists, else Clock In
        if ($activeShift) {
            return $this->executeClockOut($user, $activeShift, $location);
        } else {
            return $this->executeClockIn($user, $location);
        }
    }

    /**
     * Executes Clock-In Sequence
     */
    public function executeClockIn(array $user, string $location): array 
    {
        $now = date('Y-m-d H:i:s');

        // Insert Record via Person 1's Model
        $recordId = $this->attendanceModel->createClockIn($user['id'], $now, $location);

        $response = [
            'success'     => true,
            'status_code' => 201,
            'action'      => 'CLOCKED_IN',
            'record_id'   => $recordId,
            'timestamp'   => $now,
            'location'    => $location,
            'user' => [
                'employee_id' => $user['employee_id'],
                'name'        => $user['first_name'] . ' ' . $user['last_name']
            ],
            'message' => 'Clock-in recorded successfully.'
        ];

        // Dispatch Async Webhook to Google Sheets (Person 6)
        $this->triggerGoogleSheetsSync($user['employee_id'], 'CLOCKED_IN', $now, $location);

        return $response;
    }

    /**
     * Executes Clock-Out Sequence
     */
    public function executeClockOut(array $user, array $activeShift, string $location): array 
    {
        // Business Rule Check: Cooldown Guard
        $cooldownGuard = AttendanceRules::enforceCooldown($activeShift);
        if ($cooldownGuard && !$cooldownGuard['allowed']) {
            return [
                'success' => false,
                'status_code' => $cooldownGuard['status_code'],
                'message' => $cooldownGuard['message']
            ];
        }

        $now = date('Y-m-d H:i:s');

        // Update Record via Person 1's Model
        $this->attendanceModel->updateClockOut($activeShift['id'], $now);

        $response = [
            'success'     => true,
            'status_code' => 200,
            'action'      => 'CLOCKED_OUT',
            'record_id'   => $activeShift['id'],
            'timestamp'   => $now,
            'location'    => $location,
            'user' => [
                'employee_id' => $user['employee_id'],
                'name'        => $user['first_name'] . ' ' . $user['last_name']
            ],
            'message' => 'Clock-out recorded successfully.'
        ];

        // Dispatch Async Webhook to Google Sheets (Person 6)
        $this->triggerGoogleSheetsSync($user['employee_id'], 'CLOCKED_OUT', $now, $location);

        return $response;
    }

    // =========================================================
    // PERSON 7 – Staff Portal methods
    // =========================================================

    /**
     * Get attendance history for one user.
     * Converts your shift rows (clock_in / clock_out) into the
     * sign_in / sign_out format the frontend expects.
     */
    public function getHistoryForUser(int $userId, bool $todayOnly = false): array 
    {
        // Get shifts from the Attendance model
        $shifts = $this->attendanceModel->getShiftsByUserId($userId, $todayOnly);

        $records = [];

        foreach ($shifts as $shift) {
            // Clock-in event
            if (!empty($shift['clock_in'])) {
                $records[] = [
                    'id'          => $shift['id'],
                    'type'        => 'sign_in',
                    'timestamp'   => $shift['clock_in'],
                    'location'    => $shift['location'] ?? null,
                    'sync_status' => $shift['sync_status'] ?? 'synced',
                    'date'        => substr($shift['clock_in'], 0, 10),
                ];
            }

            // Clock-out event (only if it exists)
            if (!empty($shift['clock_out'])) {
                $records[] = [
                    'id'          => $shift['id'] . '_out',
                    'type'        => 'sign_out',
                    'timestamp'   => $shift['clock_out'],
                    'location'    => $shift['location'] ?? null,
                    'sync_status' => $shift['sync_status'] ?? 'synced',
                    'date'        => substr($shift['clock_out'], 0, 10),
                ];
            }
        }

        // Sort by timestamp ascending for today, descending for full history
        usort($records, function ($a, $b) use ($todayOnly) {
            $cmp = strcmp($a['timestamp'], $b['timestamp']);
            return $todayOnly ? $cmp : -$cmp;
        });

        return $records;
    }

    /**
     * Staff currently onsite = users with an open shift today
     * (clock_in today and clock_out is NULL)
     */
    public function getOnsiteStaff(): array 
    {
        return $this->attendanceModel->getOnsiteStaff();
    }

    // =========================================================

    /**
     * Helper to trigger Person 6's service without breaking if unconfigured
     */
    private function triggerGoogleSheetsSync(string $employeeId, string $action, string $timestamp, string $location): void 
    {
        if ($this->sheetsService) {
            try {
                $this->sheetsService->syncRecord($employeeId, $action, $timestamp, $location);
            } catch (\Exception $e) {
                // Log silently so Google Sheets delays never break core clock-in
                error_log("Google Sheets Sync Warning: " . $e->getMessage());
            }
        }
    }
}