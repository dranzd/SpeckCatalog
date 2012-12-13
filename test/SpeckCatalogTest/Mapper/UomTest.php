<?php

namespace SpeckCatalogTest\Mapper;
use SpeckCatalogTest\Mapper\Asset\AbstractTestCase;

class UomTest extends AbstractTestCase
{
    public function testFindReturnsUomModel()
    {
        $this->insertUom('EA', 'Each');

        $mapper = $this->getMapper();
        $return = $mapper->find(array('uom_code' => 'EA'));
        $this->assertTrue($return instanceOf \SpeckCatalog\Model\Uom);
    }

    public function getMapper()
    {
        return $this->getServiceManager()->get('speckcatalog_uom_mapper');
    }
}
