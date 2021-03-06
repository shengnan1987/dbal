<?php

namespace Doctrine\Tests\DBAL\Sharding\SQLAzure;

use Doctrine\DBAL\Sharding\SQLAzure\SQLAzureShardManager;

class SQLAzureShardManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testNoFederationName()
    {
        $this->expectException('Doctrine\DBAL\Sharding\ShardingException');
        $this->expectExceptionMessage('SQLAzure requires a federation name to be set during sharding configuration.');

        $conn = $this->createConnection(array('sharding' => array('distributionKey' => 'abc', 'distributionType' => 'integer')));
        $sm = new SQLAzureShardManager($conn);
    }

    public function testNoDistributionKey()
    {
        $this->expectException('Doctrine\DBAL\Sharding\ShardingException');
        $this->expectExceptionMessage('SQLAzure requires a distribution key to be set during sharding configuration.');

        $conn = $this->createConnection(array('sharding' => array('federationName' => 'abc', 'distributionType' => 'integer')));
        $sm = new SQLAzureShardManager($conn);
    }

    public function testNoDistributionType()
    {
        $this->expectException('Doctrine\DBAL\Sharding\ShardingException');

        $conn = $this->createConnection(array('sharding' => array('federationName' => 'abc', 'distributionKey' => 'foo')));
        $sm = new SQLAzureShardManager($conn);
    }

    public function testGetDefaultDistributionValue()
    {
        $conn = $this->createConnection(array('sharding' => array('federationName' => 'abc', 'distributionKey' => 'foo', 'distributionType' => 'integer')));

        $sm = new SQLAzureShardManager($conn);
        self::assertNull($sm->getCurrentDistributionValue());
    }

    public function testSelectGlobalTransactionActive()
    {
        $conn = $this->createConnection(array('sharding' => array('federationName' => 'abc', 'distributionKey' => 'foo', 'distributionType' => 'integer')));
        $conn->expects($this->at(1))->method('isTransactionActive')->will($this->returnValue(true));

        $this->expectException('Doctrine\DBAL\Sharding\ShardingException');
        $this->expectExceptionMessage('Cannot switch shard during an active transaction.');

        $sm = new SQLAzureShardManager($conn);
        $sm->selectGlobal();
    }

    public function testSelectGlobal()
    {
        $conn = $this->createConnection(array('sharding' => array('federationName' => 'abc', 'distributionKey' => 'foo', 'distributionType' => 'integer')));
        $conn->expects($this->at(1))->method('isTransactionActive')->will($this->returnValue(false));
        $conn->expects($this->at(2))->method('exec')->with($this->equalTo('USE FEDERATION ROOT WITH RESET'));

        $sm = new SQLAzureShardManager($conn);
        $sm->selectGlobal();
    }

    public function testSelectShard()
    {
        $conn = $this->createConnection(array('sharding' => array('federationName' => 'abc', 'distributionKey' => 'foo', 'distributionType' => 'integer')));
        $conn->expects($this->at(1))->method('isTransactionActive')->will($this->returnValue(true));

        $this->expectException('Doctrine\DBAL\Sharding\ShardingException');
        $this->expectExceptionMessage('Cannot switch shard during an active transaction.');

        $sm = new SQLAzureShardManager($conn);
        $sm->selectShard(1234);

        self::assertEquals(1234, $sm->getCurrentDistributionValue());
    }

    public function testSelectShardNoDistributionValue()
    {
        $conn = $this->createConnection(array('sharding' => array('federationName' => 'abc', 'distributionKey' => 'foo', 'distributionType' => 'integer')));
        $conn->expects($this->at(1))->method('isTransactionActive')->will($this->returnValue(false));

        $this->expectException('Doctrine\DBAL\Sharding\ShardingException');
        $this->expectExceptionMessage('You have to specify a string or integer as shard distribution value.');

        $sm = new SQLAzureShardManager($conn);
        $sm->selectShard(null);
    }

    private function createConnection(array $params)
    {
        $conn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->setMethods(array('getParams', 'exec', 'isTransactionActive'))
            ->disableOriginalConstructor()
            ->getMock();
        $conn->expects($this->at(0))->method('getParams')->will($this->returnValue($params));
        return $conn;
    }
}
