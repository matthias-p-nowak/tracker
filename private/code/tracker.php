<?php
namespace Code;

class Tracker
{
    /**
     * @return void
     */
    public static function ShowHome(): void
    {
        echo <<< EOM
        <dialog x-action="remove" id="login_dialog">remove</dialog>
        EOM;
        $tracker = new Tracker();
        $tracker->show_Home();
    }
    /**
     * @return void
     */
    private function show_Home(): void
    {
        global $status;
        $status->mode ??= 'again';
        match ($status->mode) {
            default => $this->show_Default(),
        };
    }
    /**
     * @return void
     */
    private function show_Default(): void
    {

    }

}
