<?php
$time_start = microtime(true);
require("bootstrapper.inc.php");

include('templates/header.inc.php');

$do_save_to_db = false;

//$q = "SELECT * FROM hot100_uniq ORDER BY weeks DESC LIMIT 0,1";
//$q = "SELECT p.*, l.lyrics, l.title, l.artist FROM lyrics l, processing p WHERE p.lyric_id = l.id ORDER BY p.pri_key ASC LIMIT 1,3";
//$q = "SELECT l.* FROM lyrics_hot100 l WHERE l.artist LIKE 'Eminem' ORDER BY RAND() LIMIT 1";
//$q = "SELECT l.* FROM lyrics_hot100 l WHERE l.row_id = 2332"; //19693";// 2332"; 533 622

//$q = "SELECT l.* FROM lyrics_hot100 l WHERE l.row_id IN (403031,1651690,75244,19693,2332,1169,7490,13963,533,622,1202,1182,16984,6431,7650)";
$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.pri_key FROM lyrics_hot100 l, processing p WHERE p.lyric_id = l.id AND p.lyric_id IN (900,533,3088538,3930567,535,3309339,403031,3138625,1651690,4765699,75244,462996)";

//$q = "SELECT l.lyrics, l.id, l.artist, l.title, p.pri_key FROM lyrics_hot100 l, processing p WHERE p.stage = 1 AND p.lyric_id = l.id ORDER BY p.pri_key ASC LIMIT 100";


//1202  2332 1182
// l.row_id ASC LIMIT 1,1";

$rows = $registry->db->getRows($q);

//$registry->llm->SetPrompt("what color is mud?");

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
//$registry->llm->SetPromptFormat(PromptFormat::Alpaca); //pivot moe

//$grammar_string = file_get_contents('assets/json.gbnf');
//echo $grammar_string;
//$registry->llm->SetGrammar($grammar_string);


//
$registry->llm->SetMaxLength(100);
?>
<div>
<?php
//echo $reply;
?>
</div>
<table class="container">
<?php
if( is_array($rows) && count($rows) > 0 ){
    foreach($rows as $row){
        $reply = $registry->llm->Generate($row['lyrics']);

        if( $do_save_to_db ){
            $q_reply = ( strlen($reply) > 254 ) ? substr($reply , 0,254) : $reply;
            $q = "UPDATE processing SET llm_eval = ?, stage = 2 WHERE pri_key = ?";
            $registry->db->sendQueryP($q, array($q_reply, $row['pri_key']), "si");
        }

        //$q = "INSERT INTO `x_debug_log` (`title`, `info`, `ip` , `agent` ) VALUES (?, ?, ?, ?)";
        //$result = $db->sendQueryP( $q, array($title, $info, $ip, $_SERVER['HTTP_USER_AGENT']), "ssss" );


         
?>
    <tr> 
        <td><?php echo $row['title'] ?> </td>
        <td><?php echo $row['artist'] ?></td>
        <td>(<?php echo $row['id'] ?>)</td>
        <td><pre><?php echo $reply; ?></pre></td>
    </tr>
<?php
//echo nl2br($row['lyrics']);
//$q = "SELECT id, row_id, lyrics FROM lyrics WHERE  "
// <?php echo nl2br(substr($row['lyrics'], 0 , 800));
//    <hr />
//<p> <?php echo nl2br($row['lyrics']); 

    }
}
?>
</table>
<p><b><?php echo $registry->llm->GetModel() ?></b></p>
<?php
$end_time = microtime(true) - $time_start;
?>
<p>completed <?php echo count($rows) ?> in <?php echo number_format((float)$end_time, 2, '.', '') ?> sec</p>
<p><?php echo number_format((float)($end_time / count($rows)), 2, '.', '') ?>s ave per call</p>
<?php
include('templates/footer.inc.php');