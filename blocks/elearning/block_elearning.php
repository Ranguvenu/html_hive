<?php
class block_elearning extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_elearning');
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

		$renderer = $this->page->get_renderer('block_elearning');
		
        if(isloggedin()){
			$this->content =  new stdClass;
			$this->content->text   = $renderer->get_elearning_content();
	        $this->contentgenerated = true;
			return $this->content;
	    }else{
			return false;
		}
	}
}