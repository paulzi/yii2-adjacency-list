# Yii2 Adjacency List Behavior

Implementation of adjacency list algorithm for storing the trees in DB tables.

[![Packagist Version](https://img.shields.io/packagist/v/paulzi/yii2-adjacency-list.svg)](https://packagist.org/packages/paulzi/yii2-adjacency-list)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/paulzi/yii2-adjacency-list/master.svg)](https://scrutinizer-ci.com/g/paulzi/yii2-adjacency-list/?branch=master)
[![Build Status](https://img.shields.io/travis/paulzi/yii2-adjacency-list/master.svg)](https://travis-ci.org/paulzi/yii2-adjacency-list)
[![Total Downloads](https://img.shields.io/packagist/dt/paulzi/yii2-adjacency-list.svg)](https://packagist.org/packages/paulzi/yii2-adjacency-list)

## Install

Install via Composer:

```bash
composer require paulzi/yii2-adjacency-list
```

or add

```bash
"paulzi/yii2-adjacency-list" : "^2.0"
```

to the `require` section of your `composer.json` file.

## Migrations example

```php
class m150722_150000_adjacency_list extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('{{%adjacency_list}}', [
            'id'        => Schema::TYPE_PK,
            'parent_id' => Schema::TYPE_INTEGER . ' NULL',
            'sort'      => Schema::TYPE_INTEGER . ' NOT NULL',
            'name'      => Schema::TYPE_STRING . ' NOT NULL', // example field
        ], $tableOptions);
        $this->createIndex('parent_sort', '{{%adjacency_list}}', ['parent_id', 'sort']);
    }
}
```

## Configuring

```php
use paulzi\adjacencyList\AdjacencyListBehavior;

class Sample extends \yii\db\ActiveRecord
{
    public function behaviors() {
        return [
            [
                'class' => AdjacencyListBehavior::className(),
            ],
        ];
    }
}
```

Optional you can setup Query for finding roots:

```php
class Sample extends \yii\db\ActiveRecord
{
    public static function find()
    {
        return new SampleQuery(get_called_class());
    }
}
```

Query class:

```php
use paulzi\adjacencyList\AdjacencyListQueryTrait;

class SampleQuery extends \yii\db\ActiveQuery
{
    use AdjacencyListQueryTrait;
}
```

## Sortable Behavior

This behavior attach SortableBehavior. You can use its methods (for example, reorder()).

## Options

- `$parentAttribute = 'parent_id'` - parent attribute in table schema.
- `$sortable = []` - SortableBehavior settings - see [paulzi/yii2-sortable](https://github.com/paulzi/yii2-sortable).
- `$checkLoop = false` - check loop when moving nodes (slower).
- `$parentsJoinLevels = 3` - amount of join levels, when finding ancestors.
- `$childrenJoinLevels = 3` - amount of join levels, when finding descendants.

## Usage

### Selection

**Getting the root nodes**

If you connect `AdjacencyListQueryTrait`, you can get all the root nodes:

```php
$roots = Sample::find()->roots()->all();
```

**Getting ancestors of a node**

To get ancestors of a node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$parents = $node11->parents; // via relation unsorted
$parents = $node11->parentsOrdered; // via relation sorted
$parents = $node11->getParents()->all(); // via query
$parents = $node11->getParents(2)->all(); // get 2 levels of ancestors
```

To get parent of a node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$parent = $node11->parent; // via relation
$parent = $node11->getParent()->one(); // via query
```

To get root of a node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$root = $node11->root; // via relation
$root = $node11->getRoot()->one(); // via query
```

For get ordered list of primary keys ancestors:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$ids = $node11->getParentsIds();
$ids = $node11->getParentsIds(3, false); // get 3 levels of ancestors primary keys with force updating from DB 
```

**Getting descendants of a node**

To get all the descendants of a node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$descendants = $node11->descendants; // via relation unsorted
$descendants = $node11->descendantsOrdered; // via relation sorted
$descendants = $node11->getDescendants()->all(); // via query
$descendants = $node11->getDescendants(2, true)->all(); // get 2 levels of descendants and self node
```
*Note: guaranteed order on each parent nodes, nodes of different parents can be mixed with each other and option childrenJoinLevels can change this order.

To populate `children` relations for self and descendants of a node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$tree = $node11->populateTree(); // populate all levels
$tree = $node11->populateTree(2); // populate 2 levels of descendants
```

To get the children of a node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$children = $node11->children; // via relation
$children = $node11->getChildren()->all(); // via query
```

For get ordered array of primary keys descendants per level:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$ids = $node11->getDescendantsIds(); // get array of per-level descendants primary keys
$ids = $node11->getDescendantsIds(null, true); // get flat array of descendants primary keys
$ids = $node11->getDescendantsIds(3, false, false); // get 3 levels array of per-level descendants primary keys with force updating from DB 
```
*Note: guaranteed order on each parent nodes, nodes of different parents can be mixed with each other and option childrenJoinLevels can change this order.

**Getting the leaves nodes**

To get all the leaves of a node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$leaves = $node11->leaves; // via relation
$leaves = $node11->getLeaves(2)->all(); // get 2 levels of leaves via query
```

**Getting the neighbors nodes**

To get the next node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$next = $node11->next; // via relation
$next = $node11->getNext()->one(); // via query
```

To get the previous node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$prev = $node11->prev; // via relation
$prev = $node11->getPrev()->one(); // via query
```

### Some checks

```php
$node1 = Sample::findOne(['name' => 'node 1']);
$node11 = Sample::findOne(['name' => 'node 1.1']);
$node11->isRoot() - return true, if node is root
$node11->isLeaf() - return true, if node is leaf
$node11->isChildOf($node1) - return true, if node11 is child of $node1
```


### Modifications

To make a root node:

```php
$node11 = new Sample();
$node11->name = 'node 1.1';
$node11->makeRoot()->save();
```

*Note: if you allow multiple trees and attribute `tree` is not set, it automatically takes the primary key value.*

To prepend a node as the first child of another node:

```php
$node1 = Sample::findOne(['name' => 'node 1']);
$node11 = new Sample();
$node11->name = 'node 1.1';
$node11->prependTo($node1)->save(); // inserting new node
```

To append a node as the last child of another node:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$node12 = Sample::findOne(['name' => 'node 1.2']);
$node12->appendTo($node11)->save(); // move existing node
```

To insert a node before another node:

```php
$node13 = Sample::findOne(['name' => 'node 1.3']);
$node12 = new Sample();
$node12->name = 'node 1.2';
$node12->insertBefore($node13)->save(); // inserting new node
```

To insert a node after another node:

```php
$node13 = Sample::findOne(['name' => 'node 1.3']);
$node14 = Sample::findOne(['name' => 'node 1.4']);
$node14->insertAfter($node13)->save(); // move existing node
```

To delete a node with descendants:

```php
$node11 = Sample::findOne(['name' => 'node 1.1']);
$node11->delete(); // delete node, children come up to the parent
$node11->deleteWithChildren(); // delete node and all descendants 
```
*Note: when deleting with delete() child nodes mixed with parent*

Reorder children:

```php
$model = Sample::findOne(1);
$model->reorderChildren(true); // reorder with center zero
$model = Sample::findOne(2);
$model->reorderChildren(false); // reorder from zero
```

## Updating from 1.x to 2.x

1) Move attributes `sortAttribute`, `step` into `sortable` attribute.
2) Change namespace from `paulzi\adjacencylist` to `paulzi\adjacencyList`.
3) Include `paulzi\yii2-sortable` (`composer update`).