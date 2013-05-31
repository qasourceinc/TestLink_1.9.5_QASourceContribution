/* 
* Author : QASource
* Defect ID : 5751
* Function gets the count of pass, fail, blocked test cases for folder
* We call this function from pages  /lib/functions/testplan.class.filter.manual_test.php and /lib/ajax/loadTreeAjax_test.php
* This function is embedded in query so as to get the count of its testcases
* Here we have created two temp tables
*/
delimiter //
create function hierarchy
(
p_cat_id bigint unsigned, p_test_plan bigint unsigned
)
returns varchar(100)  DETERMINISTIC
begin

declare v_done tinyint unsigned default 0;
declare v_depth bigint unsigned default 0;
declare v_count varchar(100); 


CREATE TEMPORARY TABLE hier(
 parent_id bigint unsigned, 
 id bigint unsigned, 
 depth bigint unsigned default 0,
 node_type_id bigint,
 status varchar(10),
INDEX idx_node_type_id (node_type_id))
/* engine = memory; */
engine = InnoDB;


INSERT INTO hier 
SELECT	NHTS.parent_id, NHTS.id, v_depth, NHTS.node_type_id, null
FROM	nodes_hierarchy NHTS 
WHERE	NHTS.node_type_id = 2  AND 
	NHTS.parent_id = p_cat_id
UNION 
SELECT parent_id, id,v_depth, node_type_id,status from stored_result where parent_id = p_cat_id;

/* CREATE TEMPORARY TABLE tmp engine=memory SELECT * FROM hier; */
CREATE TEMPORARY TABLE tmp engine=InnoDB SELECT * FROM hier;

WHILE NOT v_done DO
    IF EXISTS( SELECT 1 FROM nodes_hierarchy c
			inner join tmp ON c.parent_id = tmp.id and tmp.depth = v_depth) THEN

        INSERT INTO hier SELECT c.parent_id, c.id, v_depth + 1, c.node_type_id, c.status FROM 
	(SELECT NHTS.parent_id, NHTS.id, v_depth, NHTS.node_type_id, null status
	FROM	nodes_hierarchy NHTS 
	WHERE	NHTS.node_type_id = 2  
	UNION 
	   SELECT parent_id, id,v_depth, node_type_id,status from stored_result ) c
	inner join tmp ON c.parent_id = tmp.id AND tmp.depth = v_depth;

        set v_depth = v_depth + 1;          

        delete from tmp;
        insert into tmp select * from hier where depth = v_depth;

    ELSE
        SET v_done = 1;
    END IF;
END WHILE;

set v_count = (SELECT  concat(cast(count(*) as char), ',', 
					   cast(sum(if(status='p', 1,0)) as char), ',', 
					   cast(sum(if(status='f', 1,0)) as char), ',', 
					   cast(sum(if(status='b', 1,0)) as char), ',', 
					   cast((count(*) - (sum(if(status='p', 1,0)) + sum(if(status='f', 1,0)) + sum(if(status='b', 1,0)))) as char))
			   FROM  hier
			   
               WHERE
					hier.node_type_id = 3);
        

DROP TEMPORARY TABLE IF EXISTS hier;
DROP TEMPORARY TABLE IF EXISTS tmp;

return v_count;
end//
delimiter ;



