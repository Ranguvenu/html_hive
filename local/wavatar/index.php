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
 * @subpackage local_wavatar
 */


require_once(__DIR__ . '/../../config.php');
global $OUTPUT, $PAGE, $USER;

require_login(); 
$title = get_string('viewrequest', 'local_request');

// Set up the page.
$url = new moodle_url("/local/wavatar/index.php");
$sitecontext = context_system::instance();
$PAGE->set_context($sitecontext);

// $PAGE->set_pagelayout('admin');
$PAGE->set_url($url);


$PAGE->requires->css('/local/wavatar/svgavatars/css/spectrum.css');
$PAGE->requires->css('/local/wavatar/svgavatars/css/svgavatars.css');

$PAGE->requires->jquery();
$PAGE->requires->js('/local/wavatar/svgavatars/js/svg.min.js');
$PAGE->requires->js('/local/wavatar/svgavatars/js/spectrum.min.js');
$PAGE->requires->js('/local/wavatar/svgavatars/js/jquery.scrollbar.min.js');
$PAGE->requires->js('/local/wavatar/svgavatars/js/canvg/rgbcolor.js');
$PAGE->requires->js('/local/wavatar/svgavatars/js/canvg/StackBlur.js');
$PAGE->requires->js('/local/wavatar/svgavatars/js/canvg/canvg.js');

$PAGE->requires->js('/local/wavatar/svgavatars/js/svgavatars.en.js');
$PAGE->requires->js('/local/wavatar/svgavatars/js/svgavatars.core.min.js');


echo $OUTPUT->header();
echo 'Avatar integration';
echo '<div id="svgAvatars"></div>';
echo $OUTPUT->footer();
