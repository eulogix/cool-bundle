<?php

/*
 * This file is part of the Eulogix\Cool package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Eulogix\Cool\Bundle\CoreBundle\CWidget\Workflows;

use Eulogix\Cool\Lib\Cool;
use Eulogix\Cool\Lib\DataSource\DataSourceInterface;
use Eulogix\Cool\Lib\DataSource\SimpleValueMap;
use Eulogix\Cool\Lib\Form\Field\FieldInterface;
use Eulogix\Lib\Activiti\om\Task;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class PropertiesTaskEditorForm extends BaseTaskEditorForm {

    /**
     * @var string
     */
    private $embeddedFormsLayout;

    public function build() {

        $this->setFieldsReadOnly(false)->setReadOnly(false);
        $this->getServerAttributes()->set('taskIsClaimable', false);

        $wfEngine = Cool::getInstance()->getFactory()->getWorkflowEngine();
        $user = Cool::getInstance()->getLoggedUser();
        $task = $this->getTaskObject();

        $this->setId(implode('/', ['WORKFLOW_FORM', $task->getProcessDefinitionKey(), $task->getTaskDefinitionKey()]));

        $this->buildActivitiForm();

        if(!$task->getAssignee()) {
            $this->setFieldsReadOnly(true);

            if($wfEngine->canTaskBeClaimedByUser($task)) {

                $this->getServerAttributes()->set('taskIsClaimable', true);
                $this->addFieldButton('claim_button')
                    ->setLabel( $this->getCommonTranslator()->trans("claim") )
                    ->setConfirmMessage( $this->getCommonTranslator()->trans("confirm_claim") )
                    ->setOnClick( "widget.callAction('claim');");

            }

        } else if($task->getAssignee() != $user->getUsername()) {
            $this->setReadOnly(true);
        }

        //for debugging
        $vars = $this->getTaskVariables();
        $this->getAttributes()->set('activiti_variables_dump', "<pre>".print_r($vars, true)."</pre>");

        $this->getServerAttributes()->set('task', $task);
        $this->getServerAttributes()->set('assigneeAccount', $wfEngine->getAssigneeSystemUser($task));
        $this->getServerAttributes()->set('candidateAccounts', $wfEngine->getCandidateSystemUsers($task));
        $this->getServerAttributes()->set('candidateGroups', $wfEngine->getCandidateSystemGroups($task));

        return parent::build();
    }

    public function onClaim() {
        $user = Cool::getInstance()->getLoggedUser();
        $activiti = Cool::getInstance()->getFactory()->getActiviti();
        $task = $this->getTaskObject();

        try {
            $activiti->claimTask($task->getId(), $user->getUsername());
            $this->reBuild();
            $this->addMessageInfo( $this->getCommonTranslator()->trans("TASK_CLAIMED") );
            $this->addEvent("recordSaved");
        } catch(\Exception $e) {
            $this->addMessageError($e->getMessage());
        }
    }

    /**
     * Builds the visual representation of an activiti form using the task form data
     */
    protected function buildActivitiForm() {

        $formData = $this->getTaskFormData();

        $formProperties = $formData['formProperties'];
        foreach($formProperties as $formElement) {
            switch($formElement['type']) {
                case 'enum': $this->buildActivitiEnum($formElement); break;
                case 'string': $this->buildActivitiString($formElement); break;
                case 'long': $this->buildActivitiLong($formElement); break;
                //non standard fields (cool extensions)
                case 'json':
                case 'textarea': $this->buildActivitiTextArea($formElement); break;
                case 'embeddedForm': $this->buildActivitiEmbeddedForm($formElement); break;
                case 'boolean': $this->buildActivitiBoolean($formElement); break;
            }
        }

        if(!$this->getReadOnly())
            $this->addFieldSubmit("proceed")->setLabel( $this->getCommonTranslator()->trans("proceed") );
    }

    /**
     * @param array $formElement
     */
    protected function buildActivitiEnum($formElement)
    {
        $field = $this->addFieldSelect($formElement['id']);

        $map = [];
        foreach($formElement['enumValues'] as $ev)
            $map[] = ['value'=>$ev['id'], 'label'=>$ev['name']];
        $field->setValueMap(new SimpleValueMap($map));

        $this->setUpActivitiField($field, $formElement);
    }

    /**
     * @param array $formElement
     */
    protected function buildActivitiString($formElement)
    {
        $field = $this->addFieldTextBox($formElement['id']);
        $this->setUpActivitiField($field, $formElement);
    }

    /**
     * @param array $formElement
     */
    protected function buildActivitiLong($formElement)
    {
        $field = $this->addFieldNumber($formElement['id']);
        $this->setUpActivitiField($field, $formElement);
    }

    protected  function buildActivitiBoolean($formElement)
    {
        $field = $this->addFieldCheckbox($formElement['id']);
        $this->setUpActivitiField($field, $formElement);
    }

    /**
     * @param array $formElement
     */
    protected function buildActivitiTextArea($formElement)
    {
        $field = $this->addFieldTextArea($formElement['id']);
        $this->setUpActivitiField($field, $formElement);
    }

    protected function setUpActivitiField(FieldInterface $field, $formElement) {
        $fieldName = $formElement['name'];

        $label = preg_match('/^(.+?)\[T\]$/sim', $fieldName, $m)
            ? $this->getTranslator()->trans($m[1])
            : $fieldName;

        $field->setLabel($label)
              ->setValue($formElement['value'])
              ->setReadOnly(!$formElement['writable']);

        //submit the field only if it is writable
        if($formElement['writable']) {
              $field->setGroup('activiti');
        }

        if($formElement['datePattern']) {}
        if($formElement['readable']) {}
    }

    private function buildActivitiEmbeddedForm($formElement)
    {
        //we use this string to pass the definition, as activiti does not allow passing custom key value pairs like ENUM controls
        if(!preg_match('/(.+?)($|:(.+?))$/sim', $formElement['value'], $m))
            return;

        $serverId = $m[1];
        $parameters = @$m[3] ? json_decode($m[3],true) : [];

        if($formElement['writable']===false)
            $parameters['readOnly'] = true;

        $this->embeddedFormsLayout.="<h2>{{ '{$formElement['name']}'|t }}</h2><br>".
        "{{ coolWidget('$serverId', ".json_encode($parameters).",
         { onlyContent:true }
         ) }}";
    }

    public function getDefaultLayout() {
        return parent::getDefaultLayout().$this->embeddedFormsLayout;
    }

}