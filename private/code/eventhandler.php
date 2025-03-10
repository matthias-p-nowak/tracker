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

}