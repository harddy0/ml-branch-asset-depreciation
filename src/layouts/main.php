<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'My App') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSET_URL ?>css/style.css">
    <script>const BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body class="h-full bg-slate-100 overflow-hidden flex flex-col">

    <?php include dirname(__DIR__) . '/includes/header.php'; ?>

    <div class="flex flex-1 h-0 overflow-hidden">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <main class="flex-1 overflow-y-auto p-8 bg-[#fdfdfd]">
            <div class="animate-fadeIn max-w-7xl mx-auto">
                <?php echo $content; ?>
            </div>
        </main>
    </div>

</body>
</html>
<script>
window.formatFullDate = function (input) {
    if (!input && input !== 0) return '';
    try {
        const d = input instanceof Date ? input : new Date(String(input));
        if (!isNaN(d))
            return new Intl.DateTimeFormat('en-US', { month:'long', day:'numeric', year:'numeric' }).format(d);
    } catch (e) {}
    return String(input);
};
window.setFullDate = function (id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = window.formatFullDate(val);
};
</script>
