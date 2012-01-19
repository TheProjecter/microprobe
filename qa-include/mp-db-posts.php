<?php

	function mp_get_count_posts_by_userid_category_posttype($userid, $categoryid, $posttype)
	/*
	 * Return the list of questions that userid has posted on $categoryid
	 */
	 {
		return qa_db_read_one_value(qa_db_query_sub('SELECT count(postid) FROM ^posts where userid = # AND categoryid = # AND type = $',
					$userid, $categoryid, $posttype), true);
	 }
	 
	 
	 function mp_get_days_since_last_post($userid, $categoryid, $posttype)
	 /*
	  * Return the days since the a user last posted a post of type $posttype in category $categoryid
	  */
	  {
		return qa_db_read_one_value(
					qa_db_query_sub('SELECT DATEDIFF(NOW(), MAX(created)) DAYS_SINCE_LAST_POST FROM ^posts where userid = # AND categoryid = # AND type = $ GROUP BY userid',
					$userid, $categoryid, $posttype), true);
	  }