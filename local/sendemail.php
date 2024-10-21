<?php

require_once(dirname(__FILE__) . '/../config.php');

global $DB, $USER, $CFG;


	$sql="SELECT * from {local_emaillogs} where sent_date=0 and status=0 ";//LIMIT 100
	
	$send_usersto=$DB->get_records_sql($sql, array(), 0, 100);
	
	foreach($send_usersto as $usersto) {
				
		$receipient=str_replace(",",";",$usersto->to_userid);	
	    //print_object($receipient);
		$subject=$usersto->subject;
		$msg=$usersto->body_html;
		$from=$usersto->from_emailid;
		$sendto=explode(';', $receipient);
		
	   	// added by anil
		$fromuser = core_user::get_support_user();
	   if(count($sendto)>1){
		foreach($sendto as $key => $val) {
		
			$touser = $DB->get_record('user', array('id'=>$val));
			$result=sendhtml_message($touser,$fromuser,$subject,$msg,$cc);
	
		}
			
	   }else{
			$val=implode(',',$sendto);
			
			$touser = $DB->get_record('user', array('id'=>$val));
			$result=sendhtml_message($touser,$fromuser,$subject,$msg,$cc);
			
	   }
		
		
		if($result){
			$send = new stdClass();
			$send->id = $usersto->id;
			$send->sent_date=time();
			$send->status=1;
			$DB->update_record('local_emaillogs', $send);
		}
			
	}


	/* function with html and attachment */
	
	function sendhtml_message($touser,$from,$subject,$msg,$cc){
		global $CFG,$DB;
		echo "HTML Content <br/>";
			
			$findme_ilt   = '[ilt_enroluserfulname]';
			$pos = strpos($msg, $findme_ilt);
			$findme='[lep_enroluserfulname]';
		    $pos_lep = strpos($msg, $findme);
			if ($pos === false || $pos_lep === false) {
					echo "entered";
					$findme_ilt='[ilt_enroluserfulname]';
					$usersid='[ilt_link]';
					$findmail='[ilt_enroluseremail]';
					$findme_lep='[lep_enroluserfulname]';
					$findmail_lep='[lep_enroluseremail]';
					$find_department='[ilt_department]';
					$find_lep_department='[lep_department]';
					$name=$touser->firstname.' '.$touser->lastname;
					$email=$touser->email;
					$costcenter=$DB->get_field('user','open_costcenterid',array('id'=>$touser->id));
					$department=$DB->get_field('local_costcenter','fullname',array('id'=>$costcenter));
					
					$string=str_replace(array($findme_ilt, $findmail,$findme_lep,$findmail_lep,$find_department,$find_lep_department), array($name, $email,$name,$email,$department,$department), $msg);
					//print_object($string);
					$textbody = html_to_text($string);	
			} else {
					echo "not-entered";
					$findme_ilt='[ilt_enroluserfulname]';
					$usersid='[ilt_link]';
					$findmail='[ilt_enroluseremail]';
					$findme_lep='[lep_enroluserfulname]';
					$findmail_lep='[lep_enroluseremail]';
					$find_department='[ilt_department]';
					$find_lep_department='[lep_department]';
					$name=$touser->firstname.' '.$touser->lastname;
					$email=$touser->email;
		    		$costcenter=$DB->get_field('user','open_costcenterid',array('id'=>$touser->id));
					$department=$DB->get_field('local_costcenter','fullname',array('id'=>$costcenter));
					$string=str_replace(array($findme_ilt, $findmail,$findme_lep,$findmail_lep,$find_department,$find_lep_department), array($name, $email,$name,$email,$department,$department), $msg);
					$textbody = html_to_text($string);	
			
			
			}
		
		$result=email_to_user($touser, $from, $subject, $textbody, $string ,$attachment = '', $attachname = '',
        $usetrueaddress = true, $replyto, $replytoname = 'Traning-Desk', $wordwrapwidth = 79,$cc);
	
		return $result;
	}


?>