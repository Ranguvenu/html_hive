<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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

defined('MOODLE_INTERNAL') OR die('not allowed');
require_once($CFG->dirroot.'/local/evaluation/item/evaluation_item_class.php');

class evaluation_item_info extends evaluation_item_base {
    protected $type = "info";

    /** Mode recording response time (for non-anonymous evaluations only) */
    const MODE_RESPONSETIME = 1;
    /** Mode recording current course */
    const MODE_COURSE = 2;
    /** Mode recording current course category */
    const MODE_CATEGORY = 3;

    /** Special constant to keep the current timestamp as value for the form element */
    const CURRENTTIMESTAMP = '__CURRENT__TIMESTAMP__';

    public function build_editform($item, $evaluation) {
        global $DB, $CFG;
        require_once('info_form.php');
        if(!$item){
            $item= (object) $item;
        }
        //get the lastposition number of the evaluation_items
        $position = $item->position;
        $lastposition = $DB->count_records('local_evaluation_item', array('evaluation'=>$evaluation->id));
        if ($position == -1) {
            $i_formselect_last = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position = $lastposition + 1;
        } else {
            $i_formselect_last = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);

        $item->presentation = empty($item->presentation) ? self::MODE_COURSE : $item->presentation;
        $item->required = 0;

        //all items for dependitem
        $evaluationitems = evaluation_get_depend_candidates_for_item($evaluation, $item);
        $commonparams = array('cmid'=>$evaluation->id,
                             'id'=>isset($item->id) ? $item->id : null,
                             'typ'=>$item->typ,
                             'items'=>$evaluationitems,
                             'evaluation'=>$evaluation->id);

        // Options for the 'presentation' select element.
        $presentationoptions = array();
        if ($evaluation->anonymous == EVALUATION_ANONYMOUS_NO || $item->presentation == self::MODE_RESPONSETIME) {
            // "Response time" is hidden anyway in case of anonymous evaluation, no reason to offer this option.
            // However if it was already selected leave it in the dropdown.
            $presentationoptions[self::MODE_RESPONSETIME] = get_string('responsetime', 'local_evaluation');
        }
        $presentationoptions[self::MODE_COURSE]  = get_string('course');
        $presentationoptions[self::MODE_CATEGORY]  = get_string('coursecategory');

        //build the form
        $this->item_form = new evaluation_info_form('edit_item.php',
                                                  array('item'=>$item,
                                                  'common'=>$commonparams,
                                                  'positionlist'=>$positionlist,
                                                  'position' => $position,
                                                  'presentationoptions' => $presentationoptions));
    }

    public function save_item() {
        global $DB;

        if (!$this->get_data()) {
            return false;
        }
        $item = $this->item;

        if (isset($item->clone_item) AND $item->clone_item) {
            $item->id = ''; //to clone this item
            $item->position++;
        }

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->id = $DB->insert_record('local_evaluation_item', $item);
        } else {
            $DB->update_record('local_evaluation_item', $item);
        }

