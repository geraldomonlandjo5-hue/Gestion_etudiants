<?php

declare(strict_types=1);

function render_student_form(array $options): void
{
    $values = array_merge([
        'nom' => '',
        'prenom' => '',
        'matricule' => '',
        'filiere' => '',
        'niveau' => '',
        'email' => '',
    ], $options['values'] ?? []);
    $errors = is_array($options['errors'] ?? null) ? $options['errors'] : [];
    $action = (string) ($options['action'] ?? '/traitement.php');
    $submit = (string) ($options['submit'] ?? 'Enregistrer');
    $title = (string) ($options['title'] ?? 'Nouvel étudiant');
    $intro = (string) ($options['intro'] ?? '');
    $id = $options['id'] ?? null;
    $cancel = $options['cancel'] ?? null;

    echo '<section class="panel">';
    echo '<h2>' . e($title) . '</h2>';
    if ($intro !== '') {
        echo '<p class="muted">' . e($intro) . '</p>';
    }
    if ($errors !== []) {
        echo '<p class="banner banner-error" role="alert">Merci de corriger les champs indiqués.</p>';
    }

    echo '<form method="post" action="' . e($action) . '" accept-charset="UTF-8" novalidate>';
    echo '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
    if ($id !== null) {
        echo '<input type="hidden" name="id" value="' . e((string) $id) . '">';
    }

    echo '<div class="fields">';
    render_text_field('prenom', 'Prénom', $values['prenom'], $errors, 'given-name', 'Amina');
    render_text_field('nom', 'Nom', $values['nom'], $errors, 'family-name', 'Benali');
    render_text_field(
        'matricule',
        'Matricule',
        $values['matricule'],
        $errors,
        'off',
        'INF-2024-014',
        'Unique pour chaque étudiant.',
        false,
        'matricule'
    );
    render_select_field('niveau', 'Niveau', $values['niveau'], $errors);
    render_text_field('filiere', 'Filière', $values['filiere'], $errors, 'off', 'Informatique', '', true);
    render_text_field('email', 'Adresse e-mail', $values['email'], $errors, 'email', 'amina.benali@exemple.fr', '', true, 'email');
    echo '</div>';

    echo '<div class="form-actions">';
    echo '<button class="button" type="submit">' . e($submit) . '</button>';
    if (is_string($cancel) && $cancel !== '') {
        echo '<a class="button button-ghost" href="' . e($cancel) . '">Annuler</a>';
    }
    echo '</div>';
    echo '</form>';
    echo '</section>';
}

function render_text_field(
    string $name,
    string $label,
    string $value,
    array $errors,
    string $autocomplete,
    string $placeholder,
    string $hint = '',
    bool $wide = false,
    string $type = 'text'
): void {
    $error = is_string($errors[$name] ?? null) ? $errors[$name] : '';
    $errorId = 'erreur-' . $name;
    $classes = 'field' . ($wide ? ' field-wide' : '') . ($error !== '' ? ' field-invalid' : '');
    $maxlength = $name === 'matricule' ? 30 : ($name === 'email' ? 120 : 80);
    $inputType = $type === 'email' ? 'email' : 'text';
    $spellcheck = $name === 'matricule' || $name === 'email' ? 'false' : 'true';

    echo '<div class="' . $classes . '">';
    echo '<label for="' . e($name) . '">' . e($label) . '</label>';
    echo '<input id="' . e($name) . '" name="' . e($name) . '" type="' . e($inputType) . '"';
    echo ' value="' . e($value) . '" maxlength="' . $maxlength . '" autocomplete="' . e($autocomplete) . '"';
    echo ' placeholder="' . e($placeholder) . '" spellcheck="' . $spellcheck . '" required';
    if ($name === 'matricule') {
        echo ' pattern="[A-Za-z0-9][A-Za-z0-9._-]{1,29}"';
    }
    if ($error !== '') {
        echo ' aria-invalid="true" aria-describedby="' . e($errorId) . '"';
    } elseif ($hint !== '') {
        echo ' aria-describedby="aide-' . e($name) . '"';
    }
    echo '>';
    if ($hint !== '') {
        echo '<p class="hint" id="aide-' . e($name) . '">' . e($hint) . '</p>';
    }
    if ($error !== '') {
        echo '<p class="field-error" id="' . e($errorId) . '">' . e($error) . '</p>';
    }
    echo '</div>';
}

function render_select_field(string $name, string $label, string $value, array $errors): void
{
    $error = is_string($errors[$name] ?? null) ? $errors[$name] : '';
    $errorId = 'erreur-' . $name;
    $classes = 'field' . ($error !== '' ? ' field-invalid' : '');

    echo '<div class="' . $classes . '">';
    echo '<label for="' . e($name) . '">' . e($label) . '</label>';
    echo '<select id="' . e($name) . '" name="' . e($name) . '" required';
    if ($error !== '') {
        echo ' aria-invalid="true" aria-describedby="' . e($errorId) . '"';
    }
    echo '>';
    echo '<option value="">Choisir un niveau</option>';
    foreach (NIVEAUX as $code => $libelle) {
        $selected = $value === $code ? ' selected' : '';
        echo '<option value="' . e($code) . '"' . $selected . '>' . e($libelle) . '</option>';
    }
    echo '</select>';
    if ($error !== '') {
        echo '<p class="field-error" id="' . e($errorId) . '">' . e($error) . '</p>';
    }
    echo '</div>';
}
