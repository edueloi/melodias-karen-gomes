<?php
require_once 'config.php';
$pdo = getDB();
$res = $pdo->query("PRAGMA table_info(profissionais)")->fetchAll();
foreach($res as $col) {
    echo $col['name'] . "\n";
}
