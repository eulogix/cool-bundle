<?php

/*
 * This file is part of the Eulogix\Cool package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Eulogix\Cool\Bundle\CoreBundle\CWidget\Core\Files;

use Eulogix\Cool\Lib\DataSource\FileRepositoryDataSource;
use Eulogix\Cool\Lib\File\FileRepositoryFactory;
use Eulogix\Cool\Lib\File\FileRepositoryInterface;
use Eulogix\Cool\Lib\Form\Form;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class FileRepositoryUploaderForm extends Form {

    /**
     * @var FileRepositoryInterface
     */
    private $repo;

    public function build() {
        parent::build();

        $parameters = $this->parameters->all();
        $id = $parameters['repositoryId'];
        $this->repo = FileRepositoryFactory::fromId($id);
        $this->repo->setParameters($parameters);

        $this->addFieldFile('files')->setMultiple(true)->setMaxFiles(-1);
        $this->addFieldSubmit('upload');
        return $this;
    }

    public function onSubmit() {

        $parameters = $this->request->all();
        $this->fill( $parameters );
        $targetFolder = $this->parameters->get('folder');
        $targetFolder = $targetFolder == FileRepositoryDataSource::ROOT_PLACEHOLDER ? null : $targetFolder;

        $uplpoadErrors = FALSE;

        if($f = $this->getField('files')->getUploadedFiles()) {
            foreach($f as $file) {
                //$file->setProperty(CoolTableFileRepository::PROP_CATEGORY, $categoryName);
                try {
                    $this->repo->storeFileAt($file, $targetFolder);
                    $this->addMessageInfo($this->getTranslator()->trans('SUCCESS_UPLOAD').' '.$file->getName());
                } catch (\Exception $e){
                    $this->addMessageError($this->getTranslator()->trans('DUPLICATED_FILENAME').' '.$file->getName());
                    $uplpoadErrors = TRUE;
                }

            }

            if (!$uplpoadErrors){
                $this->addCommandJs(" widget.dialog.hide(); widget.dialog.rfe.reload(); ");
                $this->getField('files')->clearUploadedFiles();
            }  else {
                $this->addCommandJs(" widget.dialog.rfe.reload(); ");
            }

        }
    }

}