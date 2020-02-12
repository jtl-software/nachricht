<?php
/**
 * This file is part of the jtl-software/nachricht
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) JTL-Software-GmbH
 * @author rherrgesell
 * @license http://opensource.org/licenses/MIT MIT
 * @link https://packagist.org/packages/jtl/nachricht Packagist
 * @link https://github.com/jtl-software/nachricht GitHub
 */

namespace JTL\Nachricht\Contract\Hook;

use JTL\Nachricht\Contract\Event\Event;

/**
 * Implement AfterEventErrorHook Interface to handle Exception during Event processing.
 * You may throw the provided $thorwable parameter again to let the event getting re-queued by Nachricht.
 * Otherwise Event processing will be marked as successful.
 *
 */
interface AfterEventErrorHook
{
    /**
     * @param Event $event
     * @param \Throwable $throwable
     * @throws \Throwable
     */
    public function onError(Event $event, \Throwable $throwable): void;
}
