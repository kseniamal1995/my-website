<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Name;

// Получаем имя из параметров маршрутизатора
// Переменная $name устанавливается маршрутизатором при извлечении из URL
// Если переменная не установлена, пытаемся извлечь из других источников

if (!isset($name)) {
    // Проверяем GET-параметр (для обратной совместимости)
    if (!empty($_GET['name'])) {
        $name = $_GET['name'];
    } else {
        // Если имя не определено, перенаправляем на главную
        header('Location: /');
        exit;
    }
}

try {
    $nameModel = new Name();
    $nameDetails = $nameModel->getNameDetails($name);
    
    if (!$nameDetails) {
        header('Location: /');
        exit;
    }
} catch (\Exception $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: /');
    exit;
}

// Функция для форматирования числа популярности
function formatPopularity($popularity) {
    if ($popularity >= 90) return "Very Popular";
    if ($popularity >= 70) return "Popular";
    if ($popularity >= 50) return "Moderate";
    if ($popularity >= 30) return "Uncommon";
    return "Rare";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nameDetails['name']); ?> - Name Details</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <header class="menu">
        <span>Name Generator</span>
        <nav>
            <a href="/" class="nav-link">Back to Search</a>
        </nav>
    </header>
    <main class="main">
        <article class="name-details">
            <header class="name-details__header">
                <h1 class="name-details__title"><?php echo htmlspecialchars($nameDetails['name']); ?></h1>
                <div class="name-details__meta">
                    <span class="name-details__gender">
                        <?php 
                        $genderText = [
                            'm' => 'Boy Name',
                            'f' => 'Girl Name',
                            'n' => 'Gender Neutral Name'
                        ][$nameDetails['gender']] ?? '';
                        echo htmlspecialchars($genderText); 
                        ?>
                    </span>
                    <?php if (!empty($nameDetails['origin_name'])): ?>
                        <span class="name-details__origin"><?php echo htmlspecialchars($nameDetails['origin_name']); ?> Origin</span>
                    <?php endif; ?>
                    <span class="name-details__popularity"><?php echo formatPopularity($nameDetails['popularity']); ?></span>
                </div>
            </header>

            <?php if (!empty($nameDetails['meaning_text'])): ?>
            <section class="name-details__section">
                <h2 class="name-details__section-title">Meaning</h2>
                <p class="name-details__text"><?php echo nl2br(htmlspecialchars($nameDetails['meaning_text'])); ?></p>
            </section>
            <?php endif; ?>

            <?php if (!empty($nameDetails['detail'])): ?>
            <section class="name-details__section">
                <h2 class="name-details__section-title">Additional Information</h2>
                <p class="name-details__text"><?php echo nl2br(htmlspecialchars($nameDetails['detail'])); ?></p>
            </section>
            <?php endif; ?>

            <?php if (!empty($nameDetails['styles'])): ?>
            <section class="name-details__section">
                <h2 class="name-details__section-title">Name Style</h2>
                <div class="name-details__tags">
                    <?php foreach ($nameDetails['styles'] as $style): ?>
                        <span class="name-details__tag"><?php echo htmlspecialchars($style['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($nameDetails['meanings'])): ?>
            <section class="name-details__section">
                <h2 class="name-details__section-title">Associated Meanings</h2>
                <div class="name-details__tags">
                    <?php foreach ($nameDetails['meanings'] as $meaning): ?>
                        <span class="name-details__tag"><?php echo htmlspecialchars($meaning['meaning']); ?></span>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </article>
    </main>
</body>
</html> 