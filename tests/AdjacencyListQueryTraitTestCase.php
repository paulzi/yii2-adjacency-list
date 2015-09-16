<?php

namespace tests;

use tests\models\Node;
use tests\models\NodeJoin;
use Yii;

class AdjacencyListQueryTraitTestCase extends BaseTestCase
{
    public function testRoots()
    {
        $data = [1, 41];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::find()->roots()->orderBy('id')->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::find()->roots()->orderBy('id')->all()));
    }
}