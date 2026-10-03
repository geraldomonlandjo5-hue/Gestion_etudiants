<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($uri) ? rawurldecode($uri) : '/';

$blocked = str_contains($path, '..')
    || preg_match('#^/(data|includes)(/|$)#', $path) === 1
    || preg_match('#\.(sqlite3?|db)$#i', $path) === 1;

if ($blocked) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Introuvable.\n";
    return true;
}

return false;
