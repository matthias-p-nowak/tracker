<?php

namespace Code;

class Calculator
{
    /**
     * @var array<<missing>,mixed>
     */
    public array $activities = [];
    public array $children = [];

    public array $levels = [];

    /**
     * @return void
     */
    public function calculate(): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' calculating...');
        $db = Db\DbCtx::getCtx();
        $sql = <<< 'EOS'
            TRUNCATE ${prefix}Accounted;
             -- summarize work
            INSERT INTO `${prefix}Accounted` (`Activity`, `Day`, `YearWeek`, `WeekDay`, `Sofar`) 
            WITH cte_hours AS
            (
            SELECT 
             e.Activity, 
             e.Started, TIMESTAMPDIFF(SECOND, e.Started, e.Ended) / 3600 AS 'hours', TO_DAYS(e.started) AS 'day', YEARWEEK(e.Started,3) AS 'yearweek', WEEKDAY(e.Started) AS 'weekday'
            FROM 
            ${prefix}Event e JOIN ${prefix}Activity a ON e.Activity=a.Activity
            WHERE a.Results=1 ),
            cte_sum AS (
            SELECT 
             c.Activity, c.started, c.yearweek, c.weekday, c.day, c.hours, SUM(c.hours) OVER (PARTITION BY c.Activity
            ORDER BY c.Started) AS 'Sofar'
            FROM cte_hours c
            ),
            cte_day AS
            (
            SELECT 
            	c.Activity, c.day, c.yearweek, c.weekday, MAX(c.Sofar) AS 'Sofar'
            FROM cte_sum c
            GROUP BY c.Activity, c.yearweek, c.weekday, c.day )
            SELECT c.Activity,	c.day, c.yearweek, c.weekday, c.Sofar FROM cte_day c;
            -- making accounted
            UPDATE `${prefix}Accounted` SET `Accounted`=CEIL(`Sofar`*2)/2; 
            -- getting day accounts
                    update `${prefix}Accounted` a JOIN (
                        SELECT `Activity`, `Day`, LAG(`Accounted`) OVER (PARTITION BY `Activity` order by `Day`) as 'PreviousAccount'
                        from `${prefix}Accounted` ) sq 
                        ON a.`Activity`=sq.`Activity` and a.`Day`=sq.`Day` set a.`DayAccount`= COALESCE(a.`Accounted`-sq.`PreviousAccount`, a.`Accounted`);
            SELECT * FROM ${prefix}Accounted;
            -- temp table for hierarchy
            CREATE TEMPORARY TABLE  if NOT EXISTS ActHier(
            `Activity` VARCHAR(255) NOT NULL , `Parent` VARCHAR(255) NOT NULL, `Src` INT NOT NULL );
            -- empty it
            TRUNCATE TABLE ActHier;
            -- insert hierarchy
            INSERT INTO ActHier 
             WITH RECURSIVE CTE_Activity AS (
                        SELECT distinct e.Activity, e.Activity AS 'Parent', 0 AS 'Src'
                        FROM ${prefix}Event e JOIN ${prefix}Activity a on e.Activity=a.Activity
                        WHERE a.Results=1
                        UNION ALL
                        SELECT a.Activity, c.Parent, c.Src +1 AS 'Src'
                        FROM ${prefix}Activity AS a
                        JOIN CTE_Activity AS c ON a.Parent = c.Activity
                        WHERE a.Results = 1 )
            SELECT DISTINCT Activity, Parent, Src FROM CTE_Activity;
            -- insert empty records if necessary
            INSERT IGNORE INTO ${prefix}Accounted (`Activity`, `Day`, `YearWeek`, `WeekDay`)
            SELECT h.Parent, a.`Day`, a.`YearWeek`, a.`WeekDay`
            FROM ${prefix}Accounted a JOIN ActHier h ON a.Activity = h.Activity ;
            -- update with the sum
            UPDATE ${prefix}Accounted a JOIN (
            SELECT 
            h.`Parent`, a.`Day`, SUM(a.`DayAccount`) AS 'Total'
            FROM ActHier h JOIN ${prefix}Accounted a ON h.Activity = a.Activity
            GROUP BY h.parent, a.Day
            ) j ON a.`Activity`= j.`Parent` AND a.`Day`=j.`Day` SET a.`DayTotal`=j.`Total` ;
        EOS;
        $res = $db->query($sql);
    }
    
    /**
     * @return void
     */
    public function calcHierarchy(): void
    {
        $db = Db\DbCtx::getCtx();
        $activities = 0;
        $this->levels = [];
        foreach ($db->findRows('Activity') as $row) {
            $activities += 1;
            $this->levels[$row->Activity] = 0;
            $this->activities[$row->Activity] = $row;
            if (!is_null($row->Parent)) {
                $this->children[$row->Parent][$row->Activity] = $row;
                $this->levels[$row->Parent] = 0;
            }
        }
        AgainFromScratch:
        foreach ($this->activities as $act => $row) {
            $this->levels[$act] = 0;
        }
        do {
            $again = false;
            foreach ($this->children as $parent => $crow) {
                foreach ($crow as $child => $row) {
                    $cl = $this->levels[$child] ?? 0;
                    $pl = $this->levels[$parent] ?? 0;
                    if ($pl > $activities) {
                        $db->deleteRow($row);
                        unset($this->children[$parent][$child]);
                        goto AgainFromScratch;
                    } else {
                        if ($cl < $pl + 1) {
                            $this->levels[$child] = $pl + 1;
                            $again = true;
                        }
                    }
                }
            }
        } while ($again);
    }

    /**
     * @return void
     * @param mixed $activity
     */
    public function showTree($activity): void
    {
        $line='<span name="'.$activity.'">'.$activity.'</span>';
        if(isset($this->activities[$activity])){
            if($this->activities[$activity]->Results){
                $line='<b name="'.$activity.'">'.$line.'</b>';
            }
        }else{
            $line='<em name="'.$activity.'">'.$line.'</em>';
        }
        echo '<div>'.$line;
        foreach(($this->children[$activity] ?? []) as $child => $row){
            $this->showTree($child);
        }
        echo '</div>';
    }
}
