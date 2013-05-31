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
	
	?>
	<script type="text/javascript" language="javascript">
		<!--
		var fRoot 	= '<?php echo $basehref ;?>';
		var menuUrl = '<?php echo $menuUrl ;?>';
		var args  	= '<?php echo $args ;?>';
		var additionalArgs  = '<?php echo $additionalArgs ;?>';
		
		// To solve problem diplaying help
		// var SP_html_help_file  = '{$SP_html_help_file}';
		
		//attachment related JS-Stuff
		var attachmentDlg_refWindow = null;
		var attachmentDlg_refLocation = null;
		var attachmentDlg_bNoRefresh = false;
		
		// bug management (using logic similar to attachment)
		var bug_dialog = new bug_dialog();

		// for ext js
		// var extjsLocation = '{$smarty.const.TL_EXTJS_RELATIVE_PATH}';
		
		//-->
		function ST(id,version)
		{
			var _FUNCTION_NAME_='ST';
			var action_url=fRoot+menuUrl+"?version_id="+version+"&level=testcase&id="+id+args;
			alert(_FUNCTION_NAME_ + " " +action_url);
			parent.workframe.location = action_url;
		}
		/* function ajaxRequest(parentID,b,plan_id){
			var url	=	fRoot+"/lib/ajax/loadTreeAjax.php?parentID="+parentID+"&plan_id="+plan_id;
			alert("sa="+url);

			$.get(url, function(data){
				if(document.getElementById(parentID).style.display == 'inline'){
					document.getElementById(parentID).style.display	=	"none";
				}else{
					document.getElementById(parentID).style.display	=	"inline";
					$("#"+parentID).html(data);
				}
			});
		} */
	</script> 
	<script type="text/javascript" src="<?php echo $basehref;?>gui/javascript/testlink_library.js" language="javascript"></script>
	<script type="text/javascript" src="<?php echo $basehref;?>gui/javascript/test_automation.js" language="javascript"></script>
	<script type="text/javascript" src="<?php echo $basehref;?>third_party/prototype/prototype.js" language="javascript"></script>
	
	<script type="text/javascript" src='<?php echo $basehref;?>gui/javascript/ext_extensions.js'></script>
	<!--<script type="text/javascript" src='<?php echo $basehref;?>gui/javascript/execTreeWithMenu.js'></script>-->
	<script type="text/javascript" src='<?php echo $basehref;?>gui/javascript/jquery.min.js'></script>
	<script type="text/javascript" src='<?php echo $basehref;?>gui/javascript/jquery-ui-1.7.1.custom.min.js'></script>
	<?php
	if(isset($_REQUEST['parentID'])){
		$parentID	=	$_REQUEST['parentID'];
		$plan_id	=	$_REQUEST['plan_id'];
		$pnode		=	array();
		_get_subtree_rec($plan_id,$parentID,$pnode,$filters = null, $options = null);
		/* echo "<pre>";
		print_r($pnode); */
		?>
		<table border='1' style='padding-left:56px;'>
		<?php
		// echo "<pre>"; print_r($pnode);
		foreach($pnode as $key => $value){
			if(!empty($value)){
				foreach($value as $k => $v){
					$name			=	$v['name'];
					$id				=	$v['id'];
					$tcversion_id	=	$v['tcversion_id'];
					echo "<tr>";
					if(!empty($tcversion_id)){
						echo "<td><a href='javascript:ST(".$id.",".$tcversion_id.")'>$name</a></td>";
					}else{
						?>
						<td><a href='javascript:ajaxRequest2("<?php echo $id;?>","<?php echo $tcversion_id;?>","<?php echo $plan_id;?>")'><?php echo $name;?></a><div id='<?php echo $id;?>'></div></td>
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
			$staticSql[0] = "" .
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
		
		// $rs = $db->fetchRowsIntoMap($sql,'id');
		$tt	=	mysql_query($sql);
		$rs	=	array();
		if(mysql_num_rows($tt) > 0){
			while($rr1	=	mysql_fetch_assoc($tt)){
				$rs[$rr1['id']]	=	$rr1;
			}
		}
		if( count($rs) == 0 )
		{
			return $qnum;
		}
		
		$i	=	0;
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