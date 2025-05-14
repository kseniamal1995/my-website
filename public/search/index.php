<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Name;

try {
    $nameModel = new Name();
    
    // Получаем параметры из GET запроса
    $filters = [];
    
    // Фильтр по полу
    if (!empty($_GET['gender']) && in_array($_GET['gender'], ['m', 'f', 'n'])) {
        $filters['gender'] = $_GET['gender'];
    }
    
    // Фильтр по стилям
    if (!empty($_GET['styles'])) {
        $styles = explode(',', $_GET['styles']);
        if (!empty($styles)) {
            $filters['styles'] = array_map('intval', $styles);
        }
    }
    
    // Получаем имена
    $names = $nameModel->searchNames($filters);
} catch (\Exception $e) {
    $names = [];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Name Generator</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header class="menu">
        <span>Name Generator</span>
        <nav>
            <a href="/" class="nav-link">Back to Search</a>
        </nav>
    </header>
    <main class="main">
        <section class="titlePage">
            <h1 class="title1">Search Results</h1>
            <?php if (empty($names)): ?>
                <p class="body1">No names found matching your criteria. Try adjusting your filters.</p>
            <?php else: ?>
                <div class="names-grid">
                    <?php foreach ($names as $name): ?>
                        <a href="/names/<?php echo strtolower(urlencode($name['name'])); ?>" class="name-card">
                            <h2 class="name-card__title"><?php echo htmlspecialchars($name['name']); ?></h2>
                            <div class="name-card__content">
                                <span class="name-card__gender">
                                    <?php 
                                    $genderText = [
                                        'm' => 'Boy',
                                        'f' => 'Girl',
                                        'n' => 'Neutral'
                                    ][$name['gender']] ?? '';
                                    echo htmlspecialchars($genderText); 
                                    ?>
                                </span>
                                <?php if (!empty($name['meaning_text'])): ?>
                                    <p class="name-card__meaning"><?php echo htmlspecialchars($name['meaning_text']); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html> 