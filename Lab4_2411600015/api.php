<?php
/**
 * api.php
 * ------------------------------------------------------------------
 * Simple PHP backend for the Student Management Dashboard
 * (Laboratory Exercise 4, Part 7). Serves the roster as JSON and
 * supports adding students and updating a student's GPA (the
 * "stock level" in the lab's inventory vocabulary).
 *
 * Run under XAMPP (Apache) and call it from js/dataManager.js.
 *
 * Endpoints (relative to this file, e.g. http://localhost/lab4/api.php):
 *   GET  ?action=list                    -> full roster as JSON
 *   GET  ?action=get&id=2411600001        -> single student
 *   POST ?action=add                      -> add a student (JSON body)
 *   POST ?action=update&id=2411600001      -> patch one student's fields (JSON body)
 *   POST ?action=delete&id=2411600001       -> remove a student
 *
 * Data persists to data/students.json so changes survive requests.
 * A real deployment would use MySQL (XAMPP ships with it), but a flat
 * JSON file keeps this lab focused on the JS <-> API data flow rather
 * than SQL/PDO setup.
 * ------------------------------------------------------------------
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight support for cross-origin fetch() calls during development.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('DATA_FILE', __DIR__ . '/data/students.json');
define('GPA_GOOD_STANDING', 3.0);
define('GPA_AT_RISK', 2.0);

// ---------------------------------------------------------------
// Storage helpers
// ---------------------------------------------------------------

function loadStudents(): array {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function saveStudents(array $students): void {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(DATA_FILE, json_encode(array_values($students), JSON_PRETTY_PRINT));
}

// ---------------------------------------------------------------
// Domain helpers (mirrors js/dataManager.js so the API and the
// client-side fallback data behave identically)
// ---------------------------------------------------------------

function deriveStatus(float $gpa): string {
    if ($gpa >= GPA_GOOD_STANDING) return 'Good Standing';
    if ($gpa >= GPA_AT_RISK) return 'At Risk';
    return 'Probation';
}

function withComputed(array $s): array {
    $gpa = (float) ($s['unitPrice'] ?? 0);
    $qty = (float) ($s['quantity'] ?? 0);
    $s['stockStatus'] = deriveStatus($gpa);
    $s['reorderLevel'] = GPA_GOOD_STANDING;
    $s['totalValue'] = round($qty * $gpa, 2);
    return $s;
}

function respond($payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function readJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ---------------------------------------------------------------
// Routing
// ---------------------------------------------------------------

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];
$students = loadStudents();

if ($method === 'GET' && $action === 'list') {
    respond(array_map('withComputed', $students));
}

if ($method === 'GET' && $action === 'get') {
    $id = $_GET['id'] ?? '';
    $found = array_values(array_filter($students, fn($s) => $s['studentId'] === $id));
    if (!$found) {
        respond(['error' => 'Student not found'], 404);
    }
    respond(withComputed($found[0]));
}

if ($method === 'POST' && $action === 'add') {
    $body = readJsonBody();
    $required = ['studentId', 'name', 'category', 'yearLevel', 'quantity', 'unitPrice', 'attendanceRate'];
    foreach ($required as $field) {
        if (!isset($body[$field])) {
            respond(['error' => "Missing field: $field"], 400);
        }
    }
    $duplicate = array_filter($students, fn($s) => $s['studentId'] === $body['studentId']);
    if ($duplicate) {
        respond(['error' => 'Student ID already exists'], 409);
    }
    $body['id'] = count($students) ? max(array_column($students, 'id')) + 1 : 1;
    $students[] = $body;
    saveStudents($students);
    respond(withComputed($body), 201);
}

if ($method === 'POST' && $action === 'update') {
    $id = $_GET['id'] ?? '';
    $body = readJsonBody();
    $updated = null;

    foreach ($students as &$s) {
        if ($s['studentId'] === $id) {
            foreach ($body as $key => $value) {
                if ($key !== 'id' && $key !== 'studentId') {
                    $s[$key] = $value;
                }
            }
            $updated = $s;
            break;
        }
    }
    unset($s);

    if (!$updated) {
        respond(['error' => 'Student not found'], 404);
    }
    saveStudents($students);
    respond(withComputed($updated));
}

if ($method === 'POST' && $action === 'delete') {
    $id = $_GET['id'] ?? '';
    $before = count($students);
    $students = array_values(array_filter($students, fn($s) => $s['studentId'] !== $id));
    if (count($students) === $before) {
        respond(['error' => 'Student not found'], 404);
    }
    saveStudents($students);
    respond(['success' => true]);
}

respond(['error' => 'Unknown action or method'], 400);
