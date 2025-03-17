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
WHERE a.Results=1
),
cte_sum AS
(
SELECT 
 c.Activity,
 c.started,
 c.yearweek,
 c.weekday,
 c.day,
 c.hours, SUM(c.hours) OVER (PARTITION BY c.Activity
ORDER BY c.Started) AS 'Sofar'
FROM cte_hours c
),
cte_day AS
(
SELECT 
	c.Activity,
	c.day,
	c.yearweek,
	c.weekday,
	MAX(c.Sofar) AS 'Sofar'
FROM cte_sum c
GROUP BY 	
	c.Activity,
	c.yearweek,
 	c.weekday,
 	c.day
)
SELECT c.Activity,	c.day,
	c.yearweek,
	c.weekday, c.Sofar
FROM cte_day c;


-- making accounted
UPDATE `${prefix}Accounted` SET `Accounted`=CEIL(`Sofar`*2)/2; 

 -- getting day accounts
        update `${prefix}Accounted` a JOIN (
            SELECT `Activity`, `Day`, LAG(`Accounted`) OVER (PARTITION BY `Activity` order by `Day`) as 'PreviousAccount'
            from `${prefix}Accounted` 
        ) sq ON a.`Activity`=sq.`Activity` and a.`Day`=sq.`Day`
        set a.`DayAccount`= COALESCE(a.`Accounted`-sq.`PreviousAccount`, a.`Accounted`);

SELECT * FROM ${prefix}Accounted;


