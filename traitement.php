<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

csrf_check();

try {
    [$data, $errors] = validate_student($_POST);
    if ($errors !== []) {
        remember_form($errors, $data);
        redirect('/index.php');
    }

    insert_student($data);
} catch (PDOException $exception) {
    if (is_unique_violation($exception)) {
        remember_form(['matricule' => 'Ce matricule est déjà utilisé.'], only_student_fields($_POST));
        redirect('/index.php');
    }
    error_log($exception->getMessage());
    flash('error', 'L’enregistrement a échoué. Réessayez.');
    redirect('/index.php');
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    flash('error', 'L’enregistrement a échoué. Réessayez.');
    redirect('/index.php');
}

flash('success', 'L’étudiant a été ajouté.');
redirect('/index.php');
