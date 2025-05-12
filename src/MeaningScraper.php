<?php

declare(strict_types=1);

namespace BabyNames\Scraper;

require_once __DIR__ . '/../vendor/autoload.php';

use PDO;
use PDOException;
use RuntimeException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;

final class MeaningScraper
{
    private const API_URL = 'https://tb-babynames-api.thebump.com/v2/lists/meanings';
    private PDO $pdo;
    private Client $client;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly string $dbname = 'my_database',
        private readonly string $username = 'user',
        private readonly string $password = 'user_password'
    ) {
        $this->connectToDatabase();
        $this->initializeHttpClient();
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

    private function initializeHttpClient(): void
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
            'verify' => true,
            'http_errors' => false
        ]);
    }

    public function scrape(): void
    {
        try {
            $response = $this->client->get(self::API_URL);
            
            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException("API request failed with status code: " . $response->getStatusCode());
            }

            $data = json_decode((string) $response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("JSON decode error: " . json_last_error_msg());
            }

            $this->processMeanings($data);
            echo "Meanings processed successfully\n";
        } catch (GuzzleException $e) {
            throw new RuntimeException("API request failed: " . $e->getMessage());
        }
    }

    private function processMeanings(array $meanings): void
    {
        $sql = "INSERT INTO meanings (id, meaning) 
                VALUES (:id, :meaning)
                ON DUPLICATE KEY UPDATE 
                meaning = VALUES(meaning)";
        
        $stmt = $this->pdo->prepare($sql);
        $processed = 0;

        foreach ($meanings as $item) {
            // Некоторые ID могут содержать несколько значений через запятую
            $ids = explode(',', $item['id']);
            foreach ($ids as $id) {
                try {
                    $stmt->execute([
                        ':id' => trim($id),
                        ':meaning' => $item['meaning']
                    ]);
                    $processed++;
                    echo "Saved meaning: {$item['meaning']} (ID: $id)\n";
                } catch (PDOException $e) {
                    echo "Error saving meaning {$item['meaning']}: " . $e->getMessage() . "\n";
                }
            }
        }

        echo "Total meanings processed: $processed\n";
    }
}

// Запускаем скрипт
try {
    $scraper = new MeaningScraper();
    $scraper->scrape();
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 