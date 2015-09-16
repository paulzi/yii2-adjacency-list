<?php
/**
 * @link https://github.com/paulzi/yii2-adjacency-list
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-adjacency-list/blob/master/LICENSE)
 */

namespace paulzi\adjacencylist;

use yii\base\Behavior;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;


/**
 * Adjacency List Behavior for Yii2
 * @author PaulZi <pavel.zimakoff@gmail.com>
 *
 * @property ActiveRecord $owner
 */
class AdjacencyListBehavior extends Behavior
{
    const OPERATION_MAKE_ROOT       = 1;
    const OPERATION_PREPEND_TO      = 2;
    const OPERATION_APPEND_TO       = 3;
    const OPERATION_INSERT_BEFORE   = 4;
    const OPERATION_INSERT_AFTER    = 5;
    const OPERATION_DELETE_ALL      = 6;

    /**
     * @var string
     */
    public $parentAttribute = 'parent_id';

    /**
     * @var string
     */
    public $sortAttribute = 'sort';

    /**
     * @var int
     */
    public $step = 100;

    /**
     * @var bool
     */
    public $checkLoop = false;

    /**
     * @var int
     */
    public $parentsJoinLevels = 3;

    /**
     * @var int
     */
    public $childrenJoinLevels = 3;

    /**
     * @var bool
     */
    protected $operation;

    /**
     * @var ActiveRecord|self|null
     */
    protected $node;

    /**
     * @var ActiveRecord[]
     */
    private $_parentsOrdered;

    /**
     * @var array
     */
    private $_parentsIds;

