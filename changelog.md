# Changelog for Laravel Repository

## 1.0.1.dev

- Correction d'un problème lors de la sélection d'un champ dans une relation sans avoir
  sélectionné de champ dans le modèle parent.
- Correction dans la méthode newQuery() pour que l'objet EloquentBuilder retourné
  encapsule une fresh instance du modèle.
- Ajout de la méthode newModel() dans EloquentRepository.

## 1.0.0 (2015-09-08)

- First release.
