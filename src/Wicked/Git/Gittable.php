<?php

namespace Wicked\Git;

/**
 * Interface Gittable
 *
 * @package Wicked\Git
 */
interface Gittable {
    /**
     * Dereference a reference into a SHA
     *
     * @param string $reference
     *
     * @return string SHA
     */
    public function dereference($reference);

    /**
     * Set the internal branch pointer to the given branch name
     *
     * @param $name
     */
    public function setBranch($name);

    /**
     * @param $name
     */
    public function createBranch($name);

    /**
     * @param $name
     * @param $mustBeMerged
     */
    public function deleteBranch($name, $mustBeMerged);

    # plumbing

    /**
     * @param string $sha
     *
     * @return string
     */
    public function catFile($sha);

    /**
     * @param string $sha
     *
     * @return string
     */
    public function loadTree($sha);

    /**
     * @param string $filename
     *
     * @return string[] An array of SHAs of the commits the filename is in
     */
    public function log($filename);

    /**
     * @param string $sha
     *
     * @return string[] An array of filenames in the given commit
     */
    public function files($sha);

    /**
     * @param string $sha
     *
     * @return Metadata
     */
    public function commitMetadata($sha);

    # read

    /**
     * Get a tree for a given path
     *
     * @param string $path
     *
     * @return Tree
     */
    public function tree($path = '.');

    /**
     * Get a commit tree starting at the given SHA
     *
     * @param string $sha
     *
     * @return Commit
     */
    public function commits($sha = null, $number = 20);

    /**
     * Get a commit
     *
     * @param string $sha The SHA of the commit to fetch, if not given the HEAD of the current branch is fetched
     *
     * @return Commit
     */
    public function commit($sha = null);

    /**
     * Get a file from the head
     *
     * @param string $filename
     *
     * @return Blob
     */
    public function file($filename);

    /**
     * Get the current index
     *
     * @return Commit
     */
    public function index();

    # write

    /**
     * Add a new file
     *
     * @param string $filename      Name of the file to add
     * @param string $content       Content to add
     * @param string $commitMessage Commit message to use. If not provided, addition will not be committed and must be comitted manually.
     *
     * @return bool
     */
    public function add($filename, $content, $commitMessage = null);

    /**
     * Update an existing file
     *
     * @param string $filename      Name of the file to update
     * @param string $content       New content
     * @param string $commitMessage Commit message to use. If not provided, addition will not be committed and must be comitted manually.
     */
    public function update($filename, $content, $commitMessage = null);

    /**
     * Rename a file
     *
     * @param string $from          Name of the file to rename
     * @param string $to            New name of the file
     * @param string $commitMessage Commit message to use. If not provided, addition will not be committed and must be comitted manually.
     */
    public function move($from, $to, $commitMessage = null);

    /**
     * Copy a file
     *
     * @param string $from          Name of the file to copy
     * @param string $to            New name of the copy
     * @param string $commitMessage Commit message to use. If not provided, addition will not be committed and must be comitted manually.
     */
    public function copy($from, $to, $commitMessage = null);

    /**
     * Delete a file
     *
     * @param string $filename      Name of the file to delete
     * @param string $commitMessage Commit message to use. If not provided, addition will not be committed and must be comitted manually.
     */
    public function remove($filename, $commitMessage = null);

    /**
     * Commit the index
     *
     * @param string $commitMessage
     *
     * @return string
     */
    public function save($commitMessage);

}
