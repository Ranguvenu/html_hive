<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Collapsed Topics Information
 *
 * A topic based format that solves the issue of the 'Scroll of Death' when a course has many topics. All topics
 * except zero have a toggle that displays that topic. One or more topics can be displayed at any given time.
 * Toggles are persistent on a per browser session per course basis but can be made to persist longer by a small
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.txt' file.
 *
 * @package    course/format
 * @subpackage toggletop
 * @version    See the value of '$plugin->version' in below.
 * @copyright  &copy; 2012-onwards G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @link       http://docs.moodle.org/en/Collapsed_Topics_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('format_toggletop_defaults',
            get_string('defaultheadingsub', 'format_toggletop'),
            format_text(get_string('defaultheadingsubdesc', 'format_toggletop'), FORMAT_MARKDOWN)));

    // /* Toggle instructions - 1 = no, 2 = yes. */
    // $name = 'format_toggletop/defaultdisplayinstructions';
    // $title = get_string('defaultdisplayinstructions', 'format_toggletop');
    // $description = get_string('defaultdisplayinstructions_desc', 'format_toggletop');
    // $default = 2;
    // $choices = array(
    //     1 => new lang_string('no'), // No.
    //     2 => new lang_string('yes')   // Yes.
    // );
    // $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    /* Layout configuration.
      Here you can see what numbers in the array represent what layout for setting the default value below.
      1 => Toggle word, toggle section x and section number - default.
      2 => Toggle word and section number.
      3 => Toggle word and toggle section x.
      4 => Toggle word.
      5 => Toggle section x and section number.
      6 => Section number.
      7 => No additions.
      8 => Toggle section x.
      Default layout to use - used when a new Collapsed Topics course is created or an old one is accessed for the first time
      after installing this functionality introduced in CONTRIB-3378. */
    $name = 'format_toggletop/defaultlayoutelement';
    $title = get_string('defaultlayoutelement', 'format_toggletop');
    $description = get_string('defaultlayoutelement_descpositive', 'format_toggletop');
    $default = 1;
    $choices = array(// In insertion order and not numeric for sorting purposes.
        1 => new lang_string('setlayout_all', 'format_toggletop'), // Toggle word, toggle section x and section number - default.
        3 => new lang_string('setlayout_toggle_word_section_x', 'format_toggletop'), // Toggle word and toggle section x.
        2 => new lang_string('setlayout_toggle_word_section_number', 'format_toggletop'), // Toggle word and section number.
        5 => new lang_string('setlayout_toggle_section_x_section_number', 'format_toggletop'), // Toggle section x and section number.
        4 => new lang_string('setlayout_toggle_word', 'format_toggletop'), // Toggle word.
        8 => new lang_string('setlayout_toggle_section_x', 'format_toggletop'), // Toggle section x.
        6 => new lang_string('setlayout_section_number', 'format_toggletop'), // Section number.
        7 => new lang_string('setlayout_no_additions', 'format_toggletop')                     // No additions.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    /* Structure configuration.
      Here so you can see what numbers in the array represent what structure for setting the default value below.
      1 => Topic.
      2 => Week.
      3 => Latest Week First.
      4 => Current Topic First.
      5 => Day.
      Default structure to use - used when a new Collapsed Topics course is created or an old one is accessed for the first time
      after installing this functionality introduced in CONTRIB-3378. */
    $name = 'format_toggletop/defaultlayoutstructure';
    $title = get_string('defaultlayoutstructure', 'format_toggletop');
    $description = get_string('defaultlayoutstructure_desc', 'format_toggletop');
    $default = 1;
    $choices = array(
        1 => new lang_string('setlayoutstructuretopic', 'format_toggletop'), // Topic.
        2 => new lang_string('setlayoutstructureweek', 'format_toggletop'), // Week.
        3 => new lang_string('setlayoutstructurelatweekfirst', 'format_toggletop'), // Latest Week First.
        4 => new lang_string('setlayoutstructurecurrenttopicfirst', 'format_toggletop'), // Current Topic First.
        5 => new lang_string('setlayoutstructureday', 'format_toggletop')                // Day.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Default number of columns between 1 and 4.
    $name = 'format_toggletop/defaultlayoutcolumns';
    $title = get_string('defaultlayoutcolumns', 'format_toggletop');
    $description = get_string('defaultlayoutcolumns_desc', 'format_toggletop');
    $default = 1;
    $choices = array(
        1 => new lang_string('one', 'format_toggletop'), // Default.
        2 => new lang_string('two', 'format_toggletop'), // Two.
        3 => new lang_string('three', 'format_toggletop'), // Three.
        4 => new lang_string('four', 'format_toggletop')   // Four.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Default column orientation - 1 = vertical and 2 = horizontal.
    $name = 'format_toggletop/defaultlayoutcolumnorientation';
    $title = get_string('defaultlayoutcolumnorientation', 'format_toggletop');
    $description = get_string('defaultlayoutcolumnorientation_desc', 'format_toggletop');
    $default = 2;
    $choices = array(
        1 => new lang_string('columnvertical', 'format_toggletop'),
        2 => new lang_string('columnhorizontal', 'format_toggletop') // Default.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle text alignment.
    // 1 = left, 2 = center and 3 = right - done this way to avoid typos.
    $name = 'format_toggletop/defaulttogglealignment';
    $title = get_string('defaulttogglealignment', 'format_toggletop');
    $description = get_string('defaulttogglealignment_desc', 'format_toggletop');
    $default = 2;
    $choices = array(
        1 => new lang_string('left', 'format_toggletop'), // Left.
        2 => new lang_string('center', 'format_toggletop'), // Centre.
        3 => new lang_string('right', 'format_toggletop')   // Right.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle icon position.
    // 1 = left and 2 = right - done this way to avoid typos.
    $name = 'format_toggletop/defaulttoggleiconposition';
    $title = get_string('defaulttoggleiconposition', 'format_toggletop');
    $description = get_string('defaulttoggleiconposition_desc', 'format_toggletop');
    $default = 1;
    $choices = array(
        1 => new lang_string('left', 'format_toggletop'), // Left.
        2 => new lang_string('right', 'format_toggletop')   // Right.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle icon set.
    // arrow        => Arrow icon set.
    // bulb         => Bulb icon set.
    // cloud        => Cloud icon set.
    // eye          => Eye icon set.
    // folder       => Folder icon set.
    // groundsignal => Ground signal set.
    // led          => LED icon set.
    // point        => Point icon set.
    // power        => Power icon set.
    // radio        => Radio icon set.
    // smiley       => Smiley icon set.
    // square       => Square icon set.
    // sunmoon      => Sun / Moon icon set.
    // switch       => Switch icon set.
    $name = 'format_toggletop/defaulttoggleiconset';
    $title = get_string('defaulttoggleiconset', 'format_toggletop');
    $description = get_string('defaulttoggleiconset_desc', 'format_toggletop');
    $default = 'arrow';
    $choices = array(
        'arrow' => new lang_string('arrow', 'format_toggletop'), // Arrow icon set.
        'bulb' => new lang_string('bulb', 'format_toggletop'), // Bulb icon set.
        'cloud' => new lang_string('cloud', 'format_toggletop'), // Cloud icon set.
        'eye' => new lang_string('eye', 'format_toggletop'), // Eye icon set.
        'folder' => new lang_string('folder', 'format_toggletop'), // Folder icon set.
        'groundsignal' => new lang_string('groundsignal', 'format_toggletop'), // Ground signal set.
        'led' => new lang_string('led', 'format_toggletop'), // LED icon set.
        'point' => new lang_string('point', 'format_toggletop'), // Point icon set.
        'power' => new lang_string('power', 'format_toggletop'), // Power icon set.
        'radio' => new lang_string('radio', 'format_toggletop'), // Radio icon set.
        'smiley' => new lang_string('smiley', 'format_toggletop'), // Smiley icon set.
        'square' => new lang_string('square', 'format_toggletop'), // Square icon set.
        'sunmoon' => new lang_string('sunmoon', 'format_toggletop'), // Sun / Moon icon set.
        'switch' => new lang_string('switch', 'format_toggletop') // Switch icon set.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    /* One section - 1 = no, 2 = yes. */
    $name = 'format_toggletop/defaultonesection';
    $title = get_string('defaultonesection', 'format_toggletop');
    $description = get_string('defaultonesection_desc', 'format_toggletop');
    $default = 1;
    $choices = array(
        1 => new lang_string('no'), // No.
        2 => new lang_string('yes')   // Yes.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle all icon hovers.
    // 1 => No.
    // 2 => Yes.
    $name = 'format_toggletop/defaulttoggleallhover';
    $title = get_string('defaulttoggleallhover', 'format_toggletop');
    $description = get_string('defaulttoggleallhover_desc', 'format_toggletop');
    $default = 2;
    $choices = array(
        1 => new lang_string('no'),
        2 => new lang_string('yes')
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Default toggle foreground colour in hexadecimal RGB with preceding '#'.
    $name = 'format_toggletop/defaulttgfgcolour';
    $title = get_string('defaulttgfgcolour', 'format_toggletop');
    $description = get_string('defaulttgfgcolour_desc', 'format_toggletop');
    $default = '#000000';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $settings->add($setting);

    $opacityvalues = array(
        '0.0' => '0.0',
        '0.1' => '0.1',
        '0.2' => '0.2',
        '0.3' => '0.3',
        '0.4' => '0.4',
        '0.5' => '0.5',
        '0.6' => '0.6',
        '0.7' => '0.7',
        '0.8' => '0.8',
        '0.9' => '0.9',
        '1.0' => '1.0'
    );

    // Default toggle foreground opacity between 0 and 1 in 0.1 increments.
    $name = 'format_toggletop/defaulttgfgopacity';
    $title = get_string('defaulttgfgopacity', 'format_toggletop');
    $description = get_string('defaulttgfgopacity_desc', 'format_toggletop');
    $default = '1.0';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $opacityvalues));

    // Default toggle foreground hover colour in hexadecimal RGB with preceding '#'.
    $name = 'format_toggletop/defaulttgfghvrcolour';
    $title = get_string('defaulttgfghvrcolour', 'format_toggletop');
    $description = get_string('defaulttgfghvrcolour_desc', 'format_toggletop');
    $default = '#888888';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $settings->add($setting);

    // Default toggle foreground hover opacity between 0 and 1 in 0.1 increments.
    $name = 'format_toggletop/defaulttgfghvropacity';
    $title = get_string('defaulttgfghvropacity', 'format_toggletop');
    $description = get_string('defaulttgfghvropacity_desc', 'format_toggletop');
    $default = '1.0';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $opacityvalues));

    // Default toggle background colour in hexadecimal RGB with preceding '#'.
    $name = 'format_toggletop/defaulttgbgcolour';
    $title = get_string('defaulttgbgcolour', 'format_toggletop');
    $description = get_string('defaulttgbgcolour_desc', 'format_toggletop');
    $default = '#e2e2f2';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $settings->add($setting);

    // Default toggle background opacity between 0 and 1 in 0.1 increments.
    $name = 'format_toggletop/defaulttgbgopacity';
    $title = get_string('defaulttgbgopacity', 'format_toggletop');
    $description = get_string('defaulttgbgopacity_desc', 'format_toggletop');
    $default = '1.0';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $opacityvalues));

    // Default toggle background hover colour in hexadecimal RGB with preceding '#'.
    $name = 'format_toggletop/defaulttgbghvrcolour';
    $title = get_string('defaulttgbghvrcolour', 'format_toggletop');
    $description = get_string('defaulttgbghvrcolour_desc', 'format_toggletop');
    $default = '#eeeeff';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $settings->add($setting);

    // Default toggle background hover opacity between 0 and 1 in 0.1 increments.
    $name = 'format_toggletop/defaulttgbghvropacity';
    $title = get_string('defaulttgbghvropacity', 'format_toggletop');
    $description = get_string('defaulttgbghvropacity_desc', 'format_toggletop');
    $default = '1.0';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $opacityvalues));

    // Show the section summary when collapsed.
    // 1 => No.
    // 2 => Yes.
    $name = 'format_toggletop/defaultshowsectionsummary';
    $title = get_string('defaultshowsectionsummary', 'format_toggletop');
    $description = get_string('defaultshowsectionsummary_desc', 'format_toggletop');
    $default = 1;
    $choices = array(
        1 => new lang_string('no'),
        2 => new lang_string('yes')
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    $settings->add(new admin_setting_heading('format_toggletop_configuration',
            get_string('configurationheadingsub', 'format_toggletop'),
            format_text(get_string('configurationheadingsubdesc', 'format_toggletop'), FORMAT_MARKDOWN)));

    /* Toggle persistence - 1 = on, 0 = off.  You may wish to disable for an AJAX performance increase.
      Note: If turning persistence off remove any rows containing 'toggletop_toggle_x' in the 'name' field
      of the 'user_preferences' table in the database.  Where the 'x' in 'toggletop_toggle_x' will be
      a course id. */
    $name = 'format_toggletop/defaulttogglepersistence';
    $title = get_string('defaulttogglepersistence', 'format_toggletop');
    $description = get_string('defaulttogglepersistence_desc', 'format_toggletop');
    $default = 1;
    $choices = array(
        0 => new lang_string('off', 'format_toggletop'), // Off.
        1 => new lang_string('on', 'format_toggletop')   // On.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle preference for the first time a user accesses a course.
    // 0 => All closed.
    // 1 => All open.
    $name = 'format_toggletop/defaultuserpreference';
    $title = get_string('defaultuserpreference', 'format_toggletop');
    $description = get_string('defaultuserpreference_desc', 'format_toggletop');
    $default = 0;
    $choices = array(
        0 => new lang_string('toggletopclosed', 'format_toggletop'),
        1 => new lang_string('toggletopopened', 'format_toggletop')
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle icon size.
    $name = 'format_toggletop/defaulttoggleiconsize';
    $title = get_string('defaulttoggleiconsize', 'format_toggletop');
    $description = get_string('defaulttoggleiconsize_desc', 'format_toggletop');
    $default = 'tc-medium';
    $choices = array(
        'tc-small' => new lang_string('small', 'format_toggletop'),
        'tc-medium' => new lang_string('medium', 'format_toggletop'),
        'tc-large' => new lang_string('large', 'format_toggletop')
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle border radius top left.
    $name = 'format_toggletop/defaulttoggleborderradiustl';
    $title = get_string('defaulttoggleborderradiustl', 'format_toggletop');
    $description = get_string('defaulttoggleborderradiustl_desc', 'format_toggletop');
    $default = '0.7';
    $choices = array(
        '0.0' => new lang_string('em0_0', 'format_toggletop'),
        '0.1' => new lang_string('em0_1', 'format_toggletop'),
        '0.2' => new lang_string('em0_2', 'format_toggletop'),
        '0.3' => new lang_string('em0_3', 'format_toggletop'),
        '0.4' => new lang_string('em0_4', 'format_toggletop'),
        '0.5' => new lang_string('em0_5', 'format_toggletop'),
        '0.6' => new lang_string('em0_6', 'format_toggletop'),
        '0.7' => new lang_string('em0_7', 'format_toggletop'),
        '0.8' => new lang_string('em0_8', 'format_toggletop'),
        '0.9' => new lang_string('em0_9', 'format_toggletop'),
        '1.0' => new lang_string('em1_0', 'format_toggletop'),
        '1.1' => new lang_string('em1_1', 'format_toggletop'),
        '1.2' => new lang_string('em1_2', 'format_toggletop'),
        '1.3' => new lang_string('em1_3', 'format_toggletop'),
        '1.4' => new lang_string('em1_4', 'format_toggletop'),
        '1.5' => new lang_string('em1_5', 'format_toggletop'),
        '1.6' => new lang_string('em1_6', 'format_toggletop'),
        '1.7' => new lang_string('em1_7', 'format_toggletop'),
        '1.8' => new lang_string('em1_8', 'format_toggletop'),
        '1.9' => new lang_string('em1_9', 'format_toggletop'),
        '2.0' => new lang_string('em2_0', 'format_toggletop'),
        '2.1' => new lang_string('em2_1', 'format_toggletop'),
        '2.2' => new lang_string('em2_2', 'format_toggletop'),
        '2.3' => new lang_string('em2_3', 'format_toggletop'),
        '2.4' => new lang_string('em2_4', 'format_toggletop'),
        '2.5' => new lang_string('em2_5', 'format_toggletop'),
        '2.6' => new lang_string('em2_6', 'format_toggletop'),
        '2.7' => new lang_string('em2_7', 'format_toggletop'),
        '2.8' => new lang_string('em2_8', 'format_toggletop'),
        '2.9' => new lang_string('em2_9', 'format_toggletop'),
        '3.0' => new lang_string('em3_0', 'format_toggletop'),
        '3.1' => new lang_string('em3_1', 'format_toggletop'),
        '3.2' => new lang_string('em3_2', 'format_toggletop'),
        '3.3' => new lang_string('em3_3', 'format_toggletop'),
        '3.4' => new lang_string('em3_4', 'format_toggletop'),
        '3.5' => new lang_string('em3_5', 'format_toggletop'),
        '3.6' => new lang_string('em3_6', 'format_toggletop'),
        '3.7' => new lang_string('em3_7', 'format_toggletop'),
        '3.8' => new lang_string('em3_8', 'format_toggletop'),
        '3.9' => new lang_string('em3_9', 'format_toggletop'),
        '4.0' => new lang_string('em4_0', 'format_toggletop')
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle border radius top right.
    $name = 'format_toggletop/defaulttoggleborderradiustr';
    $title = get_string('defaulttoggleborderradiustr', 'format_toggletop');
    $description = get_string('defaulttoggleborderradiustr_desc', 'format_toggletop');
    $default = '0.7';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle border radius bottom right.
    $name = 'format_toggletop/defaulttoggleborderradiusbr';
    $title = get_string('defaulttoggleborderradiusbr', 'format_toggletop');
    $description = get_string('defaulttoggleborderradiusbr_desc', 'format_toggletop');
    $default = '0.7';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Toggle border radius bottom left.
    $name = 'format_toggletop/defaulttoggleborderradiusbl';
    $title = get_string('defaulttoggleborderradiusbl', 'format_toggletop');
    $description = get_string('defaulttoggleborderradiusbl_desc', 'format_toggletop');
    $default = '0.7';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    /* Format responsive.  Turn on to support a non responsive theme theme. */
    $name = 'format_toggletop/formatresponsive';
    $title = get_string('formatresponsive', 'format_toggletop');
    $description = get_string('formatresponsive_desc', 'format_toggletop');
    $default = 0;
    $choices = array(
        0 => new lang_string('off', 'format_toggletop'), // Off.
        1 => new lang_string('on', 'format_toggletop')   // On.
    );
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));
}
