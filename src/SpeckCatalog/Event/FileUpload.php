<?php

namespace SpeckCatalog\Event;

class FileUpload
{
    public function preFileUpload($e)
    {
        $sm = $e->getTarget()->getServiceLocator();

        $formData = $e->getParam('params');
        $getter = 'get' . ucfirst($formData['file_type']) . 'Upload';

        $catalogOptions = $sm->get('speckcatalog_module_options');

        if($formData['file_type'] === 'productDocument'){
            $e->getParam('options')->setAllowedFileTypes(array('pdf' => 'pdf'));
            $e->getParam('options')->setUseMin(false);
            $e->getParam('options')->setUseMax(false);
        }

        $appRoot = __DIR__ . '/../../../../..';
        $path = $appRoot . $catalogOptions->$getter();
        $e->getParam('options')->setDestination($path);
    }

    public function postFileUpload($e)
    {
        $sm = $e->getTarget()->getServiceLocator();
        $params = $e->getParams();
        switch ($params['params']['file_type']) {
            case 'productImage' :
                $imageService = $sm->get('speckcatalog_product_image_service');
                $image = $imageService->getModel();
                $image->setProductId($params['params']['product_id'])
                    ->setFileName($params['fileName']);
                $imageService->insert($image);
                break;
            case 'productDocument' :
                $documentService = $sm->get('speckcatalog_document_service');
                $document = $documentService->getEntityMapper();
                $document->setProductId($params['params']['product_id'])
                    ->setFileName($params['fileName']);
                $documentService->insert($document);
                break;
            case 'optionImage' :
                $imageService = $sm->get('speckcatalog_option_image_service');
                $image = $imageService->getEntityMapper();
                $image->setOptionId($params['params']['option_id'])
                    ->setFileName($params['fileName']);
                $imageService->insert($image);
                break;
            default :
                throw new \Exception('no handler for file type - ' . $params['params']['file_type']);
        }
    }
}
