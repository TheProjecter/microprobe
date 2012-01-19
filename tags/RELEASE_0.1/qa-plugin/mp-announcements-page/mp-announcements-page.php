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

	class mp_announcements_page {
		
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
					'title' => 'Announcements',
					'request' => 'mp-announcements-page',
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		function match_request($request)
		{
			if ($request=='mp-announcements-page')
				return true;

			return false;
		}
		
		function process_request($request)
		{
			require_once QA_INCLUDE_DIR.'mp-app-posts.php';
			require_once QA_INCLUDE_DIR.'mp-db-users.php';
			
			$qa_content=qa_content_prepare();

			// if the user is not logged in, request user to login
			if (!qa_get_logged_in_userid()) {
				$qa_content['error']=qa_insert_login_links('Please ^1log in^2 or ^3register^4 first.', $request);
				
				return $qa_content;
			}

			$qa_content['title']='Course Announcements';
			
			// DISPLAY ANNOUCEMENTS	
			$data = '<div class="qa-q-list">';
			
			// retrieve annoucements
			$announcements = mp_announcements_get_all( mp_get_categoryid() );
			
			if ( count( $announcements ) == 0 ) 
			{
				$data .= "No announcements";
			}
			else
			{
				foreach ($announcements as $announcement ) 
				{
					$data .= '<div class="qa-q-list-item">';
					$data .= '<div class="qa-q-item-title">'.$announcement['title'].'</div>';
					$data .= '<div class="qa-q-view-content">'.$announcement['content'].'</div>';
					$data .= '<div class="qa-q-item-meta">Posted by <A HREF="'.qa_path_html('user/'.$announcement['handle']).'">'.$announcement['handle'].'</A> on '.$announcement['created'].'</div>';
					$data .= '</div>';
					$data .= '<div class="qa-q-list-item-clear" ></div>';
				}
			}
			
			$data .= '</div>';

			$qa_content['custom_2']=$data;
			
			// create the sub menu for navigation
			$qa_content['navigation']['sub'] = mp_announcements_sub_navigation();
			$qa_content['navigation']['sub']['default']['selected'] = true;
			return $qa_content;
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/