<?php
	/**
	 * Author : QASource
	 * Defect ID : 5751
	 * Filters and temp table code for first level folders
	 * The hierarchy() function used in below query can be found on /db_functions/hierarchy_final_test.sql
	 **/
	error_reporting(0);
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
	$whereClauseTop	=	"";
	$joinClause		=	"";
	$wherePassFail	=	"";
	$group_by		=	"";
	$whereNotRun	=	"";
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
		
		if($full_access == 1){
			if(!empty($test_case_id)){
				$whereClause	=	" AND NHTC.id = ".$test_case_id;
			}
			if(!empty($filter_assigned_user)){
				$joinClause		=	" JOIN user_assignments UA on TPTCV.id = UA.feature_id ";
				$whereClause	.=	" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'];
			}
			if(!empty($filter_testcase_name)){
				$whereClause	.=	" AND NHTC.name like '%".$filter_testcase_name."%' ";
			}
			if(!empty($filter_toplevel_testsuite)){
				/* $whereClause	.=	" AND NHTC.id = ".$filter_toplevel_testsuite; */
				$whereClauseTop	.=	" AND NHTS.id =	".$filter_toplevel_testsuite;
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
						$key			=	implode(",",$filter_keywords);
						$joinClause		.=	" JOIN testcase_keywords TK ON NHTC.id = TK.testcase_id ";
						$group_by		.=	" GROUP BY NHTC.id ";
						if($filter_keywords_filter_type == 'Or'){
							$whereClause	.=	" AND TK.keyword_id IN (".$key.") ";
						}else{
							foreach($filter_keywords as $key => $value){
								$whereClause	.=	" AND TK.keyword_id = ".$key." ";
							}
						}
					}
				}
			}
			if(!empty($filter_priority) ||  !empty($filter_execution_type)){
				$joinClause		.=	" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	";
				if(!empty($filter_priority)){
					$whereClause	.=	" AND TV.importance = ".$filter_priority." ";
				}
				if(!empty($filter_execution_type)){
					$whereClause	.=	" AND TV.execution_type = ".$filter_execution_type." ";
				}
			}
			if(!empty($filter_result_result) AND $filter_result_result != 'a'){
				if($filter_result_result == 'n'){
					$whereNotRun	=	" WHERE status is null ";
				}else{
					$joinClause		.=	" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ";
					$whereClause	.=	" AND EXC.status = '".$filter_result_result."' ";
					$whereClause	.=	" AND EXC.testplan_id = '".$tplan_id."' ";
					$whereClause	.=	" AND EXC.build_id = '".$_SESSION['t_build_id']."' ";
					$wherePassFail	=	" AND status = '".$filter_result_result."' ";
					$whereNotRun	=	" WHERE status ='".$filter_result_result."' ";
				}
			}
		}else{
			$joinClause		=	" JOIN user_assignments UA on TPTCV.id = UA.feature_id ";
			$whereClause	=	" AND UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$_SESSION['t_build_id'];
			
			if(!empty($filter_assigned_user)){
				$whereClause	=	" AND UA.user_id=".$filter_assigned_user." AND UA.build_id=".$_SESSION['t_build_id'];
			}
			if(!empty($test_case_id)){
				$whereClause	=	" AND NHTC.id = ".$test_case_id;
			}
			if(!empty($filter_testcase_name)){
				$whereClause	.=	" AND NHTC.name like '%".$filter_testcase_name."%' ";
			}
			if(!empty($filter_toplevel_testsuite)){
				/* $whereClause	.=	" AND NHTC.id = ".$filter_toplevel_testsuite; */
				$whereClauseTop	.=	" AND NHTS.id =	".$filter_toplevel_testsuite;
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
						$key			=	implode(",",$filter_keywords);
						$joinClause		.=	" JOIN testcase_keywords TK ON NHTC.id = TK.testcase_id ";
						$group_by		.=	" GROUP BY NHTC.id ";
						
						if($filter_keywords_filter_type == 'Or'){
							$whereClause	.=	" AND (TK.keyword_id IN (".$key.") )";
						}else{
							foreach($filter_keywords as $key => $value){
								$whereClause	.=	" AND TK.keyword_id = ".$key." ";
							}
						}
					}
				}
			}
			if(!empty($filter_priority) ||  !empty($filter_execution_type)){
				$joinClause		.=	" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	";
				if(!empty($filter_priority)){
					$whereClause	.=	" AND TV.importance = ".$filter_priority." ";
				}
				if(!empty($filter_execution_type)){
					$whereClause	.=	" AND TV.execution_type = ".$filter_execution_type." ";
				}
			}
			if(!empty($filter_result_result) AND $filter_result_result != 'a'){
				if($filter_result_result == 'n'){
					$whereNotRun	=	" WHERE status is null ";
				}else{
					$joinClause		.=	" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ";
					$whereClause	.=	" AND EXC.status = '".$filter_result_result."' ";
					$whereClause	.=	" AND EXC.testplan_id = '".$tplan_id."' ";
					$whereClause	.=	" AND EXC.build_id = '".$_SESSION['t_build_id']."' ";
					$wherePassFail	=	" AND status = '".$filter_result_result."' ";
					$whereNotRun	=	" WHERE status ='".$filter_result_result."' ";
				}
			}
		}
	}else{
		$wherClause	=	"";
		$joinClause	=	"";
		if($full_access == 1){
			
		}else{
			$joinClause		=	" JOIN user_assignments UA on TPTCV.id = UA.feature_id ";
			$whereClause	=	" AND UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$_SESSION['t_build_id'];
		}
	}
		$session_id		=	session_id();
		$tt_sql			=	"CREATE TEMPORARY TABLE IF NOT EXISTS stored_result (parent_id bigint unsigned, id bigint unsigned,node_type_id bigint, status varchar(10),INDEX indx_1(parent_id)) engine = InnoDB";
		$this->db->createTempTable($tt_sql);
		$tt_query	=	"INSERT INTO stored_result (SELECT parent_id, id, node_type_id,status from
														(
															SELECT NHTC.parent_id, NHTC.id, NHTC.node_type_id, (select status from executions where id = (select max(id) from executions where testplan_id = ".$tplan_id." and tcversion_id=TPTCV.tcversion_id and build_id = ".$_SESSION['t_build_id'].$wherePassFail."  )) status ".
															" FROM {$this->tables['nodes_hierarchy']} NHTC " .
															" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
															" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
															 $joinClause .
															" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
															" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
															 $whereClause .$group_by.
														 ") a "
														 . $whereNotRun .
														 "
													) ";
		$this->db->insertInTempTable($tt_query);
	
		$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy(NHTS.id,".$tplan_id.") as count
				FROM nodes_hierarchy NHTS
				WHERE NHTS.node_type_id =2 "
				. $whereClauseTop .
				" AND NHTS.parent_id =".$target.") a  where count>0";				
		$staticSql[1] =	
			" SELECT NHTC.node_order AS spec_order, " .
			"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
			"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
			" FROM {$this->tables['nodes_hierarchy']} NHTC " .
			" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
			" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
			$joinClause . 
			" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
			" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
			" AND NHTC.parent_id = ". $target.
			$whereClause.$group_by
			;
?>