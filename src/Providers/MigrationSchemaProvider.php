<?php

namespace Maksym\Db\Providers;

use Maksym\Db\Contracts\MigrationSchema\mysqlSchemaDriver;
use Maksym\Db\DbDriver;
use Maksym\Db\Exceptions\DbException;
use Maksym\Db\Interfaces\MigrationSchemaInterface;

class MigrationSchemaProvider
{
    /**
     * @param DbDriver $db
     * @return MigrationSchemaInterface|mysqlSchemaDriver|sqlsrvSchemaDriver|pqsqlSchemaDriver|sqliteSchemaDriver
     * @throws DbException
     */
    public function register(DbDriver $db)
    {
        $className = "Maksym\\Db\\Contracts\\MigrationSchema\\{$db->getDriver()}SchemaDriver";
        if (class_exists($className)) {
            return new $className($db);
        } else {
            throw new DbException('MigrationSchemaProvider::register(): method for this DbDriver not exists');
        }
    }
}