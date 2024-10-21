<?php
class block_empcredits extends block_base {
    public function init() {
        $this->title = get_string('emptitle', 'block_empcredits');
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

		$renderer = $this->page->get_renderer('block_empcredits');
		
             if(isloggedin()){
		$this->content =  new stdClass;
		$this->content->text   = $renderer->get_empcredits_credits();
	    $this->contentgenerated = true;
		return $this->content;
	     }
	}
}