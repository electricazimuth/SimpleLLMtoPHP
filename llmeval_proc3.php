<?php
set_time_limit(0);


require("bootstrapper.inc.php");

//include('templates/header.inc.php');


$stage = 3;

$is_test_run = true;
$last_stage = $stage - 1;
$logname = 'llmeval.' . $stage;



//1202  2332 1182
// l.row_id ASC LIMIT 1,1";
$test_q = "SELECT COUNT(*) as counter FROM processing WHERE stage = " . $last_stage;
$test_rows = $registry->db->getRows($test_q);
$rows_to_do = (int)$test_rows[0]['counter'];
$auto_stop = false;

while($rows_to_do > 200 && !$auto_stop){

    $time_start = microtime(true);

    $q = "SELECT l.lyrics, l.id, l.artist, l.title, p.pri_key FROM lyrics_hot100 l, processing p WHERE p.stage = 1 AND p.lyric_id = l.id ORDER BY p.pri_key ASC LIMIT 200";
    $rows = $registry->db->getRows($q);


    if( is_array($rows) && count($rows) > 0 ){
        foreach($rows as $row){

            if( !$is_test_run ){
                $q_reply = ( strlen($reply) > 254 ) ? substr($reply , 0,254) : $reply;
                $q = "UPDATE processing SET llm_eval = ?, stage = 2 WHERE pri_key = ?";
                $registry->db->sendQueryP($q, array($q_reply, $row['pri_key']), "si");
            }
        }
    }

    //short circuit the while loop - we only do a single loop in test mode
    if($is_test_run){
        $auto_stop = true;
    }

    $test_rows = $registry->db->getRows($test_q);
    $rows_to_do = (int)$test_rows[0]['counter'];


    $loginfo = array();
    $loginfo['model'] = $registry->llm->GetModel();
    $loginfo['totaltime'] = microtime(true) - $time_start;
    $loginfo['numrows'] = count($rows);
    $loginfo['avetime'] = number_format((float)($loginfo['totaltime'] / count($rows)), 2, '.', '');
    $loginfo['todo'] = $rows_to_do;
    $loginfo['lastkey'] = $row['pri_key'];

    Utils::DbLog($logname, json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT), $registry->db );

    

}

if($is_test_run){
    echo '<pre>';
    var_dump($loginfo);
    echo '</pre>';
}
//include('templates/footer.inc.php');
echo 'done';
