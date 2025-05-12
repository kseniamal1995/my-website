<?php

declare(strict_types=1);

namespace BabyNames\Scraper;

require_once __DIR__ . '/../vendor/autoload.php';

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class CategoryScraper
{
    private PDO $pdo;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly string $dbname = 'my_database',
        private readonly string $username = 'user',
        private readonly string $password = 'user_password'
    ) {
        $this->connectToDatabase();
    }

    private function connectToDatabase(): void
    {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};port=3306;dbname={$this->dbname}",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            echo "Connected to database successfully\n";
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    public function scrape(string $jsonData): void
    {
        try {
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("JSON decode error: " . json_last_error_msg());
            }

            if (!isset($data['listCategories']['categories'])) {
                throw new RuntimeException("Invalid JSON structure: missing categories");
            }

            $this->processCategories($data['listCategories']['categories']);
            echo "Categories processed successfully\n";
        } catch (RuntimeException $e) {
            throw new RuntimeException("Scraping failed: " . $e->getMessage());
        }
    }

    private function processCategories(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $category) {
            // Проверяем обязательные поля
            if (!isset($category['id'], $category['name'], $category['slug'])) {
                echo "Skipping invalid category: missing required fields\n";
                continue;
            }

            // Сохраняем основную категорию
            $categoryId = $this->saveCategory(
                $category['id'],
                $category['name'],
                $category['slug'],
                $category['description'] ?? null,
                $parentId
            );

            // Обрабатываем дочерние категории, если они есть
            if (isset($category['childLists']) && is_array($category['childLists'])) {
                foreach ($category['childLists'] as $index => $childCategory) {
                    // Генерируем ID для дочерней категории, так как в JSON его нет
                    $childId = $category['id'] * 1000 + $index + 1;
                    
                    $this->saveCategory(
                        $childId,
                        strip_tags($childCategory['name']), // Удаляем HTML-теги из названия
                        $childCategory['slug'],
                        null,
                        $category['id']
                    );
                }
            }
        }
    }

    private function saveCategory(
        int $id,
        string $name,
        string $slug,
        ?string $description,
        ?int $parentId
    ): int {
        try {
            $sql = "INSERT INTO categories (id, name, slug, description, parent_id) 
                    VALUES (:id, :name, :slug, :description, :parent_id)
                    ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    slug = VALUES(slug),
                    description = VALUES(description),
                    parent_id = VALUES(parent_id)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':parent_id' => $parentId
            ]);

            echo "Saved category: $name (ID: $id, Parent: " . ($parentId ?? 'NULL') . ")\n";
            return $id;
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to save category: " . $e->getMessage());
        }
    }
}

// Пример использования:
try {
    $jsonData = file_get_contents(__DIR__ . '/categories.json');
    if ($jsonData === false) {
        throw new RuntimeException("Failed to read categories.json");
    }

    $scraper = new CategoryScraper();
    $scraper->scrape($jsonData);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 