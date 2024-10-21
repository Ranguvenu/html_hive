<?php 
     
    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/local/externalcertificate/index.php');
    } else{
        $filterdata =  $mform->get_data();
        if($filterdata){
            $collapse = false;
        } else{
            $collapse = true;
        }
    }
    if(empty($filterdata) && !empty($jsonparam)){
        $filterdata = json_decode($jsonparam);
        foreach($thisfilters AS $filter){
            if(empty($filterdata->$filter)){
                unset($filterdata->$filter);
            }
        }
        $mform->set_data($filterdata);
    }
    if($filterdata){
        $show = 'show';
    } else{ 
        $show = '';
    } 
    
    echo  '<div class="'.$show.'" >
            <div id="filters_form" class="card card-body p-2" style = "background-color : #fff">';
             $mform->display();
    echo        '</div>';
    if(!is_siteadmin() && !(has_capability('local/externalcertificate:manage', $systemcontext) && has_capability('local/externalcertificate:view', $systemcontext))){
        if($formtype == 'externalfilteringform'){
            echo '<button style="float:right; cursor: pointer;" onclick="(function(e){
                    require(\'local_externalcertificate/external_certificates\').init({
                        contextid:1, callback:\'certificate_form\', component:\'local_externalcertificate\', pluginname:\'local_externalcertificate\'
                    })
                })(event)" title="'.get_string('uploadcertificate', 'local_externalcertificate').'">
                '. get_string('addnew', 'local_externalcertificate') .'
                </button>';
        }
    
    }
    if(is_siteadmin() ||(has_capability('local/externalcertificate:manage', $systemcontext)&& has_capability('local/externalcertificate:view', $systemcontext))){
          echo "<a class='course_extended_menu_itemlink btn btn-primary pull-right' data-action='mastercertificate_form' data-value='0' title = '".get_string('addnewmastercourse', 'local_externalcertificate')."' href='master_certificatedata.php' ><span class='createicon'> ".get_string('addnewmastercourse', 'local_externalcertificate')."</span></a>";
      
    }
    echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams); 
