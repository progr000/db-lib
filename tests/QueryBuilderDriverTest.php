<?php

namespace Tests;

use Maksym\Db\DbDriver;
use Maksym\Db\Exceptions\DbException;
use Maksym\Db\Providers\ExtendedStdClass;
use Maksym\Db\QueryBuilderDriver;
use Maksym\Config\ConfigException;

class QueryBuilderDriverTest extends _BaseTestCase
{
    /** @var QueryBuilderDriver */
    private static $qb;

    /**
     * @return void
     * @throws ConfigException
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        config()->set('databases', [
            'default-db-connection-name' => 'sqlite-for-developing',
            'sqlite-for-developing' => [
                'dsn' => "sqlite:/tmp/test-sqlite-for-developing.sq3",
                'user' => 'any',
                'password' => 'any',
                'table_prefix' => 'tbl_',
                'charset' => 'utf8',
            ]
        ]);
        //self::newQB();
    }

    /**
     * @return QueryBuilderDriver
     * @throws ConfigException
     */
    private static function newQB()
    {
        $instance = DbDriver::getInstance('sqlite-for-developing');
        self::$qb = new QueryBuilderDriver($instance, ExtendedStdClass::class, "{{test}}", true);
        return self::$qb;
    }

    /**
     * @return void
     * @throws ConfigException
     */
    public function setUp()
    {
        self::newQB();
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testSelect()
    {
        $res = self::$qb->select("a1, b1, c1")->all();
        $this->assertEquals("SELECT a1, b1, c1 FROM tbl_test", $res);
        $res = self::$qb->select(['a', 'b', 'c'])->all();
        $this->assertEquals("SELECT a, b, c FROM tbl_test", $res);
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testAlias()
    {
        $res = self::$qb->alias('t1')->all();
        $this->assertEquals("SELECT * FROM tbl_test AS t1", $res);
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testInnerJoin()
    {
        $res = self::$qb
            ->innerJoin('{{test2}}', '{{test}}.a = {{test2}}.b AND {{test}}.c=:cc', [
                'cc' => 4
            ])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test INNER JOIN tbl_test2 ON (tbl_test.a = tbl_test2.b AND tbl_test.c=4)",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testLeftJoin()
    {
        $res = self::$qb
            ->leftJoin('{{test2}}', '{{test}}.a = {{test2}}.b AND {{test}}.c=:cc', [
                'cc' => 4
            ])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test LEFT JOIN tbl_test2 ON (tbl_test.a = tbl_test2.b AND tbl_test.c=4)",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testRightJoin()
    {
        $res = self::$qb
            ->rightJoin('{{test2}}', '{{test}}.a = {{test2}}.b AND {{test}}.c=:cc', [
                'cc' => 4
            ])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test RIGHT JOIN tbl_test2 ON (tbl_test.a = tbl_test2.b AND tbl_test.c=4)",
            $res
        );
    }

    /**
     * @return void
     * @throws ConfigException
     * @throws DbException
     */
    public function testWhere()
    {
        /**/
        $res = self::$qb
            ->where(['a' => 1, 'b' => 2, 'c' => 3])
            ->where(['d' => 4])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test WHERE ((a = 1) AND (b = 2) AND (c = 3)) AND (d = 4)",
            $res
        );

        /**/
        self::newQB();
        $res = self::$qb
            ->where("a = 1 AND b = :bb AND c = 3", ['bb' => 2])
            ->where(['d' => 4])
            ->where('e = :ee', ['ee' => 5])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test WHERE (a = 1 AND b = 2 AND c = 3) AND (d = 4) AND (e = 5)",
            $res
        );
    }

    /**
     * @return void
     * @throws ConfigException
     * @throws DbException
     */
    public function testOrWhere()
    {
        /**/
        $res = self::$qb
            ->orWhere(['a' => 1])
            ->orWhere(['d' => 4])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test WHERE (a = 1) OR (d = 4)",
            $res
        );

        /**/
        self::newQB();
        $res = self::$qb
            ->orWhere('a=:aa', ['aa' => 'a1'])
            ->orWhere(['d' => 4])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test WHERE (a='a1') OR (d = 4)",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testOrderBy()
    {
        /**/
        $res = self::$qb
            ->orderBy(['a', 'b'])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test ORDER BY a ASC, b ASC",
            $res
        );

        /**/
        $res = self::$qb
            ->orderBy(['a' => 'asc', 'b' => 'Desc', 'c' => 'ASC'])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test ORDER BY a ASC, b DESC, c ASC",
            $res
        );

        /**/
        $res = self::$qb
            ->orderBy('a asc, c DESC')
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test ORDER BY a asc, c DESC",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testGroupBy()
    {
        /**/
        $res = self::$qb
            ->groupBy(['a', 'b'])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test GROUP BY a, b",
            $res
        );

        /**/
        $res = self::$qb
            ->groupBy('a, c')
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test GROUP BY a, c",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testHaving()
    {
        /**/
        $res = self::$qb
            ->having('a > :aa', ['aa' => 5])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test",
            $res
        );

        /**/
        $res = self::$qb
            ->groupBy(['a'])
            ->having('a > :aa', ['aa' => 5])
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test GROUP BY a HAVING (a > 5)",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testLimit()
    {
        $res = self::$qb
            ->limit(5)
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test LIMIT 5 OFFSET 0",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testOffset()
    {
        /**/
        $res = self::$qb
            ->offset(5)
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test",
            $res
        );

        /**/
        $res = self::$qb
            ->limit(4)
            ->offset(5)
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test LIMIT 4 OFFSET 5",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testGet()
    {
        $res = self::$qb
            ->get();
        $this->assertEquals(
            "SELECT * FROM tbl_test",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testAll()
    {
        $res = self::$qb
            ->all();
        $this->assertEquals(
            "SELECT * FROM tbl_test",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testOne()
    {
        $res = self::$qb
            ->one();
        $this->assertEquals(
            "SELECT * FROM tbl_test LIMIT 1 OFFSET 0",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testCount()
    {
        $res = self::$qb
            ->count();
        $this->assertEquals(
            "SELECT count(*) as cnt FROM tbl_test",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testMax()
    {
        $res = self::$qb
            ->max('a');
        $this->assertEquals(
            "SELECT max(a) as max_a FROM tbl_test",
            $res
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function testMin()
    {
        $res = self::$qb
            ->min('a');
        $this->assertEquals(
            "SELECT min(a) as min_a FROM tbl_test",
            $res
        );
    }

    /**
     * @return void
     * @throws ConfigException
     * @throws DbException
     */
    public function testDelete()
    {
        /**/
        $res = self::$qb
            ->delete();
        $this->assertEquals(
            "DELETE FROM tbl_test",
            $res
        );

        /**/
        self::newQB();
        $res = self::$qb
            ->delete(['a' => 5]);
        $this->assertEquals(
            "DELETE FROM tbl_test WHERE (a = 5)",
            $res
        );

        /**/
        self::newQB();
        $res = self::$qb
            ->delete('c > 3');
        $this->assertEquals(
            "DELETE FROM tbl_test WHERE (c > 3)",
            $res
        );

        /**/
        self::newQB();
        $res = self::$qb
            ->where(['b' => 4])
            ->delete();
        $this->assertEquals(
            "DELETE FROM tbl_test WHERE (b = 4)",
            $res
        );
    }

}