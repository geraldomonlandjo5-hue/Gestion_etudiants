<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/formulaire.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    render_http_error(405, 'Méthode refusée', 'Cette page accepte uniquement l’affichage et l’enregistrement du formulaire.');
}

$id = filter_input($method === 'POST' ? INPUT_POST : INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($id) || $id < 1) {
    render_http_error(404, 'Étudiant introuvable', 'La fiche demandée n’existe pas.');
}

try {
    $student = find_student($id);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    render_http_error(500, 'Erreur', 'Impossible d’accéder à la base de données.');
}

if ($student === null) {
    render_http_error(404, 'Étudiant introuvable', 'La fiche demandée n’existe pas.');
}

$values = $student;
$errors = [];

if ($method === 'POST') {
    csrf_check();

    try {
        [$data, $errors] = validate_student($_POST, $id);
        if ($errors === []) {
            update_student($id, $data);
            flash('success', 'Les modifications ont été enregistrées.');
            redirect('/index.php');
        }
        $values = $data;
    } catch (PDOException $exception) {
        if (is_unique_violation($exception)) {
            $errors = ['matricule' => 'Ce matricule est déjà utilisé.'];
            $values = only_student_fields($_POST);
        } else {
            error_log($exception->getMessage());
            flash('error', 'La modification a échoué. Réessayez.');
            redirect('/update.php?id=' . $id);
        }
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        flash('error', 'La modification a échoué. Réessayez.');
        redirect('/update.php?id=' . $id);
    }
}

page_start('Modifier un étudiant');
render_flash(take_flash());
?>
  <div class="page-head">
    <h1>Modifier un étudiant</h1>
    <p><?= e(student_label($student)) ?> · <code><?= e($student['matricule']) ?></code></p>
  </div>
  <div class="narrow">
    <?php
    render_student_form([
        'title' => 'Fiche',
        'action' => '/update.php',
        'submit' => 'Enregistrer les modifications',
        'values' => $values,
        'errors' => $errors,
        'id' => $id,
        'cancel' => '/index.php',
    ]);
    ?>
  </div>
<?php
page_end();
