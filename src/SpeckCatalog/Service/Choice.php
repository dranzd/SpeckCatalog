<?php

namespace SpeckCatalog\Service;

class Choice extends AbstractService
{
    protected $entityMapper = 'speckcatalog_choice_mapper';
    protected $optionService;
    protected $productService;

    public function getByOptionId($optionId, $populate=false, $recursive=false)
    {
        $choices = $this->getEntityMapper()->getByOptionId($optionId);
        if ($populate) {
            foreach ($choices as $choice) {
                $this->populate($choice, $recursive);
            }
        }
        return $choices;
    }

    public function populate($choice, $recursive=false, $children=true)
    {
        $allChildren = ($children === true) ? true : false;
        $children    = (is_array($children)) ? $children : array();

        if ($allChildren || in_array('parent', $children)) {
            $option = $this->getOptionService()->find(array('option_id' => $choice->getOptionId()));
            $choice->setParent($option);
        }
        if ($allChildren || in_array('options', $children)) {
            $options = $this->getOptionService()->getByParentChoiceId($choice->getChoiceId(), $recursive);
            $choice->setOptions($options);
        }
        if ($allChildren || in_array('product', $children)) {
            if($choice->getProductId()){
                $product = $this->getProductService()->getFullProduct($choice->getProductId());
                $choice->setProduct($product);
            }
        }
    }

    public function addOption($choiceOrId, $optionOrId)
    {
        $choiceId = ( is_int($choiceOrId) ? $choiceOrId : $choiceOrId->getChoiceId() );
        $optionId = ( is_int($optionOrId) ? $optionOrId : $optionOrId->getOptionId() );
        $this->getEntityMapper()->addOption($choiceId, $optionId);
        return $this->getOptionService()->find(array('option_id' => $optionId));
    }

    public function persist($form)
    {
        $data = $form->getData();
        $orig = $form->getOriginalData();

        if (isset($data['choice_id']) && (int) $data['choice_id'] > 0) {
            return $this->update($data, $orig);
        }

        $model = $this->insert($data);
        return $model;
    }

    public function sortOptions($choiceId, $order)
    {
        return $this->getEntityMapper()->sortOptions($choiceId, $order);
    }

    public function removeOption($choiceOrId, $optionOrId)
    {
        $productId = ( is_int($choiceOrId) ? $choiceOrId : $choiceOrId->getProductId() );
        $optionId  = ( is_int($optionOrId) ? $optionOrId : $optionOrId->getOptionId() );
        $this->getEntityMapper()->removeOption($productId, $optionId);
    }

    public function choiceFromProduct($productOrId)
    {
        $product = ( !is_int($productOrId) ? $productOrId : $this->getProductService()->find($productOrId) );
        $choice = $this->getEntity()->setProductId($product->getProductId());
        return $this->persist($choice);
    }

    /**
     * @return optionService
     */
    public function getOptionService()
    {
        if (null === $this->optionService) {
            $this->optionService = $this->getServiceLocator()->get('speckcatalog_option_service');
        }
        return $this->optionService;
    }

    /**
     * @param $optionService
     * @return self
     */
    public function setOptionService($optionService)
    {
        $this->optionService = $optionService;
        return $this;
    }

    /**
     * @return productService
     */
    public function getProductService()
    {
        if (null === $this->productService) {
            $this->productService = $this->getServiceLocator()->get('speckcatalog_product_service');
        }
        return $this->productService;
    }

    /**
     * @param $productService
     * @return self
     */
    public function setProductService($productService)
    {
        $this->productService = $productService;
        return $this;
    }
}
