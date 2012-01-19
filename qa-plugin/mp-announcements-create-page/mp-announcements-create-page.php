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

	class mp_announcements_create_page {
		
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
					'title' => 'Create new annoucement',
					'request' => 'mp-announcements-create-page',
					'nav' => 'null', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		function match_request($request)
		{
			if ($request=='mp-announcements-create-page')
				return true;

			return false;
		}
		
		function process_request($request)
		{
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			require_once QA_INCLUDE_DIR.'qa-app-posts.php';
			require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
			require_once QA_INCLUDE_DIR.'mp-db-users.php';
			
			// report that we entered this page
			qa_report_event('page_enter', qa_get_logged_in_userid(), qa_get_logged_in_handle(), qa_cookie_get(), array('params'=>$_SERVER['QUERY_STRING']));
	
			// create the editor and update its content
			qa_get_post_content('editor', 'content', $ineditor, $incontent, $informat, $intext);
			$editorname=isset($ineditor) ? $ineditor : qa_opt('editor_for_qs');
			$editor=qa_load_editor(@$incontent, @$informat, $editorname);

			// retrieve variable data
			$innotify=qa_post_text('notify') ? true : false;

			// handle creation of annoucement
			if (qa_post_text('docreate')) {
				//retrieve data
				$title = qa_post_text('title');
				$content = $incontent;
				$format = $informat;
				
				// validate data
				
				
				// handle create work
				// actual create process is in file mp-app-posts.php
				$postid = qa_post_create('AN', null, $title, $content, $format, mp_get_categoryid(), null, qa_get_logged_in_userid(), $innotify);

				// redirect page
				qa_redirect('mp-announcements-page'); // our work is done here
			}
							
			$qa_content=qa_content_prepare();

			// if the user is not logged in, request user to login
			if (!qa_get_logged_in_userid()) {
				$qa_content['error']=qa_insert_login_links('Please ^1log in^2 or ^3register^4 first.', $request);
				
				return $qa_content;
			}
			
			$qa_content['title']='Create Announcement';

			$qa_content['form_newannouncement']=array(
				'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
				
				'style' => 'tall',
				
				'fields' => array(
					'title' => array(
						'label' => qa_lang_html('announcements/a_title_label'),
						'tags' => 'NAME="title"',
						'value' => qa_html(qa_post_text('title')),
						'error' => qa_html(@$errors['title']),
					),
					'content' => array_merge(
						$editor->get_field($qa_content, @$incontent, @$informat, 'content', 12, false),
						array(
							'label' => qa_lang_html('announcements/a_content_label'),
							'error' => qa_html(@$errors['content']),
							)
					),
					'notify' => array(
						'label' => 'Send email notification to all registered students',
						'tags' => 'NAME="notify"',
						'type' => 'checkbox',
						'value' => qa_html($innotify),
					),
				),
				
				'buttons' => array(
					'ok' => array(
						'tags' => 'NAME="docreate"',
						'label' => 'Create Announcement',
						'value' => '1',
					),
				),
				
				'hidden' => array(
					'hiddenfield' => '1',
					'editor' => qa_html($editorname),
				),
			);
			
			// create the sub menu for navigation
			$qa_content['navigation']['sub'] = mp_announcements_sub_navigation();
			$qa_content['navigation']['sub']['create']['selected'] = true;
			
			return $qa_content;
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/