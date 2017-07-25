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

use Eulogix\Cool\Lib\Activiti\dataSource\TaskDataSource;
use Eulogix\Cool\Lib\Cool;
use Eulogix\Cool\Lib\Lister\Lister;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class TaskLister extends Lister {

    public function __construct($parameters = [])
    {
        parent::__construct($parameters);

        $ds = new TaskDataSource(Cool::getInstance()->getFactory()->getActiviti());
        $this->setDataSource($ds->build());
    }

    public function build() {
        parent::build();
        $this->getColumn('id')->setSortable(true);
        $this->getColumn('createTime')->setSortable(true);

        //we listen to the taskFlow event of the editor, that signals that a new row has been generated by activiti and
        //is in charge of the current user. chances are that the current filter of the lister makes it visible to the user,
        //so we update the grid visual too
        $this->addCommandJs("widget.on('editorOpened', function(payload) {
            payload.editor.on('taskFlow', function(tfPayload) {
                widget.setInEditRowID(tfPayload.task_id);
            });
        });");

        return $this;
    }

    public function getDefaultEditorServerId() {
        return 'EulogixCoolCore/Workflows/TaskEditorForm';
    }

    /**
     * @return string|null
     */
    public function getDefaultFilterWidget()
    {
        return 'EulogixCoolCore/Workflows/TaskFilterForm';
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return "COOL_WF_TASK_LISTER";
    }

}