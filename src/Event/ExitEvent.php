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

namespace Stagehand\FSM\Event;

/**
 * @since Class available since Release 2.0.0
 */
class ExitEvent implements EventInterface
{
    /**
     * @since Method available since Release 2.1.0
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getEventId()
    {
        return EventInterface::EVENT_EXIT;
    }
}
