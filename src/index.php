<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $pdo = new PDO(
        "mysql:host=db;port=3306;dbname=my_database",
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

    // Include view
    require_once __DIR__ . '/views/names.php';

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}