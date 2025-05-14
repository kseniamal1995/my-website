<?php

namespace App\Models;

use App\Database\Database;

class Origin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllOrigins() {
        $stmt = $this->db->query("SELECT id, short_name, slug, short_name FROM origins ORDER BY name");
        return $stmt->fetchAll();
    }

    public function getOriginsByLetter($letter) {
        $stmt = $this->db->query("SELECT id, short_name, slug, short_name FROM origins WHERE letter = ? ORDER BY name", [$letter]);
        return $stmt->fetchAll();
    }
} 