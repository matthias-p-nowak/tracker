<?php

namespace Code;

/**
 * Handles all login related functions
 */
class Login
{
    /**
     * @return void
     */
    public static function Check_Login(): void
    {
        global $status, $config;
        $login = new Login();
        $dbctx = Db\DbCtx::getCtx();
        $user = $_POST['username'] ?? '';
        if(isset($_COOKIE['tracker'])){
            $pwdsIt = $dbctx->findRows('Password');
            $cookie=$_COOKIE['tracker'];
            foreach ($pwdsIt as $pwEntry) {
                if($cookie == $pwEntry->Cookie){
                    self::Good_Login($pwEntry);
                    return;
                }
            }
        }
        $pw = $_POST['password'] ?? '';
        if (in_array($user, $config->users)) {
            error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
            switch ($_POST['name'] ?? 'none') {
                case 'send_pw':
                    $login->sendNewPassword($user);
                    return;
                case 'login':
                    $pwdsIt = $dbctx->findRows('Password');
                    foreach ($pwdsIt as $pwEntry) {
                        if (password_verify($pw, $pwEntry->Hash)) {
                            self::Good_Login($pwEntry);
                            return;
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        $login->showLoginDialog();
    }
    /**
     * @return void
     */
    private function showLoginDialog(): void
    {
        global $status;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        echo <<< EOM
        <dialog x-action="replace" class="normal" id="login_dialog">
        <h1>Login</h1>
        <form action="$scriptURL/login" onsubmit="return false;" >
        <table>
        <tbody>
        <tr>
            <td><label for="username">User</label></td>
            <td><input type="email" name="username" autofocus tabindex="1" placeholder="your email address"></td>
        </tr>
        <tr>
            <td><label for="password">Password</label></td>
            <td><input type="password" name="password"  tabindex="2" placeholder="your password"></td>
        </tr>
        <tr>
            <td colspan="2">
                <button  name="send_pw" tabindex="4" onclick="hxl_submit_form(event);">send new password</button>
                <button name="login" tabindex="3" onclick="hxl_submit_form(event);">login</button>
            </td>
        </tr>
        </tbody>
        </table>
        <form>
        </dialog>
        EOM;
    }

    const ALFABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ23456789';
    /**
     * @return void
     * @param mixed $email
     */
    private function sendNewPassword($email): void
    {
        global $config;
        $pw = '';
        $e = 1;
        $l = strlen(SELF::ALFABET);
        while ($e < ($config->pw_complexity ?? 1e12)) {
            $e *= $l;
            $i = random_int(0, $l - 1);
            $pw .= SELF::ALFABET[$i];
        }
        $cookie = \base64_encode(\random_bytes(48));
        $message = <<< EOM
            Hello,

            please use the following password next time '$pw' (Remove the quotes).

            Greetings
        EOM;
        $adhead = ['From' => ($config->from ?? 'no from specified')];
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' sending password');
        $r = \mail($email, 'password provided', $message, $adhead);
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . "{$r}");
        $hpw = password_hash($pw, PASSWORD_DEFAULT);
        if ($r) {
            echo <<< EOM
                <script>alert('An email has been sent. Wait for email and try again.');</script>
            EOM;
            $pw = new Db\Password();
            $pw->Cookie = $cookie;
            $pw->Hash = $hpw;
            $dbctx = Db\DbCtx::getCtx();
            $dbctx->storeRow($pw);

        } else {
            http_response_code(500);
            echo <<< EOM
            Could not send an email to $email.
            EOM;
        }

    }
    /**
     * @return void
     * @param mixed $pwEntry the database entry for this login
     */
    private static function Good_Login($pwEntry): void
    {
        $now = new \DateTime('now');
        $pwEntry->Used = $now->format('Y-m-d H:i:s');
        $pwEntry->ctx->storeRow($pwEntry);
        $cookieOptions = array(
            'expires' => time() + 1209600,
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax',
        );
        \setcookie('tracker', $pwEntry->Cookie, $cookieOptions);
        Tracker::Show_Home();
        $_SESSION['authenticated']=true;
        $dbctx=Db\DbCtx::getCtx();
        // TODO find latest activity and store that
    }

}
