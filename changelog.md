Changelog for Laravel Repository
================================

1.2.0 (2016-03-22)
------------------

- Source code released with the MIT license
- Added license file
- Suppression du système d'options (bancal).
- Les fonctionnalités de filtrage de la méthode newQuery() ont été déportée dans filter().
- $order peut maintenant être fourni sous forme de tableau.
- La méthode getManyByIds() a été renommée getAllByIds().
- La méthode newModel() a été renommée model().
- La méthode newQuery() a été renommée query().
- La méthode create() retourne l'instance de l'entité créée et non plus l'id.
- Ajout de la méthode exists().
- Des commentaires ont été ajoutés aux parsers pour une meilleure compréhension de ceux-ci.

1.1.0 (2015-10-26)
------------------

- Ajout de paramètres/fonctionnalités à la méthode newQuery(), ainsi qu'aux méthodes
  de récupération, pour ordonner, limiter et appliquer un offset.
- $columns peut maintenant être fourni sous forme de chaîne de caractères.

1.0.1 (2015-10-23)
------------------

- Correction d'un problème lors de la sélection d'un champ dans une relation sans avoir
  sélectionné de champ dans le modèle parent.
- Correction dans la méthode newQuery() pour que l'objet EloquentBuilder retourné
  encapsule une fresh instance du modèle.
- Ajout de la méthode newModel() dans EloquentRepository.

1.0.0 (2015-09-08)
------------------

- First release.
