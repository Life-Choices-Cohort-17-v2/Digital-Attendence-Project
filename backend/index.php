<?php
/**
 * Front Controller – every request to /backend/* comes here.
 */
session_start();

// Define the base path so routes can be matched correctly.
// $_SERVER['SCRIPT_NAME'] is e.g. /insite/Digital-Attendence-Project/backend/index.php
// so dirname gives /insite/Digital-Attendence-Project/backend
define('BASE_PATH', dirname($_SERVER['SCRIPT_NAME']));

require_once __DIR__ . '/src/routes/web.php';