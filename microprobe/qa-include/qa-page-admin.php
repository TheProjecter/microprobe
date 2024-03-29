<?php
	
/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Controller for most admin pages which just contain options


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	// report that we entered this page
	qa_report_event('page_enter', qa_get_logged_in_userid(), qa_get_logged_in_handle(), qa_cookie_get(), array('params'=>$_SERVER['QUERY_STRING'],'path'=>$_SERVER['SCRIPT_NAME']));
		
	require_once QA_INCLUDE_DIR.'qa-db-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	

//	Get list of categories and all options
	
	$categories=qa_db_select_with_pending(qa_db_category_nav_selectspec(null, true));
	

//	Check admin privileges (do late to allow one DB query)

	if (!qa_admin_check_privileges($qa_content))
		return $qa_content;


//	For non-text options, lists of option types, minima and maxima
	
	$optiontype=array(
		'avatar_profile_size' => 'number',
		'avatar_q_list_size' => 'number',
		'avatar_q_page_a_size' => 'number',
		'avatar_q_page_c_size' => 'number',
		'avatar_q_page_q_size' => 'number',
		'avatar_store_size' => 'number',
		'avatar_users_size' => 'number',
		'columns_tags' => 'number',
		'columns_users' => 'number',
		'feed_number_items' => 'number',
		'flagging_hide_after' => 'number',
		'flagging_notify_every' => 'number',
		'flagging_notify_first' => 'number',
		'hot_weight_a_age' => 'number',
		'hot_weight_answers' => 'number',
		'hot_weight_q_age' => 'number',
		'hot_weight_views' => 'number',
		'hot_weight_votes' => 'number',
		'logo_height' => 'number-blank',
		'logo_width' => 'number-blank',
		'max_len_q_title' => 'number',
		'max_num_q_tags' => 'number',
		'max_rate_ip_as' => 'number',
		'max_rate_ip_cs' => 'number',
		'max_rate_ip_flags' => 'number',
		'max_rate_ip_logins' => 'number',
		'max_rate_ip_messages' => 'number',
		'max_rate_ip_qs' => 'number',
		'max_rate_ip_uploads' => 'number',
		'max_rate_ip_votes' => 'number',
		'max_rate_user_as' => 'number',
		'max_rate_user_cs' => 'number',
		'max_rate_user_flags' => 'number',
		'max_rate_user_messages' => 'number',
		'max_rate_user_qs' => 'number',
		'max_rate_user_uploads' => 'number',
		'max_rate_user_votes' => 'number',
		'min_len_a_content' => 'number',
		'min_len_c_content' => 'number',
		'min_len_q_content' => 'number',
		'min_len_q_title' => 'number',
		'min_num_q_tags' => 'number',
		'page_size_activity' => 'number',
		'page_size_ask_check_qs' => 'number',
		'page_size_ask_tags' => 'number',
		'page_size_home' => 'number',
		'page_size_hot_qs' => 'number',
		'page_size_qs' => 'number',
		'page_size_related_qs' => 'number',
		'page_size_search' => 'number',
		'page_size_tag_qs' => 'number',
		'page_size_tags' => 'number',
		'page_size_una_qs' => 'number',
		'page_size_user_posts' => 'number',
		'page_size_users' => 'number',
		'pages_prev_next' => 'number',
		'q_urls_title_length' => 'number',
		
		'allow_change_usernames' => 'checkbox',
		'allow_multi_answers' => 'checkbox',
		'allow_private_messages' => 'checkbox',
		'allow_view_q_bots' => 'checkbox',
		'avatar_allow_gravatar' => 'checkbox',
		'avatar_allow_upload' => 'checkbox',
		'avatar_default_show' => 'checkbox',
		'captcha_on_anon_post' => 'checkbox',
		'captcha_on_feedback' => 'checkbox',
		'captcha_on_register' => 'checkbox',
		'captcha_on_reset_password' => 'checkbox',
		'captcha_on_unconfirmed' => 'checkbox',
		'comment_on_as' => 'checkbox',
		'comment_on_qs' => 'checkbox',
		'confirm_user_emails' => 'checkbox',
		'do_ask_check_qs' => 'checkbox',
		'do_complete_tags' => 'checkbox',
		'do_count_q_views' => 'checkbox',
		'do_example_tags' => 'checkbox',
		'do_related_qs' => 'checkbox',
		'feed_for_activity' => 'checkbox',
		'feed_for_hot' => 'checkbox',
		'feed_for_qa' => 'checkbox',
		'feed_for_questions' => 'checkbox',
		'feed_for_search' => 'checkbox',
		'feed_for_tag_qs' => 'checkbox',
		'feed_for_unanswered' => 'checkbox',
		'feed_full_text' => 'checkbox',
		'feed_per_category' => 'checkbox',
		'feedback_enabled' => 'checkbox',
		'flagging_of_posts' => 'checkbox',
		'follow_on_as' => 'checkbox',
		'links_in_new_window' => 'checkbox',
		'logo_show' => 'checkbox',
		'neat_urls' => 'checkbox',
		'notify_admin_q_post' => 'checkbox',
		'notify_users_default' => 'checkbox',
		'q_urls_remove_accents' => 'checkbox',
		'show_c_reply_buttons' => 'checkbox',
		'show_custom_footer' => 'checkbox',
		'show_custom_header' => 'checkbox',
		'show_custom_home' => 'checkbox',
		'show_custom_in_head' => 'checkbox',
		'show_custom_sidebar' => 'checkbox',
		'show_custom_sidepanel' => 'checkbox',
		'show_home_description' => 'checkbox',
		'show_selected_first' => 'checkbox',
		'show_url_links' => 'checkbox',
		'show_user_points' => 'checkbox',
		'show_user_titles' => 'checkbox',
		'show_view_counts' => 'checkbox',
		'show_when_created' => 'checkbox',
		'site_maintenance' => 'checkbox',
		'suspend_register_users' => 'checkbox',
		'tag_separator_comma' => 'checkbox',
		'votes_separated' => 'checkbox',
		'voting_on_as' => 'checkbox',
		'voting_on_q_page_only' => 'checkbox',
		'voting_on_qs' => 'checkbox',
	);
	
	$optionmaximum=array(
		'feed_number_items' => QA_DB_RETRIEVE_QS_AS,
		'max_len_q_title' => QA_DB_MAX_TITLE_LENGTH,
		'page_size_activity' => QA_DB_RETRIEVE_QS_AS,
		'page_size_ask_check_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_ask_tags' => QA_DB_RETRIEVE_QS_AS,
		'page_size_home' => QA_DB_RETRIEVE_QS_AS,
		'page_size_hot_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_related_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_search' => QA_DB_RETRIEVE_QS_AS,
		'page_size_tag_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_tags' => QA_DB_RETRIEVE_TAGS,
		'page_size_una_qs' => QA_DB_RETRIEVE_QS_AS,
		'page_size_user_posts' => QA_DB_RETRIEVE_QS_AS,
		'page_size_users' => QA_DB_RETRIEVE_USERS,
	);
	
	$optionminimum=array(
		'flagging_hide_after' => 2,
		'flagging_notify_every' => 1,
		'flagging_notify_first' => 1,
		'max_num_q_tags' => 2,
		'page_size_activity' => 3,
		'page_size_ask_check_qs' => 3,
		'page_size_ask_tags' => 3,
		'page_size_home' => 3,
		'page_size_hot_qs' => 3,
		'page_size_qs' => 3,
		'page_size_search' => 3,
		'page_size_tag_qs' => 3,
		'page_size_tags' => 3,
		'page_size_users' => 3,
	);
	

