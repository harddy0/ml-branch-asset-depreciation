<?php
require_once __DIR__ . '/../../src/includes/init.php';

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

try {
    $stmt = $pdo->prepare("INSERT INTO asset_categories (category_code, category_name, asset_life_months) VALUES (?, ?, ?)");
    $stmt->execute([$code, $name, $life]);
    $_SESSION['flash_success'] = "Category \"{$name}\" ({$code}) added successfully.";
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['flash_error'] = "Category code \"{$code}\" already exists. Please choose a different code.";
    } else {
        $_SESSION['flash_error'] = 'Failed to add category. Please try again.';
    }
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;