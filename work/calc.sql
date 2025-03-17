        -- get ended
        UPDATE tracker_Event e JOIN (
            SELECT Started, LAG(Started) OVER( ORDER BY Started DESC ) AS 'Ended' FROM tracker_Event
            ) c on e.Started=c.Started
         set e.Ended=c.Ended;
        TRUNCATE tracker_Accounted;
        
        -- summarize work
        INSERT INTO `tracker_Accounted` (`Activity`, `Day`, `YearWeek`, `WeekDay`, `Sofar` )
        WITH RECURSIVE CTE_1 AS (
            SELECT e.Activity, e.Activity AS 'Parent', 0 AS 'Src'
            FROM tracker_Event e JOIN tracker_Activity a on e.Activity=a.Activity
            WHERE a.Results=1
            UNION ALL
            SELECT a.Activity, c.Parent, c.Src +1 AS 'Src'
            FROM tracker_Activity AS a
            JOIN CTE_1 AS c ON a.Parent = c.Activity
            WHERE a.Results = 1 )
        , CTE_2 AS (
            SELECT c.Activity, c.Parent, c.Src,
            ROW_NUMBER() OVER (PARTITION BY c.Activity, c.Parent order by c.Src) as 'RN'
            FROM CTE_1 c )
        , CTE_3 AS (
            SELECT 
            c.Parent, e.Activity, e.Started, c.Src,
            TIMESTAMPDIFF(Second, e.Started, e.Ended) / 3600 AS 'hours',
            TO_DAYS(e.started) AS 'day',
            YEARWEEK(e.Started,3) AS 'yearweek',
            WEEKDAY(e.Started) AS 'weekday'
            FROM tracker_Event AS e 
            JOIN CTE_2 AS c on c.Activity=e.Activity
            WHERE c.RN = 1 )
        , CTE_4 AS (
            SELECT `Started`, `Src`, `Activity`, `day`, `yearweek`, `weekday`, `Parent`,
            sum(hours) over (Partition by `Parent` ORDER BY `Started`) as 'Sofar' 
            FROM CTE_3 WHERE `hours` is not null )
        , CTE_5 AS (
            SELECT `Parent` AS 'Activity', `day`, `yearweek`, `weekday`,
            MAX(`Sofar`) as 'Sofar'
            FROM CTE_4
            GROUP BY `Parent`, `day`, `yearweek`, `weekday` )
        SELECT `Activity`, `day`, `yearweek`, `weekday`, `Sofar` from  CTE_5;
        
        -- making accounted
        UPDATE `tracker_Accounted` SET `Accounted`=CEIL(`Sofar`*2)/2; 
        
        -- getting day accounts
        update `tracker_Accounted` a JOIN (
            SELECT `Activity`, `Day`, LAG(`Accounted`) OVER (PARTITION BY `Activity` order by `Day`) as 'PreviousAccount'
            from `tracker_Accounted` 
        ) sq ON a.`Activity`=sq.`Activity` and a.`Day`=sq.`Day`
        set a.`DayAccount`= COALESCE(a.`Accounted`-sq.`PreviousAccount`, a.`Accounted`);
