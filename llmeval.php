<?php
set_time_limit(0);

$time_start = microtime(true);
require("bootstrapper.inc.php");

//include('templates/header.inc.php');
$logname = 'llmeval.00';
$do_save_to_db = true;

$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.pri_key FROM lyrics_hot100 l, processing p WHERE p.stage = 1 AND p.lyric_id = l.id ORDER BY p.pri_key ASC LIMIT 100";


//1202  2332 1182
// l.row_id ASC LIMIT 1,1";

$rows = $registry->db->getRows($q);

$registry->llm->SetMemory(
'I want you to act as a song lyric checker. You are checking lyrics for any problematic language or themes. You will read through the lyrics and assess the swearing used and the offensive and provocative nature of the contents of the lyrics.
I would like you to respond with numerical ratings from 0 to 10 for any swearing and the lyrics offensiveness and provocativeness. 
A swearing rating of 10 is when theres a lot of strong swearing, swear words from high to low score; "cunt", "nigger", "piss", "pussy", "fuck", "dick", "cock", "shit", "twat", "bollocks" and "wanker" the least. 
An offensive rating of 10 is for something that most people would be offended by. 
A provocative rating of 10 is for lyrics which are overtly provocative. Strong swear words like "cunt" would score a 10 for offensiveness and swearing.
Your response should contain only "swearing:", "offensive:" and "provacative:" followed by a number. 

IMPORTANT remember to only respond with the "swearing", "offensive" and "provocative" numerical ratings. Nothing else. No summaries, references, calculations or explainations. 
Check the following lyrics and respond with your ratings:

# LYRICS
'
);
/*

An example response follows:

swearing: {swearing rating}
offensive: {offensive rating}
provocative: {provocative rating}


# Example:
swearing: {swearing rating}
offensive: {offensive rating}
provocative: {provocative rating} 
*/


$registry->llm->SetPromptFormat(PromptFormat::Alpaca);//VicunaShort ); Mistral //mixtral,llongorca - ChatML, Laser - Ollama , Alpaca -pivot moe LlamaChat Vicuna   MistralStopper MPT

$registry->llm->SetMaxLength(100);

if( is_array($rows) && count($rows) > 0 ){
    foreach($rows as $row){
        $reply = $registry->llm->Generate($row['lyrics']);

        if( $do_save_to_db ){
            $q_reply = ( strlen($reply) > 254 ) ? substr($reply , 0,254) : $reply;
            $q = "UPDATE processing SET llm_eval = ?, stage = 2 WHERE pri_key = ?";
            $registry->db->sendQueryP($q, array($q_reply, $row['pri_key']), "si");
        }
    }
}
$loginfo = array();
$loginfo['model'] = $registry->llm->GetModel();
$loginfo['totaltime'] = microtime(true) - $time_start;
$loginfo['numrows'] = count($rows);
$loginfo['avetime'] = number_format((float)($end_time / count($rows)), 2, '.', '');

Utils::DbLog($logname, json_encode($loginfo,JSON_FORCE_OBJECT | JSON_PRETTY_PRINT), $registry->db );

//include('templates/footer.inc.php');
echo 'done';
