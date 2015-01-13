# DynamicTable
Permet de créer dynamiquement des bases de données et de les modifier

```php
require_once 'DynamicTable.php';

$table = new DynamicTable('my_dynamic_table', PDO);

// L'identifiant primaire est automatiquement créé et se nomme id
$table->create(array(
  array(
    'title' => 'nom',
    'type' => 'varchar',
    'comment' => 'Le nom de la personne',
  ),
  array(
    'title' => 'prenom',
    'type' => 'varchar',
  ),
  array(
    'title' => 'date_naissance',
    'type' => 'date',
  ),
  array(
    'title' => 'infos',
    'type' => 'text',
  ),
  array(
    'title' => 'ref_id',
    'type' => 'int',
  ),
))
  ->renameRow('infos', 'informations')
  ->addRow('adresse', 'varchar', 'after', 'prenom')
  ->orderRow('ref_id', 'id')
  ->deleteRow('date_naissance')
  ->renameTable('my_new_dynamic_table');
```
