<?php
set_time_limit(0);


require("bootstrapper.inc.php");

//include('templates/header.inc.php');


$stage = 2;

$is_test_run = true;
$last_stage = $stage - 1;
$next_stage = $stage + 1;
$logname = $stage . 'format';
$loginfo = array();


//1202  2332 1182
// l.row_id ASC LIMIT 1,1";
$test_q = "SELECT COUNT(*) as counter FROM processing WHERE stage = " . $stage;
$test_rows = $registry->db->getRows($test_q);
$rows_to_do = (int)$test_rows[0]['counter'];
$auto_stop = false;

while($rows_to_do > 200 && !$auto_stop){

    $time_start = microtime(true);
//lyric_id 	hotu_id 	stage 	status 	swearcount 	llm_eval 	swearing 	offensive 	provocative
    $q = "SELECT * FROM processing p WHERE p.stage = " . $stage . " ORDER BY p.pri_key ASC LIMIT 400";
    $rows = $registry->db->getRows($q);

    if( is_array($rows) && count($rows) > 0 ){
        foreach($rows as $row){

            $data = Utils::ProcessRatings($row['llm_eval']);
            $okay = true;
            foreach($data as $key => $val){
                if( $val < 0){
                    //error
                    $okay = false;
                }
            }

            if( !$is_test_run ){
                if( $okay ){

                    $q = "UPDATE processing SET stage = ?,swearing = ?, offensive = ?, provocative = ?  WHERE pri_key = ?";
                    $registry->db->sendQueryP($q, array($next_stage, $data['swearing'], $data['offensive'], $data['provocative']), "iiiii");

                }else{

                    $q = "UPDATE processing SET stage = ? WHERE pri_key = ?";
                    $registry->db->sendQueryP($q, array($last_stage, $row['pri_key']), "ii");


                }
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
    if( count($rows) > 0){
        $loginfo['avetime'] = number_format((float)($loginfo['totaltime'] / count($rows)), 2, '.', '');
    }
    $loginfo['todo'] = $rows_to_do;
    $loginfo['lastkey'] = $row['pri_key'];

    Utils::DbLog($logname, json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT), $registry->db );

    

}

if($is_test_run){
    echo '<pre>';
    json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);
    echo '</pre>';
}
//include('templates/footer.inc.php');
echo 'done';
