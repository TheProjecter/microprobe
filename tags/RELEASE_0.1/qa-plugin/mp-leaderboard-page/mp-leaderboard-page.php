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
	
	class mp_leaderboard_page {
		
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
					'title' => 'Leaderboard',
					'request' => 'mp-leaderboard-page',
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		function match_request($request)
		{
			if ($request=='mp-leaderboard-page')
				return true;

			return false;
		}
		
		function process_request($request)
		{
			// if the user is not logged in, request user to login
			if (!qa_get_logged_in_userid()) {
				$qa_content=qa_content_prepare();
				$qa_content['error']=qa_insert_login_links('Please ^1log in^2 or ^3register^4 to view leaderboard.', $request);
				
				return $qa_content;
			}
		
			$qa_content=qa_content_prepare();

			$qa_content['title']='Leaderboards';
			
			// retrieve raw points data
			$Qpoints = qa_db_read_all_assoc(
								qa_db_query_sub(
									'SELECT U2.handle, 500 * COUNT(P.postid) points, 100 * SUM(P.upvotes) upvotes, 100 * SUM(P.downvotes) downvotes
									 FROM (SELECT * FROM ^posts WHERE type = "Q" and categoryid = #) P RIGHT JOIN mp_user_category_map U ON P.userid = U.userid, ^users U2
									 WHERE U.userid = U2.userid AND U.categoryid = #
									 GROUP BY U.userid 
									 ORDER BY points DESC', mp_get_categoryid(), mp_get_categoryid()), 'handle' );
			$Apoints = qa_db_read_all_assoc(
								qa_db_query_sub(
									'SELECT U2.handle, 500 * COUNT(P.postid) points, 100* SUM(P.upvotes) upvotes, 100 * SUM(P.downvotes) downvotes
									 FROM (SELECT * FROM ^posts WHERE type = "A" AND categoryid = #) P RIGHT JOIN  mp_user_category_map U ON P.userid = U.userid, ^users U2
									 WHERE U.userid = U2.userid AND U.categoryid = #								 
									 GROUP BY U.userid 
									 ORDER BY points DESC', mp_get_categoryid(), mp_get_categoryid()), 'handle' );									 

			 // Leaderboard by participation								
			$participationData = array();
				
			// combine the Q and A data
			foreach( array_keys($Qpoints) as $key) {
				$participationData[$key]['handle'] = $key;
				$participationData[$key]['points'] = $Qpoints[$key]['points'] + $Apoints[$key]['points'] + $Qpoints[$key]['upvotes'] - $Qpoints[$key]['downvotes'] + $Apoints[$key]['upvotes'] - $Apoints[$key]['downvotes'];
			}
				
			// Obtain a list of columns so it can be sorted using array_multisort
			$points1 = array();
			foreach ($participationData as $key => $row) {
				$points1[$key]  = $row['points'];
			}
			array_multisort($points1, SORT_DESC, $participationData);
				
			
			// Leaderboard by contribution
			$contributionData = array();
			
			// combine the Q and A data
			foreach( array_keys($Qpoints) as $key) {
				$contributionData[$key]['handle'] = $key;
				$contributionData[$key]['points'] = $Apoints[$key]['points'] + $Apoints[$key]['upvotes'] - $Apoints[$key]['downvotes'];
			}
				
			// Obtain a list of columns so it can be sorted using array_multisort
			$points = array();
			foreach ($contributionData as $key => $row) {
				$points[$key]  = $row['points'];
			}
			array_multisort($points, SORT_DESC, $contributionData);
			

			// calculate activity points
			$activityPoints = qa_db_read_all_assoc(
								qa_db_query_sub(
									'SELECT QU.handle, COALESCE(SUM(U.points), 0) points FROM `mp_userpoints` U RIGHT JOIN mp_user_category_map M ON U.userid = M.userid AND U.categoryid = M.categoryid, qa_users QU 
									WHERE M.userid = QU.userid AND M.categoryid = # 
									GROUP BY M.userid
									ORDER BY points DESC, QU.handle ASC', 
									mp_get_categoryid()), 'handle' );	
			
			// calculate overall points
			//$allPoints = qa_db_read_all_assoc(
			//			qa_db_query_sub('SELECT U.handle, P.userid, COALESCE(SUM(POINTS),0) points FROM (SELECT * FROM mp_userpoints WHERE categoryid = # ) P RIGHT JOIN ^users U ON P.userid = U.userid  GROUP BY P.userid ORDER BY points DESC', 
			//							mp_get_categoryid()));
			$allPoints = array();

			foreach (array_keys($Qpoints) as $key) {
				$allPoints[$key] = $participationData[$key]['points'] + $contributionData[$key]['points'] + $activityPoints[$key]['points'];
			}
			asort($allPoints, SORT_NUMERIC);
			$allPoints = array_reverse($allPoints, true);
			
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////
			// begin data display
			//////////////////////////////////////////////////////////////////////////////////////////////////////
			
			$data = '<table><tr><td>';
			
			$data .= '<div style="background-color:#EEEEFF;visibility: visible; border:1px solid #4488FF; width:160px; overflow:auto; "> 
										<div style="background-color:#4488FF; font-family:Arial; font-size:14px; text-align:center; color:white; padding:3px;">Total Points</div> 
										<div style="font-family:Arial; font-size:14px; text-align:center; color:black; padding:3px;">';
									
			$data .= '<table width="100%">';
			foreach ( $allPoints as $handle => $points) {
				$data .= '<tr><td align="left">'.$handle.'</td><td align="right">'.$points.'</td></tr>';
			}
			
			$data .='</table></div></div><br />';

			$data .= '</td><td>&nbsp</td><td>';
			
			$data .= '<div style="background-color:#EEEEFF;visibility: visible; border:1px solid #4488FF; width:160px; overflow:auto; "> 
										<div style="background-color:#4488FF; font-family:Arial; font-size:14px; text-align:center; color:white; padding:3px;">Participation</div> 
										<div style="font-family:Arial; font-size:14px; text-align:center; color:black; padding:3px;">';
										
			$data .= '<table width="100%">';
			foreach ( $participationData as $row) {
				$data .= '<tr><td align="left">'.$row['handle'].'</td><td align="right">'.$row['points'].'</td></tr>';
			}
				
			$data .='</table></div></div><br /> ';

			$data .= '</td><td>&nbsp</td><td>';

			$data .= '<div style="background-color:#EEEEFF;visibility: visible; border:1px solid #4488FF; width:160px; overflow:auto; "> 
										<div style="background-color:#4488FF; font-family:Arial; font-size:14px; text-align:center; color:white; padding:3px;">Knowledge Contribution</div> 
										<div style="font-family:Arial; font-size:14px; text-align:center; color:black; padding:3px;">';
										
			$data .= '<table width="100%">';
			foreach ( $contributionData as $row) {
				$data .= '<tr><td align="left">'.$row['handle'].'</td><td align="right">'.$row['points'].'</td></tr>';
			}
				
			$data .='</table></div></div><br /> ';

			$data .= '</td><td>&nbsp</td><td>';

			$data .= '<div style="background-color:#EEEEFF;visibility: visible; border:1px solid #4488FF; width:160px; overflow:auto; "> 
										<div style="background-color:#4488FF; font-family:Arial; font-size:14px; text-align:center; color:white; padding:3px;">Usage Activity</div> 
										<div style="font-family:Arial; font-size:14px; text-align:center; color:black; padding:3px;">';
										
			$data .= '<table width="100%">';
			foreach ( $activityPoints as $row) {
				$data .= '<tr><td align="left">'.$row['handle'].'</td><td align="right">'.$row['points'].'</td></tr>';
			}
				
			$data .='</table></div></div><br /> ';

			$data .= '</td></tr></table>';
			
			$qa_content['custom_1'] = $data;	
			
			return $qa_content;
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/