<?php
namespace Code;

class Tracker
{
    /**
     * cleaning up and showing the home screen
     * @return void
     */
    public static function Show_Home(): void
    {
        echo <<< EOM
        <dialog x-action="remove" id="login_dialog">remove</dialog>
        EOM;
        $tracker = new Tracker();
        $tracker->showTopBox();
        $tracker->showMain();
    }
    /**
     * shows the top box according to current state
     * @return void
     */
    private function showTopBox(): void
    {
        global $status;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $againStyle = '';
        $editStyle = '';
        $resultStyle = '';
        $configStyle = '';
        $current = $status->current ?? 'Again';
        match ($status->mode ?? '') {
            'edit' => $editStyle = 'style="background-color: yellow;"',
            'result' => $resultStyle = 'style="background-color: aqua;"',
            'config' => $configStyle = 'style="background-color: bisque"',
            default => $againStyle = 'style="background-color: lightgreen;"',
        };
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
            <div id="topbox" x-action="replace" >
            $lt
            <form action="$scriptURL/change_mode" onsubmit="return false;" onclick="hxl_submit_form(event);">
            <span name="again" $againStyle >$current</span>
            <span name="edit" $editStyle >Edit</span>
            <span name="result" $resultStyle >Results</span>
            <span name="config" $configStyle >Configure</span>
            </form>
            </div>
        EOM;
    }

    /**
     * switching mode according to top switches
     * @return void
     */
    public static function Change_Mode(): void
    {
        global $status;
        $status->mode = $_POST['name'] ?? 'again';
        $tracker = new Tracker();
        $tracker->showTopBox();
        $tracker->showMain();
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
    }