//	Define the options to show (and some other visual stuff) based on request
	
	$formstyle='tall';
	$checkboxtodisplay=null;
	
	switch (@$qa_request_lc_parts[1]) {
		case 'emails':
			$subtitle='admin/emails_title';
			$showoptions=array('from_email', 'feedback_email', 'notify_admin_q_post', 'feedback_enabled', 'email_privacy');
			
			if (!QA_FINAL_EXTERNAL_USERS)
				$showoptions[]='custom_welcome';
			break;
			
		case 'layout':
			$subtitle='admin/layout_title';
			$showoptions=array('logo_show', 'logo_url', 'logo_width', 'logo_height', 'show_custom_sidebar', 'custom_sidebar', 'show_custom_sidepanel', 'custom_sidepanel', 'show_custom_header', 'custom_header', 'show_custom_footer', 'custom_footer', 'show_custom_in_head', 'custom_in_head', 'show_custom_home', 'custom_home_heading', 'custom_home_content', 'show_home_description', 'home_description');
			
			$checkboxtodisplay=array(
				'logo_url' => 'option_logo_show',
				'logo_width' => 'option_logo_show',
				'logo_height' => 'option_logo_show',
				'custom_sidebar' => 'option_show_custom_sidebar',
				'custom_sidepanel' => 'option_show_custom_sidepanel',
				'custom_header' => 'option_show_custom_header',
				'custom_footer' => 'option_show_custom_footer',
				'custom_in_head' => 'option_show_custom_in_head',
				'custom_home_heading' => 'option_show_custom_home',
				'custom_home_content' => 'option_show_custom_home',
				'home_description' => 'option_show_home_description',
			);
			break;
			
		case 'users':
			$subtitle='admin/users_title';

			if (!QA_FINAL_EXTERNAL_USERS) {
				require_once QA_INCLUDE_DIR.'qa-util-image.php';
				
				$showoptions=array('allow_change_usernames', 'allow_private_messages', '', 'avatar_allow_gravatar');
				
				if (qa_has_gd_image())
					array_push($showoptions, 'avatar_allow_upload', 'avatar_store_size', 'avatar_default_show');
					
				array_push($showoptions, '', 'avatar_profile_size', 'avatar_users_size', 'avatar_q_page_q_size', 'avatar_q_page_a_size', 'avatar_q_page_c_size', 'avatar_q_list_size', '');
	
				$checkboxtodisplay=array(
					'avatar_store_size' => 'option_avatar_allow_upload',
					'avatar_default_show' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
					'avatar_profile_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
					'avatar_users_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
					'avatar_q_page_q_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
					'avatar_q_page_a_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
					'avatar_q_page_c_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
					'avatar_q_list_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
				);
			} else
				$showoptions=array();
	
			$formstyle='wide';
			break;
			
		case 'viewing':
			$subtitle='admin/viewing_title';
			$showoptions=array('q_urls_title_length', 'q_urls_remove_accents', 'do_count_q_views', '', 'voting_on_qs', 'voting_on_q_page_only', 'voting_on_as', 'votes_separated', '', 'show_url_links', 'links_in_new_window', 'show_when_created');
			
			if (count(qa_get_points_to_titles()))
				$showoptions[]='show_user_titles';
			
			array_push($showoptions, 'show_user_points', '', 'sort_answers_by', 'show_selected_first', 'show_a_form_immediate',
				'show_c_reply_buttons', '', 'do_related_qs', 'match_related_qs', 'page_size_related_qs', '', 'pages_prev_next'
			);

			$formstyle='wide';

			$checkboxtodisplay=array(
				'votes_separated' => 'option_voting_on_qs || option_voting_on_as',
				'voting_on_q_page_only' => 'option_voting_on_qs',
				'match_related_qs' => 'option_do_related_qs',
				'page_size_related_qs' => 'option_do_related_qs',
			);
			break;
			
		case 'lists':
			$subtitle='admin/lists_title';
			
			$showoptions=array('page_size_home', 'page_size_activity', 'page_size_qs', 'page_size_hot_qs', 'page_size_una_qs');
			
			if (qa_opt('do_count_q_views'))
				$showoptions[]='show_view_counts';
				
			$showoptions[]='';
			
			if (qa_using_tags())
				array_push($showoptions, 'page_size_tags', 'columns_tags');
				
			array_push($showoptions, 'page_size_users', 'columns_users', '');
			
			if (qa_using_tags())
				$showoptions[]='page_size_tag_qs';
				
			array_push($showoptions, 'page_size_user_posts', 'page_size_search', '', 'hot_weight_q_age', 'hot_weight_a_age', 'hot_weight_answers', 'hot_weight_votes');
			
			if (qa_opt('do_count_q_views'))
				$showoptions[]='hot_weight_views';
			
			$formstyle='wide';
			
			break;
		
		case 'posting':
			$getoptions=qa_get_options(array('tags_or_categories'));
			
			$subtitle='admin/posting_title';

			$showoptions=array('allow_multi_answers', 'comment_on_qs', 'comment_on_as', 'follow_on_as', '');
			
			if (count(qa_list_modules('editor'))>1)
				array_push($showoptions, 'editor_for_qs', 'editor_for_as', 'editor_for_cs', '');
			
			array_push($showoptions, 'min_len_q_title', 'max_len_q_title', 'min_len_q_content');
			
			if (qa_using_tags())
				array_push($showoptions, 'min_num_q_tags', 'max_num_q_tags', 'tag_separator_comma');
			
			array_push($showoptions, 'min_len_a_content', 'min_len_c_content', 'notify_users_default', 'block_bad_words', '', 'do_ask_check_qs', 'match_ask_check_qs', 'page_size_ask_check_qs', '');

			if (qa_using_tags())
				array_push($showoptions, 'do_example_tags', 'match_example_tags', 'do_complete_tags', 'page_size_ask_tags');

			$formstyle='wide';

			$checkboxtodisplay=array(
				'min_len_c_content' => 'option_comment_on_qs || option_comment_on_as',
				'match_ask_check_qs' => 'option_do_ask_check_qs',
				'page_size_ask_check_qs' => 'option_do_ask_check_qs',
				'match_example_tags' => 'option_do_example_tags',
				'page_size_ask_tags' => 'option_do_example_tags || option_do_complete_tags',
			);
			break;
			
		case 'permissions':
			$subtitle='admin/permissions_title';
			
			$permitoptions=qa_get_permit_options();
			
			$showoptions=array();
			$checkboxtodisplay=array();
			
			foreach ($permitoptions as $permitoption) {
				$showoptions[]=$permitoption;
				
				if ($permitoption=='permit_view_q_page') {
					$showoptions[]='allow_view_q_bots';
					$checkboxtodisplay['allow_view_q_bots']='option_permit_view_q_page<'.qa_js(QA_PERMIT_ALL);
				
				} else {
					$showoptions[]=$permitoption.'_points';
					$checkboxtodisplay[$permitoption.'_points']='(option_'.$permitoption.'=='.qa_js(QA_PERMIT_POINTS).') ||(option_'.$permitoption.'=='.qa_js(QA_PERMIT_POINTS_CONFIRMED).')';
				}
			}
			
			$formstyle='wide';
			break;
		
		case 'feeds':
			$subtitle='admin/feeds_title';
			
			$showoptions=array('feed_for_questions', 'feed_for_qa', 'feed_for_activity');
			
			if (qa_using_categories())
				$showoptions[]='feed_per_category';
			
			array_push($showoptions, 'feed_for_hot', 'feed_for_unanswered');
			
			if (qa_using_tags())
				$showoptions[]='feed_for_tag_qs';
				
			array_push($showoptions, 'feed_for_search', 'feed_number_items', 'feed_full_text');
							
			$formstyle='wide';

			$checkboxtodisplay=array(
				'feed_per_category' => 'option_feed_for_qa || option_feed_for_questions || option_feed_for_unanswered || option_feed_for_activity',
			);
			break;
		
		case 'spam':
			$subtitle='admin/spam_title';
			
			$showoptions=array();
			
			$getoptions=qa_get_options(array('feedback_enabled', 'permit_post_q', 'permit_post_a', 'permit_post_c'));
			
			if (!QA_FINAL_EXTERNAL_USERS)
				array_push($showoptions, 'confirm_user_emails', 'suspend_register_users', '');
			
			$maxpermitpost=max($getoptions['permit_post_q'], $getoptions['permit_post_a'], $getoptions['permit_post_c']);
			
			if ($maxpermitpost > QA_PERMIT_USERS)
				$showoptions[]='captcha_on_anon_post';
				
			if ($maxpermitpost > QA_PERMIT_CONFIRMED)
				$showoptions[]='captcha_on_unconfirmed';
				
			if (!QA_FINAL_EXTERNAL_USERS)
				array_push($showoptions, 'captcha_on_register', 'captcha_on_reset_password');
			
			if ($getoptions['feedback_enabled'])
				$showoptions[]='captcha_on_feedback';
				
			if (count($showoptions))
				array_push($showoptions, 'recaptcha_public_key', 'recaptcha_private_key', '');
				
			array_push($showoptions, 'flagging_of_posts', 'flagging_notify_first', 'flagging_notify_every', 'flagging_hide_after', '');
			
			$checkboxtodisplay=array(
				'flagging_hide_after' => 'option_flagging_of_posts',
				'flagging_notify_every' => 'option_flagging_of_posts',
				'flagging_notify_first' => 'option_flagging_of_posts',
				'max_rate_ip_flags' =>  'option_flagging_of_posts',
				'max_rate_user_flags' => 'option_flagging_of_posts',
			);

			array_push($showoptions, 'max_rate_ip_qs', 'max_rate_ip_as', 'max_rate_ip_cs', 'max_rate_ip_uploads', 'max_rate_ip_votes', 'max_rate_ip_flags');
			
			if (qa_opt('allow_private_messages'))
				$showoptions[]='max_rate_ip_messages';
			
			array_push($showoptions,
				'max_rate_ip_logins', 'block_ips_write', '',
				'max_rate_user_qs', 'max_rate_user_as', 'max_rate_user_cs', 'max_rate_user_uploads', 'max_rate_user_votes', 'max_rate_user_flags'
			);

			if (qa_opt('allow_private_messages'))
				$showoptions[]='max_rate_user_messages';
			
			$formstyle='wide';

			if ($maxpermitpost > QA_PERMIT_USERS)
				$checkboxtodisplay=array_merge($checkboxtodisplay, array(
					'captcha_on_unconfirmed' => 'option_confirm_user_emails && option_captcha_on_anon_post',
					'recaptcha_public_key' => 'option_captcha_on_register || option_captcha_on_anon_post || option_captcha_on_reset_password || option_captcha_on_feedback',
					'recaptcha_private_key' => 'option_captcha_on_register || option_captcha_on_anon_post || option_captcha_on_reset_password || option_captcha_on_feedback',
				));
			else
				$checkboxtodisplay=array_merge($checkboxtodisplay, array(
					'captcha_on_unconfirmed' => 'option_confirm_user_emails',
					'recaptcha_public_key' => 'option_captcha_on_register || option_captcha_on_unconfirmed || option_captcha_on_reset_password || option_captcha_on_feedback',
					'recaptcha_private_key' => 'option_captcha_on_register || option_captcha_on_unconfirmed || option_captcha_on_reset_password || option_captcha_on_feedback',
				));
			break;
		
		default:
			$subtitle='admin/general_title';
			$showoptions=array('site_title', 'site_url', 'neat_urls', 'site_language', 'site_theme', 'tags_or_categories', 'site_maintenance');
			break;
	}
	

