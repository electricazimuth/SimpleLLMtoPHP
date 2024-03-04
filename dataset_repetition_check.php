<?php
/*
 * check dataset for 
 * - number of words (we dont want really short snippets)
 * - repeated words
 */

set_time_limit(0);

$loginfo = array();
require("bootstrapper.inc.php");
$stage = 2;
$next_stage = $stage + 1;

//include('templates/header.inc.php');
$logname = $stage . '.repeatition';

$is_test_run = false;
$continue_running = true;

//clear out any previous entries
$registry->db->sendQuery('TRUNCATE TABLE section_repeatition');

//1202  2332 1182
// l.row_id ASC LIMIT 1,1";
$test_q = "SELECT COUNT(*) as counter FROM sections_topics  WHERE processed = " . $stage . " AND error = 0";
$test_rows = $registry->db->getRows($test_q);
$total_rows = (int)$test_rows[0]['counter'];

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
echo ' done ' . PHP_EOL ;
