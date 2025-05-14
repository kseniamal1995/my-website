<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Router\Router;
use App\Models\Origin;
use App\Models\Meaning;
use App\Models\Style;

// Настраиваем маршрутизатор
$router = new Router();

// Добавляем маршрут для деталей имени
$router->addRoute('/names/{name}', __DIR__ . '/names/index.php');

// Проверяем, соответствует ли запрос одному из зарегистрированных маршрутов
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/') {
    $router->run();
    exit;
}

// Если маршрут не найден, продолжаем выполнение основного кода страницы

try {
    $originModel = new Origin();
    $origins = $originModel->getAllOrigins();

    $meaningModel = new Meaning();
    $meanings = $meaningModel->getAllMeanings();

    $styleModel = new Style();
    $styles = $styleModel->getAllStyles();
} catch (\Exception $e) {
    // В случае ошибки подключения к БД, используем пустые массивы
    $origins = [];
    $meanings = [];
    $styles = [];
    error_log("Database error: " . $e->getMessage());
}

// Разделяем стили на две группы для отображения в два ряда
$stylesFirstRow = array_slice($styles, 0, 6);
$stylesSecondRow = array_slice($styles, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Name Generator</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="menu">
        <span>Name Generator</span>
        <nav>
            <div class="language-selector">
                <input type="checkbox" id="language-toggle" class="language-selector__toggle">
                <label for="language-toggle" class="language-selector__button">
                    <img src="assets/flags/en.svg" alt="English" class="language-selector__flag">
                    <span>English</span>
                    <svg class="language-selector__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </label>
                <div class="language-selector__menu">
                    <button class="language-selector__option">
                        <img src="assets/flags/en.svg" alt="English" class="language-selector__flag">
                        <span>English</span>
                    </button>
                    <button class="language-selector__option">
                        <img src="assets/flags/es.svg" alt="Spanish" class="language-selector__flag">
                        <span>Spanish</span>
                    </button>
                </div>
            </div>
        </nav>
    </header>
    <main class="main">
        <section class="titlePage">
            <h1 class="title1">Select a unique name for your baby</h1>
            <p class="body1">Still searching for the right name? Use our Baby Name Generator to find the perfect baby names based on your style and preferences</p>
        </section>
        <section class="filters">
        <section class="block">
            <h2 class="title2" style="text-align: left;">Search by gender</h2>
            <div class="gender-cards">
                <div class="gender-card gender-card__boys" data-selected="false">
                    <div class="gender-card__image__boys">
                        <img src="assets/images/man.svg" alt="Boys names" class="gender-card__icon">
                    </div>
                    <p class="body1 gender-card__title">Boys</p>
                </div>
                <div class="gender-card gender-card__girls" data-selected="false">
                    <div class="gender-card__image__girls">
                        <img src="assets/images/girls.svg" alt="Girls names" class="gender-card__icon">
                    </div>
                    <p class="body1 gender-card__title">Girls</p>
                </div>
                <div class="gender-card gender-card__either" data-selected="false">
                    <div class="gender-card__image__either">
                        <img src="assets/images/either.svg" alt="Either names" class="gender-card__icon">
                    </div>
                    <p class="body1 gender-card__title">Either</p>
                </div>
            </div>
        </section>
        <section class="block">
            <h2 class="title2" style="text-align: left; margin-bottom: 16px;">Style</h2>
            <div class="cardsStyleBlock">
                <div class="cardStyle"><p class="body1">All</p></div>
                <?php if (!empty($stylesFirstRow)): ?>
                    <?php foreach ($stylesFirstRow as $style): ?>
                        <div class="cardStyle" data-slug="<?php echo htmlspecialchars($style['slug']); ?>">
                            <p class="body1"><?php echo htmlspecialchars($style['name']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="cardsStyleBlock">
                <?php if (!empty($stylesSecondRow)): ?>
                    <?php foreach ($stylesSecondRow as $style): ?>
                        <div class="cardStyle" data-slug="<?php echo htmlspecialchars($style['slug']); ?>">
                            <p class="body1"><?php echo htmlspecialchars($style['name']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <section class="blockHorizontal">
        <section class="block">
            <h2 class="title2" style="text-align: left; margin-bottom: 16px;">Origin</h2>
            <div class="origin-selector">
                <button class="origin-selector__button body1">
                    <span>All</span>
                    <svg class="origin-selector__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="origin-selector__menu">
                    <button class="origin-selector__option selected body1">All</button>
                    <?php if (!empty($origins)): ?>
                        <?php foreach ($origins as $origin): ?>
                            <button class="origin-selector__option body1" data-slug="<?php echo htmlspecialchars($origin['slug']); ?>">
                                <?php echo htmlspecialchars($origin['short_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <section class="block">
            <h2 class="title2" style="text-align: left; margin-bottom: 16px;">Meaning</h2>
            <div class="origin-selector">
                <button class="origin-selector__button body1">
                    <span>All</span>
                    <svg class="origin-selector__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="origin-selector__menu">
                    <button class="origin-selector__option selected body1">All</button>
                    <?php if (!empty($meanings)): ?>
                        <?php foreach ($meanings as $meaning): ?>
                            <button class="origin-selector__option body1" data-id="<?php echo htmlspecialchars($meaning['id']); ?>">
                                <?php echo htmlspecialchars($meaning['meaning']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        </section>
    <button class="buttonPrimary body1">Generate names</button>
    </main>

    <footer>
        <p>&copy; 2024 My Website. All rights reserved.</p>
    </footer>

    <script src="assets/scripts/gender-cards.js"></script>
    <script src="assets/scripts/style-cards.js"></script>
    <script src="assets/scripts/origin-selector.js"></script>
    <script src="assets/scripts/search-form.js"></script>
</body>
</html> 