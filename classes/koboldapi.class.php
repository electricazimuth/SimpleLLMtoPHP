<?php
/*
* Common Statics / Utilities - just a place to keep reused methods for the API
*
**** 
*/
enum PromptFormat
{
    case ChatML;
    case MistralStopper;
    case Alpaca;
    case Ollama;
    case VicunaShort;
    case LlamaChat;
    case MPT;
    case Mistral;
    case GemmaIT;
    case None;
}
/*
{"n": 1, "max_context_length": 1600, "max_length": 120, 
    "rep_pen": 1.1, 
    "temperature": 0.7, 
    "top_p": 0.92, "top_k": 100, "top_a": 0, "typical": 1, 
    
    "tfs": 1,
     "rep_pen_range": 320, "rep_pen_slope": 0.7, "sampler_order": [6, 0, 1, 3, 4, 2, 5], 
     
     "memory": "you are a mad scientist, respond like a crazy scientist.\n", 
     
     "min_p": 0, "presence_penalty": 0, "genkey": "KCPP7340", "prompt": "<|im_end|>\n<|im_start|>user\nwhat color is the sky?<|im_end|>\n<|im_start|>assistant\n", "quiet": true, 
     
     "stop_sequence": ["<|im_end|>\n<|im_start|>user", "<|im_end|>\n<|im_start|>assistant"], "use_default_badwordsids": false}
*/


class KoboldApi {

    private $host = 'http://localhost';
    private $format = PromptFormat::None;
    private $payload = array(
        'max_context_length' =>  1024,
        'max_length' =>  100,
        'prompt' =>  'how do you mix paints to make the color green?',
        'quiet' =>  false,
        'rep_pen' =>  1.1,
        'rep_pen_range' =>  320,
        'rep_pen_slope' =>  0.7,
        'temperature' =>  0.2,
        'tfs' =>  1,
        'top_a' =>  0,
        'top_k' =>  100,
        'top_p' =>  0.92,
        'typical' =>  1
    );
    private $promptPrefix = "";
    private $promptPostfix = "";
/*
    max_context_length	[...]
    max_length	[...]
    prompt*	[...]
    rep_pen	[...]
    rep_pen_range	[...]
    sampler_order	[...]
    sampler_seed	[...]
    stop_sequence	[...]
    temperature	[...]
    tfs	[...]
    top_a	[...]
    top_k	[...]
    top_p	[...]
    min_p	[...]
    typical	[...]
    use_default_badwordsids	[...]
    mirostat	[...]
    mirostat_tau	[...]
    mirostat_eta	[...]
    genkey	[...]
    grammar	[...]
    grammar_retain_state	[...]
    memory	[...]
    trim_stop	[...]
    }
*/


    /**
     * Construct won't be called inside this class and is uncallable from
     * the outside. This prevents instantiating this class.
     * This is by purpose, because we want a static class.
     */
    public function __construct($host = 'http://localhost') {
        $this->host = $host;
    }
/*
    private function SetPayload($payload){
        $this->payload = $payload;
    }
*/
    public function SetPrompt($prompt){
        $this->payload['prompt'] = $this->ApplyFormat( $prompt );
    }

    public function SetMaxLength($length){
        if( is_numeric($length)){
            $this->payload['max_length'] = intval($length);
        }
    }

    public function SetMaxContextLength($length){
        if( is_numeric($length)){
            $this->payload['max_context_length'] = intval($length);
        }
    }

    public function SetGrammar($grammar_string){
        $this->payload['grammar'] = $grammar_string;
    }

