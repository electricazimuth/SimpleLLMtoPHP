<?php
/*
 * converts generated lyrics into phones
 * add in data ready for sending to the engine room
 * 
 * RESET 
 * UPDATE `generation_tests` SET processed = 0;
 * TRUNCATE TABLE `generation_lines` 
 */

set_time_limit(0);

$loginfo = array();
require 'bootstrapper.inc.php';

//require __DIR__ . '/vendor/autoload.php';
die("comment out line 17 to run" );

$stage = 0;
$next_stage = $stage + 1;


$logname = $stage . '.phonegen';
$timing_key = 'harmonic';
$is_test_run = false;//true;
$continue_running = true;
$max_phone_count = 40;
$loginfo = array();
//clear out any previous entries

$test_q = "SELECT COUNT(*) as counter FROM generation_tests WHERE processed = " . $stage ;
$test_rows = $registry->db->getRows($test_q);
$rows_to_do = (int)$test_rows[0]['counter'];
$auto_stop = false;
$num_done = 0;
$total = $rows_to_do;
$limit = ($is_test_run)? 5 : 25;




$split_phones_str = file_get_contents('data/split_on_cmuphones.txt');
$split_phones = preg_split('/\s+/', $split_phones_str);

$notes_file = 'data/blackbird/4-only-waiting-moment.json';
$notes_str = file_get_contents($notes_file);
$notes = json_decode($notes_str, true);


$ph_timings_file = 'data/larapa_timing.json';
$ph_timings_str = file_get_contents($ph_timings_file);
$ph_timings_flat = json_decode($ph_timings_str, true);
$ph_timings = array();
foreach($ph_timings_flat as $row){
    $ph_timings[ $row['phone'] ] = $row;
}

$note_seq = explode(' ',$notes['note_seq']);
$note_dur = explode(' ',$notes['note_dur']);

if( count($note_seq) != count($note_dur) ){
    echo 'ERROR note seq (' . count($note_seq) . ') has diff count to not dur (' . count($note_dur) . ') in ' . $notes_file;
    die();
}

$total_note_duration = array_sum($note_dur);

$notes_data = array();
$_running_note_dur = 0.0;
//create an array with key as note position through sequence
foreach($note_dur as $k => $note_d){
    $notes_data[ strval($_running_note_dur) ] = $note_seq[$k];
    $_running_note_dur += $note_d;
}

$slur_0 = array_fill(0, count($note_seq), 0);


$template = array(
    "offset" => 0.0,
    "text" => '',
    "ph_seq" => '',
    "ph_num" => '',
    "ph_dur" => '',
    "note_seq" => $notes['note_seq'],
    "note_dur" => $notes['note_dur'],
    "note_slur" => implode(' ', $slur_0)
);



