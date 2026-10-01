<?php

// One-off maintenance helper: create the MySQL test database and grant the
// least-privilege application account access to it.
//
// The application account is granted rights on the test database only, so a
// test run can never touch the development data or the legacy source.

$host = '127.0.0.1';
$port = 3399;
$appUser = 'arabcia_2026';
$appPassword = null; // set below from the value already in .env
$testDatabase = 'arabcia_2026_test';

// Read the password out of the .env file rather than duplicating the secret.
// A regex is used instead of parse_ini_file() because the password contains
// characters (#, %, !) that ini parsing treats as syntax.
$envContents = (string) file_get_contents(__DIR__.'/../.env');

preg_match('/^DB_PASSWORD=(.*)$/m', $envContents, $matches);
$appPassword = trim($matches[1] ?? '', "\"' \r\n");

if ($appPassword === '') {
    fwrite(STDERR, "DB_PASSWORD is empty in .env\n");
    exit(1);
}

$pdo = new PDO("mysql:host={$host};port={$port}", 'root', '');

$pdo->exec(sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    $testDatabase
));

foreach (['localhost', '127.0.0.1'] as $grantHost) {
    $pdo->exec(sprintf(
        "GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%s'",
        $testDatabase,
        $appUser,
        $grantHost
    ));
}

$pdo->exec('FLUSH PRIVILEGES');

echo "granted {$appUser} on {$testDatabase}\n";
