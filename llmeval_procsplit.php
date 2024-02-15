<?php
/*
 * Processes slices the lyrics into sections (20,000 on 1.6Ghz atom takes about 8mins )
 * 
 * Reset:
 * TRUNCATE TABLE lyrics_sections
 * UPDATE lyrics_formerged SET processed = 0
 */
set_time_limit(0);
require("bootstrapper.inc.php");

// /Volumes/T7/vhosts/versiond/SimpleLLMtoPHP/llmeval_procsplit.php
//include('templates/header.inc.php');
$stage = 0;

$is_test_run = false;
$next_stage = $stage + 1;
$logname = $stage . '.slice';
$loginfo = array();


//1202  2332 1182
// l.row_id ASC LIMIT 1,1";
$test_q = "SELECT COUNT(*) as counter FROM lyrics_formerged WHERE processed = " . $stage;
$test_rows = $registry->db->getRows($test_q);
$rows_to_do = (int)$test_rows[0]['counter'];
$auto_stop = false;

$total = $rows_to_do;
$num_done = 0;

while($rows_to_do > 0 && !$auto_stop){

    $time_start = microtime(true);
//lyric_id 	hotu_id 	stage 	status 	swearcount 	llm_eval 	swearing 	offensive 	provocative
    $q = "SELECT * FROM lyrics_formerged WHERE processed = " . $stage . " ORDER BY row_id ASC LIMIT 50"; //id = 1107047
    $rows = $registry->db->getRows($q);
    //var_dump($is_test_run, $rows);
    if( $is_test_run ){

        include('templates/header.inc.php');
    ?>
        <div class="container">
        <table class=" table table-striped">
    <?php
    }
    if( is_array($rows) && count($rows) > 0 ){

        foreach($rows as $row){

            $slices = Utils::SliceLyricSections($row['lyrics']);
            $okay = true;

            $proc_stage = $next_stage;
            if( !$is_test_run ){

                if( is_array($slices) && count($slices) > 0 ){
                    $countr = 1;
                    foreach($slices as $slice){
                        $q = "INSERT INTO lyrics_sections (lyrics, lyric_id, section) VALUES (?, ? , ?)";
                        $registry->db->sendQueryP($q, array($slice, $row['id'], $countr), "sii");
                        $countr++;
                    }

                    

                }else{

                    $proc_stage = 9;

                    //$q = "UPDATE processing SET stage = ? WHERE pri_key = ?";
                    //$registry->db->sendQueryP($q, array($last_stage, $row['pri_key']), "ii");


                }

                $q = "UPDATE lyrics_formerged SET processed = ?  WHERE row_id = ?";
                $registry->db->sendQueryP($q, array($proc_stage, $row['row_id']), "ii");

            }else{
                ?>
                <tr> 
                    <td><pre><?php echo substr( $row['lyrics'], 0 , 500) ?></pre></td>
                    <td><?php
                    foreach($slices as $k => $v){
                        echo '<pre>' . $k . ': ' . $v . '</pre><hr />';
                    }
                    ?>
                    </td>

                </tr>

                <?php
            }

            $num_done++;
            Utils::CliProgressBar($num_done, $total);

        }
    }

    if( $is_test_run ){
       ?>
       </table></div>
       <?php
    }

    //short circuit the while loop - we only do a single loop in test mode
    if($is_test_run){
        $auto_stop = true;
    }

    $test_rows = $registry->db->getRows($test_q);
    $rows_to_do = (int)$test_rows[0]['counter'];


    $loginfo = array();
    $loginfo['totaltime'] = microtime(true) - $time_start;
    $loginfo['numrows'] = count($rows);
    if( count($rows) > 0){
        $loginfo['avetime'] = number_format((float)($loginfo['totaltime'] / count($rows)), 2, '.', '');
    }
    $loginfo['todo'] = $rows_to_do;
    $loginfo['lastkey'] = $row['row_id'];

    Utils::DbLog($logname, json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT), $registry->db );

}

if($is_test_run){
    echo '<pre>';
    echo json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);
    echo '</pre>';
}
//include('templates/footer.inc.php');
echo 'done ';
