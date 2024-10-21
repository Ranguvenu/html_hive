<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->libdir.'/filelib.php');

/**
 * Files external functions
 *
 * @package    local_video
 * @category   external
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_video_external extends external_api {
    /**
    * Returns description of get_files parameters
    *
    * @return external_function_parameters
    */

    public static function show_parameters() {
        return new external_function_parameters(
            array(
                'id'  => new external_value(PARAM_INT, 'id',0),
            )
        );
    }

    public static function show($id) {
        global $DB;
        $params = self::validate_parameters (
            self::show_parameters(),array('id'=>$id)
        );
        
        $context = context_system::instance();
        self::validate_context($context);

        if($id) {
            $result = $DB->execute('UPDATE {local_video} SET status = 0 WHERE status = 1');
            $result = $DB->execute('UPDATE {local_video} SET status = 1 WHERE id = '.$id);
            $video = $DB->get_record('local_video', array('id' => $id), '*', IGNORE_MULTIPLE);
            if($result == true) {
                \core\notification::add(get_string('noti','local_video',$video->title), \core\output\notification::NOTIFY_SUCCESS);
            }
        } else {
              throw new moodle_exception('Error');
        }
    }

    public static function show_returns() {
        return new external_value(PARAM_BOOL, 'Status');
    }
}
