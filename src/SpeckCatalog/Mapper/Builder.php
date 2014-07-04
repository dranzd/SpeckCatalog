<?php

namespace SpeckCatalog\Mapper;

use Zend\Db\Sql;

class Builder extends AbstractMapper
{
    protected $tableName = 'catalog_builder_product';
    protected $tableFields = array('product_id', 'choice_id', 'option_id');
    protected $tableKeyFields = array('product_id', 'choice_id', 'option_id');
    protected $model = '\SpeckCatalog\Model\Builder\Relational';

    public function persist($row)
    {
        $where = array(
            'product_id' => $row['product_id'],
            'option_id'  => $row['option_id'],
        );

        if ($this->findRow($where)) {
            $this->update($row, $where);
        } else {
            $this->insert($row);
        }
    }

    public function getLinkers(array $data)
    {
        if (!isset($data['product_id']) && !isset($data['parent_product_id'])) {
            throw new \Exception('need a product_id or a parent_product_id!');
        }

        $cbp = 'catalog_builder_product';
        $cpo = 'catalog_product_option';
        $fields = array(
            'cpo' => array(
                'parent_product_id' => 'product_id',
            ),
        );

        $select = $this->getSelect($cbp); // linker is 1to1 with choices

        $select->join(
            $cpo,
            "{$cpo}.option_id = {$cbp}.option_id",
            $fields['cpo']
        );

        $where = new Sql\Where();
        if (isset($data['product_id'])) {
            $where->equalTo("{$cbp}.product_id", $data['product_id']);
        }
        if (isset($data['parent_product_id'])) {
            $where->equalTo("{$cpo}.product_id", $data['parent_product_id']);
        }
        $select->where($where);
        $result = $this->selectMany($select);

        return $result;
    }


    public function getBuildersByProductId($productId)
    {
        $c_p_o = 'catalog_product_option';
        $c_b_p = 'catalog_builder_product';

        $select = $this->getSelect($c_b_p)
            ->columns(array('*'))
            ->join($c_p_o,
                $c_p_o . '.option_id = ' . $c_b_p . '.option_id',
                array(),
                'INNER'
            )
            ->where(array($c_p_o . '.product_id' => $productId));

        $result = $this->selectMany($select);

        $products = array();
        foreach($result as $row) {
            $products[$row['product_id']][$row['option_id']] = $row['choice_id'];
        }

        return $products;
    }

    public function getBuilderOptionsByProductId($productId, $builderProductId = null)
    {
        $c_c   = 'catalog_choice';
        $c_o   = 'catalog_option';
        $c_p_o = 'catalog_product_option';
        $c_b_p = 'catalog_builder_product';

        $concat = "CONCAT(`{$c_o}`.`name`,' > ', `{$c_c}`.`override_name`)";
        $choiceName = new Sql\Expression($concat);

        $select = $this->getSelect($c_c)
            ->columns(array('choice_id', 'choice_name' => $choiceName))
            ->join($c_p_o,
                $c_p_o . '.option_id=' . $c_c . '.option_id',
                array('option_id'),
                'INNER'
            )
            ->join($c_o,
                $c_o . '.option_id=' . $c_c . '.option_id',
                array('name'),
                'INNER'
            )
            ->where(array(
                $c_o   . '.builder' => 1,
                $c_p_o . '.product_id' => $productId,
            ));

        if ($builderProductId) {
            $select->join(
                $c_b_p,
                $c_c . '.choice_id=' . $c_b_p . '.choice_id',
                array('product_id'),
                'LEFT OUTER'
            );
        }

        $result = $this->selectMany($select);

        $options = array();
        foreach($result as $row) {
            $options[$row['option_id']]['choices'][$row['choice_id']] = $row['choice_name'];
            $options[$row['option_id']]['name'] = $row['name'];
            if ($builderProductId && $row['product_id'] == $builderProductId) {
                $options[$row['option_id']]['selected'] = $row['choice_id'];
            } elseif (!isset($options[$row['option_id']]['selected'])) {
                $options[$row['option_id']]['selected'] = null;
            }
        }

        return $options;
    }

}
