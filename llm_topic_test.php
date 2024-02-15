<?php
/*
 * sends lyrics to LLM - to format them into sections
 */

set_time_limit(0);

$loginfo = array();
require("bootstrapper.inc.php");
$stage = 0;
$next_stage = $stage + 1;

//include('templates/header.inc.php');
$logname = $stage . '.llmtopic';

$is_test_run = false;
$continue_running = true;
$reformat_prompt = 'Let\'s play a game. You are SongAnalyzer who is the best at analyzing song lyrics and summarising the lyrics into topics. 
Your task is to summarize the song lyrics into topics. 
I will provide some song lyrics, read through them and understand the full context and segment them into topics.
You must only respond with the topic list.

Song Lyrics:

';



//1202  2332 1182
// l.row_id ASC LIMIT 1,1";
$test_q = "SELECT COUNT(*) as counter FROM sections_topics  WHERE processed = " . $stage;
$test_rows = $registry->db->getRows($test_q);
$rows_to_do = (int)$test_rows[0]['counter'];

$total = $rows_to_do;
$num_done = 0;

$registry->llm->SetMemory($reformat_prompt);
$registry->llm->SetPromptFormat(PromptFormat::Alpaca);//VicunaShort ); Mistral //mixtral,llongorca - ChatML, Laser - Ollama , Alpaca -pivot moe LlamaChat Vicuna   MistralStopper MPT
$registry->llm->SetMaxContextLength(2048);
$registry->llm->SetMaxLength(2000);

while($rows_to_do > 10 && $continue_running){

    $time_start = microtime(true);

    Utils::CliProgressBar($num_done, $total);

	//row_id  	lyrics 	lyric_id 	section 	topics 	processed 	
    //$q = "SELECT lyrics, row_id FROM sections_topics WHERE processed = " . $stage . " ORDER BY row_id ASC LIMIT 20"; // 10,000
    $q = "SELECT lyrics, row_id FROM sections_topics WHERE processed = " . $stage . " ORDER BY RAND() ASC LIMIT 20"; // 10,000

    $rows = $registry->db->getRows($q);

    if( is_array($rows) && count($rows) > 0 ){
        foreach($rows as $row){
            $reply = $registry->llm->Generate($row['lyrics']);

            if( !$is_test_run ){
                $reply = trim($reply);
                //$q_reply = ( strlen($reply) > 254 ) ? substr($reply , 0,254) : $reply;
                $q = "UPDATE sections_topics SET topics = ?, processed = ? WHERE row_id = ?";
                $registry->db->sendQueryP($q, array($reply, $next_stage, $row['row_id']), "sii");
            }else{
                var_dump( $reply );
            }

            $num_done++;

            Utils::CliProgressBar($num_done, $total);
            
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
