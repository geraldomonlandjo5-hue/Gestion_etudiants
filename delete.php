<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

csrf_check();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!is_int($id) || $id < 1) {
    flash('error', 'La suppression a été refusée.');
    redirect('/index.php');
}

try {
    $student = find_student($id);
    if ($student === null) {
        flash('error', 'Cet étudiant est introuvable.');
        redirect('/index.php');
    }

    delete_student($id);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    flash('error', 'La suppression a échoué. Réessayez.');
    redirect('/index.php');
}

flash('success', 'La fiche de ' . student_label($student) . ' a été supprimée.');
redirect('/index.php');
