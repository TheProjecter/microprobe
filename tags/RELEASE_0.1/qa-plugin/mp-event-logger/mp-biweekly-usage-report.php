<?php

	require_once '/afs/cs.pitt.edu/usr0/ezegarra/public/html/microprobe/qa-include/qa-base.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-emails.php';	
	require_once QA_INCLUDE_DIR.'mp-db-users.php';
	require_once QA_INCLUDE_DIR.'mp-db-posts.php';
	require_once QA_INCLUDE_DIR.'mp-db-points.php';	
	
	// the category or course is an argument we pass
	$category = qa_get('category');
	// get the debug flag
	$debug = qa_get('debug');
	$body = "";
	
	// build the list of subjects for the emails
	$subjects = array('Your Biweekly Microprobe activity for course ', 
					'Hello, this is your biweekly Microprobe activity for course ', 
					'A new edition of your biweekly Microprobe activity report for course ');
	$subjectindex = rand(0, (count($subjects) > 0? count($subjects)-1: 0));
	
	// set the debug mode.  if debug flag not set, set it to false by default
	if ($debug == "true") 
	{
		$debug = TRUE;
	}
	else
	{
		$debug = FALSE;
	}
	
	// check to ensure we provide a category
	if (!$category) {
		echo 'ERROR: You must provide a category as a http argment: category=categoryid';
	} 
	else // begin processing
	{
		$categoryinfo = mp_get_categoryinfo($category);
	
		// retrieve the list of users
		$users = mp_get_category_userids($category);
		
		foreach ($users as $user) 
		{
			$u = mp_db_user_find_by_userid($user['userid']);
			$body = sprintf("It has been %s days since your last login to Microprobe %s\n\n", mp_db_user_find_dayssincelastlogin_by_userid($user['userid']), $u[0]['email']);
			
			$questioncount = mp_get_count_posts_by_userid_category_posttype($user['userid'], $category, 'Q');
			
			$body .= sprintf("ACTIVITY INFORMATION\n");
			
			if ( $questioncount > 0 )
			{
				$body.= sprintf("You have posted %s questions so far. ", $questioncount);
				$body.= sprintf("It has been %s days since you last posted a question.\n", mp_get_days_since_last_post($user['userid'], $category, 'Q'));
			}
			else
			{
				$body.= sprintf("You have not posted a question before.  Posting a question will help increase your activity points while motivating the knowledge sharing process.\n\n");
			}
			
			$answercount = mp_get_count_posts_by_userid_category_posttype($user['userid'], $category, 'A');

			if ( $answercount > 0 )
			{
				$body.= sprintf("You have posted %d answers so far. ", $answercount);
				$body.= sprintf("It has been %d days since you last posted an answer.\n", mp_get_days_since_last_post($user['userid'], $category, 'A'));
			}
			else
			{
				$body.= sprintf("You have not posted an answer before.  Posting an answer will help increase your activity and knowledge sharing points while helping your fellow students.\n\n");
			}
				
			// point awareness
			$body.= sprintf("\nPOINTS INFORMATION\n");
			$body.= sprintf("You currently have %6s activity points.\n", mp_get_activity_points_by_userid($user['userid'], $category));
			$body.= sprintf("You currently have %6s knowledge contribution points.\n", mp_get_contribution_points_by_userid($user['userid'], $category));
			$body.= sprintf("You currently have %6s participation points.\n", mp_get_participation_points_by_userid($user['userid'], $category));
			$body.= sprintf("You currently have %6s total points.\n\n", mp_get_total_points_by_userid($user['userid'], $category));
			
			// contribution
			//$body.= "Within last month, a total of X questions were asked. Of those questions, X were asked by you.  Of those question N were answered.<br />";
			//$body.= "Maybe you can help by answered those questions.<br />";
			$body .= sprintf("You can increase your points by logging in to the system, asking questions, posting answers, or voting for either a question or an answer.\n\n");
			$body .= sprintf("Sincerely,\nThe MicroProbe team");
			
			// send the mail to the user
			//qa_send_notification($user['userid'], null, null, $subject, $body, null);
			if ( $debug == FALSE )
			{
				qa_send_notification($user['userid'], null, null, $subjects[$subjectindex].$categoryinfo['title'], $body, null);
				//qa_send_notification(1, null, null, $subjects[$subjectindex].$categoryinfo['title'], $body, null);
			}
			
			echo $debug.'<hr>'.$body;
		}
	}
?>