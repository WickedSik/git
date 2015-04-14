<?php

namespace Wicked\Git;

/**
 * Class Commit
 *
 * @package Wicked\Git
 * @member Tree $tree
 * @member array $files
 */
class Commit
{
    /** @var Repo */
    private $repo;
    /** @var string */
    public $sha;
    /** @var Tree */
    private $tree;
    /** @var Metadata */
    private $metadata;
    /** @var array */
    private $files;

    /**
     * @param Repo $repo
     * @param $sha
     */
    public function __construct(Repo $repo, $sha)
    {
        $this->repo = $repo;
        $this->sha = $repo->dereference($sha);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->sha;
    }

    /**
     * @param Tree $tree
     */
    public function setTree(Tree $tree)
    {
        $this->tree = $tree;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'tree':
                $this->setTree($value);
                break;
        }
    }

    /**
     * @return Tree
     */
    public function getTree()
    {
        if (!is_a($this->tree, 'Tree')) {
            $this->tree = new Tree($this->repo, $this->tree);
        }
        return $this->tree;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        if (!$this->files) {
            $this->files = $this->repo->files($this->sha);
        }
        return $this->files;
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws Exception
     */
    public function getMetadata($key)
    {
        if (!$this->metadata) {
            $this->metadata = $this->repo->commitMetadata($this->sha);
        }
        return $this->metadata->$key;
    }

    /**
     * @param string $key
     *
     * @return array|mixed|Tree
     */
    public function __get($key)
    {
        switch ($key) {

            case 'tree':
                return $this->getTree();

            case 'files':
                return $this->getFiles();

            default:
                return $this->getMetadata($key);

        }
    }

}