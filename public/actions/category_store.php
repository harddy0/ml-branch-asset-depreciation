<?php
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/CategoryService.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

$code  = strtoupper(trim($_POST['category_code']    ?? ''));
$name  = trim($_POST['category_name']               ?? '');
$life  = (int)($_POST['asset_life_months']          ?? 0);

// Validation
if (empty($code) || empty($name) || $life < 1) {
    $_SESSION['flash_error'] = 'All fields are required and asset life must be at least 1 month.';
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}
if (strlen($code) > 10) {
    $_SESSION['flash_error'] = 'Category code must be 10 characters or fewer.';
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}
if (!preg_match('/^[A-Z0-9]+$/', $code)) {
    $_SESSION['flash_error'] = 'Category code may only contain uppercase letters and numbers.';
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

// Delegate DB logic to the Service Class
$categoryService = new \App\CategoryService($pdo);
$result = $categoryService->createCategory($code, $name, $life);

if ($result['success']) {
    $_SESSION['flash_success'] = "Category \"{$name}\" ({$code}) added successfully.";
} else {
    $_SESSION['flash_error'] = $result['error'];
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;