        return $DB->get_record('local_evaluation_item', array('id'=>$item->id));
    }

    /**
     * Helper function for collected data, both for analysis page and export to excel
     *
     * @param stdClass $item the db-object from evaluation_item
     * @param int|false $groupid
     * @param int $courseid
     * @return stdClass
     */
    protected function get_analysed($item, $groupid = false, $courseid = false) {

        $presentation = $item->presentation;
        $analysed_val = new stdClass();
        $analysed_val->data = null;
        $analysed_val->name = $item->name;
        $values = evaluation_get_group_values($item, $groupid, $courseid);
        if ($values) {
            $data = array();
            foreach ($values as $value) {
                $datavalue = new stdClass();

                if ($presentation == self::MODE_RESPONSETIME) {
                    $datavalue->value = $value->value;
                    $datavalue->show = $value->value ? userdate($datavalue->value) : '';
                }

                $data[] = $datavalue;
            }
            $analysed_val->data = $data;
        }
        return $analysed_val;
    }

    public function get_printval($item, $value) {

        if (strval($value->value) === '') {
            return '';
        }
        return $item->presentation == self::MODE_RESPONSETIME ?
                userdate($value->value) : $value->value;
    }

    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
        $out =  "<table class=\"analysis itemtype_{$item->typ}\">";
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);
        $data = $analysed_item->data;
        if (is_array($data)) {
            $out .= '<tr><th colspan="2" align="left">';
            $out .= $itemnr . ' ';
            if (strval($item->label) !== '') {
                $out .= '('. format_string($item->label).') ';
            }
            $out .= format_text($item->name, FORMAT_HTML, array('noclean' => true, 'para' => false));
            $out .= '</th></tr>';
            $sizeofdata = count($data);
            for ($i = 0; $i < $sizeofdata; $i++) {
                $class = strlen(trim($data[$i]->show)) ? '' : ' class="isempty"';
                $out .= '<tr'.$class.'><td colspan="2" class="singlevalue">';
                $out .= str_replace("\n", '<br />', $data[$i]->show);
                $out .= '</td></tr>';
            }
        }
        $out .= '</table>';
    }

    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false) {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);

        $worksheet->write_string($row_offset, 0, $item->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $item->name, $xls_formats->head2);
        $data = $analysed_item->data;
        if (is_array($data)) {
            $worksheet->write_string($row_offset, 2, $data[0]->show, $xls_formats->value_bold);
            $row_offset++;
            $sizeofdata = count($data);
            for ($i = 1; $i < $sizeofdata; $i++) {
                $worksheet->write_string($row_offset, 2, $data[$i]->show, $xls_formats->default);
                $row_offset++;
            }
        }
        $row_offset++;
        return $row_offset;
    }

    /**
     * Calculates the value of the item (time, course, course category)
     *
     * @param stdClass $item
     * @param stdClass $evaluation
     * @return string
     */
    protected function get_current_value($item, $evaluation) {
        global $DB;

        if ($item->presentation == self::MODE_RESPONSETIME) {
            if ($evaluation->anonymous != EVALUATION_ANONYMOUS_YES) {
                // Response time is not allowed in anonymous evaluations.
                return time();
            }
        }
        return '';
    }

    /**
     * Adds an input element to the complete form
     *
     * @param stdClass $item
     * @param local_evaluation_complete_form $form
     */
    public function complete_form_element($item, $form) {
        if ($form->get_mode() == local_evaluation_complete_form::MODE_VIEW_RESPONSE) {
            $value = strval($form->get_item_value($item));
        } else {
            $value = $this->get_current_value($item,
                    $form->get_evaluation());
        }
        $printval = $this->get_printval($item, (object)['value' => $value]);

        $class = '';

        if ($item->presentation == self::MODE_RESPONSETIME) {
            $class = 'info-responsetime';
            $value = $value ? self::CURRENTTIMESTAMP : '';
        }

        $name = $this->get_display_name($item);
        $inputname = $item->typ . '_' . $item->id;

        $element = $form->add_form_element($item,
                ['select', $inputname, $name,
                    array($value => $printval),
                    array('class' => $class)],
                false,
                false);
        $form->set_element_default($inputname, $value);
        $element->freeze();
        if ($form->get_mod() == local_evaluation_complete_form::MODE_COMPLETE) {
            $element->setPersistantFreeze(true);
        }
    }

    /**
     * Converts the value from complete_form data to the string value that is stored in the db.
     * @param mixed $value element from local_evaluation_complete_form::get_data() with the name $item->typ.'_'.$item->id
     * @return string
     */
    public function create_value($value) {
        if ($value === self::CURRENTTIMESTAMP) {
            return strval(time());
        }
        return parent::create_value($value);
    }

    public function can_switch_require() {
        return false;
    }

    public function get_data_for_external($item) {
        global $DB;
        $evaluation = $DB->get_record('local_evaluations', array('id' => $item->evaluation), '*', MUST_EXIST);
        // Return the default value (course name, category name or timestamp).
        return $this->get_current_value($item, $evaluation, $evaluation->course);
    }

    /**
     * Return the analysis data ready for external functions.
     *
     * @param stdClass $item     the item (question) information
     * @param int      $groupid  the group id to filter data (optional)
     * @param int      $courseid the course id (optional)
     * @return array an array of data with non scalar types json encoded
     * @since  Moodle 3.3
     */
    public function get_analysed_for_external($item, $groupid = false, $courseid = false) {

        $externaldata = array();
        $data = $this->get_analysed($item, $groupid, $courseid);

        if (is_array($data->data)) {
            foreach ($data->data as $d) {
                $externaldata[] = json_encode($d);
            }
        }
        return $externaldata;
    }
}
