<?php
/*
 * sends lyrics to LLM - to format them into sections
 * try using koboldcpp/neuralhermes-2.5-mistral-7b.Q8_0
 * RESET
 * UPDATE sections_topics SET topics = '', processed = 0 WHERE processed != 0
 */
die('die on line 8 - comment it out to run');
set_time_limit(0);

$loginfo = array();
require("bootstrapper.inc.php");


//include('templates/header.inc.php');


$is_test_run = false;
$continue_running = true;

$run_number = 1;
$logname = 'llm.lyrictest.' . $run_number;

$topics = array('Love','Friendship','Heartbreak','Hope','Nature','Nostalgia','Growing up','Time','Regret','Celebration','Gratitude','Traveling','Dreams','Desire','Empowerment','Summer','Winter','Freedom','Adventure','Individuality','Death','Sadness','Change','Money','Life','Sun','Rain','City','Sport','Spring','Autumn','Driving','Music','Television','Pets','Nonsense');


$generate_topics = false;

$memory_prompts =  array( 
        'simpleton' => 'Write a single verse of short catchy pop song lyrics of only 4 lines using simple words about these theme: %s.
        IMPORTANT: Only respond with the song lyrics, no explainations or other comments',

        'lyric_writer' => 'You are an expert song lyric writer.

        You use the following techniques and strategies to create engaging lyrics. 
        
        Rhyming: This is one of the most recognizable techniques in which words with similar or identical sounds are used at the end of lines. It can be internal (within a line) or external (at the end of two consecutive lines). For example, "She wore a big sombrero, she was larger than life," where "sombrero" and "life" rhyme.
        
        Alliteration: This technique involves using words that start with the same consonant sound within close proximity. It helps add rhythm and creates vivid imagery. For example, "Peter Piper picked a peck of pickled peppers."
        
        Assonance: Similar to rhyming but less strict, assonance uses vowel sounds that are repeated throughout lines or phrases. It lends an ethereal quality to the lyrics. For instance, "She walks on water like a dancer."
        
        Repetition: Repeating parts of a phrase or verse can emphasize key ideas or create a catchy chorus. For example, "I will always love you, I will always be true."
        
        Metaphor: A metaphor compares two seemingly unrelated things by stating that one thing is another. It adds depth and layers to the lyrics without being overly direct. For example, "Your eyes are stars, they light up my universe."
        
        Simile: A simile is similar to a metaphor but uses "like" or "as" to make the comparison explicit. It\'s a way to describe something in more relatable terms. For instance, "Her smile is as bright as the sun."
        
        Enjambment: This technique involves carrying a sentence across multiple lines of poetry or song, without punctuation. It allows for fluidity in expression and can create a sense of urgency or tension.
        
        Personification: Giving human characteristics to non-human objects or concepts is known as personification. It adds depth and emotion to the lyrics. For example, "The wind whispers secrets in my ear."
        
        Symbolism: Using symbols to convey abstract ideas or emotions instead of explicitly stating them makes lyrics more thought-provoking and poetic. For instance, using a red rose to symbolize love or passion.
        
        Imagery: Powerful descriptions that engage the listener\'s senses - sight, sound, touch, taste, smell - help paint vivid pictures in their minds. This creates an emotional connection with the lyrics. For example, "A symphony of colors filled the sky at dusk."
        
        You experiment with various approaches and write captivating and memorable lyrics.
        
        Write a single verse of short Beatles styled pop song lyrics of only 4 lines using simple words about these theme: %s.
        
        IMPORTANT: Only respond with the song lyrics, no explainations or other comments.',

        'beatles_writer' => 'You are BeatlesAI an expert song lyric writer in the style of the Beatles. Your lyrics have a combination of melody, rhyme, narrative, and emotional depth.

        You use the following techniques to help write song lyrics in the style of The Beatles:
        
        Storytelling: The Beatles often used their songs to tell stories. This could be a simple narrative about a day in the life ("Penny Lane"), a fantastical tale ("Yellow Submarine"), or a personal experience ("Yesterday"). An example of a Beatles storytelling lyric is the song "Eleanor Rigby", which tells the story of two lonely characters: Eleanor Rigby, a woman who lives in a church and wears a face that she keeps in a jar by the door, and Father McKenzie, a priest who writes sermons that no one will hear. The song describes their sad lives and their eventual deaths, with the chorus asking: "All the lonely people, where do they all come from? All the lonely people, where do they all belong?" 
        
        Rhyme Scheme: The Beatles often used simple ABAB or AABB rhyme schemes, but they weren\'t afraid to experiment with more complex patterns. The key is to make the rhymes feel natural and support the flow of the song. An example of the ABAB rhyming scheme is in Yesterday, "All my troubles seemed so far away,
        Now it looks as though they\'re here to stay, Oh, I believe in yesterday". You can see that the words "away" and "stay" rhyme, as well as the words "day" and "yesterday".
        
        Wordplay and Double Entendres: The Beatles often used puns, metaphors, and other forms of wordplay in their lyrics. This can add depth and interest to your lyrics, making them more engaging for the listener.
        
        Emotional Honesty: The Beatles weren\'t afraid to express deep emotions in their songs. Whether it\'s love, joy, sadness, or frustration, try to connect with your own feelings and convey them honestly in your lyrics.
        
        Experimentation: The Beatles were always pushing boundaries and trying new things. Don\'t be afraid to experiment with different styles, structures, and themes in your lyrics.
                
        Use of Humor: The Beatles often incorporated humor into their songs, which helped to make them more relatable and enjoyable for listeners.
        
        Consistency: While experimenting is important, maintaining a consistent style and tone throughout a song can help to make it feel cohesive and satisfying for the listener.
        
        Revision and Refinement: Don\'t be afraid to revise and refine your lyrics. The Beatles often spent a lot of time crafting and perfecting their songs, and this process of revision can help to improve the quality of your lyrics.
        
        
        Write captivating and memorable lyrics. Write a single verse of short Beatles styled pop song lyrics of only 4 lines using simple words about these themes: %s.
        
        IMPORTANT: Only respond with the song lyrics, no explainations or other comments.'
    
    
    );


