<?php
	/**
	 * Author : QASource
	 * Defect ID : 5751
	 * Instead the original functionality we have implemented the ajax call by calling this page
	 * This page is being caled from file (/gui/templates/execute/execNavigator.tpl)
	 * This is called when we click on nodes(folders)
	 * On every hit it creates temp table, enters records and simultaneously after getting count drops that temp table.
	 * The hierarchy() function used in below query can be found on /db_functions/hierarchy_final_test.sql
	 *				 
	 **/
	require_once('../../config.inc.php');
	require_once('common.php');
	testlinkInitPage($db);
	$k	=	&$db;
	$tree_manager = new tree($k);
	error_reporting(0);
	$session_id		=	session_id();
	if(isset($_REQUEST['parentID'])){
		$parentID		=	$_REQUEST['parentID'];
		$plan_id		=	$_REQUEST['plan_id'];
		$grandParentID	=	$_REQUEST['grandParentID'];
		$build_id		=	$_REQUEST['build_id'];
		$pnode			=	array();
		$count			=	_get_subtree_rec($plan_id,$parentID,$build_id,$pnode,$filters = null, $options = null,$db);
		$grandParent_name	=	$db->getGrandParentName($grandParentID);
		
		?>
		<table border='0' style='padding-left:40px;' cellspacing='0' cellpadding='0'>
		<?php
		foreach($pnode as $key => $value){
			if(!empty($value)){
				foreach($value as $k => $v){
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
	
	/*
	 * Author : QASource
	 * Defect ID : 5751
	 * This function calls when we hit on node(folder) with its parent_id 
	 */
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
			$role_id	=	$db->getRoleId($_SESSION['userID']);
			$full_access=	0;
			if($role_id == 8){
				$full_access	=	1;
			}
			
			// new code with filters
			
			
			$whereClause	=	"";
			$whereClauseTop	=	"";
			$joinClause		=	"";
			$wherePassFail	=	"";
			$group_by		=	"";
			$whereNotRun	=	"";
			$whereBottom	=	"";
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
						/* $whereClauseTop	.=	" AND NHTS.id =	".$filter_toplevel_testsuite; */
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
						$whereClause	.=	" AND NHTC.id = ".$test_case_id;
					}
					if(!empty($filter_testcase_name)){
						$whereClause	.=	" AND NHTC.name like '%".$filter_testcase_name."%' ";
					}
					if(!empty($filter_toplevel_testsuite)){
						/* $whereClause	.=	" AND NHTC.id = ".$filter_toplevel_testsuite; */
						/* $whereClauseTop	.=	" AND NHTS.id =	".$filter_toplevel_testsuite; */
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
			
			$session_id	=	session_id();
			
			$tt_sql			=	"CREATE TEMPORARY TABLE IF NOT EXISTS stored_result (parent_id bigint unsigned, id bigint unsigned,node_type_id bigint, status varchar(10),INDEX indx_1(parent_id)) engine = InnoDB";
			$db->createTempTable($tt_sql);
			$tt_query	=	"INSERT INTO stored_result (SELECT parent_id, id, node_type_id,status from
															(
																SELECT NHTC.parent_id, NHTC.id, NHTC.node_type_id, (select status from executions where id = (select max(id) from executions where testplan_id = ".$tplan_id." and tcversion_id=TPTCV.tcversion_id and build_id = ".$_SESSION['t_build_id'].$wherePassFail." )) status ".
																" FROM nodes_hierarchy NHTC " .
																" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
																" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
																 $joinClause .
																" WHERE NHTC.node_type_id = 3 " .
																" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
																 $whereClause .$group_by.
															 ") a "
														 . $whereNotRun .
														 "
														) ";
			$db->insertInTempTable($tt_query);
			
			
			$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy(NHTS.id,".$tplan_id.",'".$session_id."') as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2 ".
					" AND NHTS.parent_id =".$target.") a  where count>0";				
			$staticSql[1] =	
				" SELECT NHTC.node_order AS spec_order, " .
				"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
				"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
				" FROM nodes_hierarchy NHTC " .
				" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
				" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
				$joinClause . 
				" WHERE NHTC.node_type_id = 3 " .
				" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
				" AND NHTC.parent_id = ". $target.
				$whereClause.$group_by
				;
				
			$sql = $staticSql[0] . " UNION " . $staticSql[1];
		
		} // End init static area
		
		$rs = $db->fetchRowsIntoMap($sql,'id');
		$db->dropTempTable('DROP TEMPORARY TABLE IF EXISTS stored_result');
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
					$node['external_id'] = '';
				}			
				
			 
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