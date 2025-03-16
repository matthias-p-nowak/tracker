<?php
namespace Code;

class EventHandler{


    /**
     * @return void
     */
    public static function Event_Again(): void
    {
        global $status;
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . print_r($_POST, true));
        if (isset($_POST['error'])) {
            error_log($_POST['error']);
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        $ev = new EventHandler();
        $ev->eventAgain($ip);
    }
    
    /**
     * @return void
     * @param mixed $ip
     */
    private function eventAgain($ip): void
    {
        $id = $_POST['id'];
        $ev = new Db\Event();
        $db = Db\DbCtx::getCtx();
        if ($id > -1) {
            $row = $db->findRows('Event', ['id' => $id], ' limit 1')->current();
            $ev->Activity = $row->Activity;
        } else {
            $ev->Activity = '';
        }
        $ev->IP = $ip;
        if (isset($_POST['latitude'])) {
            $ev->Latitude = $_POST['latitude'];
        }
        if (isset($_POST['longitude'])) {
            $ev->Longitude = $_POST['longitude'];
        }
        $db->storeRow($ev);
        $ev=$db->findRows('Event',[],' order by Started desc limit 1');
        $ev=\iterator_to_array($ev)[0];
        $tr=new Tracker();
        $tr->editEvent($ev);
    }

    /**
     * Showing more events
     * @return void
     */
    public static function More_Events(): void
    {
        global $status;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $ls = $status->lastShown ?? ((new \DateTime())->format('y-m-d H:i:s'));
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' more event...');
        $db = Db\DbCtx::getCtx();
        $m = 0;
        $sql = <<< 'EOS'
        SELECT * FROM `${prefix}Event` WHERE Started < :st ORDER BY Started DESC limit 5;
        EOS;
        $evs = $db->sqlAndRows($sql, 'Event', ['st' => $ls]);
        echo <<< EOM
        <div id="sentinel" x-action="remove">removing</div>
        EOM;
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        foreach ($evs as $ev) {
            $status->lastShown = $ev->Started;
            echo <<< EOM
            <form  action="$scriptURL/show_event" class="row" id="ev_$ev->Id"
                onsubmit="return false;" onclick="hxl_submit_form(event);"
                x-action="append" x-id="recents">
            $lt
            <input type="hidden" name="id" value="{$ev->Id}">
            <span >{$ev->Started}</span><span>{$ev->Activity}<br >{$ev->Details}</span>
            <span>{$ev->IP}<br>{$ev->Latitude} / {$ev->Longitude}</span>
            <span name="delete">x</span>
            </form>
            EOM;
            // onclick="hxl_submit_form(event);"
            $m += 1;
        }
        echo <<<EOM
        EOM;
        if ($m > 0) {
            echo <<< EOM
            <div id="sentinel" action="$scriptURL/more_events" x-action="append" x-id="main">more...</div>
            <script>watch4more();</script>
            EOM;
        } else {
            $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
            echo <<< EOM
            <div x-action="append" x-id="main">$lt all events shown</div>
            EOM;
        }
    }
    /**
     * Show the event for editing
     * @return void
     */
    public static function Show_Event(): void
    {
        $db = Db\DbCtx::getCtx();
        $id = $_POST["id"];
        $evs = $db->findRows('Event', ['Id' => $id]);
        $ev = $evs->current();
        $tr = new Tracker();
        if('delete' == ($_POST['name'] ?? '')){
            $db->deleteRow($ev);
            $tr->deleteRow($ev);
            //$tr->showMain();
        }else{
            $tr->editEvent($ev);
        }
    }
    /**
     * @return void
     */
    public static function Edit_Event(): void
    {
        error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__.' editing event changed='.$_POST['name']);
        $db=Db\DbCtx::getCtx();
        $id=$_POST['id'];
        $name=$_POST["name"];
        if($name === 'delete'){
            $ev=new Db\Event();
            $ev->Id=$id;
            $db->deleteRow($ev);
            $lt='<!-- '.__FILE__.':'.__LINE__.' '.' -->';
            echo <<< EOM
            <div id="main" x-action="replace">
            $lt
            <h4>Row deleted</h4>
            </div
            EOM;
            return;
        }
        $row=$db->findRows('Event',['Id'=>$id])->current();
        $row->Activity=$_POST['activity'];
        $row->Details=$_POST['details'];
        $row->Started=$_POST['started'];
        $db->storeRow($row);
        $now= new \DateTime();
        $now=$now->format('H:i:s');
        echo <<< EOM
            <span id="ev_changed" x-action="replace">$now</span>
        EOM;
    }

}