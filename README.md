# Gestion des étudiants

Petite application web en PHP pour gérer les fiches étudiants : ajout, liste, recherche, modification et suppression.

Chaque fiche contient le nom, le prénom, un matricule unique, la filière, le niveau et l’adresse e-mail.

## Lancer en local

PHP 8 avec l’extension PDO SQLite est requis (`php-sqlite3` sous Debian ou Ubuntu).

Depuis la racine du projet :

```bash
php -S localhost:8000 router.php
```

Ouvrir ensuite [http://localhost:8000](http://localhost:8000).

Le routeur sert les pages et empêche le téléchargement du fichier de base. La base est créée automatiquement au premier accès.

## Données

Les fiches sont enregistrées dans une base SQLite : `data/etudiants.sqlite`. Ce fichier reste sur la machine et n’est pas versionné.