    public function SetPromptFormat( PromptFormat $format ){
        $this->format = $format;
        switch($this->format){
            case PromptFormat::ChatML:
                $this->promptPrefix = "<|im_end|>\n<|im_start|>user\n";
                $this->promptPostfix = "<|im_end|>\n<|im_start|>assistant\n";
                $this->payload['stop_sequence'] = array("<|im_end|>\n<|im_start|>user", "<|im_end|>\n<|im_start|>assistant");
            break;
            case PromptFormat::MistralStopper:
                $this->promptPrefix = "<|im_end|>\n<|im_start|>user\n";
                $this->promptPostfix = "<|im_end|>\n<|im_start|>assistant\n";
                $this->payload['stop_sequence'] = array("</|im_end|>","<|im_end|>","<|im_start|>user", "<|im_start|>assistant");
            break;
            case PromptFormat::MPT:
                $this->promptPrefix = "<human>: \n";
                $this->promptPostfix = "<bot>: \n";
                $this->payload['stop_sequence'] = array("###","<human>","<bot>");
            break;

//<system>: [system prompt]
//<human>: [question]
//<bot>:

            case PromptFormat::Alpaca:
                $this->promptPrefix = "\n### Instruction:\n";
                $this->promptPostfix = "\n### Response:\n";
                $this->payload['stop_sequence'] = array("### Instruction:", "### Response:");
            break;
            case PromptFormat::VicunaShort:
                $this->promptPrefix = "\nUSER:\n";
                $this->promptPostfix = "\nASSISTANT:\n";
                $this->payload['stop_sequence'] = array("USER:", "ASSISTANT:");
            break;
            
            case PromptFormat::Mistral:
            case PromptFormat::LlamaChat:
                $this->promptPrefix = "\n[INST] \n";
                $this->promptPostfix = "\n[/INST]\n";
                $this->payload['stop_sequence'] = array("INST]", "[/INST]");
            break;

            case PromptFormat::Ollama:
                $this->promptPrefix = "\n### Input:\n";
                $this->promptPostfix = "\n### Response:\n";
                $this->payload['stop_sequence'] = array("### Input:", "### Response:");
            break;

            case PromptFormat::GemmaIT:
                $this->promptPrefix = "<bos><start_of_turn>user\n";
                $this->promptPostfix = "<end_of_turn>\n<start_of_turn>model\n";
                $this->payload['stop_sequence'] = array("<end_of_turn>", "<bos>");
            break;

            case PromptFormat::None:
                $this->promptPrefix = "";
                $this->promptPostfix = "";
                if( isset($this->payload['stop_sequence']) ){
                    unset($this->payload['stop_sequence']);
                }

            break;
        }        
    }

    private function ApplyFormat($prompt){       
        return $this->promptPrefix . $prompt . $this->promptPostfix;
    }

    

/*
    {"n": 1, "max_context_length": 2048, "max_length": 2047, "rep_pen": 1.1, "temperature": 0.79, "top_p": 0.8, "top_k": 100, "top_a": 0, "typical": 1, "tfs": 1, "rep_pen_range": 320, "rep_pen_slope": 0.7, "sampler_order": [6, 0, 1, 3, 4, 2, 5], "memory": "", "min_p": 0, "presence_penalty": 0, "genkey": "KCPP1446", "prompt": "\n", "quiet": true, "stop_sequence": ["### Instruction:", "### Response:"], "use_default_badwordsids": false}
*/

    public function SetMemory($memory){
        $this->payload['memory'] = $memory;
    }

    public function SetTemperature($temperature){
        if( is_numeric($temperature)){
            $this->payload['temperature'] = floatval($temperature);
        }
    }

    private function PayloadJson(){
        return json_encode($this->payload) ;
    }

    private function ParseToArray($response){
        if( is_string($response) && strpos($response, '}') != -1 ){
            $array = json_decode( $response, true );
        }else{
            $array = json_decode( json_encode( $response ) , true );
        }
        return $array;
    }

    private function GetResult($response){
        $parsed = $this->ParseToArray($response);
        //var_dump( $parsed );

        return $parsed['results'][0]['text'];
    }


    // Utils::GetIdentity()
    public function Generate($prompt = false) {
        if( $prompt !== false){
            $this->SetPrompt($prompt);
        }


        // Generate curl request
        $endpoint = $this->host . '/api/v1/generate';
        //echo ' '. $endpoint . ' ';
        $session = curl_init($endpoint);

        //var_dump( $this->PayloadJson() );
        // Tell PHP not to use SSLv3 (instead opting for TLS)
        //curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        //curl_setopt($session, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $apiley));
        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt ($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt ($session, CURLOPT_POSTFIELDS, $this->PayloadJson() );
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        // obtain response
        $response = curl_exec($session);
        curl_close($session);

        //there's nothing other than wrapper on the result, lets remove it and just return the result
        return $this->GetResult($response);
    }

    public function GetModel(){
        $endpoint = $this->host . '/api/v1/model';
        $json_string = file_get_contents(  $endpoint );
        $json = $this->ParseToArray($json_string);
        if( isset($json['result']) ){
            return $json['result'];
        }
        return $json_string;

    }

    
}
    