<?php
	
	require_once('../../config.inc.php');
	require_once('common.php');
	testlinkInitPage($db);
	$k	=	&$db;
	$tree_manager = new tree($k);
	error_reporting(0);
	if(isset($_REQUEST['parentID'])){
		$parentID		=	$_REQUEST['parentID'];
		$plan_id		=	$_REQUEST['plan_id'];
		$grandParentID	=	$_REQUEST['grandParentID'];
		$build_id		=	$_REQUEST['build_id'];
		$pnode			=	array();
		$count			=	_get_subtree_rec($plan_id,$parentID,$build_id,$pnode,$filters = null, $options = null,$db);
		$grandParent_name	=	@mysql_result(mysql_query('SELECT prefix FROM testprojects WHERE id='.$grandParentID),0);
		
		?>
		<table border='0' style='padding-left:40px;' cellspacing='0' cellpadding='0'>
		<?php
		foreach($pnode as $key => $value){
			if(!empty($value)){
				foreach($value as $k => $v){
					// echo "<pre>";print_r($value);
					$name			=	$v['name'];
					$id				=	$v['id'];
					$tcversion_id	=	$v['tcversion_id'];
					$count			=	$v['count'];
					
					
					$pass			=	$v['pass'];
					$fail			=	$v['fail'];
					$blocked		=	$v['blocked'];
					$notRun			=	$v['notRun'];
					$total			=	$v['total'];
					echo "<tr>";
					if(!empty($tcversion_id)){
						$tc_external_id		=	@mysql_result(mysql_query('SELECT tc_external_id FROM tcversions WHERE id='.$tcversion_id),0);
						?>
						<td>
							<a onclick="ST(<?php echo $id; ?>,<?php echo $tcversion_id; ?>)" href='javascript:void(0)'><img src='third_party/ext-js/images/default/tree/leaf.gif'><b><?php echo $grandParent_name;?>-<?php echo $tc_external_id; ?>:</b><?php echo $name; ?></a>
						</td>
						<?php
					}else{
						?>
						<td>
							<a onclick="ajaxRequest('<?php echo $id;?>','<?php echo $tcversion_id;?>','<?php echo $plan_id;?>','<?php echo $grandParentID;?>','<?php echo $build_id;?>')" href="javascript:void(0)">
								<img src='third_party/ext-js/images/default/tree/folder.gif'><?php echo $name;?>(<?php echo $total;?>) (<font color='green'><?php echo $pass;?></font>,<font color='red'><?php echo $fail;?></font>,<font color='blue'><?php echo $blocked;?></font>,<font color='black'><?php echo $notRun;?></font>)
							</a>
							<div id='<?php echo $id;?>' style='display:none;'></div>
						</td>
						<?php
					}
					echo "</tr>";
				}
			}
		}
		?>
		</table>
		<?php
	}
	
	
	function _get_subtree_rec($tplan_id,$node_id,$build_id,&$pnode,$filters = null, $options = null,$db)
	{
		static $qnum;
		static $my;
		static $exclude_branches;
		static $exclude_children_of;
		static $node_types;
		static $tcaseFilter;
		static $tcversionFilter;
		static $pltaformFilter;
	
		static $childFilterOn;
		static $staticSql;
		static $debugMsg;
		
		if (!$my)
		{
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;

			$qnum=0;
			$node_types 	= array_flip($tree_manager->node_types);
			$my['filters'] 	= array('exclude_children_of' => null,'exclude_branches' => null,
								   'additionalWhereClause' => '', 'testcase_name' => null,
								   'platform_id' => null,
								   'testcase_id' => null,'active_testcase' => false);
							   
			$my['options'] = array('remove_empty_nodes_of_type' => null);
	
			$my['filters'] = array_merge($my['filters'], (array)$filters);
			$my['options'] = array_merge($my['options'], (array)$options);
	
			$exclude_branches = $my['filters']['exclude_branches'];
			$exclude_children_of = $my['filters']['exclude_children_of'];	
	
	
			$tcaseFilter['name'] = !is_null($my['filters']['testcase_name']);
			$tcaseFilter['id'] = !is_null($my['filters']['testcase_id']);
			
			$tcaseFilter['is_active'] = !is_null($my['filters']['active_testcase']) && $my['filters']['active_testcase'];
			$tcaseFilter['enabled'] = $tcaseFilter['name'] || $tcaseFilter['id'] || $tcaseFilter['is_active'];
	
	
			$tcversionFilter['execution_type'] = !is_null($my['filters']['execution_type']);
			$tcversionFilter['enabled'] = $tcversionFilter['execution_type'];
	
			$childFilterOn = $tcaseFilter['enabled'] || $tcversionFilter['enabled'];
			
		
	
			if( !is_null($my['options']['remove_empty_nodes_of_type']) )
			{
				// this way I can manage code or description			
				if( !is_numeric($my['options']['remove_empty_nodes_of_type']) )
				{
					$my['options']['remove_empty_nodes_of_type'] = 
								  $tree_manager->node_descr_id[$my['options']['remove_empty_nodes_of_type']];
				}
			}
	
	
			$platformFilter = "";
			if( !is_null($my['filters']['platform_id']) && $my['filters']['platform_id'] > 0 )
			{
				$platformFilter = " AND T.platform_id = " . intval($my['filters']['platform_id']) ;
			}
			$target = intval($node_id);
			
			$role_id	=	mysql_result(mysql_query('select role_id from users where id='.$_SESSION['userID']),0);
			$full_access=	0;
			if($role_id == 8){
				$full_access	=	1;
			}
			
			// new code with filters
			
			
			
			if(isset($_SESSION['isPostback']) && $_SESSION['isPostback'] == 1){
				$test_case_id				=	$_SESSION['test_case_id'];
				$filter_testcase_name		=	$_SESSION['filter_testcase_name'];
				$filter_toplevel_testsuite	=	$_SESSION['filter_toplevel_testsuite'];
				$filter_keywords_filter_type=	$_SESSION['filter_keywords_filter_type'];
				$filter_priority			=	$_SESSION['filter_priority'];
				$filter_execution_type		=	$_SESSION['filter_execution_type'];
				$filter_assigned_user		=	$_SESSION['filter_assigned_user'];
				$custom_field_6_2			=	$_SESSION['custom_field_6_2'];
				$custom_field_1_1			=	$_SESSION['custom_field_1_1'];
				$filter_result_result		=	$_SESSION['filter_result_result'];
				$filter_result_build		=	$_SESSION['filter_result_build'];
				$filter_keywords			=	$_SESSION['filter_keywords'];
				
				
				
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
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" WHERE NHTC.node_type_id = 3 " .
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
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
							" WHERE NHTC.node_type_id = 3 " .
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
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" WHERE NHTC.node_type_id = 3 " .
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
							AND NHTS.parent_id =".$target.") a  where count>0";
							// AND NHTS.id =	".$filter_toplevel_testsuite." NOT USING THIS PROBLEM IS ARRIVING
						$staticSql[1] =	
							" SELECT NHTC.node_order AS spec_order, " .
							"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
							"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" WHERE NHTC.node_type_id = 3 " .
							" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
							" AND NHTC.parent_id = ". $target/* .
							" AND NHTC.id = ".$filter_toplevel_testsuite */
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
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							$joinKey.
							" WHERE NHTC.node_type_id = 3 " .
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
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
							" WHERE NHTC.node_type_id = 3 " .
							" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
							" AND NHTC.parent_id = ". $target.
							" AND TV.importance = ".$filter_priority;
					}
					// case 7(Running fine)
					// When search is ONLY according to filter execution type
					elseif(!empty($filter_execution_type) && $test_case_id != 0 && empty($filter_assigned_user) && empty($filter_testcase_name) && empty($filter_toplevel_testsuite) &&
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
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
							" WHERE NHTC.node_type_id = 3 " .
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
									" FROM nodes_hierarchy NHTC " .
									" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
									" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
									/* " LEFT OUTER JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ". */
									" WHERE NHTC.node_type_id = 3 " .
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
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
							AND NHTS.parent_id =".$target.") a  where count>0";
							// AND NHTS.id =	".$filter_toplevel_testsuite."
						$staticSql[1] =	
								" SELECT NHTC.node_order AS spec_order, " .
								"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
								"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
							AND NHTS.parent_id =".$target.") a  where count>0";
							// AND NHTS.id =	".$filter_toplevel_testsuite."
						$staticSql[1] =	
								" SELECT NHTC.node_order AS spec_order, " .
								"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
								"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
								" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
								" WHERE NHTC.node_type_id = 3 " .
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
							AND NHTS.parent_id =".$target.") a  where count>0";
							// AND NHTS.id =	".$filter_toplevel_testsuite."
						$staticSql[1] =	
								" SELECT NHTC.node_order AS spec_order, " .
								"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
								"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
								" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
								" WHERE NHTC.node_type_id = 3 " .
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
							AND NHTS.parent_id =".$target.") a  where count>0";
							// AND NHTS.id =	".$filter_toplevel_testsuite."
						$staticSql[1] =	
								" SELECT NHTC.node_order AS spec_order, " .
								"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
								"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
								" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
								" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
							AND NHTS.parent_id =".$target.") a  where count>0";
							// AND NHTS.id =	".$filter_toplevel_testsuite."
						$staticSql[1] =	
								" SELECT NHTC.node_order AS spec_order, " .
								"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
								"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
								" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
								" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
							AND NHTS.parent_id =".$target.") a  where count>0";
							// AND NHTS.id =	".$filter_toplevel_testsuite."
						$staticSql[1] =	
								" SELECT NHTC.node_order AS spec_order, " .
								"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
								"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
								" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
							AND NHTS.parent_id =".$target.") a  where count>0";
							// AND NHTS.id =	".$filter_toplevel_testsuite."
						$staticSql[1] =	
								" SELECT NHTC.node_order AS spec_order, " .
								"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
								"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
								" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
								" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
								" FROM nodes_hierarchy NHTC " .
								" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" JOIN tcversions TV ON TV.id = TPTCV.tcversion_id	".
								" JOIN executions EXC ON  EXC.tcversion_id = TPTCV.tcversion_id ".
								" WHERE NHTC.node_type_id = 3 " .
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
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" WHERE NHTC.node_type_id = 3 " .
							" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
							" AND NHTC.parent_id = ". $target.
							" AND NHTC.name like '%".$filter_testcase_name."%' ".
							" AND NHTC.id = ".$test_case_id
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
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" WHERE NHTC.node_type_id = 3 " .
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
						" FROM nodes_hierarchy NHTC " .
						" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
						" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
						" WHERE NHTC.node_type_id = 3 " .
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
						" FROM nodes_hierarchy NHTC " .
						" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
						" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
						" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
						" WHERE NHTC.node_type_id = 3 " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ". $target.
						" AND UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$_SESSION['t_build_id'];
				}
			}
			// new code with filters ends
			$sql = $staticSql[0] . " UNION " . $staticSql[1];
		
		} // End init static area
		
		/* $target = intval($node_id);
		$sql = $staticSql[0] . $target." AND hierarchy(NHTS.id,".$tplan_id." ) " . " UNION " . $staticSql[1] . $target;
		
		if( $tcaseFilter['enabled'] )
		{
			foreach($tcaseFilter as $key => $apply)
			{
				if( $apply )
				{
					switch($key)
					{
						case 'name':
							 $sql .= " AND NHTC.name LIKE '%{$my['filters']['testcase_name']}%' ";
						break;
						
						case 'id':
							 $sql .= " AND NHTC.id = {$my['filters']['testcase_id']} ";
						break;
					}
				}
			}
		}
		
		$sql .= " ORDER BY node_order,id"; */
		$rs = $db->fetchRowsIntoMap($sql,'id');
		/* $tt	=	mysql_query($sql);
		$rs	=	array();
		if(mysql_num_rows($tt) > 0){
			while($rr1	=	mysql_fetch_assoc($tt)){
				$rs[$rr1['id']]	=	$rr1;
			}
		} */
		if( count($rs) == 0 )
		{
			return $qnum;
		}
		$qnum	=	count($rs);
		$i		=	0;
		foreach($rs as $row)
		{
			if(!isset($exclude_branches[$row['id']]))
			{
				$i++;
				$node = $row + 
						array('node_type' => $tree_manager->node_types[$row['node_type_id']],
							  'node_table' => $tree_manager->node_tables_by['id'][$row['node_type_id']]);
				$node['childNodes'] = null;
				
				if($node['node_table'] == 'testcases')
				{
					$node['leaf'] = true; 
					// $node['external_id'] = isset($highlander[$row['id']]) ? $highlander[$row['id']]['external_id'] : '';
					$node['external_id'] = '';
				}			
				
				// why we use exclude_children_of ?
				// 1. Sometimes we don't want the children if the parent is a testcase,
				//    due to the version management
				//
				if(!isset($exclude_children_of[$node_types[$row['node_type_id']]]))
				{
					// Keep walking (Johny Walker Whisky)
					//$this->_get_subtree_rec($tplan_id,$row['id'],$node,$my['filters'],$my['options']);
				}
	
			 
				// Have added this logic, because when export test plan will be developed
				// having a test spec tree where test suites that do not contribute to test plan
				// are pruned/removed is very important, to avoid additional processing
				//		        
				// If node has no childNodes, we check if this kind of node without children
				// can be removed.
				//
				list($t_total,$t_pass,$t_fail,$t_blocked,$t_notRun)	=	explode(",",$row['count']);
				$total		=	$total + $t_total;
				$pass		=	$pass + $t_pass;
				$fail		=	$fail + $t_fail;
				$blocked	=	$blocked + $t_blocked;
				$notRun		=	$notRun + $t_notRun;
				
				$pnode['total']		=	$total;
				$pnode['pass']		=	$pass;
				$pnode['fail']		=	$fail;
				$pnode['blocked']	=	$blocked;
				$pnode['notRun']	=	$notRun;
				
				$node['total']			=	$t_total;
				$node['pass']			=	$t_pass;
				$node['fail']			=	$t_fail;
				$node['blocked']		=	$t_blocked;
				$node['notRun']			=	$t_notRun;
				
				
				
				
				$doRemove = is_null($node['childNodes']) && 
							($node['node_type_id'] == $my['options']['remove_empty_nodes_of_type']);
				
			    if(!$doRemove)
			    {
		  			$pnode['childNodes'][] = $node;
		  		}	
				
			} // if(!isset($exclude_branches[$rowID]))
		} //while
		return $qnum;
	}
?>