<?php

namespace Wicked\Git;

/**
 * Class Blob
 *
 * @package Wicked\Git
 */
class Blob
{
    /** @var Repo */
    private $repo;
    /** @var Metadata */
    private $metadata;
    /** @var string */
    private $content;
    /** @var array */
    private $history = array();
    /** @var string */
    public $sha;
    /** @var string */
    public $filename;

    /**
     * @param Repo $repo
     * @param string $sha
     * @param string $filename
     */
    public function __construct(Repo $repo, $sha, $filename)
    {
        $this->repo = $repo;
        $this->sha = $sha;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        if (!$this->content) {
            $this->content = $this->repo->catFile($this->sha);
        }
        return $this->content;
    }

    /**
     * @return array
     */
    public function getHistory()
    {
        if (!$this->history) {
            $shas = $this->repo->log($this->filename);
            foreach ($shas as $sha) {
                $this->history[] = new Commit($this->repo, $sha);
            }
        }
        return $this->history;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->__get('content');
    }

    /**
     * @param string $key
     *
     * @return array|string
     */
    public function __get($key)
    {
        if ($key == 'content') {
            return $this->getContent();
        } elseif ($key == 'history') {
            return $this->getHistory();
        } else {
            return $this->getHistory()[0]->$key;
        }
    }

}