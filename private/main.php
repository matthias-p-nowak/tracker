<?php
error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);

$config = join(DIRECTORY_SEPARATOR, [__DIR__, 'main.ini']);
if (file_exists($config)) {
    $config = parse_ini_file($config, true);
} else {
    error_log('no site file found at ' . $config);
    $config = [];
}

$config = (object) $config;
$config->database = (object) $config->database;

if (isset($config->timezone)) {
    date_default_timezone_set($config->timezone);
}

// setting auto loader to this folder
$oldPath = get_include_path();
$newPath = join(PATH_SEPARATOR, [$oldPath, __DIR__]);
set_include_path($newPath);
foreach (spl_autoload_functions() as $f) {
    spl_autoload_unregister($f);
}
spl_autoload_extensions('.php');
spl_autoload_register();

session_start();

try {
    if(!isset($_SESSION['authenticated'])){
        Code\Login::Check_Login();
        return;
    }
    $status = ($_SESSION['status'] ??= new stdClass());
    
    $res = $_SERVER['PATH_INFO'] ?? '/home';
    match ($res) {
        '/change_mode' => Code\Tracker::Change_Mode(),
        '/edit_event' => Code\EventHandler::Edit_Event(),
        '/event_again' => Code\EventHandler::Event_Again(),
        '/home' => Code\Tracker::Show_Home(),
        '/more_events' => Code\EventHandler::More_Events(),
        '/more_results' => Code\Tracker::More_Results(),
        '/show_event' => Code\EventHandler::Show_Event(),
        default => error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__.' executing default action for '.$res ),
    };
} catch (Exception $ex) {
    error_log("got exception", $ex);
} finally {
    $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
    $time = number_format($time, 4);
    $included = \get_included_files();
    $incCnt = \count($included);
    $files = \print_r($included, true);
    error_log("used  $time seconds and $incCnt files: $files");
}

