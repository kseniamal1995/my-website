<?php

namespace App\Models;

use App\Database\Database;

class Style {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllStyles() {
        $stmt = $this->db->query("SELECT id, name, slug FROM styles ORDER BY name");
        return $stmt->fetchAll();
    }

    public function getStylesByIds($ids) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->db->query(
            "SELECT id, name, slug FROM styles WHERE id IN ($placeholders) ORDER BY name",
            $ids
        );
        return $stmt->fetchAll();
    }
} 