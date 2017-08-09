# phpSpellCheck

Small spellchecked in PHP,

supports:

- missing character
- removing a character
- 2 characters switched
- replace a character

(2 edits allowed per word)

Usage:

`php speller.php nothin`

returns:

```
Array
(
    [nothing] => 0.000723
    [nothings] => 0.000002
    [noting] => 0.000011
    [notion] => 0.000018
    [thin] => 0.000188
    [within] => 0.000356
)
```