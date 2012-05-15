<?php

namespace FreeAgent\Bundle\WorkflowBundle\Manager;

use Symfony\Component\DependencyInjection\Container;
use FreeAgent\Bundle\WorkflowBundle\Model\ModelInterface;

class Manager
{
    private $model;
    private $workflow;
    private $steps   = array();
    private $actions = array();
    private $container;

    /**
     * [__construct description]
     * @param Container $container [description]
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * [getDefaultStepName description]
     * @return string The default step name.
     */
    public function getDefaultStepName()
    {
        return $this->workflow['default_step'];
    }

    /**
     * [configureWorkflow description]
     * @param  string $workflowName The workflow name.
     * @return array The workflow.
     */
    public function configureWorkflow($workflowName)
    {
        $this->workflow = $this->container->getParameter('free_agent_workflow.workflows.'.$workflowName, null);

        if (is_null($this->workflow)) {
            throw new \Exception('The workflow "'.$workflowName.'" does not exist');
        }

        return $this->getWorkflow();
    }

    /**
     * [getWorkflow description]
     * @return array The workflow.
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * [setModel description]
     * @param ModelInterface $model The model subject of the workflow.
     */
    public function setModel(ModelInterface $model)
    {
        $this->model = $model;
        $this->configureWorkflow($this->model->getWorkflowName());
    }

    /**
     * [getModel description]
     * @return ModelInterface The model subject of the workflow.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * [getSteps description]
     * @return array The steps of the workflow.
     */
    public function getSteps()
    {
        return $this->workflow['steps'];
    }

    /**
     * [getStep description]
     * @param  string $stepName The name of the step.
     * @return array           The step.
     */
    public function getStep($stepName)
    {
        if (!array_key_exists($stepName, $this->workflow['steps'])) {
            throw new \Exception('Step with name "'.$stepName.'" is not in "'.get_class($this).'" workflow');
        }

        return $this->workflow['steps'][$stepName];
    }

    /**
     * [getCurrentStep description]
     * @return array The current step.
     */
    public function getCurrentStep()
    {
        return $this->getStep($this->getCurrentStepName());
    }

    /**
     * [getCurrentStepName description]
     * @return string The current step name.
     */
    public function getCurrentStepName()
    {
        return $this->getModel()->getWorkflowStepName();
    }

    /**
     * [reachStep description]
     * @param  string $stepName The name of the step to reach.
     * @return boolean           [description]
     */
    public function reachStep($stepName)
    {
        if ($this->canReachStep($stepName)){

            $this->getModel()->setWorkflowStepName($stepName);

            $this->runStepActions($stepName);

            return true;
        }

        return false;
    }

    /**
     * [canReachStep description]
     * @param  string $stepName The name of the step to reach.
     * @return [type]           [description]
     */
    public function canReachStep($stepName)
    {
        if ($stepName != $this->getCurrentStepName())
        {
            $step        = $this->getStep($stepName);
            $currentStep = $this->getCurrentStep();

            if (!array_key_exists('possible_next_steps', $currentStep)) {

                return false;
            }

            if (in_array($stepName, $currentStep['possible_next_steps'])) {

                if (!array_key_exists('validators', $step)) {
                    return true;
                } else {
                    foreach ($step['validators'] as $validator) {
                        $validator = $this->container->get($validator);

                        if (false == $validator->validate($this->getModel())) {

                            return false;
                        }

                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * [runStepActions description]
     * @return [type] [description]
     */
    public function runStepActions()
    {
        $currentStep = $this->getCurrentStepName();
        if (array_key_exists('actions', $currentStep)) {

            foreach ($step['actions'] as $action) {
                $action = $this->container->get($action);

                if (false == $action->run($this->getModel())) {

                    return false;
                }

                return true;
            }
        }
    }
}
