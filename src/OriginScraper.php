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

final class OriginScraper
{
    private const API_URL = 'https://tb-babynames-api.thebump.com/v2/lists/origins';
    private const API_URL_SHORT = 'https://tb-babynames-api.thebump.com/v2/lists/origins?isShort=true';
    
    private PDO $pdo;
    private Client $client;
    private array $shortNames = [];

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
            // Сначала получаем короткие имена
            $this->fetchShortNames();
            
            // Затем получаем полные данные и комбинируем их
            $this->fetchAndProcessFullData();
            
            echo "Origins processed successfully\n";
        } catch (GuzzleException $e) {
            throw new RuntimeException("API request failed: " . $e->getMessage());
        }
    }

    private function fetchShortNames(): void
    {
        $response = $this->client->get(self::API_URL_SHORT);
        
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException("Short names API request failed with status code: " . $response->getStatusCode());
        }

        $data = json_decode((string) $response->getBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON decode error for short names: " . json_last_error_msg());
        }

        // Создаем ассоциативный массив коротких имен
        foreach ($data as $letter => $names) {
            foreach ($names as $shortName) {
                $this->shortNames[$shortName] = $letter;
            }
        }

        echo "Fetched " . count($this->shortNames) . " short names\n";
    }

    private function fetchAndProcessFullData(): void
    {
        $response = $this->client->get(self::API_URL);
        
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException("Full data API request failed with status code: " . $response->getStatusCode());
        }

        $data = json_decode((string) $response->getBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON decode error for full data: " . json_last_error_msg());
        }

        $this->processOrigins($data);
    }

    private function processOrigins(array $data): void
    {
        $sql = "INSERT INTO origins (name, slug, short_name, letter) 
                VALUES (:name, :slug, :short_name, :letter)
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                short_name = VALUES(short_name),
                letter = VALUES(letter)";
        
        $stmt = $this->pdo->prepare($sql);
        $processed = 0;

        foreach ($data as $letter => $origins) {
            foreach ($origins as $origin) {
                try {
                    // Извлекаем короткое имя из полного
                    $fullName = $origin['name'];
                    $shortName = $this->findShortName($fullName);
                    
                    $stmt->execute([
                        ':name' => $fullName,
                        ':slug' => $origin['slug'],
                        ':short_name' => $shortName,
                        ':letter' => $letter
                    ]);
                    
                    $processed++;
                    echo "Saved origin: $fullName (Short: $shortName, Letter: $letter)\n";
                } catch (PDOException $e) {
                    echo "Error saving origin $fullName: " . $e->getMessage() . "\n";
                }
            }
        }

        echo "Total origins processed: $processed\n";
    }

    private function findShortName(string $fullName): ?string
    {
        // Удаляем "Baby Names" из полного имени
        $nameParts = explode(' Baby Names', $fullName);
        $cleanName = trim($nameParts[0]);

        // Ищем соответствие в коротких именах
        foreach ($this->shortNames as $shortName => $letter) {
            if (stripos($cleanName, $shortName) !== false) {
                return $shortName;
            }
        }

        return null;
    }
}

// Запускаем скрипт
try {
    $scraper = new OriginScraper();
    $scraper->scrape();
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 