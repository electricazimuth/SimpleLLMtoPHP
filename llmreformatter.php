<?php
set_time_limit(0);

$loginfo = array();
require("bootstrapper.inc.php");
$stage = 1;
$next_stage = $stage + 1;

//include('templates/header.inc.php');
$logname = $stage . '.llmformat';

$is_test_run = false;
$continue_running = true;
$reformat_prompt = 'I want you to act as a song lyric formatter. You will take the provided lyrics and format them into verse and chorus sections.
You will read through the lyrics and assess which sections are verse and chorus, if there is a distinct intro and/or outro also mark them.
I would like you seperate the sections of the song using a double line break and start each section with it\'s type (INTRO, VERSE, CHORUS, OUTRO) enclosed in square brackets like this "[CHORUS]" followed by a line break
IMPORTANT: Do not change any of the lyrics other than to reformat them.
Check the following lyrics and respond with your reformatted version:

LYRICS:
';



//1202  2332 1182
// l.row_id ASC LIMIT 1,1";
$test_q = "SELECT COUNT(*) as counter FROM lyrics_formatted  WHERE processed = 0";
$test_rows = $registry->db->getRows($test_q);
$rows_to_do = (int)$test_rows[0]['counter'];

$registry->llm->SetMemory($reformat_prompt);
$registry->llm->SetPromptFormat(PromptFormat::Alpaca);//VicunaShort ); Mistral //mixtral,llongorca - ChatML, Laser - Ollama , Alpaca -pivot moe LlamaChat Vicuna   MistralStopper MPT
$registry->llm->SetMaxContextLength(4096);
$registry->llm->SetMaxLength(3000);

while($rows_to_do > 10 && $continue_running){

    $time_start = microtime(true);

    $q = "SELECT lyrics, lyric_id, row_id FROM lyrics_formatted WHERE processed = 0 ORDER BY row_id ASC LIMIT 50"; // 10,000

    $rows = $registry->db->getRows($q);

    if( is_array($rows) && count($rows) > 0 ){
        foreach($rows as $row){
            $reply = $registry->llm->Generate($row['lyrics']);

            if( !$is_test_run ){
                $reply = trim($reply);
                //$q_reply = ( strlen($reply) > 254 ) ? substr($reply , 0,254) : $reply;
                $q = "UPDATE lyrics_formatted SET formatted = ?, processed = ? WHERE row_id = ?";
                $registry->db->sendQueryP($q, array($reply, 1, $row['row_id']), "sii");
            }else{
                var_dump( $reply );
            }
            
        }
    }

    if( $is_test_run){

        $continue_running = false;

    }else{

        $test_rows = $registry->db->getRows($test_q);
        $rows_to_do = (int)$test_rows[0]['counter'];


        $loginfo = array();
        $loginfo['model'] = $registry->llm->GetModel();
        $loginfo['totaltime'] = microtime(true) - $time_start;
        $loginfo['numrows'] = count($rows);
        $loginfo['avetime'] = number_format((float)($loginfo['totaltime'] / count($rows)), 2, '.', '');
        $loginfo['todo'] = $rows_to_do;
        $loginfo['lastkey'] = $row['row_id'];

        Utils::DbLog($logname, json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT), $registry->db );
    }
    

}

//include('templates/footer.inc.php');
echo json_encode($loginfo);
echo ' done ' . PHP_EOL ;
