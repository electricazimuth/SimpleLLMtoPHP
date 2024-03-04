<?php
/*
 * sends lyrics to LLM - to format them into sections
 * try using koboldcpp/neuralhermes-2.5-mistral-7b.Q8_0
 * RESET
 * UPDATE sections_topics SET topics = '', processed = 0 WHERE processed != 0
 */

set_time_limit(0);

$loginfo = array();
require("bootstrapper.inc.php");
$stage = 0;
$next_stage = $stage + 1;


$do_notify = is_writable($update_file);

//include('templates/header.inc.php');
$logname = $stage . '.llmtopic';

$alert = 0;
$alert_amount = 500;

$is_test_run = false;
$continue_running = true;
$memory_prompt = 'You are SongAnalyzer who is the best at analyzing song lyrics and scoring the lyrics into supplied topics. 
Your task is to score the song lyrics by the supplied topics. 
I will provide some song lyrics, read through them and understand the full context of the song and score them by each provided topic.
Create a score for each topic based on how relevant the song lyrics are to that topic. 
You will respond with the topic followed by a colon and the numerical integer score on each line. 
Each topic will be scored out of 10. A score of 0 is for the topic not matching and a score of 10 is for something that is a very strong match. 
Do not reply with topics that don\'t match or score 0, only respond with topics that score more than 0.
We\'re looking for 3 or more topics.
Do not create your own topics, only use the topics from the following list:

Love
Friendship
Heartbreak
Hope
Nature
Nostalgia
Growing up
Time
Regret
Celebration
Gratitude
Traveling
Dreams
Desire
Empowerment
Summer
Winter
Freedom
Adventure
Individuality
Death
Sadness
Change
Money
Life
Sun
Rain
City
Sport
Spring
Autumn
Driving
Music
Television
Pets
Nonsense

[Lyrics Start]
';

$postfix = '
[Lyrics End]

Important: Remember you must only respond with the scored topic list, and only topics from the list provided. Nothing else. No summaries, references, calculations or explainations. Once you have generated your response check that you have only used topics which are on the list, remove any which are not on the list. If you use topics which are not on the provided list you will not get paid.
';

//1202  2332 1182
// l.row_id ASC LIMIT 1,1";
$test_q = "SELECT COUNT(*) as counter FROM sections_topics  WHERE processed = " . $stage;
$test_rows = $registry->db->getRows($test_q);
$rows_to_do = (int)$test_rows[0]['counter'];

$total = $rows_to_do;
$num_done = 0;

//$registry->llm->SetMemory($memory_prompt);
$registry->llm->SetPromptFormat(PromptFormat::ChatML);//Alpaca);//VicunaShort ); Mistral //mixtral,llongorca - ChatML, Laser - Ollama , Alpaca -pivot moe LlamaChat Vicuna   MistralStopper MPT
$registry->llm->SetMaxContextLength(1024);
$registry->llm->SetMaxLength(300);

echo 'Using: ' . $registry->llm->GetModel();

while($rows_to_do > 10 && $continue_running){

    $time_start = microtime(true);

    Utils::CliProgressBar($num_done, $total);

	//row_id  	lyrics 	lyric_id 	section 	topics 	processed 	
    //$q = "SELECT lyrics, row_id FROM sections_topics WHERE processed = " . $stage . " ORDER BY row_id ASC LIMIT 20"; // 10,000
    $q = "SELECT lyrics, row_id FROM sections_topics WHERE processed = " . $stage . " ORDER BY RAND() LIMIT 100"; // 10,000

    $rows = $registry->db->getRows($q);

    if( is_array($rows) && count($rows) > 0 ){
        foreach($rows as $row){
            $prompt = $memory_prompt . $row['lyrics'] . $postfix;
            
            $reply = $registry->llm->Generate($prompt);

            if( !$is_test_run ){
                if( !empty($reply) ){
                    $reply = trim($reply);
                    $q = "UPDATE sections_topics SET topics = ?, processed = ? WHERE row_id = ?";
                    $registry->db->sendQueryP($q, array($reply, $next_stage, $row['row_id']), "sii");
                }
                //$q_reply = ( strlen($reply) > 254 ) ? substr($reply , 0,254) : $reply;
                
            }else{
                echo '<pre>';
                var_dump( $reply );
                echo '</pre>';
                echo '<hr />';
            }

            $num_done++;
            $alert++;

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

        if( $do_notify && $alert > $alert_amount ){
            $alert = 0;
            $update_string = "\n <pre> \n ==== " . date(DATE_RFC2822) . "\n". json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT) . " <pre> <hr /> \n";
            file_put_contents( $update_file, $update_string ); //FILE_APPEND

        }
    }
    

}

//include('templates/footer.inc.php');
echo json_encode($loginfo);
echo ' done ' . PHP_EOL ;
