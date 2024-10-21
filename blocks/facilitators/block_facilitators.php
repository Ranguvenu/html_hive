<?php
global $CFG,$USER, $DB, $PAGE, $OUTPUT;

//$PAGE->requires->css('/blocks/facilitators/css/style.css', true);

?>
<?php
class block_facilitators extends block_base {
    public function init() {
        $this->title = get_string('facilitators', 'block_facilitators');
    }
    public function get_content() {
		
		$facil_res='';
    if ($this->content !== null) {
      return $this->content;
    }
		global $CFG, $USER, $PAGE;
		
        $this->content = new stdClass();
		require_once($CFG->dirroot.'/blocks/facilitators/renderer.php');
		$renderer = $PAGE->get_renderer('block_facilitators');
		
		$facilitator_res = $renderer->facilitators_info();
        
		
	
		$this->content->text = $facilitator_res;
		$this->content->footer = '';
        return $this->content;
}
}