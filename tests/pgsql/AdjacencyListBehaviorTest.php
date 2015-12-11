<?php
/**
 * @link https://github.com/paulzi/yii2-adjacency-list
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-adjacency-list/blob/master/LICENSE)
 */

namespace paulzi\adjacencyList\tests\pgsql;

use paulzi\adjacencyList\tests\AdjacencyListBehaviorTestCase;

/**
 * @group pgsql
 *
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
class AdjacencyListBehaviorTest extends AdjacencyListBehaviorTestCase
{
    protected static $driverName = 'pgsql';
}