//	Filter out blanks to get list of valid options
	
	$getoptions=array();
	foreach ($showoptions as $optionname)
		if (!empty($optionname)) // empties represent spacers in forms
			$getoptions[]=$optionname;


//	Process user actions
	
	$errors=array();

	$recalchotness=false;			
	
	if (qa_clicked('doresetoptions'))
		qa_reset_options($getoptions);

	elseif (qa_clicked('dosaveoptions')) {
		foreach ($getoptions as $optionname) {
			$optionvalue=qa_post_text('option_'.$optionname);
			
			if (
				(@$optiontype[$optionname]=='number') ||
				(@$optiontype[$optionname]=='checkbox') ||
				((@$optiontype[$optionname]=='number-blank') && strlen($optionvalue))
			)
				$optionvalue=(int)$optionvalue;
				
			if (isset($optionmaximum[$optionname]))
				$optionvalue=min($optionmaximum[$optionname], $optionvalue);

			if (isset($optionminimum[$optionname]))
				$optionvalue=max($optionminimum[$optionname], $optionvalue);
				
			switch ($optionname) {
				case 'site_url':
					if (substr($optionvalue, -1)!='/') // seems to be a very common mistake and will mess up URLs
						$optionvalue.='/';
					break;
				
				case 'hot_weight_views':
				case 'hot_weight_answers':
				case 'hot_weight_votes':
				case 'hot_weight_q_age':
				case 'hot_weight_a_age':
					if (qa_opt($optionname) != $optionvalue)
						$recalchotness=true;
					break;
					
				case 'block_ips_write':
					require_once QA_INCLUDE_DIR.'qa-app-limits.php';
					$optionvalue=implode(' , ', qa_block_ips_explode($optionvalue));
					break;
					
				case 'block_bad_words':
					require_once QA_INCLUDE_DIR.'qa-util-string.php';
					$optionvalue=implode(' , ', qa_block_words_explode($optionvalue));
					break;
			}
						
			qa_set_option($optionname, $optionvalue);
		}

	//	Uploading default avatar

		if (is_array(@$_FILES['avatar_default_file']) && $_FILES['avatar_default_file']['size']) {
			require_once QA_INCLUDE_DIR.'qa-util-image.php';
			
			$oldblobid=qa_opt('avatar_default_blobid');
			
			$toobig=qa_image_file_too_big($_FILES['avatar_default_file']['tmp_name'], qa_opt('avatar_store_size'));
			
			if ($toobig)
				$errors['avatar_default_show']=qa_lang_sub('main/image_too_big_x_pc', (int)($toobig*100));
			
			else {
				$imagedata=qa_image_constrain_data(file_get_contents($_FILES['avatar_default_file']['tmp_name']), $width, $height, qa_opt('avatar_store_size'));
				
				if (isset($imagedata)) {
					require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
					
					$newblobid=qa_db_blob_create($imagedata, 'jpeg');
					
					if (isset($newblobid)) {
						qa_set_option('avatar_default_blobid', $newblobid);
						qa_set_option('avatar_default_width', $width);
						qa_set_option('avatar_default_height', $height);
						qa_set_option('avatar_default_show', 1);
					}
						
					if (strlen($oldblobid))
						qa_db_blob_delete($oldblobid);
	
				} else
					$errors['avatar_default_show']=qa_lang_sub('main/image_not_read', implode(', ', qa_gd_image_formats()));
			}
		}
	}

	$options=qa_get_options($getoptions);

	
