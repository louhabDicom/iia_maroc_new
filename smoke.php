<?php

/**
 * Page smoke test.
 *
 * Fetches every public route in every locale and reports the status, so a Blade
 * mistake on one page cannot hide behind the pages that happen to render.
 *
 * Run:  php smoke.php [base-url]
 */

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8123', '/');

// Every locale prefix, plus the unprefixed default.
$prefixes = ['', '/en', '/ar'];

$paths = [
    '/', '/programme', '/speakers', '/pricing', '/tarifs',
    '/venue', '/lieu', '/sponsors', '/partenaires',
    '/contact', '/archive', '/login', '/register', '/panier', '/inscription',
    '/compte', '/mes-commandes', '/commandes', '/verify-phone', '/archive/2024',
];

$failures = 0;
$checked = 0;

foreach ($prefixes as $prefix) {
    foreach ($paths as $path) {
        $url = $base.$prefix.$path;

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 30,
                'ignore_errors' => true,
                // No cookies: several routes redirect when signed in, and a
                // redirect is not a failure for a guest smoke test.
                'header'        => "Accept: text/html\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $status = 0;

        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
                $status = (int) $m[1];
            }
        }

        $checked++;

        // 3xx is acceptable: an authenticated-only page correctly sends a guest
        // to the sign-in form.
        $ok = $status >= 200 && $status < 400;

        if (! $ok) {
            $failures++;
            echo "FAIL  {$status}  {$prefix}{$path}\n";
        } else {
            echo "ok    {$status}  {$prefix}{$path}\n";
        }
    }
}

echo "\n{$checked} routes checked, {$failures} failing.\n";

exit($failures > 0 ? 1 : 0);