<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2008, 2011-2012 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Stagehand_FSM
 * @copyright  2006-2008, 2011-2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

namespace Stagehand\FSM;

/**
 * @package    Stagehand_FSM
 * @copyright  2006-2008, 2011-2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class FSMTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function addsAState()
    {
        $builder = new FSMBuilder();
        $builder->setStartState('locked');
        $builder->addState('foo');
        $builder->addState('bar');
        $fsm = $builder->getFSM();
        $this->assertInstanceOf('\Stagehand\FSM\IState', $fsm->getState('foo'));
        $this->assertEquals('foo', $fsm->getState('foo')->getID());
        $this->assertInstanceOf('\Stagehand\FSM\IState', $fsm->getState('bar'));
        $this->assertEquals('bar', $fsm->getState('bar')->getID());
    }

    /**
     * @test
     */
    public function setsTheFirstState()
    {
        $firstStateID = 'locked';
        $builder = new FSMBuilder();
        $builder->setStartState($firstStateID);
        $fsm = $builder->getFSM();
        $fsm->start();
        $this->assertEquals($firstStateID, $fsm->getCurrentState()->getID());

        $builder = new FSMBuilder();
        $builder->setStartState($firstStateID);
        $fsm = $builder->getFSM();
        $fsm->start();
        $this->assertEquals($firstStateID, $fsm->getCurrentState()->getID());
    }

    /**
     * @test
     */
    public function triggersAnEvent()
    {
        $unlockCalled = false;
        $lockCalled = false;
        $alarmCalled = false;
        $thankCalled = false;
        $builder = new FSMBuilder();
        $builder->setStartState('locked');
        $builder->addTransition('locked', 'insertCoin', 'unlocked', function (Event $event, $payload, FSM $fsm) use (&$unlockCalled)
        {
            $unlockCalled = true;
        });
        $builder->addTransition('unlocked', 'pass', 'locked', function (Event $event, $payload, FSM $fsm) use (&$lockCalled)
        {
            $lockCalled = true;
        });
        $builder->addTransition('locked', 'pass', 'locked', function (Event $event, $payload, FSM $fsm) use (&$alarmCalled)
        {
            $alarmCalled = true;
        });
        $builder->addTransition('unlocked', 'insertCoin', 'unlocked', function (Event $event, $payload, FSM $fsm) use (&$thankCalled)
        {
            $thankCalled = true;
        });
        $fsm = $builder->getFSM();
        $fsm->start();

        $currentState = $fsm->triggerEvent('pass');
        $this->assertEquals('locked', $currentState->getID());
        $this->assertTrue($alarmCalled);

        $currentState = $fsm->triggerEvent('insertCoin');
        $this->assertEquals('unlocked', $currentState->getID());
        $this->assertTrue($unlockCalled);

        $currentState = $fsm->triggerEvent('insertCoin');
        $this->assertEquals('unlocked', $currentState->getID());
        $this->assertTrue($thankCalled);

        $currentState = $fsm->triggerEvent('pass');
        $this->assertEquals('locked', $currentState->getID());
        $this->assertTrue($lockCalled);
    }

    /**
     * @test
     */
    public function supportsGuards()
    {
        $maxNumberOfCoins = 10;
        $numberOfCoins = 11;
        $builder = new FSMBuilder();
        $builder->setStartState('locked');
        $builder->addTransition('locked', 'insertCoin', 'unlocked', null, function (Event $event, $payload, FSM $fsm) use ($maxNumberOfCoins, $numberOfCoins)
        {
            return $numberOfCoins <= $maxNumberOfCoins;
        });
        $builder->addTransition('unlocked', 'pass', 'locked');
        $builder->addTransition('locked', 'pass', 'locked');
        $builder->addTransition('unlocked', 'insertCoin', 'unlocked');
        $fsm = $builder->getFSM();
        $fsm->start();
        $currentState = $fsm->triggerEvent('insertCoin');
        $this->assertEquals('locked', $currentState->getID());
    }

    /**
     * @test
     */
    public function supportsExitAndEntryActions()
    {
        $entryActionForInitialCalled = false;
        $entryActionForLockedCalled = false;
        $builder = new FSMBuilder();
        $builder->setStartState('locked');
        $builder->setExitAction(IState::STATE_INITIAL, function (Event $event, $payload, FSM $fsm) use (&$entryActionForInitialCalled)
        {
            $entryActionForInitialCalled = true;
        });
        $builder->setEntryAction('locked', function (Event $event, $payload, FSM $fsm) use (&$entryActionForLockedCalled)
        {
            $entryActionForLockedCalled = true;
        });
        $fsm = $builder->getFSM();
        $fsm->start();

        $this->assertTrue($entryActionForInitialCalled);
        $this->assertTrue($entryActionForLockedCalled);
        $this->assertEquals('locked', $fsm->getCurrentState()->getID());
    }

    /**
     * @test
     */
    public function setsTheId()
    {
        $fsm = new FSM('foo');
        $this->assertEquals('foo', $fsm->getFSMID());
    }

    /**
     * @test
     */
    public function supportsNestedFsms()
    {
        $childBuilder = new FSMBuilder('play');
        $childBuilder->setStartState('playing');
        $childBuilder->setActivity('playing', function (Event $event, \stdClass $payload, FSM $fsm)
        {
            ++$payload->count;
        });
        $childBuilder->addTransition('playing', 'pause', 'paused');
        $childBuilder->addTransition('paused', 'play', 'playing');
        $child = $childBuilder->getFSM();

        $payload = new \stdClass();
        $payload->count = 0;
        $parentBuilder = new FSMBuilder();
        $parentBuilder->setPayload($payload);
        $parentBuilder->addFSM($child);
        $parentBuilder->setStartState('stopped');
        $parentBuilder->setActivity('stopped', function (Event $event, \stdClass $payload, FSM $fsm)
        {
            ++$payload->count;
        });
        $parentBuilder->addTransition('stopped', 'start', 'play');
        $parentBuilder->addTransition('play', 'stop', 'stopped');
        $parent = $parentBuilder->getFSM();
        $parent->start();

        $this->assertEquals('stopped', $parent->getCurrentState()->getID());

        $child = $parent->triggerEvent('start');
        $this->assertEquals('play', $child->getID());
        $this->assertEquals('playing', $child->getCurrentState()->getID());
        $this->assertEquals(2, $payload->count);

        $currentStateOfChild = $child->triggerEvent('pause');
        $this->assertEquals('paused', $currentStateOfChild->getID());

        $currentStateOfChild = $child->triggerEvent('play');
        $this->assertEquals('playing', $currentStateOfChild->getID());
        $this->assertEquals('play', $parent->getCurrentState('play')->getID());

        $currentState = $parent->triggerEvent('stop');
        $this->assertEquals('stopped', $currentState->getID());
    }

    /**
     * @test
     */
    public function supportsNestedStates()
    {
        $entryActionForCCalled = false;
        $entryActionForSCalled = false;

        $childBuilder = new FSMBuilder('S');
        $childBuilder->setStartState('C');
        $childBuilder->addTransition('C', 'w', 'B');
        $childBuilder->addTransition('B', 'x', 'C');
        $childBuilder->addTransition('B', 'q', IState::STATE_FINAL);
        $childBuilder->setEntryAction('C', function (Event $event, $payload, FSM $fsm) use (&$entryActionForCCalled)
        {
            $entryActionForCCalled = true;
        });

        $parentBuilder = new FSMBuilder();
        $parentBuilder->setStartState('A');
        $parentBuilder->addFSM($childBuilder->getFSM());
        $parentBuilder->addTransition('A', 'r', 'D');
        $parentBuilder->addTransition('A', 'y', 'S');
        $parentBuilder->addTransition('A', 'v', 'S');
        $parentBuilder->addTransition('S', 'z', 'A');
        $parentBuilder->setEntryAction('S', function (Event $event, $payload, FSM $fsm) use (&$entryActionForSCalled)
        {
            $entryActionForSCalled = true;
        });
        $parent = $parentBuilder->getFSM();
        $parent->start();
        $this->assertEquals('A', $parent->getCurrentState()->getID());

        $parent->triggerEvent('v');
        $this->assertEquals('S', $parent->getCurrentState()->getID());
        $this->assertTrue($entryActionForSCalled);

        $child = $parent->getState('S');
        $this->assertEquals('C', $child->getCurrentState()->getID());
        $this->assertTrue($entryActionForCCalled);
    }

    /**
     * @test
     */
    public function supportsHistoryMarker()
    {
        $parentBuilder = $this->prepareWashingMachine();
        $parent = $parentBuilder->getFSM();
        $parent->start();
        $this->assertEquals('Running', $parent->getCurrentState()->getID());
        $child = $parent->getState('Running');

        $currentStateOfChild = $child->triggerEvent('w');
        $this->assertEquals('Rinsing', $currentStateOfChild->getID());

        $currentState = $parent->triggerEvent('powerCut');
        $this->assertEquals('PowerOff', $currentState->getID());

        $currentState = $parent->triggerEvent('restorePower');
        $this->assertEquals('Running', $currentState->getID());
        $this->assertEquals('Rinsing', $child->getCurrentState()->getID());

        $parent->triggerEvent('powerCut');
        $currentState = $parent->triggerEvent('reset');
        $this->assertEquals('Running', $currentState->getID());
        $this->assertEquals('Washing', $child->getCurrentState()->getID());
    }

    /**
     * @test
     */
    public function getsThePreviousState()
    {
        $builder = new FSMBuilder();
        $builder->setStartState('Washing');
        $builder->addTransition('Washing', 'w', 'Rinsing');
        $builder->addTransition('Rinsing', 'r', 'Spinning');
        $fsm = $builder->getFSM();
        $fsm->start();
        $state = $fsm->getPreviousState();
        $this->assertInstanceOf('\Stagehand\FSM\IState', $state);
        $this->assertEquals(IState::STATE_INITIAL, $state->getID());

        $fsm->triggerEvent('w');
        $state = $fsm->getPreviousState();

        $this->assertInstanceOf('\Stagehand\FSM\IState', $state);
        $this->assertEquals('Washing', $state->getID());
    }

    /**
     * @test
     */
    public function invokesTheEntryActionOfTheParentStateBeforeTheEntryActionOfTheChildState()
    {
        $lastMarker = null;
        $parentBuilder = $this->prepareWashingMachine();
        $parentBuilder->setEntryAction('Running', function (Event $event, $payload, FSM $fsm) use (&$lastMarker)
        {
            $lastMarker = 'Running';
        });
        $parent = $parentBuilder->getFSM();
        $childBuilder = new FSMBuilder($parent->getState('Running'));
        $childBuilder->setEntryAction('Washing', function (Event $event, $payload, FSM $fsm) use (&$lastMarker)
        {
            $lastMarker = 'Washing';
        });
        $parent->start();
        $this->assertEquals('Washing', $lastMarker);
    }

    /**
     * @test
     */
    public function supportsActivity()
    {
        $washingCount = 0;
        $parent = $this->prepareWashingMachine()->getFSM();
        $childBuilder = new FSMBuilder($parent->getState('Running'));
        $childBuilder->setActivity('Washing', function (Event $event, $payload, FSM $fsm) use (&$washingCount)
        {
            ++$washingCount;
        });
        $parent->start();

        $child = $childBuilder->getFSM();
        $state = $child->triggerEvent('put');
        $this->assertEquals(2, $washingCount);
        $this->assertEquals('Washing', $state->getID());

        $state = $child->triggerEvent('hit');
        $this->assertEquals(3, $washingCount);
        $this->assertEquals('Washing', $state->getID());
    }

    /**
     * @test
     */
    public function supportsPayloads()
    {
        $payload = new \stdClass();
        $payload->washingCount = 0;
        $parent = $this->prepareWashingMachine()->getFSM();
        $childBuilder = new FSMBuilder($parent->getState('Running'));
        $childBuilder->setPayload($payload);
        $childBuilder->setActivity('Washing', function ($event, $payload, FSM $fsm)
        {
            ++$payload->washingCount;
        });
        $parent->start();

        $child = $childBuilder->getFSM();
        $state = $child->triggerEvent('put');
        $this->assertEquals(2, $payload->washingCount);
        $this->assertEquals('Washing', $state->getID());

        $state = $child->triggerEvent('hit');
        $this->assertEquals(3, $payload->washingCount);
        $this->assertEquals('Washing', $state->getID());
    }

    /**
     * @test
     */
    public function transitionsWhenAnEventIsTriggeredInAnAction()
    {
        $builder = new FSMBuilder();
        $builder->setStartState('Washing');
        $test = $this;
        $builder->setEntryAction('Washing', function ($event, $payload, FSM $fsm) use ($test)
        {
            $test->assertEquals('Washing', $fsm->getCurrentState()->getID());
            $test->assertEquals(IState::STATE_INITIAL, $fsm->getPreviousState()->getID());
            $fsm->triggerEvent('w');
        });
        $builder->addTransition('Washing', 'w', 'Rinsing', function ($event, $payload, FSM $fsm) {});
        $builder->addTransition('Rinsing', 'r', 'Spinning');
        $fsm = $builder->getFSM();
        $fsm->start();
        $this->assertEquals('Rinsing', $fsm->getCurrentState()->getID());
        $this->assertEquals('Washing', $fsm->getPreviousState()->getID());
    }

    /**
     * @test
     * @expectedException \Stagehand\FSM\FSMAlreadyShutdownException
     */
    public function shutdownsTheFsmWhenTheStateReachesTheFinalState()
    {
        $finalizeCalled = false;
        $builder = new FSMBuilder();
        $builder->setStartState('ending');
        $builder->addTransition('ending', Event::EVENT_END, IState::STATE_FINAL);
        $builder->setEntryAction(IState::STATE_FINAL, function (Event $event, $payload, FSM $fsm) use (&$finalizeCalled)
        {
            $finalizeCalled = true;
        });
        $fsm = $builder->getFSM();
        $fsm->start();
        $fsm->triggerEvent(Event::EVENT_END);
        $this->assertTrue($finalizeCalled);
        $fsm->triggerEvent('foo');
    }

    /**
     * @test
     * @since Method available since Release 1.6.0
     */
    public function checksWhetherTheCurrentStateHasTheGivenEvent()
    {
        $builder = new FSMBuilder();
        $builder->setStartState('Stop');
        $builder->addTransition('Stop', 'play', 'Playing');
        $builder->addTransition('Playing', 'stop', 'Stop');
        $fsm = $builder->getFSM();
        $fsm->start();

        $this->assertEquals('Stop', $fsm->getCurrentState()->getID());
        $this->assertTrue($fsm->hasEvent('play'));
        $this->assertFalse($fsm->hasEvent('stop'));

        $currentState = $fsm->triggerEvent('play');
        $this->assertEquals('Playing', $currentState->getID());
        $this->assertTrue($fsm->hasEvent('stop'));
        $this->assertFalse($fsm->hasEvent('play'));
    }

    /**
     * @test
     * @since Method available since Release 1.7.0
     */
    public function invokesTheActivityOnlyOnceWhenAnStateIsUpdated()
    {
        $activityForDisplayFormCallCount = 0;
        $transitionActionForDisplayFormCallCount = 0;
        $activityForDisplayConfirmationCallCount = 0;

        $builder = new FSMBuilder();
        $builder->setStartState('DisplayForm');
        $builder->setActivity('DisplayForm', function ($event, $payload, FSM $fsm) use (&$activityForDisplayFormCallCount)
        {
            ++$activityForDisplayFormCallCount;
        });
        $builder->addTransition('DisplayForm', 'confirmForm', 'processConfirmForm', function ($event, $payload, FSM $fsm) use (&$transitionActionForDisplayFormCallCount)
        {
            ++$transitionActionForDisplayFormCallCount;
            $fsm->queueEvent('goDisplayConfirmation');
        });
        $builder->addTransition('processConfirmForm', 'goDisplayConfirmation', 'DisplayConfirmation');
        $builder->setActivity('DisplayConfirmation', function ($event, $payload, FSM $fsm) use (&$activityForDisplayConfirmationCallCount)
        {
            ++$activityForDisplayConfirmationCallCount;
        });
        $fsm = $builder->getFSM();
        $fsm->start();

        $this->assertEquals(1, $activityForDisplayFormCallCount);

        $fsm->triggerEvent('confirmForm');

        $this->assertEquals(1, $activityForDisplayFormCallCount);
        $this->assertEquals(1, $transitionActionForDisplayFormCallCount);
        $this->assertEquals(1, $activityForDisplayConfirmationCallCount);
    }

    /**
     * @return \Stagehand\FSM\FSMBuilder
     */
    protected function prepareWashingMachine()
    {
        $childBuilder = new FSMBuilder('Running');
        $childBuilder->setStartState('Washing');
        $childBuilder->addTransition('Washing', 'w', 'Rinsing');
        $childBuilder->addTransition('Rinsing', 'r', 'Spinning');

        $parentBuilder = new FSMBuilder();
        $parentBuilder->setStartState('Running');
        $parentBuilder->addFSM($childBuilder->getFSM());
        $parentBuilder->addTransition('PowerOff', 'restorePower', 'Running', null, null, true);
        $parentBuilder->addTransition('Running', 'powerCut', 'PowerOff');
        $parentBuilder->addTransition('PowerOff', 'reset', 'Running');

        return $parentBuilder;
    }
}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