while($rows_to_do > 5 && !$auto_stop){

    Utils::CliProgressBar($num_done, $total);
    $time_start = microtime(true);
    $q = "SELECT * FROM generation_tests WHERE processed = " . $stage . " ORDER BY row_id ASC LIMIT " . $limit;
    $rows = $registry->db->getRows($q);

    $num_done += count($rows);

    foreach( $rows as $row){

        $num_errors = 0;   
        
        if( strlen($row['lyrics']) > 0 ){
    
            $alphanumeric_chars_spaces = preg_replace("/[\-,\.]/", ' ', strtolower($row['lyrics']));
            $alphanumeric_chars = preg_replace("/[^A-Za-z0-9'\s]/", '', $alphanumeric_chars_spaces);
    
            //note array_filter - used to remove empty elements
            $lines = array_filter(explode("\n", $alphanumeric_chars ));
            
            //lyrics will be an array (keyed by line number) of an array of words
            $lyrics_lines = array();
            if( count( $lines ) > 0){
                foreach($lines as $line){
                    $lyrics_lines[] = array_filter(explode(" ", $line));
                }
            }

            //$rand_keys = array_rand($lyrics);
            //$random_line = $lyrics[$rand_keys];

            $line_number = 1;
            foreach($lyrics_lines as $line_words){

                $line_words = check_convert_numbers($line_words);

                $errored = false;

                $qmarks = array_fill(0, count($line_words), '?');
                $q = "SELECT word, phones FROM `arpa_dict` WHERE word IN (" . implode(',', $qmarks) . ")";
                $arpa_rows = $registry->db->getRowsP($q, $line_words, str_repeat("s", count($line_words)), 'word');
        
                $phones = array();
                foreach($line_words as $word){
                    if( array_key_exists($word, $arpa_rows)){
        
                        $phones[] = $arpa_rows[$word]['phones'];
        
                    }else{
                        //log error - missing word in dictionary
                        $errored = true;
                        echo 'ERRORED ' . $word . ' not in dictionary' . PHP_EOL;
                        $num_errors++;
                    }
                }
                if( !$errored ){
                    //check length 
                    //TODO - we should probably check the vowel count too...
                    $allphones = explode(' ', implode(' ', $phones) );
                    if( count($allphones) > $max_phone_count ){
                        $errored = true;
                        $num_errors++;
                        echo 'ERRORED ' . count($allphones) . ' is too many phones (max: ' . $max_phone_count . ')' . PHP_EOL;
                    }
        
                }

                //save
                if( !$errored ){


                    $phone_joined = join_phone_words_maybe_add_pauses($phones);
                    $phone_joined_split = explode(' ', $phone_joined);
                    $phone_count = count($phone_joined_split);

                    $ph_dur_0 = array_fill(0, $phone_count, 0);
                    $ph_num = ph_num_calc( $phone_joined_split, $split_phones);

                    $lyric_line = implode(' ' , $line_words);

                    //build ds style data
                    $ds_data = $template;
                    $ds_data['text'] = $lyric_line;
                    $ds_data['ph_seq'] = $phone_joined;
                    $ds_data['ph_num'] = implode(' ',$ph_num);
                    $ds_data['ph_dur'] = implode(' ', $ph_dur_0);

                    $estimated_total_phone_duration = 0.001;
                    //need to remap notes to match the "words defined by ph_num
                    foreach($phone_joined_split as $phone){
                        if( isset( $ph_timings[$phone] ) ){

                            $estimated_total_phone_duration += $ph_timings[$phone][$timing_key];
                            
                        }else{
                            echo 'ERROR: No timing for ' . $phone;
                            die();
                        }
                        
                    }

                    //fit text to notes
                    $timing_ratio = $total_note_duration / $estimated_total_phone_duration;

                    //notes are per "word" - phones grouped by $ph_num
                    $running_duration = 0.0;
                    $word_note = array();
                    $word_dur =  array();
                    foreach( $ph_num as $ph_num_val ){
                        $running_ph_index = 0;
                        //loop through the each word to generate note lengths
                        $_duration = 0.0;
                        for($i = 0; $i < $ph_num_val; $i++){

                            
                            $_phone = $phone_joined_split[ $running_ph_index ];
                            $_duration += $ph_timings[$_phone][$timing_key] * $timing_ratio;
                            $running_ph_index++;
                            

                        }
                        $running_duration += $_duration;

                        $word_note[] = get_note_at( $running_duration, $notes_data );
                        $word_dur[] = $_duration;

                        


                    }

                    $ds_data["note_seq"] = implode(' ',$word_note);
                    $ds_data["note_dur"] = implode(' ',$word_dur);
                    $ds_data["note_slur"] = implode(' ', array_fill(0, count($word_note), 0));
                    

                    if( $is_test_run){
                        echo '<pre>';
                        echo json_encode($ds_data,JSON_PRETTY_PRINT) ;//var_export($ds_data, true );
                        echo '</pre><hr />';

                    }else{
                        //save these into the database

                        $q = "INSERT INTO `generation_lines` (`generation_row_id`, `lyric_line`, `line_number`, `phone_count`, `ds_file_initial`) VALUES (?, ?, ?, ?, ?)";
                        $registry->db->sendQueryP($q, array((int)$row['row_id'], $lyric_line, $line_number, $phone_count, json_encode($ds_data) ), "isiis");
                        $line_number++;
                    }


                }


            }

            if( !$is_test_run){
                $q = "UPDATE generation_tests SET processed = ? WHERE row_id = ?";
                $registry->db->sendQueryP($q, array($next_stage, $row['row_id']), "ii");
            }
        }

    }

    if( $is_test_run){
        $auto_stop = true;
    }

    $test_rows = $registry->db->getRows($test_q);
    $rows_to_do = (int)$test_rows[0]['counter'];

}



function get_note_at( $_time, $notes_data ){
    $note_match = $notes_data[0];
    foreach($notes_data as $note_start => $note){
        if( $_time < $note_start){
            return $note_match;
        }
        $note_match = $note;
    }

    return $note_match;

}