//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html($subtitle);
	
	$qa_content['error']=qa_admin_page_error();

	$qa_content['form']=array(
		'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
		
		'style' => $formstyle,
		
		'fields' => array(),
		
		'buttons' => array(
			'save' => array(
				'label' => qa_lang_html('admin/save_options_button'),
			),
			
			'reset' => array(
				'tags' => 'NAME="doresetoptions"',
				'label' => qa_lang_html('admin/reset_options_button'),
			),
		),
		
		'hidden' => array(
			'dosaveoptions' => '1' // for IE
		),
	);

	if (qa_clicked('doresetoptions'))
		$qa_content['form']['ok']=qa_lang_html('admin/options_reset');
	elseif (qa_clicked('dosaveoptions'))
		$qa_content['form']['ok']=qa_lang_html('admin/options_saved');
	
	if ($recalchotness) {
		$qa_content['form']['ok']='<SPAN ID="recalc_ok"></SPAN>';
		
		$qa_content['script_rel'][]='qa-content/qa-admin.js?'.QA_VERSION;
		$qa_content['script_var']['qa_warning_recalc']=qa_lang('admin/stop_recalc_warning');
		
		$qa_content['script_onloads'][]=array(
			"qa_recalc_click('dorecountposts', document.getElementById('recalc_ok'), null, 'recalc_ok');"
		);
	}
		

	function qa_optionfield_make_select(&$optionfield, $options, $value, $default)
	{
		$optionfield['type']='select';
		$optionfield['options']=$options;
		$optionfield['value']=isset($options[$value]) ? $options[$value] : $options[$default];
	}
	

	foreach ($showoptions as $optionname)
		if (empty($optionname)) {
			$qa_content['form']['fields'][]=array(
				'type' => 'blank'
			);
		
		} else {
			$type=@$optiontype[$optionname];
			if ($type=='number-blank')
				$type='number';
			
			$value=$options[$optionname];
			
			$optionfield=array(
				'id' => $optionname,
				'label' => qa_lang_html('options/'.$optionname),
				'tags' => 'NAME="option_'.$optionname.'" ID="option_'.$optionname.'"',
				'value' => qa_html($value),
				'type' => $type,
				'error' => @$errors[$optionname],
			);
			
			if (isset($optionmaximum[$optionname]))
				$optionfield['note']=qa_lang_html_sub('admin/maximum_x', $optionmaximum[$optionname]);
				
			$feedrequest=null;
			$feedisexample=false;
			
			switch ($optionname) { // special treatment for certain options
				case 'site_language':
					require_once QA_INCLUDE_DIR.'qa-util-string.php';
					
					qa_optionfield_make_select($optionfield, qa_admin_language_options(), $value, '');
					
					$optionfield['label']=strtr($optionfield['label'], array(
						'^1' => '<A HREF="'.qa_html($qa_root_url_relative.'qa-include/qa-check-lang.php').'">',
						'^2' => '</A>',
					));
				
					if (!qa_has_multibyte())
						$optionfield['error']=qa_lang_html('admin/no_multibyte');
					break;
					
				case 'neat_urls':
					$neatoptions=array();

					$rawoptions=array(
						QA_URL_FORMAT_NEAT,
						QA_URL_FORMAT_INDEX,
						QA_URL_FORMAT_PARAM,
						QA_URL_FORMAT_PARAMS,
						QA_URL_FORMAT_SAFEST,
					);
					
					foreach ($rawoptions as $rawoption)
						$neatoptions[$rawoption]=
							'<IFRAME SRC="'.qa_path_html('url/test/'.QA_URL_TEST_STRING, array('dummy' => '', 'param' => QA_URL_TEST_STRING), null, $rawoption).'" WIDTH="20" HEIGHT="16" STYLE="vertical-align:middle; border:0" SCROLLING="no" FRAMEBORDER="0"></IFRAME>&nbsp;'.
							'<SMALL>'.
							qa_html(urldecode(qa_path('123/why-do-birds-sing', null, '/', $rawoption))).
							(($rawoption==QA_URL_FORMAT_NEAT) ? strtr(qa_lang_html('admin/neat_urls_note'), array(
								'^1' => '<A HREF="http://www.question2answer.org/htaccess.php" TARGET="_blank">',
								'^2' => '</A>',
							)) : '').
							'</SMALL>';
							
					qa_optionfield_make_select($optionfield, $neatoptions, $value, QA_URL_FORMAT_SAFEST);
							
					$optionfield['type']='select-radio';
					$optionfield['note']=qa_lang_html_sub('admin/url_format_note', '<SPAN STYLE=" '.qa_admin_url_test_html().'/SPAN>');
					break;
					
				case 'site_theme':
					qa_optionfield_make_select($optionfield, qa_admin_theme_options(), $value, 'Default');
					break;
				
				case 'tags_or_categories':
					qa_optionfield_make_select($optionfield, array(
						'' => qa_lang_html('admin/no_classification'),
						't' => qa_lang_html('admin/tags'),
						'c' => qa_lang_html('admin/categories'),
						'tc' => qa_lang_html('admin/tags_and_categories'),
					), $value, 'tc');

					$optionfield['error']='';
					
					if (qa_opt('cache_tagcount') && !qa_using_tags())
						$optionfield['error'].=qa_lang_html('admin/tags_not_shown').' ';
					
					if (!qa_using_categories())
						foreach ($categories as $category)
							if ($category['qcount']) {
								$optionfield['error'].=qa_lang_html('admin/categories_not_shown');
								break;
							}
					break;
				
				case 'custom_sidebar':
				case 'custom_sidepanel':
				case 'custom_header':
				case 'custom_footer':
				case 'custom_in_head':
				case 'home_description':
					unset($optionfield['label']);
					$optionfield['rows']=6;
					break;
					
				case 'custom_home_content':
					$optionfield['rows']=16;
					break;
					
				case 'custom_welcome':
					$optionfield['rows']=3;
					break;
				
				case 'avatar_allow_gravatar':
					$optionfield['label']=strtr($optionfield['label'], array(
						'^1' => '<A HREF="http://www.gravatar.com/" TARGET="_blank">',
						'^2' => '</A>',
					));
					
					if (!qa_has_gd_image()) {
						$optionfield['style']='tall';
						$optionfield['error']=qa_lang_html('admin/no_image_gd');
					}
					break;
					
				case 'avatar_default_show';
					$qa_content['form']['tags'].='ENCTYPE="multipart/form-data"';
					$optionfield['label'].=' <SPAN STYLE="margin:2px 0; display:inline-block;">'.
						qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), 32).
						'</SPAN> <INPUT NAME="avatar_default_file" TYPE="file" STYLE="width:16em;">';
					break;
				
				case 'pages_prev_next':
					qa_optionfield_make_select($optionfield, array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5), $value, 3);
					break;
	
				case 'columns_tags':
				case 'columns_users':
					qa_optionfield_make_select($optionfield, array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5), $value, 2);
					break;
					
				case 'sort_answers_by':
					qa_optionfield_make_select($optionfield, array(
						'created' => qa_lang_html('options/sort_time'),
						'votes' => qa_lang_html('options/sort_votes'),
					), $value, 'created');
					break;
					
				case 'show_a_form_immediate':
					qa_optionfield_make_select($optionfield, array(
						'always' => qa_lang_html('options/show_always'),
						'if_no_as' => qa_lang_html('options/show_if_no_as'),
						'never' => qa_lang_html('options/show_never'),
					), $value, 'if_no_as');
					break;
					
				case 'match_related_qs':
				case 'match_ask_check_qs':
				case 'match_example_tags':
					qa_optionfield_make_select($optionfield, qa_admin_match_options(), $value, 3);
					break;
					
				case 'block_bad_words':
					$optionfield['style']='tall';
					$optionfield['rows']=4;
					$optionfield['note']=qa_lang_html('admin/block_words_note');
					break;
					
				case 'editor_for_qs':
				case 'editor_for_as':
				case 'editor_for_cs':
					$editors=qa_list_modules('editor');
					
					$selectoptions=array();
					foreach ($editors as $editor)
						$selectoptions[$editor]=strlen($editor) ? $editor : qa_lang_html('admin/basic_editor');
						
					qa_optionfield_make_select($optionfield, $selectoptions, $value, '');
					break;
				
				case 'recaptcha_public_key':
					$optionfield['style']='tall';
					break;
					
				case 'recaptcha_private_key':
					require_once QA_INCLUDE_DIR.'qa-app-captcha.php';

					$optionfield['style']='tall';
					$optionfield['error']=qa_captcha_error();
					break;
					
				case 'flagging_hide_after':
				case 'flagging_notify_every':
				case 'flagging_notify_first':
					$optionfield['note']=qa_lang_html_sub('main/x_flags', '');
					break;
				
				case 'block_ips_write':
					$optionfield['style']='tall';
					$optionfield['rows']=4;
					$optionfield['note']=qa_lang_html('admin/block_ips_note');
					break;
					
				case 'allow_view_q_bots':
					$optionfield['note']=$optionfield['label'];
					unset($optionfield['label']);
					break;
				
				case 'permit_view_q_page':
				case 'permit_post_q':
				case 'permit_post_a':
				case 'permit_post_c':
				case 'permit_vote_q':
				case 'permit_vote_a':
				case 'permit_edit_q':
				case 'permit_edit_a':
				case 'permit_edit_c':
				case 'permit_flag':
				case 'permit_select_a':
				case 'permit_hide_show':
				case 'permit_delete_hidden':
				case 'permit_anon_view_ips':
					$optionfield['label']=qa_lang_html('profile/'.$optionname).':';
					
					if ( ($optionname=='permit_view_q_page') || ($optionname=='permit_post_q') || ($optionname=='permit_post_a') || ($optionname=='permit_post_c') || ($optionname=='permit_anon_view_ips') )
						$widest=QA_PERMIT_ALL;
					elseif ( ($optionname=='permit_select_a') || ($optionname=='permit_hide_show') )
						$widest=QA_PERMIT_POINTS;
					elseif ($optionname=='permit_delete_hidden')
						$widest=QA_PERMIT_EDITORS;
					else
						$widest=QA_PERMIT_USERS;
						
					if ($optionname=='permit_view_q_page')
						$narrowest=QA_PERMIT_CONFIRMED;
					elseif ( ($optionname=='permit_edit_c') || ($optionname=='permit_select_a') || ($optionname=='permit_hide_show') || ($optionname=='permit_anon_view_ips') )
						$narrowest=QA_PERMIT_MODERATORS;
					elseif ( ($optionname=='permit_post_c') || ($optionname=='permit_edit_q') || ($optionname=='permit_edit_a') || ($optionname=='permit_flag') )
						$narrowest=QA_PERMIT_EDITORS;
					elseif ( ($optionname=='permit_vote_q') || ($optionname=='permit_vote_a') )
						$narrowest=QA_PERMIT_POINTS_CONFIRMED;
					elseif ($optionname=='permit_delete_hidden')
						$narrowest=QA_PERMIT_ADMINS;
					else
						$narrowest=QA_PERMIT_EXPERTS;
					
					$permitoptions=qa_admin_permit_options($widest, $narrowest, (!QA_FINAL_EXTERNAL_USERS) && qa_opt('confirm_user_emails'));
					
					if (count($permitoptions)>1)
						qa_optionfield_make_select($optionfield, $permitoptions, $value,
							($value==QA_PERMIT_CONFIRMED) ? QA_PERMIT_USERS : min(array_keys($permitoptions)));
					else {
						$optionfield['type']='static';
						$optionfield['value']=reset($permitoptions);
					}
					break;
					
				case 'permit_post_q_points':
				case 'permit_post_a_points':
				case 'permit_post_c_points':
				case 'permit_vote_q_points':
				case 'permit_vote_a_points':
				case 'permit_flag_points':
				case 'permit_edit_q_points':
				case 'permit_edit_a_points':
				case 'permit_edit_c_points':
				case 'permit_select_a_points':
				case 'permit_hide_show_points':
				case 'permit_delete_hidden_points':
				case 'permit_anon_view_ips_points':
					unset($optionfield['label']);
					$optionfield['type']='number';
					$optionfield['prefix']=qa_lang_html('admin/users_must_have').'&nbsp;';
					$optionfield['note']=qa_lang_html('admin/points');
					break;
					
				case 'feed_for_qa':
					$feedrequest='qa';
					break;

				case 'feed_for_questions':
					$feedrequest='questions';
					break;

				case 'feed_for_hot':
					$feedrequest='hot';
					break;

				case 'feed_for_unanswered':
					$feedrequest='unanswered';
					break;

				case 'feed_for_activity':
					$feedrequest='activity';
					break;
					
				case 'feed_per_category':
					if (count($categories)) {
						$category=reset($categories);
						$categoryslug=$category['tags'];

					} else
						$categoryslug='example-category';
						
					if (qa_opt('feed_for_qa'))
						$feedrequest='qa';
					elseif (qa_opt('feed_for_questions'))
						$feedrequest='questions';
					else
						$feedrequest='activity';
					
					$feedrequest.='/'.$categoryslug;
					$feedisexample=true;
					break;
					
				case 'feed_for_tag_qs':
					$populartags=qa_db_select_with_pending(qa_db_popular_tags_selectspec(0, 1));
					
					if (count($populartags)) {
						reset($populartags);
						$feedrequest='tag/'.key($populartags);
					} else
						$feedrequest='tag/singing';
						
					$feedisexample=true;
					break;

				case 'feed_for_search':
					$feedrequest='search/why do birds sing';
					$feedisexample=true;
					break;
			}

			if (isset($feedrequest) && $value)
				$optionfield['note']='<A HREF="'.qa_path_html(qa_feed_request($feedrequest)).'">'.qa_lang_html($feedisexample ? 'admin/feed_link_example' : 'admin/feed_link').'</A>';

			$qa_content['form']['fields'][$optionname]=$optionfield;
		}
		

