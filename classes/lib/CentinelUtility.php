<?php
    function determineCardType($Card_Number){
                
        // VISA, MASTERCARD, JCB, AMEX, UNKNOWN

        $cardType = "UNKNOWN";

        if ((strlen($Card_Number) == 16) && (substr($Card_Number, 0, 1) == "4"))
        $cardType = "VISA";
        else if (strlen($Card_Number) == 13 && substr($Card_Number, 0, 1) == "5")
        $cardType = "MASTERCARD";
        else if (strlen($Card_Number) == 16 && substr($Card_Number, 0, 1) == "5")
        $cardType = "MASTERCARD";
        else if (strlen($Card_Number) == 15  && substr($Card_Number, 0, 4)== "2131")
        $cardType = "JCB";
        else if (strlen($Card_Number) == 15 && substr($Card_Number, 0, 4) == "1800")
        $cardType = "JCB";
        else if (strlen($Card_Number) == 16 && substr($Card_Number, 0, 1) == "3")
        $cardType = "JCB";
        else if (strlen($Card_Number) == 15 && substr($Card_Number, 0, 2) == "34")
        $cardType = "AMEX";
        else if (strlen($Card_Number) == 15 && substr($Card_Number, 0, 2) == "37")
        $cardType = "AMEX";

        return $cardType ;
    }

    function redirectBrowser($url) {

        $protocol = 'http://';

        if (getenv('HTTPS') == 'on'){
            $protocol = 'https://';
        }

        $url = $protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/'.$url;

        header('Location: ' . $url);
        exit();
    }

    /**
     * Clears session of any variables that begin with "Centinel_"
     */
    function clearCentinelSession() {
        unset($_SESSION['Message']);
        foreach($_SESSION as $key => $value) {
            if(preg_match("/^Centinel_.*/", $key) > 0) {
                unset($_SESSION[$key]);
            }

        }

    } 

    /**
     * Pretty-print centinel request/response
     *
     */
    function prettyPrintData($title, $dataArray) {

        $ret = "<table>\n";

        $ret .= "<h3>$title</h3>\n";
            
        if( is_array($dataArray) ) {

            $fields = $dataArray;
            foreach($fields as $key => $value) {
                if($key != "") {
                    $ret .= "<tr>\n";
                    $ret .= "\t<td><b>&nbsp;&nbsp;$key</b></td>\n";
                    $ret .= "\t<td> : </td>\n";
                    $ret .= "\t<td style='font-family: Courier; font-size: 10pt;'>$value</td>\n";
                    $ret .= "</tr>\n";
                }
            }

        } else {

            $ret .= "<tr>\n";
            $ret .= "\t<td><b>&nbsp;&nbsp;ErrorNo</b></td>\n";
            $ret .= "\t<td> : </td>\n";
            $ret .= "\t<td></td>\n";
            $ret .= "</tr>\n";

            $ret .= "<tr>\n";
            $ret .= "\t<td><b>&nbsp;&nbsp;ErrorDesc</b></td>\n";
            $ret .= "\t<td> : </td>\n";
            $ret .= "\t<td style='font-family: Courier; font-size: 10pt;'>No data</td>\n";
            $ret .= "</tr>\n";

        }

        $ret .= "</table>";

        return $ret;

    }

?>
