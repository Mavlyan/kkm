<?php
/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->dropTable($installer->getTable('kkm/status'));

$kkm_status_table = $installer->getConnection()
    ->newTable($installer->getTable('kkm/status'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        ['unsigned'       => true,
        'nullable'       => false,
        'primary'        => true,
        'auto_increment' => true
        ], 'ID')
    ->addColumn('uuid', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        [
        'nullable' => false
        ], 'Universally Unique Identifier')
    ->addColumn('operation', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        [
        'nullable' => false
        ], 'Operation')
    ->addColumn('vendor', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        [
        'nullable' => false
        ], 'Vendor code')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, [
    'nullable' => false
    ], 'status');

$installer->getConnection()->createTable($kkm_status_table);

$installer->endSetup();