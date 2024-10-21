<?php
    /**
     * This file is part of eAbyas
     *
     * Copyright eAbyas Info Solutons Pvt Ltd, India
     *
     * This program is free software; you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation; either version 3 of the License, or
     * (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/>.
     *
     * @author eabyas  <info@eabyas.in>
     */
    namespace local_classroom\task;
    class classroom_feedbackreminder extends \core\task\scheduled_task
    {

        public function get_name()
        {
            return get_string('taskclassroomreminder', 'local_classroom');
        }


        public function execute()
        {
             global $DB;
            $emailtype = 'classroom_feedback_reminder';
            $fromuser = \core_user::get_support_user();
            $availiablenotifications = $this->classroom_feedback_module_due_notifications();          
            $modules = array();  
            foreach($availiablenotifications AS $notification){
                $starttime = strtotime(date('d-m-Y', strtotime("+".$notification->reminderdays." day")));
                $endtime = $starttime+86399;
                $sql = "SELECT * FROM {local_classroom} WHERE status = :status AND completiondate != 0
                            AND  (completiondate+(60*60*24*2)) >  completiondate ";
                if($notification->moduleid ){
                    $sql .= " AND id IN ($notification->moduleid )" ;
                }
                $params = array('status' => 4,'endtime' => $endtime);
                
                $classrooms = $DB->get_records_sql($sql, $params);
                $this->send_classroom_feedback_notification( $classrooms , $notification->moduleid);
               // $modules[] = $notification->moduleid;
            } 
            $globalduenotifications = $this->classroom_feedback_global_due_notifications(); 
            $moduleids = implode(',', $modules);
           
            foreach($globalduenotifications AS $notification){
                $starttime = strtotime(date('d-m-Y', strtotime("+".$notification->reminderdays." day")));
                $endtime = $starttime+86399;
                $sql = "SELECT * FROM {local_classroom} WHERE status = :status AND completiondate != 0
                            AND  (completiondate+(60*60*24*2)) >  completiondate ";
              
            
                $params = array('status' => 4);
                $classrooms = $DB->get_records_sql($sql, $params);
                $this->send_classroom_feedback_notification( $classrooms ,$moduleids );
            } 
           
        }

        private function classroom_feedback_global_due_notifications()
        {
            global $DB;
            $globalnotification_sql = "SELECT lni.* FROM {local_notification_info} AS lni 
                WHERE (lni.moduleid=0 OR lni.moduleid IS NULL) AND lni.notificationid=(SELECT id FROM {local_notification_type} WHERE shortname=:shortname)";
            $notifications = $DB->get_records_sql($globalnotification_sql, array('shortname' => 'classroom_feedback_reminder'));
            return $notifications;
        }

        private function classroom_feedback_module_due_notifications()
        {
            global $DB;
            $modulenotification_sql = "SELECT lni.* FROM {local_notification_info} AS lni 
                WHERE (lni.moduleid!=0 OR lni.moduleid IS NOT NULL) AND lni.notificationid=(SELECT id FROM {local_notification_type} WHERE shortname=:shortname)";
            $notifications = $DB->get_records_sql($modulenotification_sql, array('shortname' => 'classroom_feedback_reminder'));
            return $notifications;
        }

        private function send_classroom_feedback_notification($classrooms, $moduleids)
        {
            global $DB;
            $classroom_notification = new \local_classroom\notification();   
                   
            $emailtype = 'classroom_feedback_reminder';
            $fromuser = \core_user::get_support_user();
            foreach($classrooms as $classroom){ 
                $userfeedbacksql = " SELECT * FROM {local_evaluations} AS e
                                 WHERE e.plugin = 'classroom' AND e.instance = :classroomid AND e.deleted = 0 ";
                $params['classroomid'] = $classroom->id;
                $feedbacks = $DB->get_records_sql($userfeedbacksql, $params);
 
                foreach($feedbacks as $feedback){
                    if($feedback->evaluationtype == 2){
                        $sql = "SELECT lc.*,lcu.userid as userid FROM {local_classroom} AS lc 
                                LEFT JOIN {local_classroom_users} AS lcu ON lcu.classroomid=lc.id 
                                JOIN {local_evaluations} AS e ON e.instance = lc.id
                            WHERE lcu.userid NOT IN (SELECT uec.userid ";
                        $sql .= "FROM mdl_local_evaluation_completed AS uec WHERE uec.evaluation=e.id)";
                        
                        if($moduleids){
                            $sql .= " AND lc.id IN ($moduleids)" ;
                        }
                        $enrolclassrooms = $DB->get_records_sql($sql, array());    
                      
                         foreach ($enrolclassrooms as $classroomcontent) {
                            $touser = \core_user::get_user($classroomcontent->userid);
                            $classroominstance = $classroomcontent;
                            $classroominstance->feedback_name =  $feedback->name;
                            $classroominstance->classroom_feedbackurl  = "local/evaluation/complete.php/id=".$feedback->id;
                           
                            if($notification = $classroom_notification->get_existing_notification($classroominstance, $emailtype)){
                                $classroom_notification->send_classroom_notification($classroominstance, $touser, $fromuser, $emailtype, $notification);
                            }
                        } 
                    }else if($feedback->evaluationtype == 1){
                        $params = array();
                        $sql = "SELECT lc.*, lct.trainerid as userid FROM {local_classroom} AS lc
                            JOIN {local_classroom_trainers} AS lct ON lct.classroomid=lc.id
                            JOIN {local_evaluations} AS e ON e.instance = lc.id
                                WHERE lcu.userid NOT IN (SELECT uec.userid ";
                                $enrolclassrooms = $DB->get_records_sql($sql, $params);
                        $sql .= "FROM mdl_local_evaluation_completed AS uec WHERE uec.evaluation=e.id)";                        
                        if($moduleids){
                            $sql .= " AND lc.id IN ($moduleids)" ;
                        }
                      
                        foreach ($enrolclassrooms as $classroomcontent) {
                            $touser = \core_user::get_user($classroomcontent->userid);
                            $classroominstance = $classroomcontent;
                            $classroominstance->feedback_name =  $feedback->name;
                            $classroominstance->classroom_feedbackurl  = "local/evaluation/complete.php/id=".$feedback->id;
                           
                            if($notification = $classroom_notification->get_existing_notification($classroominstance, $emailtype)){
                                $classroom_notification->send_classroom_notification($classroominstance, $touser, $fromuser, $emailtype, $notification);
                            }
                        } 
                    }

                }
                
            }

              /*   $sql = "SELECT date(from_unixtime(completiondate+(60*60*24*2))) as remainderdate,cr.* 
                        FROM `mdl_local_classroom` cr 
                        WHERE status = 4 and completiondate > 0 
                        AND AND  date(from_unixtime(completiondate+(60*60*24*2))) >  date(from_unixtime(completiondate))";
 */
        
        }
}
