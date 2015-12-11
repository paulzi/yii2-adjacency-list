<?php
/**
 * @link https://github.com/paulzi/yii2-adjacency-list
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-adjacency-list/blob/master/LICENSE)
 */

namespace paulzi\adjacencyList\tests\sqlite;

use paulzi\adjacencyList\tests\AdjacencyListQueryTraitTestCase;

/**
 * @group sqlite
 *
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
class AdjacencyListQueryTraitTest extends AdjacencyListQueryTraitTestCase
{
    protected static $driverName = 'sqlite';
}