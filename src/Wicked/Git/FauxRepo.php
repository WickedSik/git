<?php

namespace Wicked\Git;

/**
 * Class Repo
 *
 * @package Wicked\Git
 */
class FauxRepo extends Repo implements Gittable {
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $branch = 'master';
    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @param string $repoPath
     *
     * @throws Exception
     */
    public function __construct($repoPath) {
        $this->path = $repoPath;
    }

    /**
     * @param string $command
     *
     * @return string
     * @throws Exception
     */
    private function exec($command) {}

    /**
     * @param string $name
     */
    public function setBranch($name = 'master') {
        $this->branch = $name;
    }

    /**
     * @return string
     */
    public function getCurrentBranch() {
        return $this->branch;
    }

    /**
     * @param string $name
     * @param string $email
     *
     * @throws Exception
     */
    public function setUser($name, $email) {
        $this->exec('git config --local user.name '.escapeshellarg($name));
        $this->exec('git config --local user.email '.escapeshellarg($email));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBranches() {
        return array();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTags() {
        return array();
    }

    /**
     * @param string $name
     *
     * @throws Exception
     */
    public function createBranch($name) {
    }

    /**
     * @param string $oldName
     * @param string $newName
     *
     * @throws Exception
     */
    public function renameBranch($oldName, $newName) {
    }

    /**
     * @param string $name
     * @param bool   $mustBeMerged
     *
     * @throws Exception
     */
    public function deleteBranch($name, $mustBeMerged = true) {
    }

    /**
     * @param string $name
     *
     * @throws Exception
     */
    public function checkoutBranch($name) {
    }

    /**
     * @param string $sha
     * @param string $path
     *
     * @return array
     */
    public function loadTree($sha, $path = '') {
        return array();
    }

    /**
     * @param string $sha
     *
     * @return string
     * @throws Exception
     */
    public function catFile($sha) {
        return '';
    }

    /**
     * @param string $filename
     *
     * @return array
     * @throws Exception
     */
    public function log($filename) {
        return array();
    }

    /**
     * @param string $term
     * @return string[]
     * @throws Exception
     */
    public function searchLog($term) {
        return array();
    }

    /**
     * @param string $sha
     *
     * @return array
     * @throws Exception
     */
    public function files($sha) {
        return array();
    }

    public function clean($includeUntrackedFiles = false) {
    }

    /**
     * @param string $sha
     *
     * @return Metadata
     * @throws Exception
     */
    public function commitMetadata($sha) {
        return new Metadata(null, null, null, null, null, null, null);
    }

    /**
     * @param string|null $sha
     * @param int         $number
     *
     * @return array
     */
    public function commits($sha = null, $number = 20) {
        return array();
    }

    # read

    /**
     * @param string|null $sha
     *
     * @return Commit
     */
    public function commit($sha = null) {
        $commit = new Commit($this, null);
        return $commit;
    }

    /**
     * @param string      $filename
     * @param string      $content
     * @param string|null $commitMessage
     *
     * @return bool|string
     * @throws Exception
     */
    public function update($filename, $content, $commitMessage = null) {
        return false;
    }

    /**
     * @param string      $from
     * @param string      $to
     * @param string|null $commitMessage
     *
     * @return bool|string
     */
    public function move($from, $to, $commitMessage = null) {
        return false;
    }

    /**
     * @param string      $filename
     * @param string|null $commitMessage
     *
     * @return bool|string
     * @throws Exception
     */
    public function remove($filename, $commitMessage = null) {
        return false;
    }

    /**
     * @param string      $from
     * @param string      $to
     * @param string|null $commitMessage
     *
     * @return bool|string
     * @throws Exception
     */
    public function copy($from, $to, $commitMessage = null) {
        return false;
    }

    /**
     * Add a new file
     *
     * @param string      $filename      Name of the file to add
     * @param string      $content       Content to add
     * @param string|null $commitMessage Commit message to use. If not provided, addition will not be committed and must be comitted manually.
     *
     * @return bool
     */
    public function add($filename, $content, $commitMessage = null) {
        return false;
    }

    # write

    /**
     * Commit the index
     *
     * @param string $commitMessage
     *
     * @return string
     */
    public function save($commitMessage) {
        return '';
    }

    /**
     * @param string $reference
     *
     * @return string
     * @throws Exception
     */
    public function dereference($reference) {
        return '';
    }

    /**
     * @throws Exception
     */
    public function resetIndex() {
    }

    /**
     * @param string $filename
     *
     * @return mixed
     * @throws Exception
     */
    public function file($filename) {
        return null;
    }

    /**
     * @param string $path
     *
     * @return mixed|null|Tree
     * @throws Exception
     */
    public function tree($path = '.') {
        return null;
    }

    /**
     * @param string $branch
     *
     * @return array|null
     * @throws Exception
     */
    public function mergeConflicts($branch) {
        return null;
    }

    # merging

    /**
     * @param string $branch
     *
     * @return bool
     * @throws Exception
     */
    public function canMerge($branch) {
        return true;
    }

    /**
     * Execute a merge
     *
     * @param string      $branch
     * @param string|null $commitMessage
     *
     * @return string
     * @throws Exception
     */
    public function merge($branch, $commitMessage = null) {
        return '';
    }

    public function push() {
    }

    public function pull($args) {
    }

    public function fetch($args) {
    }

    /**
     * @return array
     * @throws Exception
     */
    public function index() {
        return array();
    }

    /**
     * Update the index to reverse a previous commit
     *
     * @param string      $sha
     * @param string|null $commitMessage
     *
     * @return bool|string
     * @throws Exception
     */
    public function revert($sha, $commitMessage = null) {
        return false;
    }

    /**
     * Update the index to undo back to a previous commit
     *
     * @param string      $sha
     * @param string|null $commitMessage
     *
     * @return bool|string
     * @throws Exception
     */
    public function undo($sha, $commitMessage = null) {
        return false;
    }
}
