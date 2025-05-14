<?php

namespace App\Models;

use App\Database\Database;

class Name {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function searchNames($filters = []) {
        $conditions = [];
        $params = [];

        // Базовый запрос
        $sql = "SELECT n.id, n.name, n.gender, n.popularity, n.meaning_text 
                FROM names n";

        // Добавляем join с name_styles если есть фильтр по стилям
        if (!empty($filters['styles'])) {
            $sql .= " INNER JOIN name_styles ns ON n.id = ns.name_id";
            $placeholders = str_repeat('?,', count($filters['styles']) - 1) . '?';
            $conditions[] = "ns.style_id IN ($placeholders)";
            $params = array_merge($params, $filters['styles']);
        }

        // Фильтр по полу
        if (!empty($filters['gender'])) {
            $conditions[] = "n.gender = ?";
            $params[] = $filters['gender'];
        }

        // Добавляем условия в запрос
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Добавляем сортировку и лимит
        $sql .= " ORDER BY n.popularity DESC LIMIT 9";

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function getNameDetails($name) {
        // Получаем основную информацию об имени
        $sql = "SELECT n.id, n.name, n.gender, n.popularity, n.meaning_text, n.detail, o.name as origin_name 
                FROM names n 
                LEFT JOIN origins o ON n.origin_id = o.id 
                WHERE LOWER(n.name) = LOWER(?)";
        
        $stmt = $this->db->query($sql, [strtolower($name)]);
        $nameDetails = $stmt->fetch();
        
        if (!$nameDetails) {
            return null;
        }

        // Получаем стили имени
        $sql = "SELECT s.name, s.slug 
                FROM styles s 
                INNER JOIN name_styles ns ON s.id = ns.style_id 
                WHERE ns.name_id = ?";
        
        $stmt = $this->db->query($sql, [$nameDetails['id']]);
        $nameDetails['styles'] = $stmt->fetchAll();

        // Получаем значения имени
        $sql = "SELECT m.meaning 
                FROM meanings m 
                INNER JOIN name_meanings nm ON m.id = nm.meaning_id 
                WHERE nm.name_id = ?";
        
        $stmt = $this->db->query($sql, [$nameDetails['id']]);
        $nameDetails['meanings'] = $stmt->fetchAll();

        return $nameDetails;
    }
} 