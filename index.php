<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=my_database",
        "user",
        "user_password",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Get filter values
    $gender = $_GET['gender'] ?? 'all';
    $meaning = $_GET['meaning'] ?? 'all';
    $origin = $_GET['origin'] ?? 'all';
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 20;

    // Prepare base query
    $baseQuery = "FROM names n 
                  LEFT JOIN origins o ON n.origin_id = o.id";
    $whereConditions = [];
    $params = [];

    if ($gender !== 'all') {
        $whereConditions[] = "n.gender = :gender";
        $params[':gender'] = $gender;
    }

    if ($meaning !== 'all') {
        $whereConditions[] = "n.meaning_text LIKE :meaning";
        $params[':meaning'] = "%$meaning%";
    }

    if ($origin !== 'all') {
        $whereConditions[] = "o.name = :origin";
        $params[':origin'] = $origin;
    }

    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) " . $baseQuery . " " . $whereClause);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $perPage);

    // Get names
    $offset = ($page - 1) * $perPage;
    $query = "SELECT n.*, o.name as origin_name " . $baseQuery . " " . $whereClause . " 
              ORDER BY n.popularity ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $names = $stmt->fetchAll();

    // Get unique origins for filter
    $originsStmt = $pdo->query("SELECT DISTINCT name FROM origins ORDER BY name");
    $origins = $originsStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get unique meanings for filter
    $meaningsStmt = $pdo->query("SELECT DISTINCT meaning_text FROM names WHERE meaning_text IS NOT NULL ORDER BY meaning_text");
    $meanings = $meaningsStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baby Names</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Baby Names</h1>
        
        <!-- Filters -->
        <form class="filters" method="GET" action="">
            <div class="filter-group">
                <label for="gender">Gender:</label>
                <select name="gender" id="gender">
                    <option value="all" <?= $gender === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="m" <?= $gender === 'm' ? 'selected' : '' ?>>Boy</option>
                    <option value="f" <?= $gender === 'f' ? 'selected' : '' ?>>Girl</option>
                    <option value="n" <?= $gender === 'n' ? 'selected' : '' ?>>Unisex</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="meaning">Meaning:</label>
                <select name="meaning" id="meaning">
                    <option value="all">All</option>
                    <?php foreach ($meanings as $m): ?>
                        <?php if ($m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= $meaning === $m ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="origin">Origin:</label>
                <select name="origin" id="origin">
                    <option value="all">All</option>
                    <?php foreach ($origins as $o): ?>
                        <option value="<?= htmlspecialchars($o) ?>" <?= $origin === $o ? 'selected' : '' ?>>
                            <?= htmlspecialchars($o) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Apply Filters</button>
        </form>

        <!-- Names List -->
        <div class="names-list">
            <?php foreach ($names as $name): ?>
                <div class="name-card">
                    <h2><?= htmlspecialchars($name['name']) ?></h2>
                    <div class="name-details">
                        <p class="gender">
                            <?= [
                                'm' => 'Boy',
                                'f' => 'Girl',
                                'n' => 'Unisex'
                            ][$name['gender']] ?? 'Unknown' ?>
                        </p>
                        <?php if ($name['origin_name']): ?>
                            <p class="origin"><?= htmlspecialchars($name['origin_name']) ?></p>
                        <?php endif; ?>
                        <?php if ($name['meaning_text']): ?>
                            <p class="meaning"><?= htmlspecialchars($name['meaning_text']) ?></p>
                        <?php endif; ?>
                        <?php if ($name['popularity']): ?>
                            <p class="popularity">Rank: #<?= htmlspecialchars($name['popularity']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&gender=<?= $gender ?>&meaning=<?= urlencode($meaning) ?>&origin=<?= urlencode($origin) ?>" class="page-link">&laquo; Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&gender=<?= $gender ?>&meaning=<?= urlencode($meaning) ?>&origin=<?= urlencode($origin) ?>" 
                       class="page-link <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&gender=<?= $gender ?>&meaning=<?= urlencode($meaning) ?>&origin=<?= urlencode($origin) ?>" class="page-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 