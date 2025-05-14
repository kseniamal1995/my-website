<?php

namespace App\Router;

/**
 * Простой маршрутизатор для приложения
 */
class Router
{
    /**
     * Зарегистрированные маршруты
     */
    private array $routes = [];
    
    /**
     * Добавить маршрут
     *
     * @param string $pattern Шаблон URL
     * @param callable|string $handler Обработчик маршрута
     * @return void
     */
    public function addRoute(string $pattern, $handler): void
    {
        $this->routes[$pattern] = $handler;
    }
    
    /**
     * Запустить маршрутизатор
     *
     * @return void
     */
    public function run(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $pathInfo = parse_url($requestUri, PHP_URL_PATH);
        
        // Для каждого зарегистрированного маршрута
        foreach ($this->routes as $pattern => $handler) {
            // Преобразуем шаблон в регулярное выражение
            $regexPattern = $this->patternToRegex($pattern);
            
            // Проверяем соответствие
            if (preg_match($regexPattern, $pathInfo, $matches)) {
                // Удаляем первый элемент (полное совпадение)
                array_shift($matches);
                
                // Извлекаем именованные параметры
                $params = [];
                preg_match_all('/{([^\/]+)}/', $pattern, $paramNames);
                if (!empty($paramNames[1])) {
                    foreach ($paramNames[1] as $index => $name) {
                        if (isset($matches[$index])) {
                            $params[$name] = $matches[$index];
                        }
                    }
                }
                
                // Обрабатываем маршрут
                $this->handleRoute($handler, $params);
                return;
            }
        }
        
        // Маршрут не найден
        $this->handleNotFound();
    }
    
    /**
     * Преобразовать шаблон маршрута в регулярное выражение
     *
     * @param string $pattern Шаблон URL
     * @return string Регулярное выражение
     */
    private function patternToRegex(string $pattern): string
    {
        // Заменяем параметры вида {name} на соответствующую группу в регулярном выражении
        $pattern = preg_replace('/{([^\/]+)}/', '([^/]+)', $pattern);
        
        // Экранируем все специальные символы
        $pattern = str_replace('/', '\/', $pattern);
        
        // Добавляем якоря и ограничители регулярного выражения
        return '/^' . $pattern . '\/?$/';
    }
    
    /**
     * Обработать маршрут
     *
     * @param callable|string $handler Обработчик маршрута
     * @param array $params Параметры маршрута
     * @return void
     */
    private function handleRoute($handler, array $params): void
    {
        if (is_callable($handler)) {
            // Если обработчик - функция, вызываем её с параметрами
            call_user_func_array($handler, $params);
        } elseif (is_string($handler) && file_exists($handler)) {
            // Если обработчик - путь к файлу, передаем параметры и подключаем его
            extract($params);
            include $handler;
        }
    }
    
    /**
     * Обработать случай, когда маршрут не найден
     *
     * @return void
     */
    private function handleNotFound(): void
    {
        header("HTTP/1.0 404 Not Found");
        echo '<h1>404 Not Found</h1>';
        echo '<p>The page you are looking for could not be found.</p>';
        exit;
    }
} 