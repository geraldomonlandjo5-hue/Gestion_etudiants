<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/formulaire.php';

$query = filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW);
$query = is_string($query) ? trim($query) : '';
if (mb_strlen($query) > 80) {
    $query = mb_substr($query, 0, 80);
}

try {
    $students = search_students($query);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    require_once __DIR__ . '/includes/layout.php';
    render_http_error(500, 'Erreur', 'Impossible d’accéder à la base de données.');
}

$form = take_form();
$flash = take_flash();
$count = count($students);

page_start('Gestion des étudiants');
render_flash($flash);
?>
  <div class="page-head">
    <h1>Étudiants</h1>
    <p>Ajoutez une fiche, recherchez-la, puis modifiez-la ou supprimez-la.</p>
  </div>

  <div class="layout">
    <?php
    render_student_form([
        'title' => 'Nouvel étudiant',
        'intro' => 'Tous les champs sont obligatoires. Le matricule ne peut pas être repris.',
        'action' => '/traitement.php',
        'submit' => 'Ajouter l’étudiant',
        'values' => $form['old'],
        'errors' => $form['errors'],
    ]);
    ?>

    <section class="panel">
      <div class="list-head">
        <h2>Liste</h2>
        <?php if ($count > 1): ?>
          <p class="muted"><?= $query === '' ? $count . ' étudiants' : $count . ' résultats' ?></p>
        <?php elseif ($count === 1): ?>
          <p class="muted"><?= $query === '' ? '1 étudiant' : '1 résultat' ?></p>
        <?php endif; ?>
      </div>

      <form class="search" method="get" action="/index.php">
        <label class="sr-only" for="q">Rechercher un étudiant</label>
        <input id="q" name="q" type="search" value="<?= e($query) ?>" maxlength="80" placeholder="Nom, prénom, matricule, filière…">
        <button class="button" type="submit">Rechercher</button>
        <?php if ($query !== ''): ?>
          <a class="button button-ghost" href="/index.php">Effacer</a>
        <?php endif; ?>
      </form>

      <?php if ($students === []): ?>
        <p class="empty">
          <?php if ($query === ''): ?>
            Aucun étudiant pour le moment. Utilisez le formulaire pour ajouter une fiche.
          <?php else: ?>
            Aucun étudiant ne correspond à « <?= e($query) ?> ».
          <?php endif; ?>
        </p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Matricule</th>
                <th>Filière</th>
                <th>Niveau</th>
                <th>E-mail</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $student): ?>
                <?php $label = student_label($student); ?>
                <tr>
                  <td data-label="Prénom"><?= e($student['prenom']) ?></td>
                  <td data-label="Nom"><?= e($student['nom']) ?></td>
                  <td data-label="Matricule"><code><?= e($student['matricule']) ?></code></td>
                  <td data-label="Filière"><?= e($student['filiere']) ?></td>
                  <td data-label="Niveau"><span class="pill"><?= e($student['niveau']) ?></span></td>
                  <td data-label="E-mail"><a class="email" href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></td>
                  <td data-label="Actions">
                    <div class="actions">
                      <a class="button button-small" href="/update.php?id=<?= e((string) $student['id']) ?>">Modifier</a>
                      <form method="post" action="/delete.php" data-confirm="Supprimer <?= e($label) ?> (<?= e($student['matricule']) ?>) ?">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= e((string) $student['id']) ?>">
                        <button class="button button-small button-danger" type="submit">Supprimer</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
<?php
page_end();
