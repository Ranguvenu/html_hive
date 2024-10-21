<?php

/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_courses
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

sample_csvsheet();
die;

function sample_csvsheet() {
    global $CFG;

    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = clean_filename('uploadusers');
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $fields = array('employeeid' => 'employeeid');
    $csvexport->add_data($fields);
    $csvexport->download_file();

    die;
}
