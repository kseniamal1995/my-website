<?php

namespace App\Models;

use App\Database\Database;

class Meaning {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllMeanings() {
        $stmt = $this->db->query("SELECT id, meaning FROM meanings ORDER BY meaning");
        return $stmt->fetchAll();
    }

    public function getMeaningsByIds($ids) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->db->query(
            "SELECT id, meaning FROM meanings WHERE id IN ($placeholders) ORDER BY meaning",
            $ids
        );
        return $stmt->fetchAll();
    }
} 