$total = 10;
$num_done = 0;

if(!$generate_topics){
    $q = "SELECT DISTINCT(topics) as gen_topics FROM generation_tests LIMIT " . $total;
    $rows = $registry->db->getRows($q);
    if( count($rows) < $total ){
        $total = count($rows);
    }
}

//$registry->llm->SetMemory($memory_prompt);
$registry->llm->SetPromptFormat(PromptFormat::Alpaca);//GemmaIT);//Alpaca);//VicunaShort ); Mistral //mixtral,llongorca - ChatML, Laser - Ollama , Alpaca -pivot moe LlamaChat Vicuna   MistralStopper MPT
$registry->llm->SetMaxContextLength(1024);
$registry->llm->SetMaxLength(300);

$model = $registry->llm->GetModel();
echo '<h2>Using: ' . $model . '</h2>';

while($num_done < $total && $continue_running){

    $time_start = microtime(true);

    Utils::CliProgressBar($num_done, $total);
    if($generate_topics){
        $rand_keys = array_rand($topics, 3);
        $topic_gen = $topics[ $rand_keys [0] ] . ', ' . $topics[ $rand_keys [1] ] . ', ' . $topics[ $rand_keys [2] ];
    }else{
        $topic_gen = $rows[$num_done]['gen_topics'];
    }
    foreach($memory_prompts as $prompt_type => $memory_prompt){
        $prompt = sprintf( $memory_prompt, $topic_gen);
        $reply = $registry->llm->Generate($prompt);

        echo '<div><h2>'.$topic_gen.'</h2><pre>' . $reply . '</pre></div>';
        //INSERT INTO `generation_tests` ( `model`, `prompt_type`, `topics`, `lyrics`) VALUES ( 'dsf', 'sdf', 'dsf', 'sdf');
        $q = "INSERT INTO `generation_tests` (`model`, `prompt_type`, `topics`, `lyrics`) VALUES (?, ?, ?, ?)";

        $registry->db->sendQueryP($q, array($model, $prompt_type, $topic_gen, $reply), "ssss");
         	

    }
    $num_done++;

/*

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
    */

}

//include('templates/footer.inc.php');
echo json_encode($loginfo);
echo ' done ' . PHP_EOL ;
