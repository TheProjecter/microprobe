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

			
	class mp_change_course_page {
		
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
					'title' => 'Change Course',
					'request' => 'mp-change-course-page',
					'nav' => 'null', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		function match_request($request)
		{
			if ($request=='mp-change-course-page')
				return true;

			return false;
		}
		
		function process_request($request)
		{
			$userid = qa_get_logged_in_userid();
			$categoryoptions = array();
			$qa_content=qa_content_prepare();

			// check if we have done a post of the page
			if ( qa_post_text('okthen') ) {
			
				// update the current category
				$newcategory = qa_post_text('category');
				
				if ( isset( $newcategory ) ) 
				{
					mp_set_categoryid($newcategory);
				
					// redirect to main page
					qa_redirect('');
				}
				else
				{
					$qa_content['error']='You must select a course to continue.';
				}
			}
		
			// retrieve list of categories user is associated with
				
			// populate category options
			$results = mp_get_categories_for_user($userid);
			foreach ( $results as $row ) {
				$categoryoptions[$row['categoryid']] = $row['title'];
			}
			
			$qa_content['title']='Registered courses';
			$qa_content['custom']='The following list displays all courses your account is associated with.  Select a course from the list below and click <B>Select</B> to change to the new course<br /><br />';

			$qa_content['form']=array(
				'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
				
				'style' => 'wide',
				
//				'title' => 'Registered courses',
				
				'fields' => array(
					'courses' => array(
						'type' => 'select-radio',
						'label' => 'Courses',
						'tags' => 'NAME="category"',
						'options' => $categoryoptions,
						'value' => mp_get_categoryid(),
						'error' => qa_html(@$errors['course'])
					)
				),
				
				'buttons' => array(
					'ok' => array(
						'tags' => 'NAME="okthen"',
						'label' => 'Select',
						'value' => '1',
					),
				),			
			);
			
			return $qa_content;
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/