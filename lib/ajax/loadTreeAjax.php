<?php
	
	require_once('../../config.inc.php');
	require_once('common.php');
	testlinkInitPage($db);
	//include('loadTreeAjaxClass.php');
	//$loadObj	=	new loadTreeAjaxClass();
	$k	=	&$db;
	$tree_manager = new tree($k);
	// echo "<pre>"; print_r($db);
	// echo $tree_manager->node_descr_id['testsuite'];
	
	error_reporting(0);
	if(isset($_REQUEST['parentID'])){
		$parentID		=	$_REQUEST['parentID'];
		$plan_id		=	$_REQUEST['plan_id'];
		$grandParentID	=	$_REQUEST['grandParentID'];
		$build_id		=	$_REQUEST['build_id'];
		$pnode			=	array();
		$count			=	_get_subtree_rec($plan_id,$parentID,$build_id,$pnode,$filters = null, $options = null,$db);
		/* echo "<pre>";
		print_r($pnode); */
		$grandParent_name	=	@mysql_result(mysql_query('SELECT prefix FROM testprojects WHERE id='.$grandParentID),0);
		
		?>
		<table border='0' style='padding-left:40px;' cellspacing='0' cellpadding='0'>
		<?php
		// echo "<pre>"; print_r($pnode);
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
					
					/* $latest_active_version_sql = "" .
                                         " SELECT MAX(TCVX.id) AS max_tcv_id, NHTCX.parent_id AS tc_id " .
                                         " FROM tcversions TCVX " .
                                         " JOIN nodes_hierarchy NHTCX " .
                                         " ON NHTCX.id = TCVX.id AND TCVX.active = 1 " .
                                         " WHERE NHTCX.parent_id = $id " .
                                         " GROUP BY NHTCX.parent_id, TCVX.tc_external_id "; */
            
					/* echo "<br><br><br>".$sql = " SELECT CFD.value " .
						   " FROM cfield_design_values CFD, nodes_hierarchy NH " .
						   " JOIN ( $latest_active_version_sql ) LAVSQL ON NH.id = LAVSQL.max_tcv_id " .
						   " WHERE CFD.node_id = NH.id "; */
					// $rows = $db->fetchColumnsIntoArray($sql,'value');
					// echo "<pre>"; print_r($rows);
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
	
			// Create invariant sql sentences
			/* $staticSql[0] = "" .
							" SELECT NHTS.node_order AS spec_order," . 
							" NHTS.node_order AS node_order, NHTS.id, NHTS.parent_id," . 
							" NHTS.name, NHTS.node_type_id, 0 AS tcversion_id " .
							" FROM nodes_hierarchy NHTS" .
							" WHERE NHTS.node_type_id = 2 " .
							" AND NHTS.parent_id = ";
						
			$staticSql[1] =	"" .
							" SELECT NHTC.node_order AS spec_order, " .
							"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
							"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id " .
							" FROM nodes_hierarchy NHTC " .
							" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
							" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
							" WHERE NHTC.node_type_id = 3 " .
							" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
							" AND NHTC.parent_id = ";	 */
							
			$target = intval($node_id);
			
			$role_id	=	mysql_result(mysql_query('select role_id from users where id='.$_SESSION['userID']),0);
			$full_access=	1;
			if($role_id == 8){
				$full_access	=	0;
			}
			
			if($full_access == 0){
				if(!empty($_SESSION['t_case'])){
					$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy_with_build( NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$_SESSION['t_userID'].",".$full_access.") as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";
						
					$staticSql[1] =	"" .
						" SELECT NHTC.node_order AS spec_order, " .
						"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
						"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
						" FROM nodes_hierarchy NHTC " .
						" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
						" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
						/* " JOIN user_assignments UA on case when ".$full_access." = 1	Then TPTCV.id else 1 end  != case when ".$full_access." = 1 then UA.feature_id else 1 end". */
						" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
						" WHERE NHTC.node_type_id = 3 " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ".$target.
						" AND UA.user_id=".$_SESSION['t_userID']." AND UA.build_id=".$_SESSION['t_build_id'];
						
						/* " AND case when  ".$full_access." = 1 then UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$build_id." else 1=1 end"; */
				}else{
					$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy_with_build_revert( NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].") as count
						FROM nodes_hierarchy NHTS
						WHERE NHTS.node_type_id =2
						AND NHTS.parent_id =".$target.") a  where count>0";
							
					$staticSql[1] =	"" .
						" SELECT NHTC.node_order AS spec_order, " .
						"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
						"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
						" FROM nodes_hierarchy NHTC " .
						" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
						" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
						/* " JOIN user_assignments UA on case when ".$full_access." = 1	Then TPTCV.id else 1 end  != case when ".$full_access." = 1 then UA.feature_id else 1 end". */
						" WHERE NHTC.node_type_id = 3 " .
						" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
						" AND NHTC.parent_id = ".$target;
						/* .
						" AND case when  ".$full_access." = 1 then UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$build_id." else 1=1 end"; */
				}
			}else{
				$staticSql[0]	=	"select node_order AS spec_order, node_order , id, parent_id, name, node_type_id,0 AS tcversion_id,count  from (SELECT NHTS.node_order AS spec_order, NHTS.node_order AS node_order,NHTS.id,NHTS.parent_id, NHTS.name, NHTS.node_type_id, 0 AS tcversion_id,hierarchy_with_build( NHTS.id,".$tplan_id.",".$_SESSION['t_build_id'].",".$_SESSION['t_userID'].",".$full_access.") as count
					FROM nodes_hierarchy NHTS
					WHERE NHTS.node_type_id =2
					AND NHTS.parent_id =".$target.") a  where count>0";
						
				$staticSql[1] =	"" .
					" SELECT NHTC.node_order AS spec_order, " .
					"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
					"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id,0 " .
					" FROM nodes_hierarchy NHTC " .
					" JOIN nodes_hierarchy NHTCV ON NHTCV.parent_id = NHTC.id " .
					" JOIN testplan_tcversions TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
					/* " JOIN user_assignments UA on case when ".$full_access." = 1	Then TPTCV.id else 1 end  != case when ".$full_access." = 1 then UA.feature_id else 1 end". */
					" JOIN user_assignments UA on TPTCV.id = UA.feature_id ".
					" WHERE NHTC.node_type_id = 3 " .
					" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
					" AND NHTC.parent_id = ".$target.
					" AND UA.user_id=".$_SESSION['t_userID']." AND UA.build_id=".$_SESSION['t_build_id'];
					
					/* " AND case when  ".$full_access." = 1 then UA.user_id=".$_SESSION['userID']." AND UA.build_id=".$build_id." else 1=1 end"; */
			}
			
							
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