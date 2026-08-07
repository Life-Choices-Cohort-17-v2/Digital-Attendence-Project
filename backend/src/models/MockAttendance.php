<?php
namespace Models;

class Attendance 
{
    private ?\PDO $pdo;
    private string $storageFile;

    public function __construct(?\PDO $pdo = null) 
    {
        $this->pdo = $pdo;
        // Temporary JSON file to persist state across HTTP requests
        $this->storageFile = sys_get_temp_dir() . '/attendance_mock_state.json';
    }

    private function loadState(): array 
    {
        if (!file_exists($this->storageFile)) {
            return ['clockedIn' => [], 'lastScanTime' => []];
        }
        return json_decode(file_get_contents($this->storageFile), true) ?? ['clockedIn' => [], 'lastScanTime' => []];
    }

    private function saveState(array $state): void 
    {
        $fp = fopen($this->storageFile, 'c+');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($state, JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    public function isClockedIn(string $employeeId): bool 
    {
        $state = $this->loadState();
        return $state['clockedIn'][$employeeId] ?? false;
    }

    public function getLastClockTime(string $employeeId): ?string 
    {
        $state = $this->loadState();
        return $state['lastScanTime'][$employeeId] ?? null;
    }

    public function recordClockIn(string $employeeId, string $timestamp): bool 
    {
        $state = $this->loadState();
        $state['clockedIn'][$employeeId] = true;
        $state['lastScanTime'][$employeeId] = $timestamp;
        $this->saveState($state);
        return true;
    }

    public function recordClockOut(string $employeeId, string $timestamp): bool 
    {
        $state = $this->loadState();
        $state['clockedIn'][$employeeId] = false;
        $state['lastScanTime'][$employeeId] = $timestamp;
        $this->saveState($state);
        return true;
    }
}