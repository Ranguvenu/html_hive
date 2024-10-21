<?php

namespace block_configurable_reports\Spout\Reader;

/**
 * Interface SheetInterface
 *
 * @package block_configurable_reports\Spout\Reader
 */
interface SheetInterface
{
    /**
     * Returns an iterator to iterate over the sheet's rows.
     *
     * @return \Iterator
     */
    public function getRowIterator();
}
