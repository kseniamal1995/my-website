<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baby Names</title>
    <link rel="stylesheet" href="public/css/styles.css">
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