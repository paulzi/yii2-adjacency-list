<?php

namespace tests\models;

use paulzi\adjacencylist\AdjacencyListQueryTrait;

class NodeQuery extends \yii\db\ActiveQuery
{
    use AdjacencyListQueryTrait;
}