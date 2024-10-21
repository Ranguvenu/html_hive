<?php
class block_nps extends block_base {
    public function init() {
        $this->title = get_string('npstitle', 'block_nps');
    }
    
	function get_content() {

		global $CFG, $USER, $DB, $OUTPUT, $PAGE;
		$systemcontext = context_system::instance();
		  if($this->content !== NULL) {
			return $this->content;
		}
		
		$this->content = new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';
		$this->page->navigation->initialise();
		$navigation = clone($this->page->navigation);

		$renderer = $this->page->get_renderer('block_nps');
		
		if(isloggedin()){
			$this->content =  new stdClass;
			$this->content->text   = $renderer->get_nps_credits();
			$this->contentgenerated = true;
		
			return $this->content;
		}
	}
}