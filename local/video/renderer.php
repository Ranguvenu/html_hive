<?php

require_once($CFG->dirroot.'/local/video/lib.php');
class local_video_renderer extends plugin_renderer_base {
    public function action_btn() {
        global $DB, $OUTPUT;
        $systemcontext = context_system::instance();
        $id = optional_param('id', 0, PARAM_INT);

        $result = $DB->get_records_sql("SELECT * FROM {local_video}");
        foreach ($result as $res) {
            $video          = $res->video;
            $res->imageurl  = img_path($res->video);
            $res->status == 1 ? true : false;
        }
        $templatecontext = [
            'result'    => array_values($result),
            'form'      => new moodle_url('/local/video/edit.php')
        ];
        echo $OUTPUT->render_from_template('local_video/index', $templatecontext);

    }
}
