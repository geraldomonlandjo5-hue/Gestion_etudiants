<?php

declare(strict_types=1);

function page_start(string $title): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');

    $documentTitle = $title === 'Gestion des étudiants'
        ? $title
        : $title . ' — Gestion des étudiants';
    $safeTitle = e($documentTitle);

    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle}</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="/index.php">
        <span class="mark" aria-hidden="true">É</span>
        <span>
          <span class="eyebrow">Registre</span>
          <span class="brand-name">Gestion des étudiants</span>
        </span>
      </a>
    </div>
  </header>
  <main class="wrap">
HTML;
}

function page_end(): void
{
    echo <<<HTML
  </main>
  <footer class="site-footer">
    <div class="wrap">Données enregistrées localement dans SQLite.</div>
  </footer>
  <script src="/assets/js/script.js"></script>
</body>
</html>
HTML;
}

function render_flash(?array $flash): void
{
    if ($flash === null || !is_string($flash['message'] ?? null) || $flash['message'] === '') {
        return;
    }

    $type = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
    $role = $type === 'success' ? 'status' : 'alert';
    echo '<p class="banner banner-' . $type . '" role="' . $role . '">' . e($flash['message']) . '</p>';
}

function render_http_error(int $status, string $title, string $message): never
{
    http_response_code($status);
    page_start($title);
    echo '<section class="panel narrow">';
    echo '<h1>' . e($title) . '</h1>';
    echo '<p>' . e($message) . '</p>';
    echo '<p><a class="button" href="/index.php">Retour à la liste</a></p>';
    echo '</section>';
    page_end();
    exit;
}
