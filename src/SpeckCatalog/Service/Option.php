<?php

namespace SpeckCatalog\Service;

use SpeckCatalog\Model\Product\Relational as RelationalProduct;
use SpeckCatalog\Model\Choice\Relational as RelationalChoice;

class Option extends AbstractService
{
    protected $entityMapper = 'speckcatalog_option_mapper';
    protected $choiceService;
    protected $imageService;
    protected $productService;

    public function getByProductId($productId, $populate=false, $recursive=false, array $extraFields = array())
    {
        $options = $this->getEntityMapper()->getByProductId($productId, $extraFields);
        if ($populate) {
            foreach ($options as $option) {
                $this->populate($option, $recursive);
            }
        }
        return $options;
    }

    public function getByParentChoiceId($choiceId, $populate=false)
    {
        $options = $this->getEntityMapper()->getByParentChoiceId($choiceId);
        if ($populate) {
            foreach ($options as $option) {
                $this->populate($option);
            }
        }
        return $options;
    }

    public function getBuildersByProductId($productId)
    {
        $choices = $this->getEntityMapper()->getBuildersByProductId($productId);
        var_dump($choices); die();
        $builders = array();
        foreach ($choices as $choice) {
            $builders[$choice['product_id']][] = $choice['choice_id'];
        }
        foreach ($builders as $pid => $choiceIds) {
            sort($choiceIds);
            $builders[$pid] = implode(',', $choiceIds);
        }
        return $builders;
    }

    public function populate($option, $recursive=false, $children=true)
    {
        $optionId = $option->getOptionId();

        $allChildren = ($children === true) ? true : false;
        $children    = (is_array($children)) ? $children : array();

        if ($allChildren || in_array('choices', $children)) {
            $choiceService = $this->getChoiceService();
            $choices = $choiceService->getByOptionId($optionId, true, $recursive);
            $option->setChoices($choices);
        }
        if ($allChildren || in_array('images', $children)) {
            $imageService = $this->getImageService();
            $option->setImages($imageService->getImages('option', $optionId));
        }
    }

    public function insert($option)
    {
        $parent = $this->getExistingParent($option);
        $option = parent::insert($option);

        if($parent instanceOf \SpeckCatalog\Model\Product) {
            $this->getProductService()->addOption($parent, $option);
            $option->setParent($parent);
        }
        if($parent instanceOf \SpeckCatalog\Model\Choice) {
            $this->getChoiceService()->addOption($parent, $option);
            $option->setParent($parent);
        }
        return $option;
    }

    public function getExistingParent($option)
    {
        if (is_array($option)) {
            $hydrator = new \Zend\Stdlib\Hydrator\ClassMethods;
            $option = $hydrator->hydrate($option, new \SpeckCatalog\Model\Option\Relational);
        }

        $parent = null;
        if ($option->getProductId()) {
            $data = array('product_id' => $option->getProductId());
            $parent = $this->getProductService()->find($data);
        } elseif ($option->getChoiceId()) {
            $data = array('choice_id' => $option->getChoiceId());
            $parent = $this->getChoiceService()->find($data);
        }
        return $parent;
    }

    public function update($option, array $originalValues = null)
    {
        if (null === $originalValues && is_array($option)) {
            $originalValues['option_id'] = $option['option_id'];
        }
        if (null === $originalValues && $option instanceOf \SpeckCatalog\Model\Option) {
            $originalValues['option_id'] = $option->getOptionId();
        }
        parent::update($option, $originalValues);
    }

    public function removeChoice($optionData, $choiceData)
    {
        $choiceService = $this->getChoiceService();

        $found = $choiceService->find($choiceData);
        if (!$found) {
            throw new \Exception(sprintf('choice not found - %n', $choiceData['choice_id']));
        }

        $choiceService->delete($choiceData); //delete
        if ($choiceService->find($choiceData)) {
            throw new \Exception(sprintf('delete unsuccessful - %n remains after delete', $choiceData['choice_id']));
        }

        return true; //choice was found, and then deleted successfully
    }

    public function sortChoices($optionId, array $order = array())
    {
        return $this->getEntityMapper()->sortChoices($optionId, $order);
    }

    /**
     * @return choiceService
     */
    public function getChoiceService()
    {
        if (null === $this->choiceService) {
            $this->choiceService = $this->getServiceLocator()->get('speckcatalog_choice_service');
        }
        return $this->choiceService;
    }

    /**
     * @param $choiceService
     * @return self
     */
    public function setChoiceService($choiceService)
    {
        $this->choiceService = $choiceService;
        return $this;
    }

    /**
     * @return imageService
     */
    public function getImageService()
    {
        if (null === $this->imageService) {
            $this->imageService = $this->getServiceLocator()->get('speckcatalog_option_image_service');
        }
        return $this->imageService;
    }

    /**
     * @param $imageService
     * @return self
     */
    public function setImageService($imageService)
    {
        $this->imageService = $imageService;
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
