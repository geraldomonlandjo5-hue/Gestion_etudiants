<?php

declare(strict_types=1);

const DB_PATH = __DIR__ . '/data/etudiants.sqlite';

const NIVEAUX = [
    'L1' => 'L1 — Licence 1',
    'L2' => 'L2 — Licence 2',
    'L3' => 'L3 — Licence 3',
    'M1' => 'M1 — Master 1',
    'M2' => 'M2 — Master 2',
    'Doctorat' => 'Doctorat',
];

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $directory = dirname(DB_PATH);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Impossible de créer le dossier de données.');
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec("PRAGMA busy_timeout = 3000");
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS etudiants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            prenom TEXT NOT NULL,
            matricule TEXT NOT NULL COLLATE NOCASE UNIQUE,
            filiere TEXT NOT NULL,
            niveau TEXT NOT NULL,
            email TEXT NOT NULL,
            recherche TEXT NOT NULL,
            cree_le TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )'
    );

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    start_session();
    $sent = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';

    if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="fr"><meta charset="utf-8"><title>Requête refusée</title><p>La requête a été refusée. Rechargez la page et réessayez.</p></html>';
        exit;
    }
}

function flash(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    start_session();
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function remember_form(array $errors, array $old): void
{
    start_session();
    $_SESSION['form'] = [
        'errors' => $errors,
        'old' => only_student_fields($old),
    ];
}

function take_form(): array
{
    start_session();
    $form = $_SESSION['form'] ?? null;
    unset($_SESSION['form']);

    if (!is_array($form)) {
        return ['errors' => [], 'old' => []];
    }

    return [
        'errors' => is_array($form['errors'] ?? null) ? $form['errors'] : [],
        'old' => is_array($form['old'] ?? null) ? only_student_fields($form['old']) : [],
    ];
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function field(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }

    $value = str_replace("\0", '', trim($value));
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return $value;
}

function only_student_fields(array $input): array
{
    return [
        'nom' => field($input, 'nom'),
        'prenom' => field($input, 'prenom'),
        'matricule' => field($input, 'matricule'),
        'filiere' => field($input, 'filiere'),
        'niveau' => field($input, 'niveau'),
        'email' => field($input, 'email'),
    ];
}

function fold(string $value): string
{
    $value = mb_strtolower($value, 'UTF-8');
    $map = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'ae',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'œ' => 'oe',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
    ];

    return strtr($value, $map);
}

function search_blob(array $data): string
{
    return fold(implode(' ', [
        $data['nom'],
        $data['prenom'],
        $data['matricule'],
        $data['filiere'],
        $data['niveau'],
        $data['email'],
    ]));
}

function like_pattern(string $value): string
{
    return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
}

function is_unique_violation(PDOException $exception): bool
{
    $state = $exception->errorInfo[0] ?? '';

    return $state === '23000' || str_contains($exception->getMessage(), 'UNIQUE');
}

function validate_student(array $input, ?int $ignoreId = null): array
{
    $data = only_student_fields($input);
    $errors = [];

    if ($data['nom'] === '' || mb_strlen($data['nom']) > 80) {
        $errors['nom'] = 'Le nom est requis (80 caractères maximum).';
    }
    if ($data['prenom'] === '' || mb_strlen($data['prenom']) > 80) {
        $errors['prenom'] = 'Le prénom est requis (80 caractères maximum).';
    }
    if (
        mb_strlen($data['matricule']) < 2
        || mb_strlen($data['matricule']) > 30
        || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,29}$/', $data['matricule'])
    ) {
        $errors['matricule'] = 'Le matricule doit contenir 2 à 30 caractères (lettres, chiffres, point, tiret ou underscore).';
    }
    if ($data['filiere'] === '' || mb_strlen($data['filiere']) > 80) {
        $errors['filiere'] = 'La filière est requise (80 caractères maximum).';
    }
    if (!array_key_exists($data['niveau'], NIVEAUX)) {
        $errors['niveau'] = 'Choisissez un niveau dans la liste.';
    }
    if (
        $data['email'] === ''
        || mb_strlen($data['email']) > 120
        || filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false
    ) {
        $errors['email'] = 'Indiquez une adresse e-mail valide.';
    }

    if (!isset($errors['matricule'])) {
        $sql = 'SELECT id FROM etudiants WHERE matricule = :matricule';
        $params = ['matricule' => $data['matricule']];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }
        $statement = db()->prepare($sql);
        $statement->execute($params);
        if ($statement->fetch()) {
            $errors['matricule'] = 'Ce matricule est déjà utilisé.';
        }
    }

    return [$data, $errors];
}

function find_student(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT id, nom, prenom, matricule, filiere, niveau, email
         FROM etudiants WHERE id = :id'
    );
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();

    return $row ?: null;
}

function search_students(string $query): array
{
    if ($query === '') {
        $statement = db()->query(
            'SELECT id, nom, prenom, matricule, filiere, niveau, email
             FROM etudiants
             ORDER BY nom COLLATE NOCASE, prenom COLLATE NOCASE, id'
        );

        return $statement->fetchAll();
    }

    $statement = db()->prepare(
        "SELECT id, nom, prenom, matricule, filiere, niveau, email
         FROM etudiants
         WHERE recherche LIKE :q ESCAPE '\\'
         ORDER BY nom COLLATE NOCASE, prenom COLLATE NOCASE, id"
    );
    $statement->execute(['q' => like_pattern(fold($query))]);

    return $statement->fetchAll();
}

function insert_student(array $data): void
{
    $statement = db()->prepare(
        'INSERT INTO etudiants (nom, prenom, matricule, filiere, niveau, email, recherche)
         VALUES (:nom, :prenom, :matricule, :filiere, :niveau, :email, :recherche)'
    );
    $statement->execute([
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'matricule' => $data['matricule'],
        'filiere' => $data['filiere'],
        'niveau' => $data['niveau'],
        'email' => $data['email'],
        'recherche' => search_blob($data),
    ]);
}

function update_student(int $id, array $data): void
{
    $statement = db()->prepare(
        'UPDATE etudiants
         SET nom = :nom, prenom = :prenom, matricule = :matricule,
             filiere = :filiere, niveau = :niveau, email = :email, recherche = :recherche
         WHERE id = :id'
    );
    $statement->execute([
        'id' => $id,
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'matricule' => $data['matricule'],
        'filiere' => $data['filiere'],
        'niveau' => $data['niveau'],
        'email' => $data['email'],
        'recherche' => search_blob($data),
    ]);
}

function delete_student(int $id): void
{
    $statement = db()->prepare('DELETE FROM etudiants WHERE id = :id');
    $statement->execute(['id' => $id]);
}

function student_label(array $student): string
{
    return trim($student['prenom'] . ' ' . $student['nom']);
}
