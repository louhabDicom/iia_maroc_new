<?php

// One-off read-only inspection of the legacy 2024 edition row.
//
// Confirms what the archive actually contains before it is copied into the new
// schema, rather than assuming. Credentials are read from .env so nothing is
// hardcoded here.

$host = '127.0.0.1';
$port = 3399;

$env = (string) file_get_contents(__DIR__.'/../.env');
preg_match('/^DB_PASSWORD=(.*)$/m', $env, $m);
$password = trim($m[1] ?? '', "\"' \r\n");

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname=iiamaroc_new_db;charset=utf8mb4",
    'arabcia_2026',
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'TABLES ('.count($tables).'): '.implode(', ', $tables)."\n\n";

foreach (['edition_edition', 'edition_sponsor', 'edition_speaker'] as $table) {
    if (! in_array($table, $tables, true)) {
        continue;
    }

    echo "=== {$table} ===\n";
    $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
    echo 'columns: '.implode(', ', $columns)."\n";

    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    echo 'rows: '.count($rows)."\n";

    foreach (array_slice($rows, 0, 5) as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    }
    echo "\n";
}