//returns a string with phones sepearted by spaces
// words may get AP or SP added in
function join_phone_words_maybe_add_pauses($phones){
    $phone_joined = '';

    foreach( $phones as $key => $phone ){

        if ($key === array_key_first($phones)) {
            $phone_joined = $phone;
        }else{
            $joiner = ' ';
            $rand = rand(0, 100);
            if( $rand > 95){
                $joiner = ' AP ';
            }else if( $rand > 65 ){
                $joiner = ' SP ';
            }
            $phone_joined .= $joiner . $phone;
        }

    }

    return $phone_joined;
}


function ph_num_calc($phones, $split_phones){
//def generate_ph_vowel_num( phones, split_phones):
    $ph_num = array();

    $phone_index = 0;
    $phone_count = 0;
    $ph_labels = array();
    $vowel_index = array();
    $appended = false;

    # each "syllable" gets split by phonemes in split_phones
    foreach($phones as $phone){

        $appended = false;
        $phone_count += 1;
        $ph_labels[] = $phone;
        if( in_array($phone , $split_phones) ){

            $ph_num[] = $phone_count;
            $phone_count = 0;
            $appended = true;
        }
    }

    if(!$appended && $phone_count > 0){
        $ph_num[] = $phone_count;
    }

    return $ph_num;
}


function check_convert_numbers($words){
    $output = array();
    foreach($words as $key => $word){
        
        if( is_numeric($word) ){

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $numword = $f->format( $word );

            if( strpos(' ',$numword)){

                $numwords = explode(' ', $numword);
                $output = array_merge($output, $numwords); 

            }else{

                $output[] = $numword;

            }

        }else{
            $output[] = $word;
        }
    }

    return $output;
}





//save these into the database


