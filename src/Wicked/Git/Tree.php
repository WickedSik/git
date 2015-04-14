<?php

namespace Wicked\Git;

/**
 * Class Tree
 *
 * @package Wicked\Git
 */
class Tree implements \Iterator, \ArrayAccess {
    /**
     * @var string
     */
    public $sha;
    /**
     * @var string
     */
    public $filename;
    /**
     * @var Repo
     */
    private $repo;
    /**
     * @var int
     */
    private $position = 0;
    /**
     * @var array
     */
    private $entries = array();
    /**
     * @var array
     */
    private $entriesArray = array();

    /**
     * @param Repo   $repo
     * @param string $sha
     * @param string $filename
     */
    public function __construct(Repo $repo, $sha, $filename = '') {
        $this->repo = $repo;
        $this->sha = $sha;
        $this->filename = $filename;
    }

    /**
     * @return array
     */
    public function entries() {
        $this->loadEntries();

        return $this->entries;
    }

    /**
     *
     */
    private function loadEntries() {
        if (!$this->entries) {
            $this->entries = $this->repo->loadTree($this->sha, $this->filename);
            $this->entriesArray = array_values($this->entries);
        }
    }

    /**
     *
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * @return mixed
     */
    public function current() {
        $this->loadEntries();

        return $this->entriesArray[$this->position];
    }

    /**
     * @return int
     */
    public function key() {
        return $this->position;
    }

    /**
     *
     */
    public function next() {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid() {
        $this->loadEntries();

        return isset($this->entriesArray[$this->position]);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        $this->loadEntries();
        if (is_null($offset)) {
            $this->entries[] = $value;
        } else {
            $this->entries[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
        $this->loadEntries();

        return isset($this->entries[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        $this->loadEntries();
        unset($this->entries[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset) {
        $this->loadEntries();

        return isset($this->entries[$offset]) ? $this->entries[$offset] : null;
    }
}