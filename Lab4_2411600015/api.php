<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight support for cross-origin fetch() calls during development.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('DATA_FILE', __DIR__ . '/data/courses.json');
define('GRADE_PASSING', 3.0);
define('GRADE_AT_RISK', 2.0);

// ---------------------------------------------------------------
// Storage helpers
// ---------------------------------------------------------------

function loadCourses(): array {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function saveCourses(array $courses): void {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(DATA_FILE, json_encode(array_values($courses), JSON_PRETTY_PRINT));
}

// ---------------------------------------------------------------
// Domain helpers (mirrors js/dataManager.js so the API and the
// client-side fallback data behave identically)
// ---------------------------------------------------------------

function deriveStatus(float $grade): string {
    if ($grade >= GRADE_PASSING) return 'Passing';
    if ($grade >= GRADE_AT_RISK) return 'At Risk';
    return 'Failing';
}

function withComputed(array $c): array {
    $grade = (float) ($c['grade'] ?? 0);
    $units = (float) ($c['units'] ?? 0);
    $c['status'] = deriveStatus($grade);
    $c['gradeThreshold'] = GRADE_PASSING;
    $c['qualityPoints'] = round($units * $grade, 2);
    return $c;
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
$courses = loadCourses();

if ($method === 'GET' && $action === 'list') {
    respond(array_map('withComputed', $courses));
}

if ($method === 'GET' && $action === 'get') {
    $id = $_GET['id'] ?? '';
    $found = array_values(array_filter($courses, fn($c) => $c['courseCode'] === $id));
    if (!$found) {
        respond(['error' => 'Course not found'], 404);
    }
    respond(withComputed($found[0]));
}

if ($method === 'POST' && $action === 'add') {
    $body = readJsonBody();
    $required = ['courseCode', 'courseName', 'category', 'units', 'grade', 'instructor', 'attendanceRate'];
    foreach ($required as $field) {
        if (!isset($body[$field])) {
            respond(['error' => "Missing field: $field"], 400);
        }
    }
    $duplicate = array_filter($courses, fn($c) => $c['courseCode'] === $body['courseCode']);
    if ($duplicate) {
        respond(['error' => 'Course code already exists'], 409);
    }
    $body['id'] = count($courses) ? max(array_column($courses, 'id')) + 1 : 1;
    $courses[] = $body;
    saveCourses($courses);
    respond(withComputed($body), 201);
}

if ($method === 'POST' && $action === 'update') {
    $id = $_GET['id'] ?? '';
    $body = readJsonBody();
    $updated = null;

    foreach ($courses as &$c) {
        if ($c['courseCode'] === $id) {
            foreach ($body as $key => $value) {
                if ($key !== 'id' && $key !== 'courseCode') {
                    $c[$key] = $value;
                }
            }
            $updated = $c;
            break;
        }
    }
    unset($c);

    if (!$updated) {
        respond(['error' => 'Course not found'], 404);
    }
    saveCourses($courses);
    respond(withComputed($updated));
}

if ($method === 'POST' && $action === 'delete') {
    $id = $_GET['id'] ?? '';
    $before = count($courses);
    $courses = array_values(array_filter($courses, fn($c) => $c['courseCode'] !== $id));
    if (count($courses) === $before) {
        respond(['error' => 'Course not found'], 404);
    }
    saveCourses($courses);
    respond(['success' => true]);
}

respond(['error' => 'Unknown action or method'], 400);
