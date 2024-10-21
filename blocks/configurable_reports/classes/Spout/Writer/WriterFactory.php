<?php

namespace block_configurable_reports\Spout\Writer;

use block_configurable_reports\Spout\Common\Exception\UnsupportedTypeException;
use block_configurable_reports\Spout\Common\Helper\GlobalFunctionsHelper;
use block_configurable_reports\Spout\Common\Type;

/**
 * Class WriterFactory
 * This factory is used to create writers, based on the type of the file to be read.
 * It supports CSV, XLSX and ODS formats.
 *
 * @package block_configurable_reports\Spout\Writer
 */
class WriterFactory
{
    /**
     * This creates an instance of the appropriate writer, given the type of the file to be read
     *
     * @api
     * @param  string $writerType Type of the writer to instantiate
     * @return WriterInterface
     * @throws \block_configurable_reports\Spout\Common\Exception\UnsupportedTypeException
     */
    public static function create($writerType)
    {
        $writer = null;

        switch ($writerType) {
            case Type::CSV:
                $writer = new CSV\Writer();
                break;
            case Type::XLSX:
                $writer = new XLSX\Writer();
                break;
            case Type::ODS:
                $writer = new ODS\Writer();
                break;
            default:
                throw new UnsupportedTypeException('No writers supporting the given type: ' . $writerType);
        }

        $writer->setGlobalFunctionsHelper(new GlobalFunctionsHelper());

        return $writer;
    }
}
