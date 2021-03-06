<?php

namespace Wicked\Git;

/**
 * Class Diff
 *
 * @package Wicked\Git
 */
class Diff implements \ArrayAccess {
    /**
     * @var array
     */
    public $diff;

    /**
     * @param array $diff
     */
    public function __construct($diff) {
        $this->diff = array();
        foreach ($diff as $filename => $patch) {
            preg_match_all(
                '#@@ -([0-9]+)(,[0-9]+)? \+([0-9]+)(,[0-9]+)? @@[^\n]*\n(.*)$#s',
                $patch,
                $matches,
                PREG_SET_ORDER
            );
            foreach ($matches as $match) {
                $fromA = $match[1];
                $fromB = $match[3];
                $lines = explode("\n", $match[5]);
                foreach ($lines as $lnum => $line) {
                    if ($line != '\ No newline at end of file') {
                        if (isset($lines[$lnum + 1]) && $lines[$lnum + 1] == '\ No newline at end of file') {
                            $nl = '';
                        } else {
                            $nl = "\n";
                        }
                        $operation = substr($line, 0, 1);
                        switch ($operation) {
                            case '+':
                                $this->diff[$filename][] = $fromB.',+ '.substr($line, 1).$nl;
                                $fromB++;
                                break;
                            case '-':
                                $this->diff[$filename][] = '-,'.$fromA.' '.substr($line, 1).$nl;
                                $fromA++;
                                break;
                            default:
                                $this->diff[$filename][] = $fromB.','.$fromA.' '.substr($line, 1).$nl;
                                $fromA++;
                                $fromB++;
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->diff[] = $value;
        } else {
            $this->diff[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->diff[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->diff[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return null
     */
    public function offsetGet($offset) {
        return isset($this->diff[$offset]) ? $this->diff[$offset] : null;
    }
}