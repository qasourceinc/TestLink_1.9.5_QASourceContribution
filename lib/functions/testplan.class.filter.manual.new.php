<?php
	if(! function_exists('pr')){
		function pr($string){
			echo "<pre>";
				print_r($string);
			echo "</pre>";
		}
	}
	// unsetting the session data if test execution is clicked	
	unset($_SESSION['test_case_id']);
	unset($_SESSION['filter_testcase_name']);
	unset($_SESSION['filter_toplevel_testsuite']);
	unset($_SESSION['filter_keywords_filter_type']);
	unset($_SESSION['filter_priority']);
	unset($_SESSION['filter_execution_type']);
	unset($_SESSION['filter_assigned_user']);
	unset($_SESSION['custom_field_6_2']);
	unset($_SESSION['custom_field_1_1']);
	unset($_SESSION['filter_result_result']);
	unset($_SESSION['filter_result_build']);
	unset($_SESSION['filter_keywords']);
	unset($_SESSION['isPostback']);
	// close
	
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
		
		/* $t_userID								=	$_SESSION['userID']; */
		$_SESSION['test_case_id']					=	$test_case_id;
		$_SESSION['filter_testcase_name']			=	$filter_testcase_name;
		$_SESSION['filter_toplevel_testsuite']		=	$filter_toplevel_testsuite;
		$_SESSION['filter_keywords_filter_type']	=	$filter_keywords_filter_type;
		$_SESSION['filter_priority']				=	$filter_priority;
		$_SESSION['filter_execution_type']			=	$filter_execution_type;
		$_SESSION['filter_assigned_user']			=	$filter_assigned_user;
		$_SESSION['custom_field_6_2']				=	$custom_field_6_2;
		$_SESSION['custom_field_1_1']				=	$custom_field_1_1;
		$_SESSION['filter_result_result']			=	$filter_result_result;
		$_SESSION['filter_result_build']			=	$filter_result_build;
		$_SESSION['filter_keywords']				=	$filter_keywords;
		$_SESSION['isPostback']						=	1;
		
		
		// admin case
		if($full_access == 1){
			// case 1(Running fine)
			// When ONLY valid test case id is searched 
			if( $test_case_id != 0 && empty($filter_assigned_user) && empty($filter_testcase_name) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a') )
			{
				// echo "1";
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
			// case 2(Running fine)
			// When search is ONLY according to the user assigened test cases
			elseif(!empty($filter_assigned_user) && empty($test_case_id) && empty($filter_testcase_name) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a') )
			{
				// echo "2";
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
			// case 3(Running fine)
			// When search is ONLY according to full test case name
			elseif(!empty($filter_testcase_name) && empty($filter_assigned_user) && empty($test_case_id) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a')){
				// echo "3";
				// this case is not workin yet
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
			// case 4(Running fine)
			// When search is ONLY according test suits
			elseif(!empty($filter_toplevel_testsuite) && empty($filter_testcase_name) && empty($filter_assigned_user) && empty($test_case_id) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a') )
			{
				// echo "4";
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_testsuit(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].") as count
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
			// case 5(Not Running fine)
			// When search is ONLY through key words. System getting hanged
			elseif(!empty($filter_keywords)){
				// echo "5";				
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
			// case 6(Running fine)
			// When search is ONLY according to filter priority
			elseif(!empty($filter_priority) && empty($test_case_id) && empty($filter_assigned_user) && empty($filter_testcase_name) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a'))
			{
				// echo "6";
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
			// case 7(Running fine)
			// When search is ONLY according to filter execution type
			elseif(!empty($filter_execution_type) && empty($test_case_id) && empty($filter_assigned_user) && empty($filter_testcase_name) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && (
				empty($filter_result_result) OR $filter_result_result == 'a'))
			{
				// echo "7";
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
			// case 8(Running fine)
			// When search is ONLY according to filter execution type
			elseif(!empty($filter_result_result) AND $filter_result_result != 'a' AND 
				empty($test_case_id) && empty($filter_assigned_user) && empty($filter_testcase_name) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type)	)
			{
				// running fine
				// echo "8";
				$whereClauseExe	.=	" AND status = '".$filter_result_result."' ";
				
					if($filter_result_result == 'n'){ 
						// echo "9";
						// returning wrong result. Not working fine. 
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
						// echo "10";
						// running fine giving result
						$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_search_filter_pass_fail(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_result_result."') as count
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
						" AND EXC.testplan_id='".$tplan_id."'".
						" AND EXC.build_id='".$_SESSION['t_build_id']."'".
						" GROUP BY TPTCV.tcversion_id "
						;
					}
			}
			// case 9 workiing fine
			// When search according to
			// YES valid test case id
			// YES user id
			// NO test case
			// NO test suit
			// NO filter priority
			// NO filter_execution type
			// No filter_result_result
			elseif( $test_case_id != 0 && !empty($filter_assigned_user) && empty($filter_testcase_name) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_case_id_user_id(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$test_case_id.",".$filter_assigned_user.") as count
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
						" AND NHTC.id = ".$test_case_id.
						" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id']
						;
			}
			// case 10 running fine
			// When search according to
			// YES valid test case id
			// YES user id
			// YES test case
			// NO test suit
			// NO filter priority
			// NO filter_execution type
			// No filter_result_result
			elseif( $test_case_id != 0 && !empty($filter_assigned_user) && !empty($filter_testcase_name) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_case_id_user_id_case_name(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$test_case_id.",".$filter_assigned_user.",'".$filter_testcase_name."') as count
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
						" AND NHTC.id = ".$test_case_id.
						" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'].
						" AND NHTC.name like '%".$filter_testcase_name."%' "
						;
			}
			// case 11 
			// When search according to
			// YES valid test case id
			// YES user id
			// YES test case
			// YES test suit
			// NO filter priority
			// NO filter_execution type
			// No filter_result_result
			elseif( $test_case_id != 0 && !empty($filter_assigned_user) && !empty($filter_testcase_name) && !empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_case_id_user_id_case_name_suit_id(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$test_case_id.",".$filter_assigned_user.",'".$filter_testcase_name."') as count
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
						" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND NHTC.id = ".$test_case_id.
						" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'].
						" AND NHTC.name like '%".$filter_testcase_name."%' ".
						" AND NHTC.id = ".$filter_toplevel_testsuite
						;
			}
			// case 12
			// When search according to
			// YES valid test case id
			// YES user id
			// YES test case
			// YES test suit
			// YES filter priority
			// NO filter_execution type
			// No filter_result_result
			elseif( $test_case_id != 0 && !empty($filter_assigned_user) && !empty($filter_testcase_name) && !empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && !empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_case_id_user_id_case_name_suit_id_filter_priority(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$test_case_id.",".$filter_assigned_user.",'".$filter_testcase_name."',".$filter_priority.") as count
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
						" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
						" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND NHTC.id = ".$test_case_id.
						" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'].
						" AND NHTC.name like '%".$filter_testcase_name."%' ".
						" AND NHTC.id = ".$filter_toplevel_testsuite.
						" AND TV.importance = ".$filter_priority
						;
			}
			// case 13
			// When search according to
			// YES valid test case id
			// YES user id
			// YES test case
			// YES test suit
			// YES filter priority
			// YES filter_execution type
			// No filter_result_result
			
			elseif( $test_case_id != 0 && !empty($filter_assigned_user) && !empty($filter_testcase_name) && !empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && !empty($filter_priority) && !empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_case_id_user_id_case_name_suit_id_filter_priority_exe_tp(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$test_case_id.",".$filter_assigned_user.",'".$filter_testcase_name."',".$filter_priority.",".$filter_execution_type.") as count
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
						" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
						" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND NHTC.id = ".$test_case_id.
						" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'].
						" AND NHTC.name like '%".$filter_testcase_name."%' ".
						" AND NHTC.id = ".$filter_toplevel_testsuite.
						" AND TV.importance = ".$filter_priority.
						" AND TV.execution_type = ".$filter_execution_type
						;
			}
			// case 14 (whole filter crieteria other than keywords)
			// When search according to
			// YES valid test case id
			// YES user id
			// YES test case
			// YES test suit
			// YES filter priority
			// YES filter_execution type
			// YES filter_result_result
			elseif( $test_case_id != 0 && !empty($filter_assigned_user) && !empty($filter_testcase_name) && !empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && !empty($filter_priority) && !empty($filter_execution_type) &&	(
				!empty($filter_result_result) OR $filter_result_result == 'p' OR $filter_result_result == 'f' OR $filter_result_result == 'b') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_case_id_user_id_case_name_suit_id_fltr_prty_exe_tp_p_f(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$test_case_id.",".$filter_assigned_user.",'".$filter_testcase_name."',".$filter_priority.",".$filter_execution_type.",'".$filter_result_result."') as count
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
						" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
						" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
						" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND NHTC.id = ".$test_case_id.
						" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'].
						" AND NHTC.name like '%".$filter_testcase_name."%' ".
						" AND NHTC.id = ".$filter_toplevel_testsuite.
						" AND TV.importance = ".$filter_priority.
						" AND TV.execution_type = ".$filter_execution_type.
						" AND EXC.status='".$filter_result_result."'".
						" AND EXC.testplan_id='".$tplan_id."'".
						" AND EXC.build_id='".$_SESSION['t_build_id']."'".
						" GROUP BY TPTCV.tcversion_id "
						;
			}
			// case 15
			// When search according to
			// NO valid test case id
			// YES user id, YES test case name, YES test suit, YES filter priority, YES filter_execution type, YES filter_result_result,  
			elseif( !empty($filter_assigned_user) && !empty($filter_testcase_name) && !empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && !empty($filter_priority) && !empty($filter_execution_type) &&	(
				!empty($filter_result_result) OR $filter_result_result == 'p' OR $filter_result_result == 'f' OR $filter_result_result == 'b') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_user_id_case_name_suit_id_fltr_prty_exe_tp_p_f(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$filter_assigned_user.",'".$filter_testcase_name."',".$filter_priority.",".$filter_execution_type.",'".$filter_result_result."') as count
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
						" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
						" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
						" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						/* " AND NHTC.id = ".$test_case_id. */
						" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'].
						" AND NHTC.name like '%".$filter_testcase_name."%' ".
						" AND NHTC.id = ".$filter_toplevel_testsuite.
						" AND TV.importance = ".$filter_priority.
						" AND TV.execution_type = ".$filter_execution_type.
						" AND EXC.status='".$filter_result_result."'".
						" AND EXC.testplan_id='".$tplan_id."'".
						" AND EXC.build_id='".$_SESSION['t_build_id']."'".
						" GROUP BY TPTCV.tcversion_id "
						;
			}
			// case 17
			// When search according to
			// NO valid test case id
			// NO user id
			// YES test case name, YES test suit, YES filter priority, YES filter_execution type, YES filter_result_result,  
			elseif( /* $test_case_id != 0 && !empty($filter_assigned_user) && */ !empty($filter_testcase_name) && !empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && !empty($filter_priority) && !empty($filter_execution_type) &&	(
				!empty($filter_result_result) OR $filter_result_result == 'p' OR $filter_result_result == 'f' OR $filter_result_result == 'b') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_case_name_suit_id_fltr_prty_exe_tp_p_f(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_testcase_name."',".$filter_priority.",".$filter_execution_type.",'".$filter_result_result."') as count
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
						" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
						" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND NHTC.name like '%".$filter_testcase_name."%' ".
						" AND NHTC.id = ".$filter_toplevel_testsuite.
						" AND TV.importance = ".$filter_priority.
						" AND TV.execution_type = ".$filter_execution_type.
						" AND EXC.status='".$filter_result_result."'".
						" AND EXC.testplan_id='".$tplan_id."'".
						" AND EXC.build_id='".$_SESSION['t_build_id']."'".
						" GROUP BY TPTCV.tcversion_id "
						;
			}
			// case 18
			// When search according to
			// NO valid test case id
			// NO user id
			// NO test case name
			// YES test suit, YES filter priority, YES filter_execution type, YES filter_result_result,  
			elseif( /* $test_case_id != 0 && !empty($filter_assigned_user) &&  !empty($filter_testcase_name) &&*/ !empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && !empty($filter_priority) && !empty($filter_execution_type) &&	(
				!empty($filter_result_result) OR $filter_result_result == 'p' OR $filter_result_result == 'f' OR $filter_result_result == 'b') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_suit_id_fltr_prty_exe_tp_p_f(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$filter_priority.",".$filter_execution_type.",'".$filter_result_result."') as count
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
						" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
						" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND NHTC.id = ".$filter_toplevel_testsuite.
						" AND TV.importance = ".$filter_priority.
						" AND TV.execution_type = ".$filter_execution_type.
						" AND EXC.status='".$filter_result_result."'".
						" AND EXC.testplan_id='".$tplan_id."'".
						" AND EXC.build_id='".$_SESSION['t_build_id']."'".
						" GROUP BY TPTCV.tcversion_id "
						;
			}
			// case 19
			// When search according to
			// NO valid test case id
			// NO user id
			// NO test case name
			// NO test suit
			// YES filter priority, YES filter_execution type, YES filter_result_result,  
			elseif( /* $test_case_id != 0 && !empty($filter_assigned_user) &&  !empty($filter_testcase_name) && !empty($filter_toplevel_testsuite) &&*/
				empty($filter_keywords) && !empty($filter_priority) && !empty($filter_execution_type) &&	(
				!empty($filter_result_result) OR $filter_result_result == 'p' OR $filter_result_result == 'f' OR $filter_result_result == 'b') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_fltr_prty_exe_tp_p_f(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$filter_priority.",".$filter_execution_type.",'".$filter_result_result."') as count
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
						" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND TV.importance = ".$filter_priority.
						" AND TV.execution_type = ".$filter_execution_type.
						" AND EXC.status='".$filter_result_result."'".
						" AND EXC.testplan_id='".$tplan_id."'".
						" AND EXC.build_id='".$_SESSION['t_build_id']."'".
						" GROUP BY TPTCV.tcversion_id "
						;
			}
			// case 20
			// When search according to
			// NO valid test case id
			// NO user id
			// NO test case name
			// NO test suit
			// NO filter priority
			// YES filter_execution type, YES filter_result_result,  
			elseif( /* $test_case_id != 0 && !empty($filter_assigned_user) &&  !empty($filter_testcase_name) && !empty($filter_toplevel_testsuite) && !empty($filter_priority) */
				empty($filter_keywords)  && !empty($filter_execution_type) &&	(
				!empty($filter_result_result) OR $filter_result_result == 'p' OR $filter_result_result == 'f' OR $filter_result_result == 'b') )
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_exe_tp_p_f(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$filter_execution_type.",'".$filter_result_result."') as count
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
						" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
						" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND TV.execution_type = ".$filter_execution_type.
						" AND EXC.status='".$filter_result_result."'".
						" AND EXC.testplan_id='".$tplan_id."'".
						" AND EXC.build_id='".$_SESSION['t_build_id']."'".
						" GROUP BY TPTCV.tcversion_id "
						;
			}
			// case 21
			// When search according to
			// NO valid test case id
			// NO user id
			// NO test case name
			// NO test suit
			// NO filter priority
			// NO filter_execution type
			// YES filter_result_result,  
			// this case is already written as case 8
			
			// case 22
			// YES valid test case id
			// YES test case name
			// NO user id
			// NO test suit
			// NO filter priority
			// NO filter_execution type
			// NO filter_result_result,  
			elseif($test_case_id != 0 && !empty($filter_testcase_name) && empty($filter_assigned_user) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a'))
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_test_id_testcase_name(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_testcase_name."',".$test_case_id.") as count
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
					" AND NHTC.name like '%".$filter_testcase_name."%' ".
					" AND NHTC.id = ".$test_case_id
					;
			}
			// case 23
			// YES valid test case id
			// YES test case name
			// YES test suit
			
			// NO user id
			// NO filter priority
			// NO filter_execution type
			// NO filter_result_result,
			elseif($test_case_id != 0 && !empty($filter_testcase_name) && !empty($filter_assigned_user) && empty($filter_toplevel_testsuite) &&
				empty($filter_keywords) && empty($filter_priority) && empty($filter_execution_type) &&	(
				empty($filter_result_result) OR $filter_result_result == 'a'))
			{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,h_admin_test_id_testcase_name_user_id(NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",'".$filter_testcase_name."',".$test_case_id.",".$filter_assigned_user.") as count
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
					" AND NHTC.name like '%".$filter_testcase_name."%' ".
					" AND NHTC.id = ".$test_case_id.
					" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id']
					;
			}
			
			
			
			
			else{
				// echo "11";
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
			// echo "12";
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