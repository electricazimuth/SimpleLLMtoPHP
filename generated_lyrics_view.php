<?php
/*
 */

set_time_limit(0);

require("bootstrapper.inc.php");

$total = 10;

$table_prefix = 'alpha';
$use_ids = false;
$_ids = array();
$row_ids = array();
// 166,289,331,325,430,351,373,385,595,604,616,613,611,592,585

// 
if( isset($_GET['ids']) && strlen($_GET['ids']) > 0 ){

    $_ids = explode( ',', $_GET['ids'] );
    $_ids = array_map('trim', $_ids);
    $_ids = array_map('intval', $_ids);


    if( array_sum($_ids) > 0){
        $use_ids = true;
    }
}

if($use_ids){
    
    $row_ids = $_ids;

}else{
    //get 10 random generated lyric lines ids - using the lines table as it should have results (some entries in _generation may not have been used / errored..)
    $q = "SELECT generation_row_id FROM " . $table_prefix . "_lines GROUP BY generation_row_id ORDER BY RAND() LIMIT " . $total;
    $rows = $registry->db->getRows($q);


    foreach($rows as $row){
        $row_ids[] = (int)$row['generation_row_id'];
    }
}

$q = "SELECT g.row_id, g.model, g.prompt_type, g.topics, g.lyrics, l.lyric_line, l.phone_count, l.line_number, v.model_checkpoint as variance_checkpoint, a.audio_file " .
    "FROM alpha_generation g, alpha_lines l, alpha_variance v, alpha_acoustic a " .
    "WHERE g.row_id = l.generation_row_id AND l.line_number <= 4 AND " .
    "v.generation_line_id = l.row_id AND a.gen_variance_id = v.row_id AND g.row_id IN(" . implode(',',$row_ids) . ") " .
    "ORDER BY l.generation_row_id ASC, l.line_number ASC";
//echo $q;
//die();
$rows = $registry->db->getRows($q);

$data = array();
foreach($rows as $row){
    $data[ $row['row_id'] ]['lyrics'] = array( 
        'id' => $row['row_id'], 
        'model' => $row['model'], 
        'prompt_type' => $row['prompt_type'], 
        'topics' => $row['topics'], 
        'lyrics' => $row['lyrics']
    );
    $data[ $row['row_id'] ]['lines'][ $row['line_number'] ] = array(
        'lyric_line' => $row['lyric_line'] , 
        'phone_count' => $row['phone_count'], 
        'line_number' => $row['line_number'], 
        'variance_checkpoint' => $row['variance_checkpoint'], 
        'audio_file' => $row['audio_file']
    );
}

?>
<!doctype html>
<html lang="en">
 <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="Van Zussi">
    <meta name="generator" content="azimuth.web">
    <title> Lyrics Audio Gen Check</title>

    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
 </head>
 <body class="">


<?php


    
    echo '<table class="table table-striped " style="break-inside: avoid-page;">';
    //echo '<tr><td>Model</td> <td>Simpleton</td> <td>Lyric Writer</td> <td>Beatles</td></tr> ';

    foreach( $data as $row_id => $_data  ){
        //foreach( $type as $_type => $lyrics){
            $model_expl = explode('/',$_data['lyrics']['model']);
            $model_e2 = explode('.', $model_expl[1]);
            $model = str_replace(  '-',' ' , $model_e2[0]);

            echo '<tr>
                    <td>'.   $_data['lyrics']['id'] . '</td>' . 
                    '<td>'. $model  . '</td>' . 
                    '<td>'.  $_data['lyrics']['prompt_type'] . '</td>' . 
                    '<td>'.  $_data['lyrics']['topics'] . '</td>' . 
                    '<td>'.  $_data['lyrics']['lyrics'] . '</td>' . 
                    '<td>
                        <table> ';
                        foreach($_data['lines'] as $_line_num => $_line){
            echo           '<tr>
                                <td>' .  $_line['lyric_line'] . '</td>' . 
                                '<td>' . $_line['phone_count'] . '</td>' . 
                                '<td>' . $_line['variance_checkpoint'] . '</td>' . 
                                '<td>' . $_line['audio_file'] . 
                                '<br /><audio controls> <source src="/' . $_line['audio_file'] . '" type="audio/wav">Your browser does not support the audio element.</audio> ' .
                              '</td>' . 

                           '</tr>';
                        }
            echo            '</table>
                    </td>
                    </tr>';
                    

          
    }

    echo '</table>';

/*
    foreach( $data[$topic] as $_model => $type  ){
        foreach( $type as $_type => $lyrics){
            echo '<tr><td>'.$_model.'</td> <td>'.$_type.'</td> <td><pre>'.$lyrics.'</pre></td></tr> '; 
        }
          
    }
*/




/*
echo '<pre>';
var_dump( $file_to_word );
echo '</pre>';

echo json_encode($loginfo);
echo ' done ' . PHP_EOL ;
*/