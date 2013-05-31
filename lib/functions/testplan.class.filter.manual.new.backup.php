<?php
	if(! function_exists('pr')){
		function pr($string){
			echo "<pre>";
				print_r($string);
			echo "</pre>";
		}
	}
	/* pr($_POST); */
	$whereClause	=	"";
	$whereClauseExe	=	"";
	$joinClause		=	"";
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
		$filter_keywords_filter_type			=	$_POST['filter_keywords_filter_type'];
		$filter_priority						=	$_POST['filter_priority'];
		$filter_execution_type					=	$_POST['filter_execution_type'];
		$filter_assigned_user					=	$_POST['filter_assigned_user'];
		$custom_field_6_2						=	$_POST['custom_field_6_2'];
		$custom_field_1_1						=	$_POST['custom_field_1_1'];
		$filter_result_result					=	$_POST['filter_result_result'];
		$filter_result_build					=	$_POST['filter_result_build'];
		$filter_keywords						=	$_POST['filter_keywords'];
		
		$t_userID								=	$_SESSION['userID'];
		
		// admin case
		if($full_access == 1){
			if($test_case_id != 0){
				echo "1";
				// running fine
				/* $whereClause	=	" AND NHTC.id = ".$test_case_id;
				$whereClauseExe	.=	" AND tcversion_id = ".$test_case_id; */
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_test_case_id(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$test_case_id.") as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";				
				$staticSql[1] =	
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
			elseif(!empty($filter_assigned_user)){
				echo "2";
				// running fine
				/* $joinClause		=	" JOIN user_assignments UA on TPTCV.id = UA.feature_id ";
				$whereClause	.=	" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'];
				$whereClauseExe	.=	" AND tester_id = ".$filter_assigned_user; */
				
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_user(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$filter_assigned_user.") as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";				
				$staticSql[1] =	
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
					" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'];
			}
			elseif(!empty($filter_testcase_name)){
				echo "3";
				// this case is not workin yet
				/* $whereClause	.=	" AND NHTS.name like '%".$filter_testcase_name."%' "; */
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_testcase_name(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_testcase_name."') as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";				
				$staticSql[1] =	
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM {$this->tables['nodes_hierarchy']} NHTC " .
					" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ". $target.
					" AND NHTC.name like '%".$filter_testcase_name."%' ";
			}
			elseif(!empty($filter_toplevel_testsuite)){
				echo "4by4";
				// not working yet
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_testsuit(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$filter_toplevel_testsuite.") as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.id =	".$filter_toplevel_testsuite."
					AND NHTS.parent_id =".$target.") a  where count>0";				
				$staticSql[1] =	
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM {$this->tables['nodes_hierarchy']} NHTC " .
					" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ". $target.
					" AND NHTC.id = ".$filter_toplevel_testsuite
					;
				
				
			}
			elseif(!empty($filter_keywords)){
				// echo "5";
				/* system is getting hanged in this case */
				$keyWhere	=	"";
				$joinKey	=	"";
				if(!empty($filter_keywords_filter_type)){
					$tt_case	=	false;
					foreach($filter_keywords as $key => $value){
						if($value == 0){
							$tt_case	=	true;
							break;
						}
					}
					if(!$tt_case){
						$joinKey		=	" JOIN testcase_keywords TK ON NHTC.id = TK.testcase_id ";
						/* $whereClause	.=	$filter_keywords_filter_type." (";
						foreach($filter_keywords as $key => $value){
							$whereClause	.=	" TK.keyword_id = ".$value." ".$filter_keywords_filter_type;
						}
						$whereClause	.=	" 1=1 )"; */
						$key			=	implode(",",$filter_keywords);
						$keyWhere		=	" ".$filter_keywords_filter_type." TK.keyword_id IN (".$key.") ";
					}
				}
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_keywords(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$key."') as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";				
				$staticSql[1] =	
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM {$this->tables['nodes_hierarchy']} NHTC " .
					" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					$joinKey.
					" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ". $target.
					$keyWhere;
			}
			elseif(!empty($filter_priority) ){
				// running fine
				/* if(!empty($filter_priority)){
					$whereFilter	.=	" AND TV.importance = ".$filter_priority." ";
				} */
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_filter_priority(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_priority."') as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";				
				$staticSql[1] =	
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM {$this->tables['nodes_hierarchy']} NHTC " .
					" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
					" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ". $target.
					" AND TV.importance = ".$filter_priority;
			}
			elseif(!empty($filter_execution_type)){
				echo "6";
				// running fine
				/* $joinClause		.=	" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	";
				if(!empty($filter_execution_type)){
					$whereFilter	.=	" AND TV.execution_type = ".$filter_execution_type." ";
				} */
				
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_filter_execution_type(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_execution_type."') as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";				
				$staticSql[1] =	
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM {$this->tables['nodes_hierarchy']} NHTC " .
					" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
					" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ". $target.
					" AND TV.execution_type = ".$filter_execution_type;
				
			}
			elseif(!empty($filter_result_result) AND $filter_result_result != 'a'){
				// running fine
				echo "7";
				$whereClauseExe	.=	" AND status = '".$filter_result_result."' ";
				
					if($filter_result_result == 'n'){
						// returning wrong result
						$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_filter_not_run(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_result_result."') as count
						FROM nodes_hierarchy NHTS
						WHERE NHTS.node_type_id =2
						AND NHTS.parent_id =".$target.") a  where count>0";				
					$staticSql[1] =	
						" SELECT NHTC.node_order AS spec_order, " .
						"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
						"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
						" FROM {$this->tables['nodes_hierarchy']} NHTC " .
						" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
						" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
						/* " LEFT OUTER JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ". */
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.     //AND  tcversion_id=TPTCV.tcversion_id
						" AND NHTC.id NOT IN (SELECT  max(tcversion_id) from executions where testplan_id = ".$tplan_id."  AND build_id = ".$_SESSION['t_build_id']." AND (status = 'p' OR status = 'f' OR status = 'b') ) "
						;
					}else{
						// running fine giving result
						$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_filter_pass_fail_run(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_result_result."') as count
						FROM nodes_hierarchy NHTS
						WHERE NHTS.node_type_id =2
						AND NHTS.parent_id =".$target.") a  where count>0";				
					$staticSql[1] =	
						" SELECT NHTC.node_order AS spec_order, " .
						"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
						"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
						" FROM {$this->tables['nodes_hierarchy']} NHTC " .
						" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
						" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
						" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND EXC.status='".$filter_result_result."'".
						" AND EXC.testplan_id='".$target."'".
						" AND EXC.build_id='".$_SESSION['t_build_id']."'".
						" GROUP BY TPTCV.tcversion_id "
						;
					}
			}else{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_normal(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].") as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";				
				$staticSql[1] =	
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM {$this->tables['nodes_hierarchy']} NHTC " .
					" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ". $target;
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
			$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_normal(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].") as count
				FROM nodes_hierarchy NHTS
				WHERE NHTS.node_type_id =2
				AND NHTS.parent_id =".$target.") a  where count>0";				
			$staticSql[1] =	
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
			/* $joinClause		=	" JOIN user_assignments UA on TPTCV.id = UA.feature_id ";
			$whereClause	=	" AND UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$_SESSION['t_build_id']; */
			
			$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_user_normal(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$_SESSION['userID'].") as count
				FROM nodes_hierarchy NHTS
				WHERE NHTS.node_type_id =2
				AND NHTS.parent_id =".$target.") a  where count>0";				
			$staticSql[1] =	
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
	}
	
	
	
	
	
?>