//	Extra items for specific pages

	switch (@$qa_request_lc_parts[1]) {
		case 'users':
			if (!QA_FINAL_EXTERNAL_USERS) {
				$userfields=qa_db_single_select(qa_db_userfields_selectspec());
	
				$listhtml='';
				
				foreach ($userfields as $userfield) {
					$listhtml.='<LI><B>'.qa_html(qa_user_userfield_label($userfield)).'</B>';
	
					$listhtml.=strtr(qa_lang_html('admin/edit_field'), array(
						'^1' => '<A HREF="'.qa_path_html('admin/userfields', array('edit' => $userfield['fieldid'])).'">',
						'^2' => '</A>',
					));
	
					$listhtml.='</LI>';
				}
				
				$listhtml.='<LI><B><A HREF="'.qa_path_html('admin/userfields').'">'.qa_lang_html('admin/add_new_field').'</A></B></LI>';
	
				$qa_content['form']['fields']['userfields']=array(
					'label' => qa_lang_html('admin/profile_fields'),
					'style' => 'tall',
					'type' => 'custom',
					'html' => strlen($listhtml) ? '<UL STYLE="margin-bottom:0;">'.$listhtml.'</UL>' : null,
				);
			}
			
			$qa_content['form']['fields'][]=array('type' => 'blank');

			$pointstitle=qa_get_points_to_titles();

			$listhtml='';
			
			foreach ($pointstitle as $points => $title) {
				$listhtml.='<LI><B>'.$title.'</B> - '.(($points==1) ? qa_lang_html_sub('main/1_point', '1', '1')
				: qa_lang_html_sub('main/x_points', qa_html(number_format($points))));

				$listhtml.=strtr(qa_lang_html('admin/edit_title'), array(
					'^1' => '<A HREF="'.qa_path_html('admin/usertitles', array('edit' => $points)).'">',
					'^2' => '</A>',
				));

				$listhtml.='</LI>';
			}

			$listhtml.='<LI><B><A HREF="'.qa_path_html('admin/usertitles').'">'.qa_lang_html('admin/add_new_title').'</A></B></LI>';

			$qa_content['form']['fields']['usertitles']=array(
				'label' => qa_lang_html('admin/user_titles'),
				'style' => 'tall',
				'type' => 'custom',
				'html' => strlen($listhtml) ? '<UL STYLE="margin-bottom:0;">'.$listhtml.'</UL>' : null,
			);
			break;
			
		case 'layout':
			$modulenames=qa_list_modules('widget');
			
			$listhtml='';
			
			foreach ($modulenames as $tryname) {
				$trywidget=qa_load_module('widget', $tryname);
				
				if (method_exists($trywidget, 'allow_template') && method_exists($trywidget, 'allow_region')) {
					$listhtml.='<LI><B>'.qa_html($tryname).'</B>';
					
					$listhtml.=strtr(qa_lang_html('admin/add_widget_link'), array(
						'^1' => '<A HREF="'.qa_path_html('admin/layoutwidgets', array('title' => $tryname)).'">',
						'^2' => '</A>',
					));
					
					if (method_exists($trywidget, 'admin_form'))
						$listhtml.=strtr(qa_lang_html('admin/widget_global_options'), array(
							'^1' => '<A HREF="'.qa_path_html('admin/plugins', null, null, null, md5('widget/'.$tryname)).'">',
							'^2' => '</A>',
						));
						
					$listhtml.='</LI>';
				}
			}
			
			if (strlen($listhtml))
				$qa_content['form']['fields']['plugins']=array(
					'label' => qa_lang_html('admin/plugin_widgets_explanation'),
					'style' => 'tall',
					'type' => 'custom',
					'html' => '<UL STYLE="margin-bottom:0;">'.$listhtml.'</UL>',
				);
			
			$widgets=qa_db_single_select(qa_db_widgets_selectspec());
			
			$listhtml='';
			
			$placeoptions=qa_admin_place_options();
			
			foreach ($widgets as $widget) {
				$listhtml.='<LI><B>'.qa_html($widget['title']).'</B> - '.
					'<A HREF="'.qa_path_html('admin/layoutwidgets', array('edit' => $widget['widgetid'])).'">'.
					@$placeoptions[$widget['place']].'</A>';
			
				$listhtml.='</LI>';
			}
			
			if (strlen($listhtml))
				$qa_content['form']['fields']['widgets']=array(
					'label' => qa_lang_html('admin/active_widgets_explanation'),
					'type' => 'custom',
					'html' => '<UL STYLE="margin-bottom:0;">'.$listhtml.'</UL>',
				);
			
			break;
		
		case 'permissions':
			$qa_content['form']['fields']['permit_block']=array(
				'type' => 'static',
				'label' => qa_lang_html('options/permit_block'),
				'value' => qa_lang_html('options/permit_moderators'),
			);
			
			if (!QA_FINAL_EXTERNAL_USERS) {
				$qa_content['form']['fields']['permit_create_experts']=array(
					'type' => 'static',
					'label' => qa_lang_html('options/permit_create_experts'),
					'value' => qa_lang_html('options/permit_moderators'),
				);
	
				$qa_content['form']['fields']['permit_see_emails']=array(
					'type' => 'static',
					'label' => qa_lang_html('options/permit_see_emails'),
					'value' => qa_lang_html('options/permit_admins'),
				);
		
				$qa_content['form']['fields']['permit_create_eds_mods']=array(
					'type' => 'static',
					'label' => qa_lang_html('options/permit_create_eds_mods'),
					'value' => qa_lang_html('options/permit_admins'),
				);
		
				$qa_content['form']['fields']['permit_create_admins']=array(
					'type' => 'static',
					'label' => qa_lang_html('options/permit_create_admins'),
					'value' => qa_lang_html('options/permit_supers'),
				);
	
			}
			break;
	}
	

	if (isset($checkboxtodisplay))
		qa_set_display_rules($qa_content, $checkboxtodisplay);


	$qa_content['navigation']['sub']=qa_admin_sub_navigation();
	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/