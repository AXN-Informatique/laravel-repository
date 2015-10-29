# Laravel Repository

Ce package offre des fonctionnalités de base pour le repository pattern avec Laravel 5.

## Installation

Inclure le package avec Composer :

```
composer require axn/laravel-repository
```

Aucun service provider n'est à inclure pour utiliser ce package. Il y a juste besoin
que les classes des repositories étendent la classe `Axn\Repository\Eloquent\EloquentRepository`
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

- **getById**($id, $columns = null)
- **getBy**(array $criteria, $columns = null)
- **getManyByIds**(array $ids, $columns = null, $order = null, $limit = null, $offset = null)
- **getAll**($columns = null, $order = null, $limit = null, $offset = null)
- **getAllBy**(array $criteria, $columns = null, $order = null, $limit = null, $offset = null)
- **getAllDistinctBy**(array $criteria, $columns = null, $order = null, $limit = null, $offset = null)
- **paginate**($perPage, array $criteria = [], $columns = null, $order = null)
- **count**(array $criteria = [])
- **create**(array $data)
- **createMany**(array $datalist)
- **updateById**($id, array $data)
- **updateManyByIds**(array $ids, array $data)
- **updateBy**(array $criteria, array $data)
- **updateOrCreate**(array $attributes, array $data)
- **deleteById**($id, $force = false)
- **deleteManyByIds**(array $ids, $force = false)
- **deleteBy**(array $criteria, $force = false)

### Sélection de colonnes (paramètre $columns)

Le paramètre $columns permet de sélectionner les colonnes à récupérer. Il peut être
fourni sous forme de tableau, ou bien sous forme de chaîne (chaque colonne séparée
par une virgule). La récupération peut également se faire dans les relations. Exemple :

```php
// Colonnes sous forme de tableau :
$users = $userRepository->getAll([
    'username',
    'email'
]);

// Ou bien sous forme de chaîne de caractères :
$users = $userRepository->getAll('username, email');

// Avec sélection dans les relations :
$users = $userRepository->getAll([
    'username',
    'email',
    'roles.display_name',
    'roles.permissions.display_name'
]);
```

### Critères de filtrage (paramètre $criteria)

Le paramètre `$criteria` permet de filtrer les enregistrements à récupérer. Le filtrage
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
$users = $userRepository->getAllBy([
    'email LIKE' => '%@axn.fr'
]);

// IS NOT NULL
$users = $userRepository->getAllBy([
    'email NOT_EQUAL' => null
]);
```

### Règles de tri (paramètre $order)

Le paramètre `$order` permet de spécifier des règles de tri (ORDER BY). Ce paramètre
doit être fourni exclusivement sous forme de chaîne de caractères. Comme pour le
paramètre `$columns`, plusieurs règles peuvent être spécifiées en les séparant par
des virgules, et il est possible de préciser la direction (asc ou desc) après le nom
du champ en séparant par un espace. Exemple :

```php
$users = $userRepository->getAll(null, 'date_inscription desc, lastname, firstname');
```

### Limitation et décalage (paramètres $limit et $offset)

Aux méthodes `getAll*` peuvent être spécifiés les paramètres `$limit` et `$offset`
qui permettent de ne sélectionner qu'un nombre limité d'enregistrements.

### Ajout de méthodes à un repository Eloquent

Il est bien sûr possible d'ajouter des méthodes à un repository, si les méthodes
de base ne sont pas suffisantes. Les méthodes suivantes peuvent alors être utilisées
pour construire des requêtes (repository Eloquent, uniquement) :

- **newModel**(array $attributes = [], $exists = false)
- **newQuery**()
- **filter**($query, array $criteria, $columns = null, $order = null, $limit = null, $offset = null)

Exemples :

```php
// App\Repositories\EloquentUserRepository

public function getAllWithTrashed($columns = null, $order = null, $limit = null, $offset = null)
{
    $query = $this->newModel()->withTrashed();

    return $this->filter($query, [], $columns, $order, $limit, $offset)->get();
}

public function getAllActive($columns = null, $order = null, $limit = null, $offset = null)
{
    $query = $this->newQuery()->where('active', 1);

    return $this->filter($query, [], $columns, $order, $limit, $offset)->get();
}

public function getAllForDataTable(array $where = [])
{
    $query = $this->newQuery()
        ->join('profils', 'profils.id', '=', 'users.profil_id')
        ->select([
            'users.id',
            'users.username',
            'users.email',
            'profils.name'
        ]);

    if (!empty($where['profil_id'])) {
        $query->where('profils.id', $where['profil_id']);
    }

    return $query->getQuery()->get();
}
```
