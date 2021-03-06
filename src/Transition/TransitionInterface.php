<?php
/*
 * Copyright (c) KUBO Atsuhiro <kubo@iteman.jp>,
* All rights reserved.
*
* This file is part of Stagehand_FSM.
*
* This program and the accompanying materials are made available under
* the terms of the BSD 2-Clause License which accompanies this
* distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
*/

namespace Stagehand\FSM\Transition;

use Stagehand\FSM\Event\TransitionEventInterface;
use Stagehand\FSM\State\StateInterface;
use Stagehand\FSM\State\TransitionalStateInterface;

/**
 * @since Class available since Release 3.0.0
 */
interface TransitionInterface
{
    public function getToState(): StateInterface;

    public function getFromState(): TransitionalStateInterface;

    public function getEvent(): TransitionEventInterface;
}
