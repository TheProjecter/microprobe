<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/example-page/qa-example-page.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Page module class for example page plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

	// make mp classes requird
	require_once QA_INCLUDE_DIR.'mp-db-users.php';

			
	class mp_classroom_page {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		function suggest_requests() // for display in admin interface
		{	
			return array(
				array(
					'title' => 'Classroom',
					'request' => 'mp-classroom-page',
					'nav' => 'null', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		function match_request($request)
		{
			if ($request=='mp-classroom-page')
				return true;

			return false;
		}
		
		function process_request($request)
		{
			$userid = qa_get_logged_in_userid();
			$categoryid = mp_get_categoryid();
			$users = mp_get_category_userids($categoryid);
			
			$qa_content=qa_content_prepare();
		
			$qa_content['title']='Classroom';

			$data = "<div class='mp-classroom'>";
			$data .= "<center><div class='mp-classroom-teacher'>&nbsp;</div></center>";
			$data .= "<div class='mp-classroom-users'>";
			foreach ($users as $user) 
			{
				$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($user['userid'], true));
				$data .="<div class='mp-classroom-user'>";
				$data .="<div class='mp-classroom-avatar'></div>";
				$data .="<div class='mp-classroom-useremail'>".qa_get_one_user_html($userinfo['handle'], false)."</div>";
				$data .="</div>";
			}
			
			$data .= "</div></div>";
			
			$qa_content['custom'] = $data;
			return $qa_content;
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/