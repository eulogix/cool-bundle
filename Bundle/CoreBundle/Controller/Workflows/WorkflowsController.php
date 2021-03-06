<?php

/*
 * This file is part of the Eulogix\Cool package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Eulogix\Cool\Bundle\CoreBundle\Controller\Workflows;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Eulogix\Cool\Lib\Cool;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class WorkflowsController extends Controller
{
     
    /**
     * @Route("/workflowsManager", name="workflowsManager", options={"expose"=true})
     * @Template(engine="twig")
     */
    public function workflowsManagerAction()
    {
        return $this->render('EulogixCoolCoreBundle:Workflows:panel.html.twig');
    }

    /**
     * @Route("/getExplorerTree", name="WorkflowsExplorerTree", options={"expose"=true})
     * @Template()
     */
    public function getExplorerTreeAction()
    {
        $activiti = Cool::getInstance()->getFactory()->getActiviti();
        $userName = Cool::getInstance()->getLoggedUser()->getUsername();

            $params = $this->get('request')->query->all();
        unset($params['processDefinitionKeyLike']);

        $bkeyLike = @$params['baseProcessNamespace'];
        if($cluster = @$params['cluster'])
            $bkeyLike.='/'.$cluster;
        $bkeyLike.='%';
        $params['processInstanceBusinessKeyLike'] = $bkeyLike;

        if($bkl = json_decode( @$params['processInstanceBusinessKeyLikes'], true ) ) {
            $params['processInstanceBusinessKeyLike'] = $bkl;
        }

        if($groups = json_decode( @$params['candidateGroups'], true ) ) {
            $params['candidateGroup'] = $groups;
        }

        return new JsonResponse( [
            'inbox' => $activiti->getUserTaskCount($userName, $params)->getData(),
         //   'involved' => [ 'count'=> $involved->getSize(), 'user'=>$userName ]
        ]);
    }

    /**
     * @Route("/activitiManager", name="activitiManager", options={"expose"=true})
     * @Template(engine="twig")
     */
    public function activitiManagerAction()
    {
        return $this->render('EulogixCoolCoreBundle:Workflows:activitiManager.html.twig');
    }


}