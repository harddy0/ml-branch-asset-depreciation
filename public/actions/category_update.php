<?php
require_once __DIR__ . '/../../src/includes/init.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

$id       = (int)($_POST['id']                  ?? 0);
$code     = strtoupper(trim($_POST['category_code']    ?? ''));
$name     = trim($_POST['category_name']               ?? '');
$life     = (int)($_POST['asset_life_months']          ?? 0);

// Validation
if (!$id || empty($code) || empty($name) || $life < 1) {
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
    // Fetch old code for potential CASCADE awareness in message
    $old = $pdo->prepare("SELECT category_code FROM asset_categories WHERE id = ?");
    $old->execute([$id]);
    $oldRow = $old->fetch(PDO::FETCH_ASSOC);

    if (!$oldRow) {
        $_SESSION['flash_error'] = 'Category not found.';
        header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
    }

    $stmt = $pdo->prepare("UPDATE asset_categories SET category_code = ?, category_name = ?, asset_life_months = ? WHERE id = ?");
    $stmt->execute([$code, $name, $life, $id]);

    $_SESSION['flash_success'] = "Category \"{$name}\" ({$code}) updated successfully.";
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['flash_error'] = "Category code \"{$code}\" is already in use by another category.";
    } else {
        $_SESSION['flash_error'] = 'Failed to update category. Please try again.';
    }
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;