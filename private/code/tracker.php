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
    private function showTopBox(): void{
        global $status;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $againStyle = '';
        $editStyle = '';
        $resultStyle = '';
        $configStyle = '';
        $current=$status->current ?? 'Again';
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
     * @return void
     */
    public static function Change_Mode(): void
    {
        global $status;
        $status->mode = $_POST['name'] ?? 'again';
        $tracker = new Tracker();
        $tracker->showTopBox();
        $tracker->showMain();
        error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__);
    }
    /**
     * shows the main content depending on the status
     * @return void
     */
    private function showMain(): void
    {
        global $status;
        match ($status->mode ?? 'again') {
            default => $this->showAgainMain()
        };
    }
    /**
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
     * @return void
     */
    private function showAllActivities(): void
    {
        $dbCtx = Db\DbCtx::getCtx();
        echo '<div class="boxed">';
        $sql = <<< 'EOS'
            WITH C_RN AS (
            SELECT *, ROW_NUMBER() OVER (PARTITION BY Activity order by Started DESC) AS RN FROM ${prefix}Event
            ) SELECT * from C_RN WHERE RN=1 ORDER By Started DESC
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
    {
    }

}
