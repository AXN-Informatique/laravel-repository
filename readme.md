## Laravel Repository

Ce package offre des fonctionnalités de base pour le repository pattern avec Laravel 5.

## Installation

Inclure le package avec Composer :

```
composer require axn/laravel-repository
```

Aucun service provider n'est à inclure pour utiliser ce package. Il y a juste besoin
que les classes des repositories étendent la classe Axn\Repository\Eloquent\EloquentRepository
et qu'il y ait une injection du modèle dans le constructeur :

```php
namespace App\Repositories;

use Axn\Repository\Eloquent\EloquentRepository;
use App\Models\User;

class EloquentUserRepository extends EloquentRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
```

## Utilisation

Les méthodes de base fournies sont les suivantes :

 - getById($id, array $columns);
 - getManyByIds(array $ids, array $columns);
 - getAll(array $columns);
 - getBy(array $criteria, array $columns);
 - getAllBy(array $criteria, array $columns);
 - getAllDistinctBy(array $criteria, array $columns);
 - paginate($perPage, array $criteria, array $columns);
 - count(array $criteria);
 - create(array $data);
 - createMany(array $datalist);
 - updateById($id, array $data);
 - updateManyByIds(array $ids, array $data);
 - updateBy(array $criteria, array $data);
 - updateOrCreate(array $attributes, array $data);
 - deleteById($id, $force);
 - deleteManyByIds(array $ids, $force);
 - deleteBy(array $criteria, $force);

Le paramètre $columns permet de sélectionner les colonnes à récupérer. La récupération
peut également se faire sur les relations. Exemple :

```php
$users = $userRepository->getAll(['username', 'email']);

// Avec sélection sur les relations :
$users = $userRepository->getAll([
    'username', 'email', 'roles.display_name', 'roles.permissions.display_name'
]);
```

Le paramètre $criteria permet de filtrer les enregistrements à récupérer. Le filtrage
peut également se faire sur les relations. Exemple :

```php
$users = $userRepository->getAllBy(['email' => 'john.dupont@axn.fr']);

// Avec critères de filtrage sur les relations (ici seront récupérés uniquement
// les utilisateurs ayant le rôle "admin") :
$users = $userRepository->getAllBy(['roles.name' => 'admin']);
```

Des opérateurs peuvent aussi être utilisés sur les critères. La liste des opérateurs
possibles est la suivante :

 - EQUAL (par défaut)
 - NOT_EQUAL
 - LIKE
 - NOT_LIKE
 - GREATER_THAN
 - GREATER_EQUAL
 - LESS_THAN
 - LESS_EQUAL
 - IN
 - NOT_IN

Exemple :

```php
$users = $userRepository->getAllBy(['email LIKE' => '%@axn.fr']);

// IS NOT NULL
$users = $userRepository->getAllBy(['email NOT_EQUAL' => null]);
```