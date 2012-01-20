<?php

	function mp_announcement_create($userid, $handle, $cookieid, $title, $content, $format, $text, $notify, $categoryid)
	{
		/* 
		 * Proceeds to create an announcement
		 *
		 */
	 
		require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';
		
		// persist data to database
		$postid = qa_db_post_create('AN', null, $userid, $cookieid, qa_remote_ip_address(), $title, $content, $format, null, $notify, $categoryid);
		qa_user_report_action(qa_get_logged_in_userid(), null, null, null, null);
				
		// update new post with category path hierarchy
		qa_db_posts_calc_category_path($postid);
		
		// send notifications
		if ( $notify && isset($postid) )
		{
			$category = mp_get_categoryinfo($categoryid);
			$recipients = mp_get_category_userids($categoryid);
			
			foreach ($recipients as $recipient )
			{
				qa_send_notification($recipient['userid'], null, null, qa_lang('emails/an_posted_subject'), qa_lang('emails/an_posted_body'), array(
					'^an_handle' => $handle,
					'^category_title' => $category['title'],
					'^an_title' => $title,
					'^an_url' => qa_path('mp-announcements-page', null, qa_opt('site_url'), null, null),
					));
			}
		}
		
		// report announcement create event
	 	qa_report_event('an_post', $userid, $handle, $cookieid, array(
			'postid' => $postid,
			'title' => $title,
			'content' => $content,
			'format' => $format,
			'text' => $text,
			'categoryid' => $categoryid,
			'notify' => $notify,
		));
		
		return $postid;
	}

	function mp_announcements_get_all($categoryid) 
	{
		/*
			Return all annoucement posts
		*/
		
		$results = qa_db_read_all_assoc(
						qa_db_query_sub( 'select p.*, u.handle from ^posts p, ^users u where p.type="AN" AND p.categoryid=# AND p.userid = u.userid ORDER BY p.created DESC', $categoryid), 'postid');
			
		return $results;
	}
	
	function mp_announcements_sub_navigation()
	{
		$level = qa_get_logged_in_level();
		
		$navigation = array();
		
		if ( $level >= QA_USER_LEVEL_EDITOR )
		{
			$navigation = array (
					'default' => array(
							'url' 	=> qa_path_html('mp-announcements-page'),
							'label' => qa_lang_html('announcements/link_all'),
						),
					'create' => array(
							'url' 	=> qa_path_html('mp-announcements-create-page'),
							'label' => qa_lang_html('announcements/link_create'),
						),
					);
		}
		else 
		{
			$navigation = array(
					'default' => array(
							'url' 	=> qa_path_html('mp-announcements-page'),
							'label' => qa_lang_html('announcements/link_all'),
					),
				);
		}
				
		return $navigation;
	}
?>