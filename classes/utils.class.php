<?php
/*
* Common Statics / Utilities - just a place to keep reused methods for the API
*
**** 


*/
class Utils {

    private static $header_identity = 'disc';
    private static $header_registration = 'resolution';
    private static $logfile = 'raceapi.log.txt';
        /**
         * Construct won't be called inside this class and is uncallable from
         * the outside. This prevents instantiating this class.
         * This is by purpose, because we want a static class.
         */
        
    private function __construct() {}

    // Utils::GetIdentity()
    public static function GetIdentity() {

        $headers = apache_request_headers();
        if( is_array($headers) && isset($headers[self::$header_identity]) && is_string($headers[self::$header_identity]) && strlen($headers[self::$header_identity]) > 1 ){

            return $headers[self::$header_identity];
    
        }

        return false;
    }

    public static function GetRegistration() {

        $headers = apache_request_headers();
        if( is_array($headers) && isset($headers[self::$header_registration]) && is_string($headers[self::$header_registration]) && strlen($headers[self::$header_registration]) > 1 ){

            return $headers[self::$header_registration];
    
        }

        return false;
    }

    //search a string for swaerwords, count thema nd score them return the total
    public static function GetSwearCount($string){

        $results = array('score' => 0, 'matches' => array() );
        
        foreach( array(
            'cunt' => 5,
            'nigger' => 3,
            'nigga' => 3,
            'fucker' => 2,
//            'fuckin' => 2,
            'fuck' => 2,
            'shit' => 1,
            'piss' => 1, 
            'pussy' => 1,
            'dick' => 1, 
            'cock' => 1, 
            'shit' => 1, 
            'twat' => 1, 
            'bollock' => 1,
            'hoe' => 1,
            'slut' => 1, 
            'ass' => 1,
            'wank' => 1

        ) as $word => $score ){

            $regex = '/\b' . $word . '/i';
            $num_matches = preg_match_all( $regex, $string);
            $results['score'] += $num_matches * $score;
            if( $num_matches > 0){
                $results['matches'][$word] = $num_matches;
                //array_push($results['matches'], array($word =>$num_matches));
            }
        }

        return $results;



    }


    public static function GetRealIpAddr() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
            
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            
        } elseif (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    //really simple validation
    public static function IsValid( $data, $validations) {
        $returnData = array();
        $okay = true;

        foreach( $validations as $vkey => $validation):

            if( isset( $data[$vkey] ) ):
                switch( $validation ):
                    case 'int':
                        $returnData[$vkey] = (int)$data[$vkey];
                        if( $returnData[$vkey] != $data[$vkey]):
                            //echo $vkey;
                            $okay = false;
                        endif;

                    break;
                    case 'float':
                       // $returnData[$vkey] = floatval($data[$vkey]);
                        //$regex = '/^-?(?:\d+|\d*\.\d+)$/';
                        //preg_match('/(foo)(bar)(baz)/', 'foobarbaz', $matches, PREG_OFFSET_CAPTURE);

                        $returnData[$vkey] = filter_var($data[$vkey], FILTER_VALIDATE_FLOAT);
                        if( $returnData[$vkey] === false):
                            $okay = false;
                        endif;

                    break;
                    case "string":
                        if( !is_string($data[$vkey]) ):

                            $returnData[$vkey] = strval($data[$vkey]);
                        else:
                            $returnData[$vkey] = $data[$vkey];
                        endif;
                        if($returnData[$vkey] == ""):
                            //echo $vkey;
                            $okay = false;
                        endif;

                    break;
                    case "return":
                        $returnData[$vkey] = $data[$vkey];
                    break;


                endswitch;

            elseif( $validation == "return"):
                $returnData[$vkey] = "";
            else:

                $okay = false;
            endif;
        endforeach;

        if( !$okay ){   
            return false;
        }

        return $returnData;
        
    }


   

    public static function ArrayFieldCopy($fields, $array){
        //$include = array( 'name', 'level','finish_time', 'video_url', 'animation_url', 'address');
        $response = array();
        foreach($fields as $k){
            if( isset($array[$k]) ){
                $response[$k] = $array[$k];
            }
        }
        return $response;
    }


    //output should match AzimuthModel.GeneralModel in Unity c#
    public static function GeneralResponse($t_message, $t_okay, $t_id, $t_error = ""){
        $response = array(
            'message' => strval($t_message),
            'okay' => boolval($t_okay),
            'id' => intval($t_id),
            'error' => strval($t_error)
        );
        return $response;
    }

    

    public static function HandleFileUploadByDate($file, $folder, $prefix = 'brn', $max_size = 2097152){
        //check files
        $response = false;
        if( is_array($file['name']) ):

            self::DoingItWrong("Multi file uploads not supported");

        else:
            //check file size
            if( $file['size'] > $max_size):
                self::DoingItWrong("File too large");
            else:

                //store files in a per day folder structure
                $today = date('ymd'); // backwards indexes better
                $uploaddir = ABSPATH . $folder . '/' . $today;
                
                self::CheckDir($uploaddir);
                $filename = $prefix .'_' . uniqid() . '.dat';

                if (move_uploaded_file($file['tmp_name'], $uploaddir . '/' .$filename )):

                    return $today . '/' . $filename;

                else:
                    self::DoingItWrong("Couldn't process file upload");
                endif;

            endif;

        endif;

        return false;

    }


    public static function CheckDir($dir) {
       
        if( !is_dir( $dir) ):

            if ( $return = mkdir($dir, 0777) ):
                chmod($dir, 0777);
            else:
                self::DoingItWrong("File upload folder issue");
            endif;

        endif;

        return true;
       
    }

    public static function Log($string , $include_date = true ) {

        $fullpath = ABSPATH . self::$logfile; //DIRECTORY_SEPARATOR .  
        $string = date(DATE_RFC2822) . "\n". $string;
        file_put_contents( $fullpath, $string, FILE_APPEND );//, fopen($config['url'], 'a'));

    }

    public static function DbLog($title, $info, $db ) {
        //$ip = self::GetRealIpAddr();
        if( strlen($info) > 255){ //just incase of malicious bot posting
            $info = substr($info,0,252) . "...";
        }
        $q = "INSERT INTO `x_debug_log` (`title`, `info`) VALUES (?, ?)";
        $result = $db->sendQueryP( $q, array($title, $info), "ss" );

    }


    public static function DoingItWrong(string $string){
        $response = array("error" => $string );
        echo json_encode($response);
        die();
    }

    /* expects something like
     * 
     * 
    swearing:   0
    offensive: 1
    provocative:1
     *
     */
    public static function ProcessRatings($llm_output){

        $result = array();
        foreach( array('swearing','offensive','provocative') as $key ){
       
            $s_pattern = '/' . $key . ':?\s*([0-9]+)/i'; // the word with possible colon with possible white space then number - case insensitive
            $s_result = preg_match($s_pattern, $llm_output, $s_matches);
            if( $s_result == 1 && count($s_matches) > 1){
                $result[$key] = intval($s_matches[1]);
            }else{
                $result[$key] = -1;
            }
        }
        return $result;
    } 
}
    