/*
"offset": 0.00149011612,
    "text": "BLACKBIRD SINGING IN THE DEAD OF NIGHT",
    "ph_seq": "B L AE1 K B ER0 D SP S IH1 NG IH0 NG SP IH0 N SP DH AH0 SP D EH1 D SP AH1 V SP N AY1 T",
    "ph_dur": "0.05805 0.10449 0.16254 0.10449 0.05805 0.16254 0.09288 0.03483 0.10449 0.08127 0.05805 0.10449 0.05805 0.03483 0.05805 0.05805 0.03483 0.02322 0.06966 0.03483 0.02322 0.05805 0.10449 0.08127 0.16254 0.1161 0.13932 0.17415 1.532517 0.0",
    "ph_num": "3 3 4 2 3 4 3 3 4 1",
    "note_seq": "G3 G3 G3 G3 F3 G3 A3 D4 D4",
    "note_dur": "0.313542 0.347917 0.313542 0.1625 0.1625 0.186458 0.127083 0.344792 1.96875",
    "note_slur": "0 0 0 0 0 0 0 0 0",
    "f0_seq": "210.5 211.2 209.6 206.9 205.3 203.9 202.1 200.6 200.2 199.4 198.0 196.9 196.1 196.1 197.4 199.6 201.9 203.7 205.2 205.9 204.9 202.3 200.3 200.0 199.8 197.5 195.5 193.6 185.8 174.7 171.7 172.6 175.1 179.4 183.8 188.8 196.7 205.7 208.9 209.7 208.6 205.9 202.8 200.2 198.3 197.5 198.4 199.8 200.4 199.1 197.3 195.0 192.2 188.5 184.0 182.7 188.4 190.3 193.3 197.8 200.3 202.2 204.2 206.6 207.5 209.0 210.1 211.5 213.1 214.7 216.3 218.0 220.0 221.9 224.8 226.1 224.4 222.1 218.3 212.9 211.4 211.7 210.1 208.0 207.2 207.1 204.5 202.9 200.9 198.2 194.3 191.2 189.0 187.1 185.2 183.1 182.5 177.1 168.9 162.2 162.1 162.6 163.3 163.3 163.2 162.3 161.2 161.4 163.3 167.4 170.8 178.2 187.8 195.0 195.4 195.5 195.7 196.3 197.5 199.4 221.5 227.8 224.7 218.3 214.4 216.5 218.2 218.0 224.9 234.8 243.8 242.8 241.9 240.2 237.6 233.5 227.8 222.0 213.5 215.4 233.6 239.9 246.9 251.0 253.3 256.4 260.8 264.2 268.1 277.4 296.5 298.6 298.4 295.7 292.2 289.0 287.8 287.7 289.1 290.9 293.3 296.4 300.8 309.9 321.1 333.2 348.0 359.6 359.8 357.2 356.1 356.8 355.7 355.2 354.8 354.8 354.6 354.2 354.7 355.3 355.5 355.4 355.3 355.6 355.6 355.9 356.6 356.7 356.7 357.4 357.9 358.4 359.0 359.4 359.6 359.6 359.8 360.4 361.5 361.5 360.1 356.5 350.6 342.0 320.9 302.3 283.8 267.3 269.1 274.4 280.2 286.2 291.1 294.6 297.2 299.8 302.2 305.8 314.2 323.9 329.8 334.0 335.3 335.1 333.4 330.6 328.2 325.6 322.6 318.6 313.5 308.3 304.5 301.8 299.7 299.0 298.0 296.8 295.4 293.7 292.6 292.0 291.2 290.1 289.3 289.5 291.5 293.9 295.9 297.8 297.8 298.9 298.0 296.6 293.7 290.8 289.0 288.8 290.7 294.9 298.1 301.0 302.5 303.4 304.0 303.9 303.2 301.7 300.7 301.0 301.5 302.2 303.4 304.0 304.6 305.4 306.1 306.7 307.2 306.9 305.8 303.8 301.2 298.3 296.7 296.2 297.2 297.7 298.7 299.3 299.3 299.3 299.0 299.0 299.1 299.8 300.2 300.8 301.2 301.7 302.3 303.9 305.3 307.9 311.2 316.6 324.3 330.9 332.4 331.7 331.5 331.1 330.5 331.0 331.7 333.0 335.4 337.7 340.7 342.7 345.0 348.0 357.9 374.1 390.1 401.0 405.7 404.1 399.3 392.7 388.4 386.8 389.9 398.1 384.1 315.8 269.8 244.3 220.5",
    "f0_timestep": "0.011609977324263039"


$total = $total_rows;
$num_done = 0;

$offset = 0;
$pagesize = 1000;


while ($offset < $total_rows && $continue_running){

    $time_start = microtime(true);

    Utils::CliProgressBar($offset, $total_rows);

    $q = "SELECT * FROM sections_topics WHERE processed = " . $stage . " AND error = 0 ORDER BY row_id ASC LIMIT " . $offset . ", " . $pagesize; // 10,000
    $rows = $registry->db->getRows($q);

    if( is_array($rows) && count($rows) > 0 ){
        foreach($rows as $row){
            $alphanumeric_chars_spaces = preg_replace("/[\-,\.]/", ' ', strtolower($row['lyrics']));
            $alphanumeric_chars = preg_replace("/[^A-Za-z0-9\s]/", '', $alphanumeric_chars_spaces);
            $words = preg_split('/\s+/', $alphanumeric_chars);
            $num_words = count($words);
            $word_counter = array();
            $words_sum = 0;
            foreach($words as $word){
                if( array_key_exists($word, $word_counter)){

                    $word_counter[$word] *= 2;//$word_counter[$word];

                }else{
                    $word_counter[$word] = 1;
                }
            }
            foreach($word_counter as $_wrd => $_multiplied){
                $words_sum += $_multiplied;
            }

            $repetition_ratio = $words_sum / $num_words;

            if( !$is_test_run ){

                //$q_reply = ( strlen($reply) > 254 ) ? substr($reply , 0,254) : $reply;
                $q = "INSERT INTO section_repeatition (section_row_id, word_count, word_multiplied, repetition_ratio) VALUES (?,?,?,?)";
                $registry->db->sendQueryP($q, array($row['row_id'], $num_words, $words_sum, $repetition_ratio), "iiid");
            }else{
                echo $row['row_id'] . ":" . implode(' ',$words) . " | " . $num_words . " | " . $words_sum . " | " .  ($words_sum / $num_words) . PHP_EOL;
            }

            $num_done++;

            Utils::CliProgressBar($offset, $total_rows);
            
        }
    }

    $offset += $pagesize;

    if( $is_test_run){

        $continue_running = false;

    }else{

        //$test_rows = $registry->db->getRows($test_q);
        //$rows_to_do = (int)$test_rows[0]['counter'];


        $loginfo = array();
        $loginfo['totaltime'] = microtime(true) - $time_start;
        $loginfo['numrows'] = count($rows);
        $loginfo['avetime'] = number_format((float)($loginfo['totaltime'] / count($rows)), 2, '.', '');
        $loginfo['todo'] = $total_rows - $offset;
        $loginfo['lastkey'] = $row['row_id'];

        //Utils::DbLog($logname, json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT), $registry->db );
    }
    

}

//include('templates/footer.inc.php');
echo json_encode($loginfo);
*/
echo ' done ' . PHP_EOL ;
