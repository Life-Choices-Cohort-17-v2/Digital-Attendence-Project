# Backend Application Root
# Digital Attendance System Backend

## Overview

The Digital Attendance System backend is an Object-Oriented PHP application responsible for:

* Employee authentication
* Attendance processing
* QR-based clock-in/out workflows
* Dashboard data processing
* External reporting integration

The backend follows a lightweight MVC-inspired architecture.

## Technology Stack

* PHP 8+
* MySQL
* PDO
* Google Apps Script
* Google Sheets Integration
* Apache/Xneelo compatible hosting environment

# Architecture Overview

The system separates application logic, database communication, and external integrations.

## Request Flow

```text
Frontend / QR Scanner
          |
          ▼
      index.php
          |
          ▼
       Routes
          |
          ▼
    Controllers
          |
          ▼
    Validators
          |
          ▼
     Services
          |
          ├──────────────► Models
          |                    |
          |                    ▼
          |                 MySQL
          |
          └──────────────► GoogleSheetsService
                               |
                               ▼
                         Google Apps Script
                               |
                               ▼
                          Google Sheet
```

## Architecture Responsibilities

### Controllers

Responsible for:

* Receiving incoming requests
* Calling required services
* Returning responses

Controllers should not contain:

* SQL queries
* Attendance business rules
* External integration logic

### Models

Responsible for:

* Communicating with MySQL
* Managing database entities
* Executing database queries

### Services

Responsible for:

* Application business logic
* Attendance processing
* External integrations

### Validators

Responsible for:

* Checking incoming request data
* Ensuring required fields exist
* Validating input before processing

### Middleware

Responsible for:

* Authentication checks
* Route protection
* Permission verification

# Folder Structure

```
src/

config/
    Database connection and external service configuration

controllers/
    Handle incoming requests and responses

models/
    Database entities and database interaction methods

services/
    Business logic and external integrations

validators/
    Validate incoming request data

middleware/
    Authentication and route protection

routes/
    Map URLs to controllers

helpers/
    Reusable utility functions

data/
    Business rules and application constraints
```

Additional files:

```
index.php
    Application entry point / front controller
```

# Database Overview

MySQL is the primary application database and source of truth.

## Main Entities

### Users

Stores:

* Authentication information
* Login credentials
* User roles

### Employees

Stores:

* Employee details
* Department information
* Employee status

### Attendance

Stores:

* Clock-in records
* Clock-out records
* Attendance status

### Roles

Stores:

* Permissions
* Access control information

# Attendance Workflow

## Clock-In Process

1. Employee scans QR code
2. Request reaches AttendanceController
3. AttendanceValidator checks input
4. AttendanceService applies attendance rules
5. Attendance record is saved in MySQL
6. Attendance event is sent to Google Sheets

## Attendance Flow

```text
QR Scanner

↓

AttendanceController

↓

AttendanceValidator

↓

AttendanceService

↓

AttendanceRules

↓

Attendance Model

↓

MySQL

↓

GoogleSheetsService

↓

Google Sheets
```

# Google Sheets Integration

Google Sheets is used only for reporting purposes.

Google Sheets does not handle:

* Authentication
* User permissions
* Attendance validation
* Business rules

## Data Flow

```text
MySQL

↓

GoogleSheetsService

↓

Google Apps Script

↓

Google Sheets
```

# Installation Setup

## Requirements

* PHP 8+
* MySQL
* Apache or PHP development server

## Clone Repository

```bash
git clone <repository-url>
```

## Configuration

Update:

```
src/config/db.php
```

Database configuration:

```
DB_HOST
DB_NAME
DB_USERNAME
DB_PASSWORD
```

Google Sheets configuration:

```
WEBHOOK_URL
SHEET_ID
```

## Start Development Server

```bash
php -S localhost:8000
```

# API Endpoints

| Method | Endpoint              | Purpose                                |
| ------ | --------------------- | -------------------------------------- |
| POST   | /login                | Authenticate users and create sessions |
| POST   | /attendance/clock-in  | Create clock-in record                 |
| POST   | /attendance/clock-out | Complete attendance record             |
| POST   | /attendance/scan      | Process QR attendance scans            |
| GET    | /dashboard/staff      | Retrieve dashboard attendance data     |

# Development Guidelines

1. Controllers should not contain SQL queries.

2. Models communicate with MySQL.

3. Services contain business logic.

4. Google Sheets communication must only happen through GoogleSheetsService.

5. PHP classes must use PascalCase naming.

6. Attendance rules belong in AttendanceRules, not controllers.

7. Controllers coordinate requests and responses, not business decisions.

8. External integrations must be isolated inside dedicated services.
