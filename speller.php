<?php

class SpellCheck
{
  
    /**
     * Minimal word size
     */
    const MINWORDSIZE = 4;
  
    /**
     * file containing loads of text
     * 
     * @var String 
     */
    private $filename = "big.txt";

    /**
     * Array containing 'word' -> #occurences
     * 
     * @var array 
     */
    private $wordList;
        
    /**
     * Array containing '#' -> word / sorted
     * 
     * @var array 
     */
    private $variantList = array();
    
    /**
     * user input
     *
     * @var string 
     */
    private $query;
    
    /**
     * Array containing list of candidate words (user word with edits)
     *
     * @var array
     */
    private $candidates;

    /**
     * load the file and build wordList
     */
    public function __construct() {
       $words = file_get_contents($this->filename);
       $this->wordList = $this->wordCount($words);
    }

    /**
     * Is word longer than the minimum length?
     * 
     * @param String $word
     * @return boolean
     */
    function minLength($word) {
        return strlen($word) >= SpellCheck::MINWORDSIZE;
    }


    /**
     * build wordlist from text
     * 
     * @param string $text
     * @return array containing 'word' -> #occurences
     */
    function wordCount($text) {
        return array_count_values(array_filter(str_word_count(strtolower(str_replace("'","",str_replace("--"," ",$text))), 1, "1234567890-_"), array($this, "minLength")));
    }

    /**
     * find and return valid candidate words
     * 
     * @param string $query
     * @return array containing 'word' -> #probability
     */
    function find($query) {
        $this->query = $query;
        
        $this->candidates = $this->getCandidates();
        sort($this->candidates);  // reIndex

        $ret = array();
        for ($i = 0; $i < count($this->candidates); $i++) {
            $ret[$this->candidates[$i]] = number_format((float)$this->probability($this->candidates[$i]), 6, '.', '');
        }
        return $ret;
    }

    /**
     * calculate probability of word
     * 
     * @param string $word
     * @return int
     */
    function probability($word) {
        if (array_key_exists($word, $this->wordList)) {
            return $this->wordList[$word]  / array_sum($this->wordList);
        }
        return 0;
    }

    /**
     * get candidate wordlist
     * 
     * @return array
     */
    function getCandidates() {
        $known = $this->known($this->query);
        $edits = $this->edits($this->query);
        $merge = array_merge($known, $edits);
        $unique = array_unique($merge);
        
        return array_flip(array_intersect_key(array_flip($unique), $this->wordList));
    }

    /**
     * get known word (test word without corrections)
     * 
     * @param string $words
     * @return array containing words
     */
    function known($words) {
        // $wordList = array_keys($this->wordCount($words));
        $wordList = array();
        $wordList[] = $words;
        return $wordList;
    }
    
    
    function edits($words) {
        $this->editsOne($words);
        $this->editsTwo();
        return $this->variantList;
    }

    /**
     * get 1-edit words (test word with 1 corrections)
     * 
     * @param string $words
     * @return array containing words
     */
    function editsOne($words) {
        $letters = "abcdefghijlkmnopqrstuvwxyz";

        $splits = array();
        $length = mb_strlen($words, "UTF-8");
        for ($i = 0; $i < $length; $i ++) {
            $splits[] = array(mb_substr($words, 0, $i, "UTF-8"), mb_substr($words, $i, $length-$i, "UTF-8"));
        }

        $splitlength = count($splits);
        for ($i = 0; $i < $splitlength-1; $i++) {
            // remove a character
            $this->variantList[] = $splits[$i][0].$splits[$i+1][1];

            // swap 2 characters
            $newEnd = $splits[$i][1];
            $newEnd = strrev(mb_substr($newEnd, 0, 2)).mb_substr($newEnd, 2);
            $this->variantList[] = $splits[$i][0].$newEnd;

            $charArr = str_split($letters);

            // replace a character
            for ($j = 0; $j < count($charArr); $j++) {
                $this->variantList[] = $splits[$i][0].$charArr[$j].$splits[$i+1][1];
            }

            // add a character
            for ($j = 0; $j < count($charArr); $j++) {
                $this->variantList[] = $splits[$i][0].$charArr[$j].$splits[$i][1];
            }
        }
        // add char at end
        for ($j = 0; $j < count($charArr); $j++) {
            $this->variantList[] = $words.$charArr[$j];
        }
        
        return $this->variantList;
    }

    /**
     * get 2-edit words (test word with 2 corrections)
     * 
     * @param string $words
     * @return array containing words
     */
    function editsTwo() {
        $edit1list = $this->variantList;
        for($i = 0; $i < count($edit1list); $i++) {
            $firstEditWord = $edit1list[$i];
            $this->editsOne($firstEditWord);
        }
        return $this->variantList;
    }

}

$time_start = microtime(true);

$spellcheck = new SpellCheck();
$word = $argv[1];

$time_middle = microtime(true);

print_r($spellcheck->find(strtolower($word)));

$time_end = microtime(true);

$time1 = $time_middle - $time_start;
$time2 = $time_end - $time_middle;

echo "Did startup in $time1 seconds\n";
echo "Did find in $time2 seconds\n";

?>
