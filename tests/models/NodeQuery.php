<?php
/**
 * @link https://github.com/paulzi/yii2-adjacency-list
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-adjacency-list/blob/master/LICENSE)
 */

namespace paulzi\adjacencyList\tests\models;

use paulzi\adjacencyList\AdjacencyListQueryTrait;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
class NodeQuery extends \yii\db\ActiveQuery
{
    use AdjacencyListQueryTrait;
}