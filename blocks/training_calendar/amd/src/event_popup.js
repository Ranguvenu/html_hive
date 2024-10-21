/**
 * Add a create new event modal to the page.
 *
 * @module     blocks/training calendar
 * @package    calendar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
 'core/str', 
 'core/modal_factory', 
 'core/modal_events', 
 'core/templates',
 'core/fragment', 
 'core/ajax', 
 'core/yui'],
        function($, Str, ModalFactory, ModalEvents,Templates) {

    return  {
        popup_eventinfo: function(args){
            var btn = '';
            var event_details = '';
            var other_details = 1;
            // var pop_desc =  args.eventlocal_eventname;
            var pop_desc =  args.eventlocal_classroomname;
            var tabone = {'active' :'active','type':'description','name':'Description'};

                var tabtwo = {'active' :'','type':'sessions','name':'Sessions'};

                var tabthree = {'active' :'','type':'prerequisites','name':'Pre-requisites'};
                var tabfour = {'active' :'','type':'targetlearners','name':'Target Learners'};
                var options = {};
                options.eventid = args.eventid;
                options.itemid = args.itemid;
                options.contenttype =  args.eventlocal_eventname;
                options.classroomname =  args.eventlocal_classroomname;

                options.tabs = [tabone,tabtwo,tabthree,tabfour];

            btn += '<ul class="eventpopup_footer">';
            switch (args.eventplugin) {
                case 'local_classroom':
                    if((args.eventenrolled == false) && (args.eventself_enrol == true)) {
                        var button_classroom = function () {
                            var tmp = null;
                            $.ajax({
                                async: false,
                                type: "POST",
                                global: false,
                                dataType: "json",
                                url: M.cfg.wwwroot + '/blocks/training_calendar/ajax.php?instance='+args.eventinstance+'&plugin=local_classroom',
                                success: function (returndata) {
                                    if ((returndata == 1)) {
                                        if(args.eventprerequisitecheck == true){//Please complete the Pre-requisite courses before Enrolling for ILT 
                                          tmp = '<li><a href= "javascript:void(0)" onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '+args.eventinstance+', classroomid:'+args.eventinstance+',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''+args.eventlocal_eventname+'\'}) })(event)"><button class="btn">Enrol</button></a> </li>';
                                        }else{
                                          tmp = '<div class="row col-xs-12"> Complete the pre-requisites to ENROL</div>'; 
                                        }
                                    }else if ((returndata == 2)) { //Please complete the Pre-requisite courses before Requesting for ILT 
                                        if(args.eventprerequisitecheck == true){
                                            tmp = '<li><a href= "javascript:void(0)" onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'enrolrequest\', id: '+args.eventinstance+', classroomid:'+args.eventinstance+',actionstatusmsg:\'classroom_enrolrequest_enrolment\',classroomname:\''+args.eventlocal_eventname+'\'}) })(event)"><button class="btn">Request</button></a> </li>';
                                        }else{
                                           tmp = '<div class="row col-xs-12"> Complete the pre-requisites to ENROL</div>';
                                        }
                                    }else if ((returndata == 3)) {
                                      tmp = '<li><button class="btn">Processing</button></li>';
                                    }else if ((returndata == 4)) {
                                      tmp = '<li><button class="btn">Waiting</button></li>';
                                    } else {
                                       tmp = '<li>Not published</li>';
                                    }
                                }
                            });
                            return tmp;
                        }();
                        btn += button_classroom;

                    }

                    else if(args.eventenrolled == true) {
                       btn += '<li><a href= "' + M.cfg.wwwroot + '/local/classroom/view.php?cid=' + args.eventinstance +'" target="_blank"><button class="btn">Launch</button></a> </li>';
                    }
                break;
                case 'local_program':
                    if((args.eventenrolled == false) && (args.eventself_enrol == true)) {
                        var button_program = function () {
                            var tmp = null;
                            $.ajax({
                                async: false,
                                type: "POST",
                                global: false,
                                dataType: "json",
                                url: M.cfg.wwwroot + '/blocks/training_calendar/ajax.php?instance='+args.eventinstance+'&plugin=local_program',
                                success: function (returndata) {
                                    if ((returndata == 1)) {
                                      tmp = '<li><a href= "javascript:void(0)" onclick="(function(e){ require(\'local_program/program\').ManageprogramStatus({action:\'selfenrol\', id: '+args.eventinstance+', programid:'+args.eventinstance+',actionstatusmsg:\'program_self_enrolment\',programname:\''+args.eventlocal_eventname+'\'}) })(event)"><button class="btn">Enrol</button></a> </li>';
                                    } else {
                                       tmp = '<li>Not published</li>';
                                    }
                                }
                            });
                            return tmp;
                        }();
                        btn += button_program;
                    }

                    else if(args.eventenrolled == true)
                    btn += '<li><a href= "' + M.cfg.wwwroot + '/local/program/view.php?bcid=' + args.eventinstance +'" target="_blank"><button class="btn">Launch</button></a> </li>';
                break;
                case 'local_certification':
                    if((args.eventenrolled == false) && (args.eventself_enrol == true)) {
                        var button_certification = function () {
                            var tmp = null;
                            $.ajax({
                                async: false,
                                type: "POST",
                                global: false,
                                dataType: "json",
                                url: M.cfg.wwwroot + '/blocks/training_calendar/ajax.php?instance='+args.eventinstance+'&plugin=local_certification',
                                success: function (returndata) {
                                    if ((returndata == 1)) {
                                      tmp = '<li><a href= "javascript:void(0)"  onclick="(function(e){ require(\'local_certification/certification\').ManagecertificationStatus({action:\'selfenrol\', id: '+args.eventinstance+', certificationid:'+args.eventinstance+',actionstatusmsg:\'certification_self_enrolment\',certificationname:\''+args.eventlocal_eventname+'\'}) })(event)"><button class="btn">Enrol</button></a> </li>';
                                    } else {
                                       tmp = '<li>Not published</li>';
                                    }
                                }
                            });
                            return tmp;
                        }();
                        btn += button_certification;
                    }

                    else if(args.eventenrolled == true)
                    btn += '<li><a href= "' + M.cfg.wwwroot + '/local/certification/view.php?ctid=' + args.eventinstance +'" target="_blank"><button class="btn">Launch</button></a> </li>';
                break;
                case 'course':
                    if((args.eventenrolled == false) && (args.eventself_enrol == true)) {
                        var button_course = function () {
                            var tmp = null;
                            $.ajax({
                                async: false,
                                type: "POST",
                                global: false,
                                dataType: "json",
                                url: M.cfg.wwwroot + '/blocks/training_calendar/ajax.php?instance='+args.eventinstance+'&plugin=local_certification',
                                success: function (returndata) {
                                    if ((returndata == 1)) {
                                      tmp = '<li><a href= "' + M.cfg.wwwroot + '/course/view.php?id=' + args.eventinstance +'" target="_blank"><button class="btn">View</button></a> </li>';
                                    } else {
                                       tmp = '<li>Not published</li>';
                                    }
                                }
                            });
                            return tmp;
                        }();
                        btn += button_course;
                    }
                    else if(args.eventenrolled == true)
                    btn += '<li><a href= "' + M.cfg.wwwroot + '/course/view.php?id=' + args.eventinstance +'" ><button class="btn">View</button></a> </li>';
                break;
                default:
                    btn += '<li><a href="'+ M.cfg.wwwroot+'" target="_blank"></li>';
                break;
            }
            btn += '</ul>';
            event_details += btn;
            
            if (args.eventeventtype == "session_open" || args.eventeventtype == "open") {
                if (args.eventlocal_eventenddate == 'null') {
                     event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"> <i class="icon fa fa-clock-o fa-fw " aria-hidden="true" title="When" aria-label="When"></i> </div><div class="col-xs-11"> Opens on ' + args.eventlocal_eventstartdate+ '</div></div>';
                } else {
                      //event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"> <i class="icon fa fa-clock-o fa-fw " aria-hidden="true" title="When" aria-label="When"></i> </div><div class="col-xs-11">' + args.eventlocal_eventstartdate+ ' - '+ args.eventlocal_eventenddate+ '</div></div>';
                      event_details += '<div class="d-flex  mt-10 mb-10"><div class="calendericon_container"> <i class="icon fa fa-calendar fa-fw " aria-hidden="true" title="ILT Start Date" aria-label="ILT Start Date"></i> </div><div class="content_container">' + args.eventstartdate + '</div></div>';
                }
            } else if (args.eventeventtype == "session_close" || args.eventeventtype == "close") {
                event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"> <i class="icon fa fa-clock-o fa-fw " aria-hidden="true" title="When" aria-label="When"></i> </div><div class="col-xs-11"> Closes on ' + args.eventlocal_eventenddate+ '</div></div>';
            } else { // all other events like course site mod
                event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"> <i class="icon fa fa-clock-o fa-fw " aria-hidden="true" title="When" aria-label="When"></i> </div><div class="col-xs-11"> ' + args.eventlocal_eventstartdate+ '</div></div>';
                event_details += '<div class="d-flex  mt-10 mb-10"><div class="calendericon_container"> <i class="icon fa fa-calendar fa-fw  " aria-hidden="true" title="Event type" aria-label="Event type"></i></i> </div><div class="content_container">' + args.eventeventtype + '</div></div>';
                event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"> <i class="icon fa fa-align-left fa-fw " aria-hidden="true" title="Summary" aria-label="Summary"></i></i> </div><div class="col-xs-11">' + args.eventsummary + '</div></div>';
                other_details = 0;
            }
            if(args.plugintype == 'local_classroom'){
                if(args.coursename){
                    var coursename = args.coursename;
                }else{
                    var coursename = 'NA';
                }
                if(args.creditpoints){
                    var creditpoints = args.creditpoints;
                }else{
                    var creditpoints = 0;
                }
                event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"> <i class="icon fa fa-book fa-fw " aria-hidden="true" title="Course" aria-label="Course"></i> </div><div class="col-xs-11"> ' + coursename+ '</div></div>';
                event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"> <i class="icon fa fa-money fa-fw " aria-hidden="true" title="Credits" aria-label="Credits"></i> </div><div class="col-xs-11"> ' + creditpoints+ '</div></div>';
            }
            if (other_details == 1) {
                event_details += '<div class="d-flex  mt-10 mb-10"><div class="linkicon_container"> <i class="icon fa fa-link fa-fw " aria-hidden="true" title="URL" aria-label="URL"></i> </div><div class="linkcontent_container"> ' + args.eventprerequisiteurl + '</div></div>';
                event_details += '<div class="d-flex  mt-10 mb-10"><div class="fileicon_container"> <i class="icon fa fa-file-text fa-fw " aria-hidden="true" title="Self Enrol" aria-label="Self Enrol"></i> </div><div class="filecontent_container"> ' + args.eventiltselfenrol + '</div></div>';
                event_details += '<div class="d-flex  mt-10 mb-10"><div class="clockicon_container"> <i class="icon fa fa-file-text fa-fw " aria-hidden="true" title="Waiting list" aria-label="Waiting list"></i> </div><div class="clock_container"> ' + args.waitinglistenable + '</div></div>';
                event_details += '<div class="d-flex mt-10 mb-10"><div class="usericon_container"> <i class="icon fa fa-user fa-fw " aria-hidden="true" title="Users" aria-label="Users"></i> </div><div class="capacity_container"> <span class=\"capacity_name\">Capacity :</span> <span class=\"capacity_count\">'+args.capacity+'</span></div><div class="enroll_container"> <span class=\"enroll_name\">Enrolled : </span><span class=\"enroll_count\">'+args.enrolledusers+'</span></div><div class="waitinglist_container"> <span class=\" waiting_name\"> Waiting List :</span><span class=\"waitinglist_count\">'+args.waitinglistinfo+'</span></div></div>';
                // event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"> <i class="icon fa fa-book fa-fw " aria-hidden="true" title="Prerequisitecourse" aria-label="Prerequisitecourse"></i> </div><div class="col-xs-11"> Pre-Requisite Courses: </div></div>';
                // event_details += '<div class="row  mt-10 mb-10"><div class="col-xs-1"></div><div class="col-xs-11"> ' + args.eventprerequisite + '</div></div>';
            }

            options.event_details = event_details;
            // event_details += Templates.render('block_training_calendar/popuptabs_display',options);
            ModalFactory.create({
                title: pop_desc,
                body: Templates.render('block_training_calendar/popuptabs_display',options),
                // footer: btn
              }).done(function(modal) {

                modal.setLarge();
                modal.show();
                modal.getRoot().click(function(e) {
                 modal.show();
                }.bind(this));
                $(".close").click(function(e) {
                    modal.hide();
                    modal.destroy();
                }.bind(this));
      
            });
        },
        load: function () {
           // do nothing
        }
    };
});
