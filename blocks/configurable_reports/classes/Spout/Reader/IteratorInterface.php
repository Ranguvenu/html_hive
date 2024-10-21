<?php

namespace block_configurable_reports\Spout\Reader;

/**
 * Interface IteratorInterface
 *
 * @package block_configurable_reports\Spout\Reader
 */
interface IteratorInterface extends \Iterator
{
    /**
     * Cleans up what was created to iterate over the object.
     *
     * @return void
     */
    public function end();
}
