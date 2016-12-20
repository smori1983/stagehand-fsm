<?php
namespace Stagehand\FSM\StateMachine\StateMachineTest;

use Stagehand\FSM\Event\EventInterface;
use Stagehand\FSM\StateMachine\StateMachineInterface;
use Stagehand\FSM\Transition\GuardEvaluatorInterface;

/**
 * @since Class available since Release 3.0.0
 */
class CallableGuardEvaluator implements GuardEvaluatorInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(EventInterface $event, $payload, StateMachineInterface $stateMachine)
    {
        return call_user_func($this->callback, $event, $payload, $stateMachine);
    }
}