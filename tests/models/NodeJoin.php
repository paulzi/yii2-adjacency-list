<?php
/**
 * @link https://github.com/paulzi/yii2-adjacency-list
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-adjacency-list/blob/master/LICENSE)
 */

namespace paulzi\adjacencyList\tests\models;

use paulzi\adjacencyList\AdjacencyListBehavior;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 *
 * @property integer $id
 * @property integer $parent_id
 * @property integer $sort
 * @property string $slug
 *
 * @property NodeJoin[] $parents
 * @property NodeJoin[] $parentsOrdered
 * @property NodeJoin $parent
 * @property NodeJoin $root
 * @property NodeJoin[] $descendants
 * @property NodeJoin[] $descendantsOrdered
 * @property NodeJoin[] $children
 * @property NodeJoin[] $leaves
 * @property NodeJoin $prev
 * @property NodeJoin $next
 *
 * @method static NodeJoin|null findOne() findOne($condition)
 *
 * @mixin AdjacencyListBehavior
 */
class NodeJoin extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tree}}';
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'tree' => [
                'class' => AdjacencyListBehavior::className(),
                'parentsJoinLevels'  => 3,
                'childrenJoinLevels' => 3,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @return NodeQuery
     */
    public static function find()
    {
        return new NodeQuery(get_called_class());
    }
}