    /**
     * @var array
     */
    private $_childrenIds;


    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT   => 'beforeSave',
            ActiveRecord::EVENT_AFTER_INSERT    => 'afterSave',
            ActiveRecord::EVENT_BEFORE_UPDATE   => 'beforeSave',
            ActiveRecord::EVENT_AFTER_UPDATE    => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE   => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_DELETE    => 'afterDelete',
        ];
    }

    /**
     * @param int|null $depth
     * @return \yii\db\ActiveQuery
     * @throws Exception
     */
    public function getParents($depth = null)
    {
        $tableName = $this->owner->tableName();
        $ids = $this->getParentsIds($depth);
        $query = $this->owner->find()
            ->andWhere(["{$tableName}.[[" . $this->getPrimaryKey() . "]]" => $ids]);
        $query->multiple = true;
        return $query;
    }

    /**
     * @return ActiveRecord[]
     * @throws Exception
     */
    public function getParentsOrdered()
    {
        if ($this->_parentsOrdered !== null) {
            return $this->_parentsOrdered;
        }
        $parents = $this->getParents()->all();
        $ids = array_flip($this->getParentsIds());
        $primaryKey = $this->getPrimaryKey();
        usort($parents, function($a, $b) use ($ids, $primaryKey) {
            $aIdx = $ids[$a->$primaryKey];
            $bIdx = $ids[$b->$primaryKey];
            if ($aIdx == $bIdx) {
                return 0;
            } else {
                return $aIdx > $bIdx ? -1 : 1;
            }
        });
        return $this->_parentsOrdered = $parents;
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws Exception
     */
    public function getParent()
    {
        return $this->owner->hasOne($this->owner->className(), [$this->getPrimaryKey() => $this->parentAttribute]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoot()
    {
        $tableName = $this->owner->tableName();
        $id = $this->getParentsIds();
        $id = $id ? $id[count($id) - 1] : $this->owner->primaryKey;
        $query = $this->owner->find()
            ->andWhere(["{$tableName}.[[" . $this->getPrimaryKey() . "]]" => $id]);
        $query->multiple = false;
        return $query;
    }

    /**
     * @param int|null $depth
     * @param bool $andSelf
     * @return \yii\db\ActiveQuery
     */
    public function getDescendants($depth = null, $andSelf = false)
    {
        $tableName = $this->owner->tableName();
        $ids = $this->getDescendantsIds($depth, true);
        if ($andSelf) {
            $ids[] = $this->owner->getPrimaryKey();
        }
        $query = $this->owner->find()
            ->andWhere(["{$tableName}.[[" . $this->getPrimaryKey() . "]]" => $ids]);
        $query->multiple = true;
        return $query;
    }

    /**
     * @return ActiveRecord[]
     * @throws Exception
     */
    public function getDescendantsOrdered()
    {
        $descendants = $this->owner->descendants;
        $ids = array_flip($this->getDescendantsIds(null, true));
        $primaryKey = $this->getPrimaryKey();
        usort($descendants, function($a, $b) use ($ids, $primaryKey) {
            $aIdx = $ids[$a->$primaryKey];
            $bIdx = $ids[$b->$primaryKey];
            if ($aIdx == $bIdx) {
                return 0;
            } else {
                return $aIdx > $bIdx ? -1 : 1;
            }
        });
        return $descendants;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        $result = $this->owner->hasMany($this->owner->className(), [$this->parentAttribute => $this->getPrimaryKey()]);
        if ($this->sortAttribute !== null) {
            $result->orderBy([$this->sortAttribute => SORT_ASC]);
        }
        return $result;
    }

    /**
     * @param int|null $depth
     * @return \yii\db\ActiveQuery
     */
    public function getLeaves($depth = null)
    {
        $query = $this->getDescendants($depth)
            ->joinWith(['children' => function ($query) {
                /** @var \yii\db\ActiveQuery $query */
                $modelClass = $query->modelClass;
                $query
                    ->from($modelClass::tableName() . ' children')
                    ->orderBy(null);
            }])
            ->andWhere(["children.[[{$this->parentAttribute}]]" => null]);
        $query->multiple = true;
        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws NotSupportedException
     */
    public function getPrev()
    {
        if ($this->sortAttribute === null) {
            throw new NotSupportedException('prev() not allow if not set sortAttribute');
        }
        $tableName = $this->owner->tableName();
        $query = $this->owner->find()
            ->andWhere([
                'and',
                ["{$tableName}.[[{$this->parentAttribute}]]" => $this->owner->getAttribute($this->parentAttribute)],
                ['<', "{$tableName}.[[{$this->sortAttribute}]]", $this->owner->getAttribute($this->sortAttribute)],
            ])
            ->orderBy(["{$tableName}.[[{$this->sortAttribute}]]" => SORT_DESC])
            ->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws NotSupportedException
     */
    public function getNext()
    {
        if ($this->sortAttribute === null) {
            throw new NotSupportedException('next() not allow if not set sortAttribute');
        }
        $tableName = $this->owner->tableName();
        $query = $this->owner->find()
            ->andWhere([
                'and',
                ["{$tableName}.[[{$this->parentAttribute}]]" => $this->owner->getAttribute($this->parentAttribute)],
                ['>', "{$tableName}.[[{$this->sortAttribute}]]", $this->owner->getAttribute($this->sortAttribute)],
            ])
            ->orderBy(["{$tableName}.[[{$this->sortAttribute}]]" => SORT_ASC])
            ->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * @param int|null $depth
     * @param bool $cache
     * @return array
     */
    public function getParentsIds($depth = null, $cache = true)
    {
        if ($cache && $this->_parentsIds !== null) {
            return $depth === null ? $this->_parentsIds : array_slice($this->_parentsIds, 0, $depth);
        }

        $parentId = $this->owner->getAttribute($this->parentAttribute);
        if ($parentId === null) {
            if ($cache) {
                $this->_parentsIds = [];
            }
            return [];
        }
        $result     = [(string)$parentId];
        $tableName  = $this->owner->tableName();
        $primaryKey = $this->getPrimaryKey();
        $depthCur   = 1;
        while ($parentId !== null && ($depth === null || $depthCur < $depth)) {
            $query = (new Query())
                ->select(["lvl0.[[{$this->parentAttribute}]] AS lvl0"])
                ->from("{$tableName} lvl0")
                ->where(["lvl0.[[{$primaryKey}]]" => $parentId]);
            for ($i = 0; $i < $this->parentsJoinLevels && ($depth === null || $i + $depthCur + 1 < $depth); $i++) {
                $j = $i + 1;
                $query
                    ->addSelect(["lvl{$j}.[[{$this->parentAttribute}]] as lvl{$j}"])
                    ->leftJoin("{$tableName} lvl{$j}", "lvl{$j}.[[{$primaryKey}]] = lvl{$i}.[[{$this->parentAttribute}]]");
            }
            if ($parentIds = $query->one($this->owner->getDb())) {
                foreach ($parentIds as $parentId) {
                    $depthCur++;
                    if ($parentId === null) {
                        break;
                    }
                    $result[] = $parentId;
                }
            } else {
                $parentId = null;
            }
        }
        if ($cache && $depth === null) {
            $this->_parentsIds = $result;
        }
        return $result;
    }

    /**
     * @param int|null $depth
     * @param bool $flat
     * @param bool $cache
     * @return array
     */
    public function getDescendantsIds($depth = null, $flat = false, $cache = true)
    {
        if ($cache && $this->_childrenIds !== null) {
            $result = $depth === null ? $this->_childrenIds : array_slice($this->_childrenIds, 0, $depth);
            return $flat && !empty($result) ? call_user_func_array('array_merge', $result) : $result;
        }

        $result       = [];
        $tableName    = $this->owner->tableName();
        $primaryKey   = $this->getPrimaryKey();
        $depthCur     = 0;
        $lastLevelIds = [$this->owner->primaryKey];
        while (!empty($lastLevelIds) && ($depth === null || $depthCur < $depth)) {
            $levels = 1;
            $depthCur++;
            $query = (new Query())
                ->select(["lvl0.[[{$primaryKey}]] AS lvl0"])
                ->from("{$tableName} lvl0")
                ->where(["lvl0.[[{$this->parentAttribute}]]" => $lastLevelIds]);
            if ($this->sortAttribute !== null) {
                $query->orderBy(["lvl0.[[{$this->sortAttribute}]]" => SORT_ASC]);
            }
            for ($i = 0; $i < $this->childrenJoinLevels && ($depth === null || $i + $depthCur + 1 < $depth); $i++) {
                $depthCur++;
                $levels++;
                $j = $i + 1;
                $query
                    ->addSelect(["lvl{$j}.[[{$primaryKey}]] as lvl{$j}"])
                    ->leftJoin("{$tableName} lvl{$j}", [
                        'and',
                        "lvl{$j}.[[{$this->parentAttribute}]] = lvl{$i}.[[{$primaryKey}]]",
                        ['is not', "lvl{$i}.[[{$primaryKey}]]", null],
                    ]);
                if ($this->sortAttribute !== null) {
                    $query->addOrderBy(["lvl{$j}.[[{$this->sortAttribute}]]" => SORT_ASC]);
                }
            }
            if ($this->childrenJoinLevels) {
                $columns = [];
                foreach ($query->all($this->owner->getDb()) as $row) {
                    $level = 0;
                    foreach ($row as $id) {
                        if ($id !== null) {
                            $columns[$level][$id] = true;
                        }
                        $level++;
                    }
                }
                for ($i = 0; $i < $levels; $i++) {
                    if (isset($columns[$i])) {
                        $lastLevelIds = array_keys($columns[$i]);
                        $result[]     = $lastLevelIds;
                    } else {
                        $lastLevelIds = [];
                        break;
                    }
                }
            } else {
                $lastLevelIds = $query->column($this->owner->getDb());
                if ($lastLevelIds) {
                    $result[] = $lastLevelIds;
                }
            }
        }
        if ($cache && $depth === null) {
            $this->_childrenIds = $result;
        }
        return $flat && !empty($result) ? call_user_func_array('array_merge', $result) : $result;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return $this->owner->getAttribute($this->parentAttribute) === null;
    }

    /**
     * @param ActiveRecord $node
     * @return bool
     */
    public function isChildOf($node)
    {
        $ids = $this->getParentsIds();
        return in_array($node->getPrimaryKey(), $ids);
    }

    /**
     * @return bool
     */
    public function isLeaf()
    {
        return count($this->owner->children) === 0;
    }

    /**
     * @return ActiveRecord
     */
    public function makeRoot()
    {
        $this->operation = self::OPERATION_MAKE_ROOT;
        return $this->owner;
    }

    /**
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function prependTo($node)
    {
        $this->operation = self::OPERATION_PREPEND_TO;
        $this->node = $node;
        return $this->owner;
    }

    /**
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function appendTo($node)
    {
        $this->operation = self::OPERATION_APPEND_TO;
        $this->node = $node;
        return $this->owner;
    }

    /**
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function insertBefore($node)
    {
        $this->operation = self::OPERATION_INSERT_BEFORE;
        $this->node = $node;
        return $this->owner;
    }

    /**
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function insertAfter($node)
    {
        $this->operation = self::OPERATION_INSERT_AFTER;
        $this->node = $node;
        return $this->owner;
    }

    /**
     * @return bool|int
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function deleteWithChildren()
    {
        $this->operation = self::OPERATION_DELETE_ALL;
        if (!$this->owner->isTransactional(ActiveRecord::OP_DELETE)) {
            $transaction = $this->owner->getDb()->beginTransaction();
            try {
                $result = $this->deleteWithChildrenInternal();
                if ($result === false) {
                    $transaction->rollBack();
                } else {
                    $transaction->commit();
                }
                return $result;
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } else {
            $result = $this->deleteWithChildrenInternal();
        }
        return $result;
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function beforeSave()
    {
        if ($this->node !== null && !$this->node->getIsNewRecord()) {
            $this->node->refresh();
        }
        switch ($this->operation) {
            case self::OPERATION_MAKE_ROOT:
                $this->owner->setAttribute($this->parentAttribute, null);
                if ($this->sortAttribute !== null) {
                    $this->owner->setAttribute($this->sortAttribute, 0);
                }
                break;

            case self::OPERATION_PREPEND_TO:
                $this->insertIntoInternal(false);
                break;

            case self::OPERATION_APPEND_TO:
                $this->insertIntoInternal(true);
                break;

            case self::OPERATION_INSERT_BEFORE:
                $this->insertNearInternal(false);
                break;

            case self::OPERATION_INSERT_AFTER:
                $this->insertNearInternal(true);
                break;

            default:
                if ($this->owner->getIsNewRecord()) {
                    throw new NotSupportedException('Method "' . $this->owner->className() . '::insert" is not supported for inserting new nodes.');
                }
        }
    }

    /**
     *
     */
    public function afterSave()
    {
        $this->operation = null;
        $this->node      = null;
    }

    /**
     * @param \yii\base\ModelEvent $event
     * @throws Exception
     */
    public function beforeDelete($event)
    {
        if ($this->owner->getIsNewRecord()) {
            throw new Exception('Can not delete a node when it is new record.');
        }
        if ($this->isRoot() && $this->operation !== self::OPERATION_DELETE_ALL) {
            throw new Exception('Method "'. $this->owner->className() . '::delete" is not supported for deleting root nodes.');
        }
        $this->owner->refresh();
    }

    /**
     *
     */
    public function afterDelete()
    {
        if ($this->operation !== static::OPERATION_DELETE_ALL) {
            $this->owner->updateAll(
                [$this->parentAttribute => $this->owner->getAttribute($this->parentAttribute)],
                [$this->parentAttribute => $this->owner->getPrimaryKey()]
            );
        }
        $this->operation = null;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function getPrimaryKey()
    {
        $primaryKey = $this->owner->primaryKey();
        if (!isset($primaryKey[0])) {
            throw new Exception('"' . $this->owner->className() . '" must have a primary key.');
        }
        return $primaryKey[0];
    }

    /**
     * @param bool $forInsertNear
     * @throws Exception
     */
    protected function checkNode($forInsertNear = false)
    {
        if ($forInsertNear && $this->node->isRoot()) {
            throw new Exception('Can not move a node before/after root.');
        }
        if ($this->node->getIsNewRecord()) {
            throw new Exception('Can not move a node when the target node is new record.');
        }

        if ($this->owner->equals($this->node)) {
            throw new Exception('Can not move a node when the target node is same.');
        }

        if ($this->checkLoop && $this->node->isChildOf($this->owner)) {
            throw new Exception('Can not move a node when the target node is child.');
        }
    }

    /**
     * @param int $to
     * @param bool $forward
     */
    protected function moveTo($to, $forward)
    {
        $this->owner->setAttribute($this->sortAttribute, $to + ($forward ? 1 : -1));

        $tableName  = $this->owner->tableName();
        $primaryKey = $this->getPrimaryKey();
        $joinCondition = [
            'and',
            [
                "n.[[{$this->parentAttribute}]]" => $this->node->getAttribute($this->parentAttribute),
                "n.[[{$this->sortAttribute}]]"   => new Expression("{$tableName}.[[{$this->sortAttribute}]] " . ($forward ? '+' : '-') . " 1"),
            ]
        ];
        if (!$this->owner->getIsNewRecord()) {
            $joinCondition[] = ['<>', "n.[[{$primaryKey}]]", $this->owner->primaryKey];
        }

        $unallocated = (new Query())
            ->select("{$tableName}.[[{$this->sortAttribute}]]")
            ->from("{$tableName}")
            ->leftJoin("{$tableName} n", $joinCondition)
            ->where([
                'and',
                [$forward ? '>=' : '<=', "{$tableName}.[[{$this->sortAttribute}]]", $to],
                [
                    "{$tableName}.[[{$this->parentAttribute}]]" => $this->node->getAttribute($this->parentAttribute),
                    "n.[[{$this->sortAttribute}]]"              => null,
                ],
            ])
            ->orderBy(["{$tableName}.[[{$this->sortAttribute}]]" => $forward ? SORT_ASC : SORT_DESC])
            ->limit(1)
            ->scalar($this->owner->getDb());

        $this->owner->updateAll(
            [$this->sortAttribute => new Expression("[[{$this->sortAttribute}]] " . ($forward ? '+' : '-') . " 1")],
            [
                'and',
                ["[[{$this->parentAttribute}]]" => $this->node->getAttribute($this->parentAttribute)],
                ['between', $this->sortAttribute, $forward ? $to + 1 : $unallocated, $forward ? $unallocated : $to - 1],
            ]
        );
    }

    /**
     * Append to operation internal handler
     * @param bool $append
     * @throws Exception
     */
    protected function insertIntoInternal($append)
    {
        $this->checkNode(false);
        $this->owner->setAttribute($this->parentAttribute, $this->node->getPrimaryKey());
        if ($this->sortAttribute !== null) {
            $to = $this->node->getChildren()->orderBy(null);
            $to = $append ? $to->max($this->sortAttribute) : $to->min($this->sortAttribute);
            if (
                !$this->owner->getIsNewRecord() && (int)$to === $this->owner->getAttribute($this->sortAttribute)
                && !$this->owner->getDirtyAttributes([$this->parentAttribute])
            ) {

            } elseif ($to !== null) {
                $to += $append ? $this->step : -$this->step;
            } else {
                $to = 0;
            }
            $this->owner->setAttribute($this->sortAttribute, $to);
        }
    }

    /**
     * Insert operation internal handler
     * @param bool $forward
     * @throws Exception
     */
    protected function insertNearInternal($forward)
    {
        $this->checkNode(true);
        $this->owner->setAttribute($this->parentAttribute, $this->node->getAttribute($this->parentAttribute));
        if ($this->sortAttribute !== null) {
            $this->moveTo($this->node->getAttribute($this->sortAttribute), $forward);
        }
    }

    /**
     * @return int
     */
    protected function deleteWithChildrenInternal()
    {
        if (!$this->owner->beforeDelete()) {
            return false;
        }
        $ids = $this->getDescendantsIds(null, true);
        $ids[] = $this->owner->primaryKey;
        $result = $this->owner->deleteAll([$this->getPrimaryKey() => $ids]);
        $this->owner->setOldAttributes(null);
        $this->owner->afterDelete();
        return $result;
    }
}