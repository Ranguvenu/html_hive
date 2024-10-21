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


defined('MOODLE_INTERNAL') || die;
global $CFG;
$local_wavatar = new admin_category('local_wavatar', new lang_string('pluginname', 'local_wavatar'),false);
$ADMIN->add('localsettings', $local_wavatar);
$settings = new admin_settingpage('local_wavatar', get_string('pluginname', 'local_wavatar'));
$ADMIN->add('localplugins', $settings);

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configselect('hide_save', get_string('hide_save_setting', 'local_wavatar'),get_string('confighide_save_setting', 'local_wavatar'),false, [true => get_string('enable', 'local_wavatar'), false => get_string('disable', 'local_wavatar')]));
    $settings->add(new admin_setting_configselect('hide_svg_download_on_Android', get_string('hide_svg_download_on_Android_setting', 'local_wavatar'),get_string('confighide_svg_download_on_Android_setting', 'local_wavatar'), true,  [true => get_string('enable', 'local_wavatar'), false => get_string('disable', 'local_wavatar')]));
    $settings->add(new admin_setting_configselect('hide_svg_download', get_string('hide_svg_download_setting', 'local_wavatar'),get_string('confighide_svg_download_setting', 'local_wavatar'), false, [true => get_string('enable', 'local_wavatar'), false => get_string('disable', 'local_wavatar')]));
    $settings->add(new admin_setting_configselect('hide_png_one_download', get_string('hide_png_one_download_setting', 'local_wavatar'),get_string('confighide_png_one_download_setting', 'local_wavatar'), false, [true => get_string('enable', 'local_wavatar'), false => get_string('disable', 'local_wavatar')]));
    $settings->add(new admin_setting_configselect('hide_png_two_download', get_string('hide_png_two_download_setting', 'local_wavatar'),get_string('confighide_png_two_download_setting', 'local_wavatar'), false, [true => get_string('enable', 'local_wavatar'), false => get_string('disable', 'local_wavatar')]));
    $settings->add(new admin_setting_configselect('hide_gravatar', get_string('hide_gravatar_setting', 'local_wavatar'),get_string('confighide_gravatar_setting', 'local_wavatar'), false, [true => get_string('enable', 'local_wavatar'), false => get_string('disable', 'local_wavatar')]));


}
$settings = null;