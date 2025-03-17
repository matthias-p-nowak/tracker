CREATE TEMPORARY TABLE  if NOT EXISTS ActHier(
`Activity` VARCHAR(255) NOT NULL ,
`Parent` VARCHAR(255) NOT NULL,
`Src` INT NOT NULL
);

TRUNCATE TABLE ActHier;

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

-- SELECT * FROM ActHier;

INSERT IGNORE INTO ${prefix}Accounted (`Activity`, `Day`, `YearWeek`, `WeekDay`)
SELECT h.Parent, a.`Day`, a.`YearWeek`, a.`WeekDay`
FROM ${prefix}Accounted a JOIN ActHier h ON a.Activity = h.Activity ;

UPDATE ${prefix}Accounted a JOIN (
SELECT 
h.`Parent`, a.`Day`, SUM(a.`DayAccount`) AS 'Total'
FROM ActHier h JOIN ${prefix}Accounted a ON h.Activity = a.Activity
GROUP BY h.parent, a.Day
) j ON a.`Activity`= j.`Parent` AND a.`Day`=j.`Day` SET a.`DayTotal`=j.`Total`
;

SELECT * FROM ${prefix}Accounted;
