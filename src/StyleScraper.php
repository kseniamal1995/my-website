<?php

declare(strict_types=1);

namespace BabyNames\Scraper;

// Автозагрузка через Composer
require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Cookie\CookieJar;
use PDO;
use PDOException;
use RuntimeException;
use DOMDocument;
use DOMXPath;
use Throwable;

final class StyleScraper
{
    private const USER_AGENTS = [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2.1 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0'
    ];
    
    private const URL = 'https://www.thebump.com/b/baby-name-generator';
    private const TIMEOUT = 30;
    private const CONNECT_TIMEOUT = 10;
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2;

    private PDO $pdo;
    private Client $client;
    private CookieJar $cookieJar;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly string $dbname = 'my_database',
        private readonly string $username = 'user',
        private readonly string $password = 'user_password'
    ) {
        $this->initializeServices();
    }

    private function initializeServices(): void
    {
        $this->connectToDatabase();
        $this->cookieJar = new CookieJar();
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
            'timeout' => self::TIMEOUT,
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'cookies' => $this->cookieJar,
            'headers' => $this->getRandomHeaders(),
            'verify' => true,
            'http_errors' => false,
            'allow_redirects' => [
                'max' => 5,
                'strict' => true,
                'referer' => true,
                'protocols' => ['https', 'http'],
                'track_redirects' => true
            ]
        ]);
    }

    private function getRandomHeaders(): array
    {
        $userAgent = self::USER_AGENTS[array_rand(self::USER_AGENTS)];
        
        return [
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Cache-Control' => 'max-age=0',
            'Referer' => 'https://www.google.com/',
            'DNT' => '1',
            'Pragma' => 'no-cache'
        ];
    }

    public function scrape(): void
    {
        try {
            $html = $this->fetchWebPageWithRetry();
            echo "HTML fetched successfully\n";
            var_dump($html);
            $styles = $this->extractStyles($html);
            echo "Styles extracted successfully\n";
            if (empty($styles)) {
                throw new RuntimeException("No styles found on the website");
            }
            
            $this->saveStyles($styles);
        } catch (Throwable $e) {
            throw new RuntimeException("Scraping failed: " . $e->getMessage(), 0, $e);
        }
    }

    private function fetchWebPageWithRetry(): string
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts < self::MAX_RETRIES) {
            try {
                // Случайная задержка между запросами
                if ($attempts > 0) {
                    $delay = self::RETRY_DELAY + rand(1, 3);
                    echo "Waiting {$delay} seconds before retry...\n";
                    sleep($delay);
                }

                // Обновляем заголовки перед каждой попыткой
                $this->client = new Client([
                    'timeout' => self::TIMEOUT,
                    'connect_timeout' => self::CONNECT_TIMEOUT,
                    'cookies' => $this->cookieJar,
                    'headers' => $this->getRandomHeaders(),
                    'verify' => true,
                    'http_errors' => false,
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['https', 'http'],
                        'track_redirects' => true
                    ]
                ]);

                $response = $this->client->get(self::URL);
                $statusCode = $response->getStatusCode();
                
                echo "Attempt " . ($attempts + 1) . " status code: " . $statusCode . "\n";
                
                if ($statusCode === 200) {
                    $html = (string) $response->getBody();
                    if (!empty($html)) {
                        return $html;
                    }
                }
                
                if ($statusCode === 403) {
                    echo "Access forbidden, trying with different headers...\n";
                }

                $lastError = "HTTP Error: " . $statusCode;
            } catch (GuzzleException $e) {
                $lastError = $e->getMessage();
                echo "Request failed: " . $lastError . "\n";
            }

            $attempts++;
        }

        throw new RuntimeException("Failed to fetch webpage after " . self::MAX_RETRIES . " attempts. Last error: " . $lastError);
    }

    private function extractStyles(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        return $this->findStylesInPage($xpath);
    }

    private function findStylesInPage(DOMXPath $xpath): array
    {
        $styles = [];
        
        // Массив XPath-выражений для поиска стилей на странице
        $xpathQueries = [
            // Поиск в фильтрах и кнопках
            '//div[contains(@class, "filter")]//button[contains(@class, "style")]',
            '//div[contains(@class, "filter")]//label[contains(@class, "style")]',
            // Поиск в выпадающих списках
            '//select[contains(@name, "style")]/option[not(position()=1)]',
            '//select[contains(@class, "style")]/option[not(position()=1)]',
            // Поиск в списках и категориях
            '//div[contains(@class, "name-styles")]//span',
            '//div[contains(@class, "style-filter")]//label',
            '//div[contains(@class, "name-category")]//a',
            // Поиск по дата-атрибутам
            '//*[@data-category="style"]',
            '//*[contains(@data-filter, "style")]',
            // Поиск в тегах с определенными классами
            '//div[contains(@class, "style-tag")]',
            '//span[contains(@class, "style-label")]'
        ];

        foreach ($xpathQueries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $style = $this->cleanStyleText($node->textContent);
                    if ($this->isValidStyle($style)) {
                        $styles[] = $style;
                    }
                }
            }
        }

        return array_unique($styles);
    }

    private function cleanStyleText(string $text): string
    {
        $text = trim($text);
        // Удаляем лишние пробелы и переносы строк
        $text = preg_replace('/\s+/', ' ', $text);
        // Удаляем специальные символы и HTML-сущности
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Приводим к правильному регистру
        $text = ucfirst(strtolower($text));
        
        return $text;
    }

    private function isValidStyle(string $style): bool
    {
        $style = trim($style);
        $minLength = 3;
        $invalidStyles = ['all', 'any', 'select style', 'style', 'filter', 'category', '-', 'select'];
        
        return !empty($style) 
            && strlen($style) >= $minLength 
            && !in_array(strtolower($style), $invalidStyles, true)
            && !is_numeric($style);
    }

    private function saveStyles(array $styles): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO styles (title, created) 
             VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE created = VALUES(created)"
        );
        
        $insertCount = 0;
        $timestamp = time();

        foreach ($styles as $style) {
            try {
                $stmt->execute([$style, $timestamp]);
                $insertCount++;
                echo "Inserted/Updated style: $style\n";
            } catch (PDOException $e) {
                echo "Error processing style '$style': " . $e->getMessage() . "\n";
            }
        }

        echo "Successfully processed $insertCount styles\n";
    }
}

// Выполнение скрипта
try {
    $scraper = new StyleScraper();
    $scraper->scrape();
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 