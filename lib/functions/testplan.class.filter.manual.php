<?php
	if(! function_exists('pr')){
		function pr($string){
			echo "<pre>";
				print_r($string);
			echo "</pre>";
		}
	}
	global $db;
	$whereClause	=	"";
	$whereClauseExe	=	"";
	$joinClause		=	"";
	
	//pr($_POST); die;
	$test_case_id	=	0;
	if(isset($_POST) && !empty($_POST)){
		$test_case_id							=	-1;
		$tpn_view_status						=	$_POST['tpn_view_status'];
		$setting_testplan						=	$_POST['setting_testplan'];
		$setting_build							=	$_POST['setting_build'];
		$hidden_setting_refresh_tree_on_action	=	$_POST['hidden_setting_refresh_tree_on_action'];
		$setting_refresh_tree_on_action			=	$_POST['setting_refresh_tree_on_action'];
		$filter_tc_id							=	$_POST['filter_tc_id'];
		if(!empty($filter_tc_id)){
			$test_case_id	=	(int)trim(substr(strrchr($filter_tc_id,'-'),1));
		}
		$filter_testcase_name					=	$_POST['filter_testcase_name'];
		$filter_toplevel_testsuite				=	$_POST['filter_toplevel_testsuite'];
		$a_filter_keywords_filter_type			=	$_POST['filter_keywords_filter_type'];
		$filter_priority						=	$_POST['filter_priority'];
		$filter_execution_type					=	$_POST['filter_execution_type'];
		$filter_assigned_user					=	$_POST['filter_assigned_user'];
		$custom_field_6_2						=	$_POST['custom_field_6_2'];
		$custom_field_1_1						=	$_POST['custom_field_1_1'];
		$filter_result_result					=	$_POST['filter_result_result'];
		$filter_result_build					=	$_POST['filter_result_build'];
		
		$t_userID								=	$_SESSION['userID'];
		
		// admin case
		if($full_access == 1){
			if($test_case_id != -1){
				$whereClause	=	" AND NHTC.id = ".$test_case_id;
				$whereClauseExe	.=	" AND tcversion_id = ".$test_case_id;
			}
			if(!empty($filter_assigned_user)){
				$joinClause		=	" JOIN user_assignments UA on TPTCV.id = UA.feature_id ";
				$whereClause	.=	" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'];
				$whereClauseExe	.=	" AND tester_id = ".$filter_assigned_user;
			}
			if(!empty($filter_testcase_name)){
				$whereClause	.=	" AND NHTS.name like '%".$filter_testcase_name."%' ";
			}
			if(!empty($filter_toplevel_testsuite)){
				// $whereClause	.=	" AND NHTS.name like '".$filter_testcase_name."' "
			}
			if(!empty($filter_keywords)){
				if(!empty($filter_keywords_filter_type)){
					$tt_case	=	false;
					foreach($filter_keywords as $key => $value){
						if($value == 0){
							$tt_case	=	true;
							break;
						}
					}
					if(!$tt_case){
						$joinClause		.=	" JOIN testcase_keywords TK ON NHTC.id = TK.testcase_id ";
						$whereClause	.=	$filter_keywords_filter_type." (";
						foreach($filter_keywords as $key => $value){
							$whereClause	.=	" TK.keyword_id = ".$value." ".$filter_keywords_filter_type;
						}
						$whereClause	.=	" 1=1 )";
					}
				}
			}
			if(!empty($filter_priority) || !empty($filter_execution_type) ){
				$joinClause		.=	" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	";
				if(!empty($filter_priority)){
					$whereClause	.=	" AND TV.importance = ".$filter_priority." ";
				}
				if(!empty($filter_execution_type)){
					$whereClause	.=	" AND TV.execution_type = ".$filter_execution_type." ";
				}
			}
			if(!empty($filter_result_result)){
				$whereClauseExe	.=	" AND status = '".$filter_result_result."' ";
			}
			
			
		}else{
			// other user case
			$whereClauseExe	.=	" AND tester_id = ".$_SESSION['userID'];
			$joinClause		=	" JOIN user_assignments UA on TPTCV.id = UA.feature_id ";
			if($test_case_id != -1){
				$whereClause	.=	" AND NHTC.id = ".$test_case_id." AND UA.build_id=".$_SESSION['t_build_id'];
				$whereClauseExe	.=	" AND tcversion_id = ".$test_case_id;
			}
			if(!empty($filter_assigned_user)){
				$whereClause	.=	" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'];
			}
			if(!empty($filter_testcase_name)){
				$whereClause	.=	" AND NHTS.name like '%".$filter_testcase_name."%' ";
			}
			if(!empty($filter_toplevel_testsuite)){
				// $whereClause	.=	" AND NHTS.name like '".$filter_testcase_name."' "
			}
			if(!empty($filter_keywords)){
				if(!empty($filter_keywords_filter_type)){
					$tt_case	=	false;
					foreach($filter_keywords as $key => $value){
						if($value == 0){
							$tt_case	=	true;
							break;
						}
					}
					if(!$tt_case){
						$joinClause		.=	" JOIN testcase_keywords TK ON NHTC.id = TK.testcase_id ";
						$whereClause	.=	$filter_keywords_filter_type." (";
						foreach($filter_keywords as $key => $value){
							$whereClause	.=	" TK.keyword_id = ".$value." ".$filter_keywords_filter_type;
						}
						$whereClause	.=	" 1=1 )";
					}
				}
			}
			if(!empty($filter_priority) || !empty($filter_execution_type) ){
				$joinClause		.=	" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	";
				if(!empty($filter_priority)){
					$whereClause	.=	" AND TV.importance = ".$filter_priority." ";
				}
				if(!empty($filter_execution_type)){
					$whereClause	.=	" AND TV.execution_type = ".$filter_execution_type." ";
				}
			}
			if(!empty($filter_result_result)){
				$whereClauseExe	.=	" AND status = '".$filter_result_result."' ";
			}
		}
	}else{
		if($full_access == 1){
			// $whereClause	=	" AND UA.build_id=".$_SESSION['t_build_id'];
		}else{
			$joinClause		=	" JOIN user_assignments UA on TPTCV.id = UA.feature_id ";
			$whereClause	=	" AND UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$_SESSION['t_build_id'];
		}
	}
	// echo"CALL heirarchy_final(".$_SESSION['testprojectID'].",".$tplan_id.",".$_SESSION['t_build_id'].",'".$whereClause."','".$whereClauseExe."','".$joinClause."')";
	$t_case_count	=	"0,0,0,0,0";
	$mysqli = new mysqli($db->db->host, $db->db->user, "admin", $db->db->database);
	$rs = $mysqli->query("CALL heirarchy_final(".$_SESSION['testprojectID'].",".$tplan_id.",".$_SESSION['t_build_id'].",'".$whereClause."','".$whereClauseExe."','".$joinClause."')");
	if($rs){
		$tt_row = $rs->fetch_assoc();
		if(!empty($tt_row['v_count'])){
			$t_case_count	=	$tt_row['v_count'];
		}
	}
	
	$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,'".$t_case_count."' as count
			FROM nodes_hierarchy NHTS
			WHERE NHTS.node_type_id =2
			AND NHTS.parent_id =".$target.") a  where count>0";
			
	$staticSql[1] =	"  " .
		" SELECT NHTC.node_order AS spec_order, " .
		"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
		"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
		" FROM {$this->tables['nodes_hierarchy']} NHTC " .
		" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
		" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
			$joinClause . 
		" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
		" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
		" AND NHTC.parent_id = ". $target
		. $whereClause
		;
	
	
	/* $test_case_id	=	0;
	if(isset($_POST) && !empty($_POST)){
		// Post back will also hace two cases 1. admin    2. user(without full access)
		$test_case_id							=	-1;
		$tpn_view_status						=	$_POST['tpn_view_status'];
		$setting_testplan						=	$_POST['setting_testplan'];
		$setting_build							=	$_POST['setting_build'];
		$hidden_setting_refresh_tree_on_action	=	$_POST['hidden_setting_refresh_tree_on_action'];
		$setting_refresh_tree_on_action			=	$_POST['setting_refresh_tree_on_action'];
		$filter_tc_id							=	$_POST['filter_tc_id'];
		if(!empty($filter_tc_id)){
			$test_case_id	=	(int)trim(substr(strrchr($filter_tc_id,'-'),1));
		}
		$filter_testcase_name					=	$_POST['filter_testcase_name'];
		$filter_toplevel_testsuite				=	$_POST['filter_toplevel_testsuite'];
		$a_filter_keywords_filter_type			=	$_POST['filter_keywords_filter_type'];
		$filter_priority						=	$_POST['filter_priority'];
		$filter_execution_type					=	$_POST['filter_execution_type'];
		$filter_assigned_user					=	$_POST['filter_assigned_user'];
		$custom_field_6_2						=	$_POST['custom_field_6_2'];
		$custom_field_1_1						=	$_POST['custom_field_1_1'];
		$filter_result_result					=	$_POST['filter_result_result'];
		$filter_result_build					=	$_POST['filter_result_build'];
		
		$t_userID								=	$_SESSION['userID'];
		if($full_access == 1){
			// this is admin case with post back
			// using function hierarchy_for_admin_with_postback_testcase_id
			if($test_case_id != -1){
				// this is the case when admin is searching records with test case id
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy_for_admin_with_postback_testcase_id( NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$test_case_id.") as count
				FROM nodes_hierarchy NHTS
				WHERE NHTS.node_type_id =2
				AND NHTS.parent_id =".$target.") a  where count>0";
				
				$staticSql[1] =	"  " .
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM {$this->tables['nodes_hierarchy']} NHTC " .
					" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ". $target.
					" AND NHTC.id = ".$test_case_id;
			}
			
		}else{
			// this is user case (other than admin) with post back
			// using function hierarchy_for_user_with_postback_testcase_id
			if($test_case_id != -1){
				// this is the case when user is searching records with test case id
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy_for_user_with_postback_testcase_id( NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$t_userID.",".$test_case_id.") as count
				FROM nodes_hierarchy NHTS
				WHERE NHTS.node_type_id =2
				AND NHTS.parent_id =".$target.") a  where count>0";
				
				$staticSql[1] =	"  " .
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM {$this->tables['nodes_hierarchy']} NHTC " .
					" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
					" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ". $target.
					" AND NHTC.id = ".$test_case_id.
					" AND UA.user_id=".$t_userID." AND UA.build_id=".$_SESSION['t_build_id'];
			}
		}
	}else{
		if($full_access == 1){
			// this is admin case without post back
			// using in this case function "hierarchy_for_admin_without_postback"
			$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy_for_admin_without_postback( NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].") as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";
					
			$staticSql[1] =	"  " .
				" SELECT NHTC.node_order AS spec_order, " .
				"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
				"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
				" FROM {$this->tables['nodes_hierarchy']} NHTC " .
				" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
				" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
				" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
				" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
				" AND NHTC.parent_id = ". $target;
		}else{
			// this is user case (other than admin) without post back
			// using in this case function "hierarchy_for_user_without_postback"
			$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy_for_user_without_postback( NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$_SESSION['userID'].") as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";
					
			$staticSql[1] =	"  " .
				" SELECT NHTC.node_order AS spec_order, " .
				"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
				"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
				" FROM {$this->tables['nodes_hierarchy']} NHTC " .
				" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
				" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
				" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
				" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
				" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
				" AND NHTC.parent_id = ". $target.
				" AND UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$_SESSION['t_build_id'];
		}
	} */
?>