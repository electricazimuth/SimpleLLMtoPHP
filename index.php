<?php

require("bootstrapper.inc.php");

include('templates/header.inc.php');


//$q = "SELECT * FROM hot100_uniq ORDER BY weeks DESC LIMIT 0,1";
//$q = "SELECT p.*, l.lyrics, l.title, l.artist FROM lyrics l, processing p WHERE p.lyric_id = l.id ORDER BY p.pri_key ASC LIMIT 1,3";
//$q = "SELECT l.* FROM lyrics_hot100 l WHERE l.artist LIKE 'Eminem' ORDER BY RAND() LIMIT 1";
$q = "SELECT l.* FROM lyrics_hot100 l WHERE l.row_id = 2332"; //19693";// 2332"; 533 622
//1202  2332 1182
// l.row_id ASC LIMIT 1,1";


$rows = $registry->db->getRows($q);

//$registry->llm->SetPrompt("what color is mud?");

$registry->llm->SetMemory(
'I want you to act as a song lyric assesor. You are checking lyrics for any problematic language or themes. You will provide a score to help assess the swearing used and the offensive and provacative nature of the contents of the lyrics.
I would like you to respond with a rating from 0 to 10 for any swearing and the lyrics offensiveness and provacativeness. 
A swearing rating of 10 is when theres a lot of strong swearing, swear words from high to low score; cunt, nigger, piss, pussy, fuck, dick, cock, shit, twat, bollocks and wanker the least. 
An offensive rating of 10 is for something that most people would be offended by. 
A provactive rating of 10 is for lyrics which are overtly provactive. Strong swear words like "cunt" would score a 10 for offensiveness and swearing.
Your response should contain only "swearing", "offensive" and "provacative" followed by a number
# Example:
swearing: 0-10
offensive: 0-10
provacative: 0-10 

# IMPORTANT remember to only respond with the "swearing", "offensive" and "provacative" ratings. Nothing else. No summaries, references, calculations or explainations. 
The folowing will be the lyrics you are checking:
# LYRICS
'
);


$registry->llm->SetPromptFormat(PromptFormat::VicunaShort ); //mixtral,llongorca - ChatML, Laser - Ollama , Alpaca -pivot moe LlamaChat Vicuna   MistralStopper MPT
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
<?php
if( is_array($rows) && count($rows) > 0 ){
    foreach($rows as $row){
        $reply = $registry->llm->Generate($row['lyrics']);
?>
    <h3><?php echo $row['title'] ?> <small>by <?php echo $row['artist'] ?> (<?php echo $row['row_id'] ?>)</small></h3>
    
    <pre>
<?php
echo $reply;

//echo nl2br($row['lyrics']);
//$q = "SELECT id, row_id, lyrics FROM lyrics WHERE  "
// <?php echo nl2br(substr($row['lyrics'], 0 , 800));
?>
    </pre>
    <hr />
    <p> <?php echo nl2br($row['lyrics']); ?> </p>
<?php
    }
}

include('templates/footer.inc.php');