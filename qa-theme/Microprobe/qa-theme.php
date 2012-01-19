<?php
	
	// make mp classes requird
	require_once QA_INCLUDE_DIR.'mp-db-users.php';
	
	class qa_html_theme extends qa_html_theme_base
	{
		// add the Google Analytics script to all pages
		function head_script() // add a Javascript file from plugin directory
		{
			$this->content['script'][]='<SCRIPT SRC="'.
				qa_html('./qa-content/mp-google-analytics.js').
				'" TYPE="text/javascript"></SCRIPT>';
				
			qa_html_theme_base::head_script();
		}
		
		// modify the user section to display
		// content such as category switch
		function logged_in() {

			qa_html_theme_base::logged_in();
			
			$qa_login_userid = qa_get_logged_in_userid();
			if (isset($qa_login_userid)) {
				$currentcourse = mp_get_categoryinfo(mp_get_categoryid());
				//$this->output('Course: '.$currentcourse['title'] );
				//$this->output('<A HREF="'.qa_path_html('mp-change-course-page').'" class="qa-user-link">Change Course</a>');
				$this->output('course: <A HREF="'.qa_path_html('mp-change-course-page').'" class="qa-user-link">'.$currentcourse['title'].'</a>');
			}
		}
		
		function nav_main_sub()
		{		
			// if the user is not logged in, hide all navigation tabs
			if (!qa_get_logged_in_userid()) {
				unset($this->content['navigation']['main']['questions']);
				unset($this->content['navigation']['main']['unanswered']);
				//unset($this->content['navigation']['main']['categories']);
				unset($this->content['navigation']['main']['ask']);
				unset($this->content['navigation']['main']['custom-9']);
				unset($this->content['navigation']['main']['custom-7'][0]);
			}
			$this->nav('main');
			$this->nav('sub');
		}
		
		function q_list_item($question)
		{
			$this->output('<DIV CLASS="qa-q-list-item'.rtrim(' '.@$question['classes']).'" '.@$question['tags'].'>');

			$this->q_item_stats($question);
			$this->q_item_main($question);
			$this->q_item_clear();

			$this->output('</DIV> <!-- END qa-q-list-item -->', '');
		}		

		function post_meta_who($post, $class) // show usernames of privileged users in italics
		{
			require_once QA_INCLUDE_DIR.'qa-app-users.php'; // for QA_USER_LEVEL_BASIC constant
			
			//echo 'MPLOG'.print_r($post, true);
			
			if (isset($post['raw']['opostid'])) // if item refers to an answer or comment...
				$level=@$post['raw']['olevel']; // ...take the level of answer or comment author
			else
				$level=@$post['raw']['level']; // otherwise take level of the question author
			
			//if ($level>QA_USER_LEVEL_BASIC) // if level is more than basic user...
			$post['who']['data']='<SPAN CLASS="mp-badge-silver">'.@$post['who']['data'].'</SPAN>'; // ...add italics
			
			
			qa_html_theme_base::post_meta_who($post, $class);
		}
	}

?>