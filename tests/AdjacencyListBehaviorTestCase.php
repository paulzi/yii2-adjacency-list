<?php
/**
 * @link https://github.com/paulzi/yii2-adjacency-list
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-adjacency-list/blob/master/LICENSE)
 */

namespace paulzi\adjacencyList\tests;

use paulzi\adjacencyList\tests\migrations\TestMigration;
use paulzi\adjacencyList\tests\models\Node;
use paulzi\adjacencyList\tests\models\NodeJoin;
use Yii;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
class AdjacencyListBehaviorTestCase extends BaseTestCase
{

    public function testGetParents()
    {
        $data = [1, 3, 9];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(27)->parents));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(27)->parents));

        $data = [4, 13];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(40)->getParents(2)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(40)->getParents(2)->all()));
    }

    public function testGetParentsOrdered()
    {
        $data = [41, 44];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(57)->parentsOrdered));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(57)->parentsOrdered));

        $data = [];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(1)->getParentsOrdered()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(1)->getParentsOrdered()));
    }

    public function testGetParent()
    {
        $data = 42;
        $this->assertEquals($data, Node::findOne(46)->parent->id);
        $this->assertEquals($data, NodeJoin::findOne(46)->parent->id);

        $data = null;
        $this->assertEquals($data, Node::findOne(41)->getParent()->one());
        $this->assertEquals($data, NodeJoin::findOne(41)->getParent()->one());
    }

    public function testGetRoot()
    {
        $data = 41;
        $this->assertEquals($data, Node::findOne(56)->root->id);
        $this->assertEquals($data, NodeJoin::findOne(56)->root->id);

        $data = 1;
        $this->assertEquals($data, Node::findOne(1)->getRoot()->one()->id);
        $this->assertEquals($data, NodeJoin::findOne(1)->getRoot()->one()->id);
    }

    public function testGetDescendants()
    {
        $data = [11, 12, 13, 32, 33, 34, 35, 36, 37, 38, 39, 40];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(4)->descendants));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(4)->descendants));

        $data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(1)->getDescendants(2, true)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(1)->getDescendants(2, true)->all()));
    }

    public function testGetDescendantsOrdered()
    {
        $data = [];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(32)->descendantsOrdered));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(32)->descendantsOrdered));

        $data = [57, 56, 55, 54];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(44)->getDescendantsOrdered()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(44)->getDescendantsOrdered()));
    }

    public function testGetChildren()
    {
        $data = [42, 44, 43, 45];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(41)->children));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(41)->children));

        $data = [];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(19)->getChildren()->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(19)->getChildren()->all()));
    }

    public function testGetLeaves()
    {
        $data = [50, 51, 52, 53];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(43)->leaves));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(43)->leaves));

        $data = [];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(3)->getLeaves(1)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, NodeJoin::findOne(3)->getLeaves(1)->all()));
    }

    public function testGetPrev()
    {
        $data = 16;
        $this->assertEquals($data, Node::findOne(15)->prev->id);
        $this->assertEquals($data, NodeJoin::findOne(15)->prev->id);

        $data = null;
        $this->assertEquals($data, Node::findOne(57)->getPrev()->one());
        $this->assertEquals($data, NodeJoin::findOne(57)->getPrev()->one());
    }

    public function testGetNext()
    {
        $data = 17;
        $this->assertEquals($data, Node::findOne(18)->next->id);
        $this->assertEquals($data, NodeJoin::findOne(18)->next->id);

        $data = null;
        $this->assertEquals($data, Node::findOne(58)->getNext()->one());
        $this->assertEquals($data, NodeJoin::findOne(58)->getNext()->one());
    }

    public function testPopulateTree()
    {
        $node = Node::findOne(4);
        $node->populateTree();
        $this->assertEquals(true, $node->isRelationPopulated('children'));
        $this->assertEquals(true, $node->children[0]->isRelationPopulated('children'));
        $this->assertEquals(true, $node->children[0]->children[0]->isRelationPopulated('children'));
        $this->assertEquals(32, $node->children[0]->children[0]->id);

        $node = NodeJoin::findOne(44);
        $node->populateTree(1);
        $this->assertEquals(true, $node->isRelationPopulated('children'));
        $this->assertEquals(false, $node->children[0]->isRelationPopulated('children'));
        $this->assertEquals(57, $node->children[0]->id);

        $node = Node::findOne(37);
        $node->populateTree();
        $this->assertEquals(true, $node->isRelationPopulated('children'));

        $node = Node::findOne(37);
        $node->populateTree(1);
        $this->assertEquals(true, $node->isRelationPopulated('children'));
        $this->assertEquals([], $node->children);
    }

    public function testIsRoot()
    {
        $this->assertTrue(Node::findOne(41)->isRoot());
        $this->assertTrue(NodeJoin::findOne(41)->isRoot());

        $this->assertFalse(Node::findOne(21)->isRoot());
        $this->assertFalse(NodeJoin::findOne(21)->isRoot());
    }

    public function testIsChildOf()
    {
        $this->assertTrue(Node::findOne(35)->isChildOf(Node::findOne(1)));
        $this->assertTrue(NodeJoin::findOne(35)->isChildOf(Node::findOne(1)));

        $this->assertTrue(Node::findOne(50)->isChildOf(Node::findOne(43)));
        $this->assertTrue(NodeJoin::findOne(50)->isChildOf(Node::findOne(43)));

        $this->assertFalse(Node::findOne(10)->isChildOf(Node::findOne(23)));
        $this->assertFalse(NodeJoin::findOne(10)->isChildOf(Node::findOne(23)));

        $this->assertFalse(Node::findOne(10)->isChildOf(Node::findOne(8)));
        $this->assertFalse(NodeJoin::findOne(10)->isChildOf(Node::findOne(8)));

        $this->assertFalse(Node::findOne(10)->isChildOf(Node::findOne(10)));
        $this->assertFalse(NodeJoin::findOne(10)->isChildOf(Node::findOne(10)));

        $this->assertFalse(Node::findOne(10)->isChildOf(Node::findOne(43)));
        $this->assertFalse(NodeJoin::findOne(10)->isChildOf(Node::findOne(43)));
    }

    public function testIsLeaf()
    {
        $this->assertTrue(Node::findOne(22)->isLeaf());
        $this->assertTrue(NodeJoin::findOne(22)->isLeaf());

        $this->assertFalse(Node::findOne(45)->isLeaf());
        $this->assertFalse(NodeJoin::findOne(45)->isLeaf());
    }

    public function testGetParentsIds()
    {
        $data = [1];
        $this->assertEquals($data, Node::findOne(3)->getParentsIds(3, false));
        $this->assertEquals($data, NodeJoin::findOne(3)->getParentsIds(3, false));

        $data = [];
        $this->assertEquals($data, Node::findOne(41)->getParentsIds());
        $this->assertEquals($data, NodeJoin::findOne(41)->getParentsIds());
    }

    public function testGetDescendantsIds()
    {
        $data = [];
        $this->assertEquals($data, Node::findOne(54)->getDescendantsIds());
        $this->assertEquals($data, NodeJoin::findOne(54)->getDescendantsIds());

        $data = [[16, 15, 14]];
        $this->assertEquals($data, Node::findOne(5)->getDescendantsIds(5, false, false));
        $this->assertEquals($data, NodeJoin::findOne(5)->getDescendantsIds(5, false, false));

        $data = [8, 9, 10, 28, 27, 23, 26, 29, 24, 25, 30, 31];
        $this->assertEquals($data, Node::findOne(3)->getDescendantsIds(null, true));
        $data = [8, 9, 10, 23, 24, 25, 28, 27, 26, 29, 30, 31];
        $this->assertEquals($data, NodeJoin::findOne(3)->getDescendantsIds(null, true));
    }

    public function testMakeRootInsert()
    {
        (new TestMigration())->up();
        $dataSet = new ArrayDataSet(require(__DIR__ . '/data/empty.php'));
        $this->getDatabaseTester()->setDataSet($dataSet);
        $this->getDatabaseTester()->onSetUp();

        $node = new Node(['slug' => 'r']);
        $this->assertTrue($node->makeRoot()->save());

        $node = new NodeJoin(['slug' => 'r']);
        $this->assertTrue($node->makeRoot()->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-make-root-insert.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testMakeRootUpdate()
    {
        $node = Node::findOne(51);
        $this->assertTrue($node->makeRoot()->save());

        $node = NodeJoin::findOne(2);
        $this->assertTrue($node->makeRoot()->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-make-root-update.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToInsertInNoEmpty()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->prependTo(Node::findOne(1))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->prependTo(NodeJoin::findOne(41))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-insert-in-no-empty.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToInsertInEmpty()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->prependTo(Node::findOne(27))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->prependTo(NodeJoin::findOne(56))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-insert-in-empty.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateSameNode()
    {
        $node = Node::findOne(2);
        $this->assertTrue($node->prependTo(Node::findOne(1))->save());

        $node = NodeJoin::findOne(43);
        $this->assertTrue($node->prependTo(NodeJoin::findOne(41))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-update-same-node.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateDeep()
    {
        $node = Node::findOne(4);
        $this->assertTrue($node->prependTo(Node::findOne(6))->save());

        $node = NodeJoin::findOne(44);
        $this->assertTrue($node->prependTo(NodeJoin::findOne(59))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-update-deep.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateOut()
    {
        $node = Node::findOne(33);
        $this->assertTrue($node->prependTo(Node::findOne(4))->save());

        $node = NodeJoin::findOne(60);
        $this->assertTrue($node->prependTo(NodeJoin::findOne(41))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-update-out.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateAnotherTree()
    {
        $node = Node::findOne(45);
        $this->assertTrue($node->prependTo(Node::findOne(3))->save());

        $node = NodeJoin::findOne(31);
        $this->assertTrue($node->prependTo(NodeJoin::findOne(49))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-update-another-tree.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateSelf()
    {
        $node = Node::findOne(4);
        $this->assertTrue($node->prependTo(Node::findOne(1))->save());

        $node = NodeJoin::findOne(60);
        $this->assertTrue($node->prependTo(NodeJoin::findOne(45))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/data.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testPrependToInsertExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->prependTo(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testPrependToUpdateExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = Node::findOne(2);
        $node->prependTo(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testPrependToUpdateExceptionIsRaisedWhenTargetIsSame()
    {
        $node = Node::findOne(3);
        $node->prependTo(Node::findOne(3))->save();
    }

    public function testPrependToUpdateNoExceptionIsRaisedWhenTargetIsChildAndNoCheckLoop()
    {
        $node = Node::findOne(10);
        $this->assertTrue($node->prependTo(Node::findOne(30))->save());
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testPrependToUpdateExceptionIsRaisedWhenTargetIsChildAndCheckLoop()
    {
        $node = Node::findOne(10);
        $node->getBehavior('tree')->checkLoop = true;
        $this->assertTrue($node->prependTo(Node::findOne(30))->save());
    }

    public function testAppendToInsertInNoEmpty()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->appendTo(Node::findOne(1))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->appendTo(NodeJoin::findOne(41))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-insert-in-no-empty.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToInsertInEmpty()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->appendTo(Node::findOne(27))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->appendTo(NodeJoin::findOne(56))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-insert-in-empty.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateSameNode()
    {
        $node = Node::findOne(4);
        $this->assertTrue($node->appendTo(Node::findOne(1))->save());

        $node = NodeJoin::findOne(44);
        $this->assertTrue($node->appendTo(NodeJoin::findOne(41))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-update-same-node.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateDeep()
    {
        $node = Node::findOne(4);
        $this->assertTrue($node->appendTo(Node::findOne(6))->save());

        $node = NodeJoin::findOne(44);
        $this->assertTrue($node->appendTo(NodeJoin::findOne(59))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-update-deep.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateOut()
    {
        $node = Node::findOne(33);
        $this->assertTrue($node->appendTo(Node::findOne(4))->save());

        $node = NodeJoin::findOne(60);
        $this->assertTrue($node->appendTo(NodeJoin::findOne(41))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-update-out.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateAnotherTree()
    {
        $node = Node::findOne(43);
        $this->assertTrue($node->appendTo(Node::findOne(2))->save());

        $node = NodeJoin::findOne(38);
        $this->assertTrue($node->appendTo(NodeJoin::findOne(58))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-update-another-tree.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }


    public function testAppendToUpdateSelf()
    {
        $node = Node::findOne(2);
        $this->assertTrue($node->appendTo(Node::findOne(1))->save());

        $node = NodeJoin::findOne(58);
        $this->assertTrue($node->appendTo(NodeJoin::findOne(45))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/data.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testAppendToInsertExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->appendTo(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testAppendToUpdateExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = Node::findOne(2);
        $node->appendTo(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testAppendToUpdateExceptionIsRaisedWhenTargetIsSame()
    {
        $node = Node::findOne(3);
        $node->appendTo(Node::findOne(3))->save();
    }

    public function testAppendToUpdateNoExceptionIsRaisedWhenTargetIsChildAndNoCheckLoop()
    {
        $node = Node::findOne(10);
        $this->assertTrue($node->appendTo(Node::findOne(30))->save());
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testAppendToUpdateExceptionIsRaisedWhenTargetIsChildAndCheckLoop()
    {
        $node = Node::findOne(10);
        $node->getBehavior('tree')->checkLoop = true;
        $this->assertTrue($node->appendTo(Node::findOne(30))->save());
    }

    public function testInsertBeforeInsertNoGap()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->insertBefore(Node::findOne(2))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->insertBefore(NodeJoin::findOne(49))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-insert-no-gap.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeInsertGap()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->insertBefore(Node::findOne(14))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->insertBefore(NodeJoin::findOne(49))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-insert-gap.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeInsertBegin()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->insertBefore(Node::findOne(16))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->insertBefore(NodeJoin::findOne(57))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-insert-begin.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeUpdateSameNode()
    {
        $node = Node::findOne(4);
        $this->assertTrue($node->insertBefore(Node::findOne(2))->save());

        $node = NodeJoin::findOne(42);
        $this->assertTrue($node->insertBefore(NodeJoin::findOne(43))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-update-same-node.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeUpdateNext()
    {
        $node = Node::findOne(3);
        $this->assertTrue($node->insertBefore(Node::findOne(2))->save());

        $node = NodeJoin::findOne(44);
        $this->assertTrue($node->insertBefore(NodeJoin::findOne(43))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/data.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeUpdateAnotherTree()
    {
        $node = Node::findOne(13);
        $this->assertTrue($node->insertBefore(Node::findOne(45))->save());

        $node = NodeJoin::findOne(45);
        $this->assertTrue($node->insertBefore(NodeJoin::findOne(17))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-update-another-tree.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertBeforeInsertExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->insertBefore(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertBeforeInsertExceptionIsRaisedWhenTargetIsRoot()
    {
        $node = new Node(['name' => 'new']);
        $node->insertBefore(Node::findOne(1))->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertBeforeUpdateExceptionIsRaisedWhenTargetIsSame()
    {
        $node = Node::findOne(3);
        $node->insertBefore(Node::findOne(3))->save();
    }

    public function testInsertBeforeUpdateExceptionIsRaisedWhenTargetIsChildAndNoCheckLoop()
    {
        $node = Node::findOne(10);
        $this->assertTrue($node->insertBefore(Node::findOne(30))->save());
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertBeforeUpdateExceptionIsRaisedWhenTargetIsChildAndCheckLoop()
    {
        $node = Node::findOne(10);
        $node->getBehavior('tree')->checkLoop = true;
        $this->assertTrue($node->insertBefore(Node::findOne(30))->save());
    }

    public function testInsertAfterInsertNoGap()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->insertAfter(Node::findOne(4))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->insertAfter(NodeJoin::findOne(50))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-insert-no-gap.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterInsertGap()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->insertAfter(Node::findOne(19))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->insertAfter(NodeJoin::findOne(50))->save());


        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-insert-gap.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterInsertEnd()
    {
        $node = new Node(['slug' => 'new1']);
        $this->assertTrue($node->insertAfter(Node::findOne(14))->save());

        $node = new NodeJoin(['slug' => 'new2']);
        $this->assertTrue($node->insertAfter(NodeJoin::findOne(54))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-insert-end.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterUpdateSameNode()
    {
        $node = Node::findOne(2);
        $this->assertTrue($node->insertAfter(Node::findOne(4))->save());

        $node = NodeJoin::findOne(43);
        $this->assertTrue($node->insertAfter(NodeJoin::findOne(42))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-update-same-node.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterUpdatePrev()
    {
        $node = Node::findOne(3);
        $this->assertTrue($node->insertAfter(Node::findOne(4))->save());

        $node = NodeJoin::findOne(44);
        $this->assertTrue($node->insertAfter(NodeJoin::findOne(42))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/data.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterUpdateAnotherTree()
    {
        $node = Node::findOne(13);
        $this->assertTrue($node->insertAfter(Node::findOne(44))->save());

        $node = NodeJoin::findOne(45);
        $this->assertTrue($node->insertAfter(NodeJoin::findOne(16))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-update-another-tree.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertAfterInsertExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->insertAfter(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertAfterInsertExceptionIsRaisedWhenTargetIsRoot()
    {
        $node = new Node(['slug' => 'new']);
        $node->insertAfter(Node::findOne(1))->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertAfterUpdateExceptionIsRaisedWhenTargetIsSame()
    {
        $node = Node::findOne(3);
        $node->insertAfter(Node::findOne(3))->save();
    }

    public function testInsertAfterUpdateExceptionIsRaisedWhenTargetIsChildAndNoCheckLoop()
    {
        $node = Node::findOne(10);
        $this->assertTrue($node->insertAfter(Node::findOne(30))->save());
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertAfterUpdateExceptionIsRaisedWhenTargetIsChildAndCheckLoop()
    {
        $node = Node::findOne(10);
        $node->getBehavior('tree')->checkLoop = true;
        $this->assertTrue($node->insertAfter(Node::findOne(30))->save());
    }

    public function testDelete()
    {
        $this->assertEquals(1, Node::findOne(3)->delete());

        $this->assertEquals(1, NodeJoin::findOne(43)->delete());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-delete.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testDeleteRoot()
    {
        Node::findOne(1)->delete();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testDeleteExceptionIsRaisedWhenNodeIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->delete();
    }

    public function testDeleteWithChildren()
    {
        $this->assertEquals(13, Node::findOne(3)->deleteWithChildren());

        $this->assertEquals(5, NodeJoin::findOne(43)->deleteWithChildren());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-delete-with-children.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeleteWithChildrenRoot()
    {
        $this->assertEquals(40, Node::findOne(1)->deleteWithChildren());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-delete-with-children-root.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeleteWithChildrenRootJoin()
    {
        $this->assertEquals(21, NodeJoin::findOne(41)->deleteWithChildren());

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-delete-with-children-root-join.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testDeleteWithChildrenExceptionIsRaisedWhenNodeIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->deleteWithChildren();

    }

    public function testReorderChildren()
    {
        $this->assertEquals(true, Node::findOne(4)->reorderChildren(true) > 0);

        $this->assertEquals(true, NodeJoin::findOne(41)->reorderChildren(false) > 0);

        $dataSet = $this->getConnection()->createDataSet(['tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-reorder-children.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\NotSupportedException
     */
    public function testExceptionIsRaisedWhenInsertIsCalled()
    {
        $node = new Node(['slug' => 'new']);
        $node->insert();
    }

    public function testUpdate()
    {
        $node = Node::findOne(3);
        $node->slug = 'update';
        $this->assertEquals(1, $node->update());
    }
}