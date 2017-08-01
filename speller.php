<?php

class SpellCheck
{
    private $filename = "big.txt";

    private $words;
    private $wordList;
    private $query;
    private $candidates;
    
    private $fileLoadTime = 0;
    private $removeCharTime = 0;
    private $swapCharTime = 0;
    private $replaceCharTime = 0;
    private $addCharTime = 0;
    
    public function __construct() {
       $time_start = microtime(true);
    
       $this->words = file_get_contents($this->filename);
       $this->wordList = $this->wordCount($this->words);
       
       $time_end = microtime(true);
       
       $this->fileLoadTime = $time_end - $time_start;
    }

    function minLength($word) {
        return strlen($word) > 2;
    }

    function wordCount($text) {
        $return = array_count_values(array_filter(str_word_count(strtolower(str_replace("'","",str_replace("--"," ",$text))), 1, "1234567890-_"), array($this, "minLength")));
        
        return $return;
    }
    
    function correction($query) {
        $this->query = $query;
        $this->candidates = array_unique($this->candidates());
        
        /*
        print_r($this->fileLoadTime);
        print_r($this->removeCharTime);
        print_r($this->swapCharTime);
        print_r($this->replaceCharTime);
        print_r($this->addCharTime);
        //exit;
        
        */
        
        // filter out nonsense
        $this->candidates = array_intersect($this->candidates, array_keys($this->wordList));

        return array_filter($this->wordList, function($testWord) { return in_array($testWord, $this->candidates); }, ARRAY_FILTER_USE_KEY);
        //array($this, "containsWord")
    }

    function containsWord($testword) {
        return in_array($testword, $this->candidates); // && array_key_exists($testword, $this->wordList);
    }

    function probability($word) {
        if (array_key_exists($word, $this->wordList)) {
            return $wordList[$word]  / array_sum($wordList);
        }
        return 0;
    }

    function candidates() {
        return array_merge(
            $this->known($this->query),
            $this->edits1($this->query),
            $this->edits2($this->query)
        );
    }

    function known($words) {
        $wordList = array_keys($this->wordCount($words));
        return $wordList;
    }
    
    function edits1($words) {
        $returnArray = array();
    
        $letters = "abcdefghijlkmnopqrstuvwxyz";
        
        $splits = array();
        $length = mb_strlen($words, "UTF-8");
        for ($i = 0; $i < $length; $i ++) {
            $splits[] = array(mb_substr($words, 0, $i, "UTF-8"), mb_substr($words, $i, $length-$i, "UTF-8"));
        }
        
        $splitlength = count($splits);
        for ($i = 0; $i < $splitlength-1; $i++) {
            $time_start = microtime(true);
                // remove a character
                $returnArray[] = $splits[$i][0].$splits[$i+1][1];
            $time_end = microtime(true);
            $this->removeCharTime += ($time_end - $time_start);
            
            $time_start = microtime(true);
                // swap 2 characters
                $newEnd = $splits[$i][1];
                $newEnd = strrev(mb_substr($newEnd, 0, 2)).mb_substr($newEnd, 2);
                $returnArray[] = $splits[$i][0].$newEnd;
            $time_end = microtime(true);
            $this->swapCharTime += ($time_end - $time_start);
            
            $charArr = str_split($letters);
            
            // replace a character
            $time_start = microtime(true);
            for ($j = 0; $j < count($charArr); $j++) {
                $returnArray[] = $splits[$i][0].$charArr[$j].$splits[$i+1][1];
            }
            $time_end = microtime(true);
            $this->replaceCharTime += ($time_end - $time_start);
            
            // add a character
            $time_start = microtime(true);
            for ($j = 0; $j < count($charArr); $j++) {
                $returnArray[] = $splits[$i][0].$charArr[$j].$splits[$i][1];
            }
            $time_end = microtime(true);
            $this->addCharTime += ($time_end - $time_start);
        }
        return array_values(array_unique($returnArray));
    }
    
    function edits2($words) {
        $edit1words = $this->edits1($words);
        
        $returnArray = array();
        for($i = 0; $i < count($edit1words); $i++) {
            $firstEditWord = $edit1words[$i];
        
            $returnArray = array_merge($returnArray, $this->edits1($firstEditWord));
            
        }
        
        return array_values(array_unique($returnArray));
    }

}

$spellcheck = new SpellCheck();

$word = $argv[1];


print_r($spellcheck->correction($word));


?>

