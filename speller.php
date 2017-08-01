<?php

class SpellCheck
{
    private $filename = "big.txt";

    private $words;
    private $wordList;
    private $query;
    private $candidates;
    
    public function __construct() {
       $this->words = file_get_contents($this->filename);
       $this->wordList = $this->wordCount($this->words);
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
        
        //print_r($this->candidates);
        // exit;

        return array_filter($this->wordList, array($this, "containsWord"), ARRAY_FILTER_USE_KEY);
    }

    function containsWord($testword) {
        return array_key_exists($testword, $this->wordList) && in_array($testword, $this->candidates);
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
            $this->edits1($this->query)//,
            //$this->edits2($this->query)
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
            // remove a character
            $returnArray[] = $splits[$i][0].$splits[$i+1][1];
            
            // swap 2 characters
            $newEnd = $splits[$i][1];
            $newEnd = strrev(mb_substr($newEnd, 0, 2)).mb_substr($newEnd, 2);
            $returnArray[] = $splits[$i][0].$newEnd;
            
            $charArr = str_split($letters);
            
            // replace a character
            for ($j = 0; $j < count($charArr); $j++) {
                $returnArray[] = $splits[$i][0].$charArr[$j].$splits[$i+1][1];
            }
            
            // add a character
            for ($j = 0; $j < count($charArr); $j++) {
                $returnArray[] = $splits[$i][0].$charArr[$j].$splits[$i][1];
            }
        }
        return array_values(array_unique($returnArray));
    }
    
    function edits2($words) {
        $edit1words = $this->edits1($words);
        
        $returnArray = array();
        for($i = 0; $i < count($edit1words)-1; $i++) {
            $firstEditWord = $edit1words[$i];
        
            $returnArray = array_merge($returnArray, $this->edits1($firstEditWord));
            
        }
        
        return array_values(array_unique($returnArray));
    }

}

$spellcheck = new SpellCheck();


print_r($spellcheck->correction("boken"));


?>

