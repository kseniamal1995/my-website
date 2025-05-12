<?php

declare(strict_types=1);

namespace BabyNames\Scraper;

require_once __DIR__ . '/vendor/autoload.php';

use PDO;
use PDOException;
use RuntimeException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;

final class NameScraper
{
    private const API_URL = 'https://tb-babynames-api.thebump.com/v2/names/search';
    private const BATCH_SIZE = 1000;
    private const MIN_DELAY = 1;
    private const MAX_DELAY = 5;
    private const MAX_RETRIES = 3;
    
    private PDO $pdo;
    private Client $client;
    private int $totalProcessed = 0;

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
                'Content-Type' => 'application/json',
            ],
            'verify' => true,
            'http_errors' => false
        ]);
    }

    public function scrape(): void
    {
        try {
            $offset = 0;
            $hasMore = true;
            $retryCount = 0;
            $total = 0;

            // Payload для тела запроса
            $payload = [
                'sortBy' => '+popularity',
                'origin' => ['any'],
                'letter' => ['any'],
                'gender' => ['any'],
                'type' => 'any',
                'syllable' => ['any'],
                'meaning' => ['any']
            ];

            while ($hasMore && $retryCount < self::MAX_RETRIES) {
                try {
                    // Случайная задержка между запросами
                    $delay = rand(self::MIN_DELAY, self::MAX_DELAY);
                    echo "Waiting {$delay} seconds before next request...\n";
                    sleep($delay);

                    // Формируем URL с query параметрами
                    $url = self::API_URL . '?limit=' . self::BATCH_SIZE . '&offset=' . $offset;

                    $response = $this->client->post($url, [
                        'json' => $payload
                    ]);

                    if ($response->getStatusCode() !== 200) {
                        throw new RuntimeException("API request failed with status code: " . $response->getStatusCode());
                    }

                    $data = json_decode((string) $response->getBody(), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("JSON decode error: " . json_last_error_msg());
                    }

                    // Обновляем общее количество имен при первом запросе
                    if ($offset === 0 && isset($data['total'])) {
                        $total = $data['total'];
                        echo "Total names to process: $total\n";
                    }

                    // Проверяем, есть ли данные
                    if (empty($data['items'])) {
                        $hasMore = false;
                        echo "No more names to process\n";
                        break;
                    }

                    $this->processNames($data['items']);
                    
                    $offset += count($data['items']);
                    echo "Processed batch. Total names so far: {$this->totalProcessed} of $total\n";
                    
                    // Проверяем, достигли ли мы конца
                    if ($offset >= $total) {
                        $hasMore = false;
                        echo "Reached the end of available names\n";
                        break;
                    }

                    // Сбрасываем счетчик retry при успешном запросе
                    $retryCount = 0;

                } catch (GuzzleException | RuntimeException $e) {
                    echo "Error processing batch: " . $e->getMessage() . "\n";
                    $retryCount++;
                    
                    if ($retryCount >= self::MAX_RETRIES) {
                        echo "Max retries reached. Stopping...\n";
                        break;
                    }
                    
                    $retryDelay = $retryCount * 5; // Увеличиваем задержку с каждой попыткой
                    echo "Retry {$retryCount} of " . self::MAX_RETRIES . ". Waiting {$retryDelay} seconds...\n";
                    sleep($retryDelay);
                }
            }

            echo "Scraping completed. Total names processed: {$this->totalProcessed}\n";
        } catch (Throwable $e) {
            throw new RuntimeException("Scraping failed: " . $e->getMessage());
        }
    }

    private function processNames(array $names): void
    {
        $sql = "INSERT INTO names (name, gender, popularity, origin_id, meaning_text, detail) 
                VALUES (:name, :gender, :popularity, :origin_id, :meaning_text, :detail)
                ON DUPLICATE KEY UPDATE 
                gender = VALUES(gender),
                popularity = VALUES(popularity),
                origin_id = VALUES(origin_id),
                meaning_text = VALUES(meaning_text),
                detail = VALUES(detail)";
        
        $stmt = $this->pdo->prepare($sql);

        foreach ($names as $name) {
            try {
                // Получаем ID происхождения по названию
                $originId = null;
                if (!empty($name['origin'])) {
                    $originId = $this->getOriginIdByName($name['origin']);
                }

                $stmt->execute([
                    ':name' => $name['name'],
                    ':gender' => $this->normalizeGender($name['gender'] ?? 'unisex'),
                    ':popularity' => $name['popularityRanking'] ?? null,
                    ':origin_id' => $originId,
                    ':meaning_text' => $name['meaning'] ?? null,
                    ':detail' => $name['detail'] ?? null
                ]);

                $this->totalProcessed++;
                echo "Saved name: {$name['name']} (Rank: {$name['popularityRanking']})\n";
            } catch (PDOException $e) {
                echo "Error saving name {$name['name']}: " . $e->getMessage() . "\n";
            }
        }
    }

    private function getOriginIdByName(string $originName): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM origins WHERE short_name = :name OR name LIKE :pattern");
        $pattern = $originName . '%';
        $stmt->execute([':name' => $originName, ':pattern' => $pattern]);
        $result = $stmt->fetch(PDO::FETCH_COLUMN);
        return $result ?: null;
    }

    private function normalizeGender(string $gender): string
    {
        return match (strtolower($gender)) {
            'male', 'm', 'boy' => 'm',
            'female', 'f', 'girl' => 'f',
            'unisex' => 'n',
            default => 'n'
        };
    }
}

// Запускаем скрипт
try {
    $scraper = new NameScraper();
    $scraper->scrape();
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 