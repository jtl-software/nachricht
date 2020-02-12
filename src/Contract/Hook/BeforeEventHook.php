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
 * Implement BeforeEventHook Interface to do stuff before the listener method is called.
 *
 */
interface BeforeEventHook
{
    /**
     * @param Event $event
     */
    public function setup(Event $event): void;
}