    /**
     * shows the main content depending on the status
     * @return void
     */
    public function showMain(): void
    {
        global $status;
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
        match ($status->mode ?? 'again') {
            'again' => $this->showAgainMain(),
            'edit' => $this->events2Edit(),
            'result' => $this->presentResults(),
            'config' => $this->activities2Configure(),
            default => error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' ### showing main for ' . $status->mode)
        };
    }
    /**
     * Lets the user select an activity to start all over again
     * @return void
     */
    private function showAgainMain(): void
    {
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
            <div id="main" x-action="replace">
            $lt
            <h2>Past events</h2>
            <form action="$scriptURL/event_again" onsubmit="return false;" onclick="register_event(event);">
            EOM;
        $this->showAllActivities();
        echo <<< EOM
            </form>
            </div>
        EOM;

    }
    /**
     * shows all activities with latest first
     * @return void
     */
    private function showAllActivities(): void
    {
        $dbCtx = Db\DbCtx::getCtx();
        echo '<div class="boxed">';
        $sql = <<< 'EOS'
            WITH C_RN AS (
            SELECT *, ROW_NUMBER() OVER (PARTITION BY Activity order by Started DESC) AS RN 
            FROM ${prefix}Event
            ) SELECT 
            Id, Activity, Details, Started, Ended, IP, Latitude, Longitude 
            from C_RN WHERE RN=1 ORDER By Started DESC
        EOS;
        foreach ($dbCtx->sqlAndRows($sql, 'Event') as $ev) {
            echo <<< EOM
                <div id="{$ev->Id}" name="{$ev->Activity}">{$ev->Activity}</div>
            EOM;
        }
        echo '</div>';
    }

    /**
     * @return void
     */
    public function editEvent(mixed $ev): void
    {$scriptURL = $_SERVER['SCRIPT_NAME'];
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
            <div id="main" x-action="replace">
            $lt
            <h2>Editing</h2>
            <div class="table">
            <form action="$scriptURL/edit_event" onsubmit="return false;" >
            <input type="hidden" name="id" value="{$ev->Id}">
            <div class="row">
            <span class="right">Activity</span>
            <input type="text" name="activity" class="wide" onchange="hxl_submit_form(event);"
                value="{$ev->Activity}" placeholder="name for activity" >
            </div>
            <div class="row">
            <span class="right">Details</span>
            <input type="text" name="details" class="wide" onchange="hxl_submit_form(event);"
                value="{$ev->Details}" placeholder="What will be done this time?" >
             </div>
              <div class="row">
             <span class="right">Started</span>
            <input type="text" name="started" class="wide" onchange="hxl_submit_form(event);"
                value="{$ev->Started}" placeholder="start time" >
             </div>
             <div class="row">
             <span class="right">Remote IP</span><span>{$ev->IP}</span>
             </div>
             <div class="row">
             <span>Position (Lat/Lon):</span>
             <span>{$ev->Latitude} / {$ev->Longitude}</span>
             </div>
             <div class="row">
             <span class="right">Changed:</span><span id="ev_changed"></span>
             </form>
             </div>
             </div>
         EOM;
        // <div class="row"><span></span><span><input type="button" name="delete" value="Delete" onclick="hxl_submit_form(event);" ></span></div>
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);}
    /**
     * show the recent events for editing
     * @return void
     */
    private function events2Edit(): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
        global $status;
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        unset($status->lastShown);
        echo <<< EOM
            <div id="main" x-action="replace">
            <h2>Recent events</h2>
            <div id="recents" class="table" >
            $lt
            </div>
            <div id="sentinel" action="$scriptURL/more_events">more...</div>
            </div>
            <script>watch4more();</script>
        EOM;
    }
    /**
     * @return void
     */
    private function presentResults(): void
    {
        global $status;
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' calculating...');
        $calc = new Calculator();
        $calc->calcHierarchy();
        $calc->calculate();
        $status->resultYearWeek = 999901;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        echo <<< EOM
            <div id="main" x-action="replace">
            <h2>Results</h2>
            <div id="sentinel" action="$scriptURL/more_results">first and more...</div>
            </div>
            <script>watch4more();</script>
        EOM;

    }
    /**
     * @return void
     */
    public static function More_Results(): void
    {
        echo '<div id="sentinel" x-action="remove">removing</div>';
        $tr = new Tracker();
        $tr->showMoreResults();

    }

    /**
     * @return void
     */
    private function showMoreResults(): void
    {
        global $status;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $db = Db\DbCtx::getCtx();
        $sql = 'SELECT * FROM `${prefix}Accounted` WHERE `YearWeek` < :yw ORDER by `YearWeek` DESC, `Activity` ';
        $res = $db->sqlAndRows($sql, 'Accounted', ['yw' => $status->resultYearWeek]);
        $weeks = 0;
        $weekArr = [];
        $weekDates = [];
        foreach ($res as $row) {
            if ($row->YearWeek != $status->resultYearWeek) {
                if (count($weekArr) > 0) {
                    $this->printWeek($weekArr,$weekDates);
                    $weekArr = [];
                }
                $weeks += 1;
                if ($weeks > 4) {
                    break;
                }
                $yw = $row->YearWeek;
                $status->resultYearWeek = $yw;
                $year = intdiv($yw, 100);
                $week = $yw % 100;
                printf('<h3 x-action="append" x-id="main"> Year %4d, week %02d</h3>', $year, $week);
                for($i=0;$i<7;$i++){
                    $d=new \DateTime();
                    $d->setISODate($year,$week,$i+1);
                    $weekDates[$i]=$d->format('Y-m-d');
                }
            }
            $weekArr[$row->Activity][$row->WeekDay] = $row->DayTotal;
        }
        if (count($weekArr) > 0) {
            $this->printWeek($weekArr,$weekDates);
            $week = [];
        }
        if ($weeks > 0) {
            echo <<< EOM
                <div id="sentinel" action="$scriptURL/more_results" x-action="append" x-id="main">more...</div>
                <script>watch4more();</script>
            EOM;
        }
    }

    /**
     * @param array $week
     * @return void
     * @param mixed $weekDates
     */
    private function printWeek(array $week,$weekDates): void
    {
        echo <<< EOM
            <div x-action="append" x-id="main">
            <table><thead><tr>
            <th>Activity</th>
            <th data-tooltip="$weekDates[0]">Mon</th>
            <th data-tooltip="$weekDates[1]">Tue</th>
            <th data-tooltip="$weekDates[2]">Wed</th>
            <th data-tooltip="$weekDates[3]">Thu</th>
            <th data-tooltip="$weekDates[4]">Fri</th>
            <th data-tooltip="$weekDates[5]">Sat</th>
            <th data-tooltip="$weekDates[6]">Sun</th>
            </tr></thead><tbody>
        EOM;
        foreach ($week as $act => $acc) {
            $man = $acc[0] ?? '';
            $tue = $acc[1] ?? '';
            $wed = $acc[2] ?? '';
            $thu = $acc[3] ?? '';
            $fri = $acc[4] ?? '';
            $sat = $acc[5] ?? '';
            $sun = $acc[6] ?? '';
            echo <<< EOM
                <tr><td class="right">$act</td>
                <td>$man</td><td>$tue</td><td>$wed</td><td>$thu</td><td>$fri</td><td>$sat</td><td>$sun</td>
                </tr>
            EOM;
        }
        echo <<< EOM
            </tbody></table></div>
        EOM;
    }
    /**
     * @return void
     */
    public  function activities2Configure(): void
    {
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
            <div id="main" x-action="replace">
            $lt
            <h2>Activities</h2>
            <div id="hierarchy">
            <form action="$scriptURL/show_activity" onsubmit="return false;" onclick="hxl_submit_form(event);">
        EOM;
        $calc = new Calculator();
        $calc->calcHierarchy();
        foreach ($calc->levels as $activity => $level) {
            if ($level > 0) {
                continue;
            }
            $calc->showTree($activity);
        }
        echo <<< EOM
            </form>
            </div>
            <form action="$scriptURL/show_activity" onsubmit="return false;" onclick="hxl_submit_form(event);">
        EOM;
        $this->showAllActivities();
        echo <<< EOM
            </form>
            </div>
        EOM;

    }
    /**
     * @return void
     * @param mixed $activity
     */
    public static function Show_Activity($activity): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
        if(is_null($activity)){
            error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__.' activity is null');
            return;
        }
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        // $activity=$_POST["name"];
        $db = Db\DbCtx::getCtx();
        $actRow = $db->findRows('Activity', ["Activity" => $activity])->current();
        if (is_null($actRow)) {
            $actRow = new Db\Activity();
            $actRow->Activity = $activity;
            $db->storeRow($actRow);
        }
        $cbChecked = ($actRow->Results ?? '') ? 'checked' : '';
        $activities = $db->query('SELECT DISTINCT Activity FROM `${prefix}Event` order by Started desc');
        $activities = array_map(fn($it) => $it[0], $activities);
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
            <div id="main" x-action="replace">
            $lt
            <h2>Activity</h2>
            <form action="$scriptURL/edit_activity" onsubmit="return false;" >
            <input type="hidden" name="original" value="$activity">
            <div class="table">
            <div class="row">
            <label class="right">Activity</label>
            <input type="text" name="activity" value="$activity" placeholder="name for activity" onchange="hxl_submit_form(event);" >
            </div>
            <div class="row">
            <span class="right">Time sheet</span>
            <label for="results">
            <input type="checkbox" name="results" id="results" $cbChecked onchange="hxl_submit_form(event);">
            <span>create results</span>
            </label>
            </div>
            <div class="row">
            <label class="right" for="sel_parent">Parent project</label>
            <span>
            <select name="sel_parent" id="sel_parent" onchange="hxl_submit_form(event);" >
        EOM;
        foreach ($activities as $act) {
            $selected = $act == ($actRow->Parent ?? '') ? ' selected ' : '';
            echo <<< EOM
                <option value="$act" $selected>$act</option>
            EOM;
        }
        $selected = is_null($actRow->Parent) ? 'selected' : '';
        echo <<< EOM
            <option value="" $selected>-</option>
            </select>
            </span>
            </div>
            <div class="row">
                <span></span>
                <span><input type="button" name="delete" value="Delete" onclick="hxl_submit_form(event);"></span>
            </div>
            <div class="row">
                <span class="right">Changed:</span><span id="conf_changed"></span>
            </div>
            </div>
            </form>
            </div>
        EOM;

    }

    /**
     * Removes the row with that id
     * @return void
     * @param mixed $ev
     */
    public function deleteRow($ev): void
    {
        error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__);
        echo <<<EOM
            <div id="ev_$ev->Id" x-action="remove"></div>
        EOM;
    }

}
