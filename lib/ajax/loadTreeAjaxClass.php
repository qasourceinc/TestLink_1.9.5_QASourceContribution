<?php
	require_once('../../config.inc.php');
	require_once('common.php');
	testlinkInitPage($db);
	
	/* require_once( '../../lib/functions/tree.class.php' );
	require_once( '../../lib/functions/assignment_mgr.class.php' );
	require_once( '../../lib/functions/attachments.inc.php' ); */
	class loadTreeAjaxClass extends testplan
	{
		function _get_subtree_rec($tplan_id,$node_id,&$pnode,$filters = null, $options = null)
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
				$node_types = array_flip($this->tree_manager->get_available_node_types());
				$my['filters'] = array('exclude_children_of' => null,'exclude_branches' => null,
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
									  $this->tree_manager->node_descr_id[$my['options']['remove_empty_nodes_of_type']];
					}
				}
		
		
				$platformFilter = "";
				if( !is_null($my['filters']['platform_id']) && $my['filters']['platform_id'] > 0 )
				{
					$platformFilter = " AND T.platform_id = " . intval($my['filters']['platform_id']) ;
				}
		
				// Create invariant sql sentences
				$staticSql[0] = " /* $debugMsg - Get ONLY TestSuites */ " .
								" SELECT NHTS.node_order AS spec_order," . 
								" NHTS.node_order AS node_order, NHTS.id, NHTS.parent_id," . 
								" NHTS.name, NHTS.node_type_id, 0 AS tcversion_id " .
								" FROM {$this->tables['nodes_hierarchy']} NHTS" .
								" WHERE NHTS.node_type_id = {$this->tree_manager->node_descr_id['testsuite']} " .
								" AND NHTS.parent_id = ";
							
				$staticSql[1] =	" /* $debugMsg - Get ONLY Test Cases with version linked to (testplan,platform) */ " .
								" SELECT NHTC.node_order AS spec_order, " .
								"        TPTCV.node_order AS node_order, NHTC.id, NHTC.parent_id, " .
								"        NHTC.name, NHTC.node_type_id, TPTCV.tcversion_id " .
								" FROM {$this->tables['nodes_hierarchy']} NHTC " .
								" JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.parent_id = NHTC.id " .
								" JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.tcversion_id = NHTCV.id " .
								" WHERE NHTC.node_type_id = {$this->tree_manager->node_descr_id['testcase']} " .
								" AND TPTCV.testplan_id = " . intval($tplan_id) . " {$platformFilter} " .
								" AND NHTC.parent_id = ";	
			
			} // End init static area
			
			$target = intval($node_id);
			$sql = $staticSql[0] . $target . " UNION " . $staticSql[1] . $target;
			
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
			
			$sql .= " ORDER BY node_order,id";
			
			$rs = $this->db->fetchRowsIntoMap($sql,'id');
			if( count($rs) == 0 )
			{
				return $qnum;
			}
		
			/// create list with test cases nodes
			//$tclist = null;
			//$ks = array_keys($rs);
			//foreach($ks as $ikey)
			//{
			//	if( $rs[$ikey]['node_type_id'] == $this->tree_manager->node_descr_id['testcase'] )
			//	{
			//		$tclist[$rs[$ikey]['id']] = $rs[$ikey]['id'];
			//	}
			//}		
			//
			//if( !is_null($tclist) )
			//{
			//	$filterOnTC = false;
			//	$glav = " /* Get LATEST ACTIVE tcversion ID */ " .  
			//			" SELECT MAX(TCVX.id) AS tcversion_id, NHTCX.parent_id AS tc_id " .
			//			" FROM {$this->tables['tcversions']} TCVX " . 
			//			" JOIN {$this->tables['nodes_hierarchy']} NHTCX " .
			//			" ON NHTCX.id = TCVX.id AND TCVX.active = 1 " .
			//			" WHERE NHTCX.parent_id IN (" . implode($tclist,',') . ")" .
			//			" GROUP BY NHTCX.parent_id,TCVX.tc_external_id  ";
			//
			//	$ssx = 	" /* Get LATEST ACTIVE tcversion MAIN ATTRIBUTES */ " .
			//			" SELECT TCV.id AS tcversion_id, TCV.tc_external_id AS external_id, SQ.tc_id " .
			//	   		" FROM {$this->tables['tcversions']} TCV " . 
			//	   		" JOIN ( $glav ) SQ " .
			//	   		" ON TCV.id = SQ.tcversion_id ";
			//
			//	if( $tcversionFilter['enabled'] || $tcaseFilter['is_active'] )
			//	{
			//		if( $tcversionFilter['execution_type'] )
			//		{
			//			$ssx .= " /* Filter LATEST ACTIVE tcversion */ " .
			//					" WHERE TCV.execution_type = " . $my['filters']['execution_type'];
			//			$filterOnTC = true;
			//		}	
			//	}
			//	
			//	$highlander = $this->db->fetchRowsIntoMap($ssx,'tc_id');
			//	if( $filterOnTC )
			//	{
			//		$ky = !is_null($highlander) ? array_diff_key($tclist,$highlander) : $tclist;
			//		if( count($ky) > 0 )
			//		{
			//			foreach($ky as $tcase)
			//			{
			//				unset($rs[$tcase]);						
			//			}
			//		}
			//	}
			//}
			$i	=	0;
			foreach($rs as $row)
			{
				if(!isset($exclude_branches[$row['id']]))
				{  
					$i++;
					$node = $row + 
							array('node_type' => $this->tree_manager->node_types[$row['node_type_id']],
								  'node_table' => $this->tree_manager->node_tables_by['id'][$row['node_type_id']]);
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
					$doRemove = is_null($node['childNodes']) && 
								($node['node_type_id'] == $my['options']['remove_empty_nodes_of_type']);
					if(!$doRemove)
					{
						$pnode['childNodes'][] = $node;
					}	
					// $pnode['childNodes'][] = $node;
					
					$pnode['spec_order']	=	$row['spec_order'];
					$pnode['node_order']	=	$row['node_order'];
					$pnode['node_order']	=	$row['node_order'];
					$pnode['id']			=	$row['id'];
					$pnode['parent_id']		=	$row['parent_id'];
					$pnode['name']			=	$row['name'];
					$pnode['node_type_id']	=	$row['node_type_id'];
					$pnode['tcversion_id']	=	$row['tcversion_id'];
					$pnode['node_type']		=	$this->tree_manager->node_types[$row['node_type_id']];
					$pnode['node_table']	=	$this->tree_manager->node_tables_by['id'][$row['node_type_id']];
					if($i>1){
						$node['leaf'] 			=	1;
						$node['external_id'] 	=	"";
						$pnode['childNodes'][] 	= 	$node;
					}
					
				} // if(!isset($exclude_branches[$rowID]))
			} //while
			return $qnum;
		}
	}
?>