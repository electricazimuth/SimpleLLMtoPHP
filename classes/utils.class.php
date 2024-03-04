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
            'bitch' => 1,
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

    /*
    * Extract data - removing any zero scored items
    * Love: 6
    * Friendship: 7
    * Heartbreak: 2
    */
    public static function ProcessTags($scored_tags){

        $result = array('matched' => false, 'results' => array() );
        $s_pattern = '/([a-z ]+)[-: ]*([0-9]+)/i';
        $s_result = preg_match_all($s_pattern, $scored_tags, $s_matches);
        //var_dump($s_result);
        // 0 => full match, 1 => first match (tag) 2 => second match
        if( $s_result > 0 && is_array($s_matches) && $s_matches > 0 ){
            $tags = $s_matches[1];
            $scores = $s_matches[2];
            if( count($tags) == count($scores)){
                $result['matched'] = true;

                foreach($tags as $k => $v){
                    if( $scores[$k] > 0){
                        $result['results'][$v] = $scores[$k];
                    } 
                }
            }
        }

        return $result;//json_encode($result,true);
    } 

    public static function SliceLyricSections($lyrics){

        //break them by the square bracket seperators
        $regex = '/\n?\[([^]]+)\]\n/i'; // match things in square brackets with line breaks
        $found = preg_match_all($regex, $lyrics, $matches, PREG_OFFSET_CAPTURE);
        //var_dump($matches);
        $chunkpositions = array();
        $chunks = array();

        //var_dump($matches);
        //echo '<hr />';
        $lastpos = 0;
        if($found > 0 && is_array($matches[0])){
            foreach($matches[0] as $k => $matchd){

                //var_dump($matchd);
                //echo '<hr />';
                if( is_array($matchd)){
                    //get next element position
                    if( $k < count($matches[0]) && isset($matches[0][$k+1]) ){
                        $nextpos = $matches[0][$k+1][1];
                    }else{
                        $nextpos = strlen($lyrics);
                    }

                    //$chunkpositions[] = $matchd[0] . ' at position ' . $matchd[1] . ' next pos ' . $nextpos;
                    $start_pos = $matchd[1] + strlen($matchd[0]);
                    $length = $nextpos - $start_pos;
                    $slice = trim( substr($lyrics, $start_pos, $length) );
                    if( !empty($slice)){
                        $chunks[] = $slice;
                    }
                    //var_dump($matchd[0] . ' ' .$matchd[1] );
                    //    echo '<hr />';

                    

                }
            }
        }

        //break them by double line breaks
        $outchunksbreak = array();
        $doublelinebreaks = "\n\n";
        if( count($chunks) ){
            foreach( $chunks as $chunk ){
                if( strpos($chunk, $doublelinebreaks)){
                    $splitbylines = explode($doublelinebreaks, $chunk);
                    foreach($splitbylines as $lines){
                        $outchunksbreak[] = $lines;
                    }
                }else{
                    $outchunksbreak[] = $chunk;
                }
            }
        }

        //limit to 10 lines per section
        $outchunks = array();
        foreach($outchunksbreak as $chunk){
            $lines = explode( "\n", $chunk);
            if( count($lines) > 10 ){
                for ($i = 0; $i < count($lines); $i += 8) {
                    // Extract 4 elements starting from the current position
                    $slice = array_slice($lines, $i, 8);
                    // Join the elements into a single string using a comma as the separator
                    $outchunks[] = implode("\n", $slice);

                }

            }else{
                $outchunks[] = $chunk;
            }
        }

        //$chunks[] = substr($lyrics, $matchd[1] )        
        
        return $outchunks;
    } 

    // for terminal output
    public static function CliProgressBar($done, $total, $width=50, $memuse = false) {
        if( self::IsCli() ){
            $perc = round(($done * 100) / $total);
            $bar = round(($width * $perc) / 100);
            //$disp = number_format($perc*100, 0);
            $info = "  $done/$total";
            if($memuse){
                $mb = round( memory_get_usage() /1048576,2);
                $memory_limit = ini_get('memory_limit'); 
                $info .= " [$mb/$memory_limit]";
            }
            
            echo sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), $info);
            flush();
        }
    }

    public static function IsCli() {
        if ( defined('STDIN') ) {
            return true;
        }

        if ( php_sapi_name() === 'cli' ) {
            return true;
        }

        if ( array_key_exists('SHELL', $_ENV) ) {
            return true;
        }

        if ( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
            return true;
        } 

        if ( !array_key_exists('REQUEST_METHOD', $_SERVER) ) {
            return true;
        }

        return false;
    }

    
}
    