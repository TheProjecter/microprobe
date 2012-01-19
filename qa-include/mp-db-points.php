<?php
/*
   This script handles db updates for points
   
   */
   
   function mp_get_activity_points_by_userid($userid, $categoryid ) 
   /*
	* Return the activity points for a given userid in categoryid
	*/
	{
		return qa_db_read_one_value(
								qa_db_query_sub(
									'SELECT COALESCE(SUM(U.points), 0) points FROM `mp_userpoints` U RIGHT JOIN mp_user_category_map M ON U.userid = M.userid AND U.categoryid = M.categoryid, qa_users QU 
									WHERE M.userid = QU.userid AND M.categoryid = # AND M.userid = # 
									GROUP BY M.userid
									ORDER BY points DESC, QU.handle ASC', 
									$categoryid, $userid ), true );	
	
	}
	
	function mp_get_participation_points_by_userid($userid, $categoryid ) 
	/*
	 * Returns the participation points for userid $userid in category $categoryid
	 */
	{
	
		// retrieve raw points data
		$Qpoints = qa_db_read_one_assoc(
							qa_db_query_sub(
								'SELECT 500 * COUNT(P.postid) points, 100 * COALESCE(SUM(P.upvotes),0) upvotes, 100 * COALESCE(SUM(P.downvotes),0) downvotes
								 FROM ^posts P
								 WHERE type = "Q" and categoryid = # and userid = #', 
								 $categoryid, $userid) );
		$Apoints = qa_db_read_one_assoc(
							qa_db_query_sub(
								'SELECT 500 * COUNT(P.postid) points, 100 * COALESCE(SUM(P.upvotes),0) upvotes, 100 * COALESCE(SUM(P.downvotes),0) downvotes
								 FROM ^posts P
								 WHERE type = "A" AND categoryid = # AND userid = #', 
								 $categoryid, $userid) );		
									 
		return  $Qpoints['points'] + $Apoints['points'] + $Qpoints['upvotes'] - $Qpoints['downvotes'] + $Apoints['upvotes'] - $Apoints['downvotes'];
	}
	
	function mp_get_contribution_points_by_userid($userid, $categoryid)
	/*
	 * Returns the contribution points for userid $userid in category $categoryid
	 */
	{
		// retrieve raw points data
		$Qpoints = qa_db_read_one_assoc(
							qa_db_query_sub(
								'SELECT 500 * COUNT(P.postid) points, 100 * COALESCE(SUM(P.upvotes),0) upvotes, 100 * COALESCE(SUM(P.downvotes),0) downvotes
								 FROM ^posts P
								 WHERE type = "Q" and categoryid = # and userid = #', 
								 $categoryid, $userid) );
		$Apoints = qa_db_read_one_assoc(
							qa_db_query_sub(
								'SELECT 500 * COUNT(P.postid) points, 100 * COALESCE(SUM(P.upvotes),0) upvotes, 100 * COALESCE(SUM(P.downvotes),0) downvotes
								 FROM ^posts P
								 WHERE type = "A" AND categoryid = # AND userid = #', 
								 $categoryid, $userid) );	
									 
		return $Apoints['points'] + $Apoints['upvotes'] - $Apoints['downvotes'];
	}
	
	function mp_get_total_points_by_userid($userid, $categoryid)
	/*
	 * Returns the total points for user $userid in category $categoryid
	 */
	{
		return mp_get_activity_points_by_userid($userid, $categoryid) + mp_get_participation_points_by_userid($userid, $categoryid ) + mp_get_contribution_points_by_userid($userid, $categoryid);
	}