<?php
/**
 * @link https://github.com/paulzi/yii2-adjacency-list
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-adjacency-list/blob/master/LICENSE)
 */

namespace paulzi\adjacencyList\tests;

use paulzi\adjacencyList\tests\models\Node;
use paulzi\adjacencyList\tests\models\NodeJoin;
use Yii;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
class AdjacencyListQueryTraitTestCase extends BaseTestCase
{
    public function testRoots()
    {
        $data = [1, 41];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::find()->roots()->orderBy('id')->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::find()->roots()->orderBy('id')->all()));
    }
}