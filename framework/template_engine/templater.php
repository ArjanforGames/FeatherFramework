<?php

namespace Framework;

define('VIEWS_DIRECTORY', 'framework/views/');
define('VIEWS_INCLUDES', 'includes/');
define('VIEWS_EXTENSION', '.fft');
define('TEMPLATE_FUNCTIONS_DELIMITER', '@');

class Template{
    private $rawFile = null;
    private $parsedFile = null;
    private $data = null;

    public function __construct($file, $data = null){
        $this->rawFile = file_get_contents(VIEWS_DIRECTORY . $file . VIEWS_EXTENSION);
        $this->data = is_null($data) ? array() : $data;
        if(!file_exists(VIEWS_DIRECTORY . $file . VIEWS_EXTENSION)) echo 'Cannot find view';
    }

    public function parse(){
        $this->parsedFile = $this->rawFile;

        // Skip all replacements if no data is parsed into the templater
        if(!is_null($this->data)){
            // Preg match the includes
            if(preg_match_all('/\{\{\{\s*?\w+\s*?\}\}\}/', $this->parsedFile, $matches)){
                $sizeofMatches = sizeof($matches[0]);
                for($i = 0; $i < $sizeofMatches; $i++){
                    $include = file_get_contents(VIEWS_DIRECTORY . VIEWS_INCLUDES . preg_replace('/[{\s}]/', '', $matches[0][$i]) . VIEWS_EXTENSION);
                    $this->parsedFile = str_replace($matches[0][$i], $include, $this->parsedFile);
                }
            }

            // Preg match template functions
            if(preg_match_all('/@(?!end)\w+(\(\w+\s(as)\s\w+\))?/', $this->parsedFile, $matches)){
                $sizeofMatches = sizeof($matches[0]);
                for($i = 0; $i < $sizeofMatches; $i++){
                    // matches[0] - Full regex
                    // matches[1] - Variables passed in the function ex. (users as user)
                    $funcArgs = explode(' ', str_replace(array('(', ')'), '', $matches[1][$i]));
                    if (0 === strpos($matches[0][$i], '@foreach')){
                        $functionContent = $this->getFunctionContent('@foreach' . $matches[1][$i], '@endforeach');
                        $newContent = null;
                        foreach($this->data[$funcArgs[0]] as $val){
                            $newContent .= str_replace($funcArgs[2], $val, $functionContent);
                        }
                        $this->parsedFile = str_replace($functionContent, $newContent, $this->parsedFile);
                        $this->parsedFile = $this->removeFunctionNames($matches[0][$i], '');
                        $this->parsedFile = $this->removeFunctionNames('@endforeach', '');
                    }
                }
            }

            // Preg match the variables
            if(preg_match_all('/\{\{\s*?\w+(->)?\w+?\s*?\}\}/', $this->parsedFile, $matches)){
                $sizeofMatches = sizeof($matches[0]);
                for($i = 0; $i < $sizeofMatches; $i++){
                    $match = explode('->', preg_replace('/[{\s}]/', '', $matches[0][$i]));
                    if(array_key_exists($match[0], $this->data)){
                        if(sizeof($match) == 2){
                            $this->parsedFile = str_replace($matches[0][$i], $this->data[$match[0]][$match[1]], $this->parsedFile);
                        }else{
                            $this->parsedFile = str_replace($matches[0][$i], $this->data[$match[0]], $this->parsedFile);
                        }
                    }
                }
            }
        }
        return $this->parsedFile;
    }

    private function getFunctionContent($start, $end){
        $r = explode($start, $this->parsedFile);
        if(isset($r[1])){
            $r = explode($end, $r[1]);
            return $r[0];
        }
        return '';
    }

    private function removeFunctionNames($from, $to){
        $from = '/' . preg_quote($from, '/') . '/';
        $this->parsedFile = preg_replace($from, $to, $this->parsedFile, 1);
        return $this->parsedFile;
    }
}