<?php

namespace Wicked\Git;

/**
 * Class Repo
 *
 * @package Wicked\Git
 */
class Repo implements Gittable {
    /**
     * @var string
     */
    private $path;
    /**
     * @var bool
     */
    private $bare = true;
    /**
     * @var string
     */
    private $branch = 'master';
    /** @var LogInterface */
    public $logger;

    /**
     * @param string $repoPath
     *
     * @param LogInterface $logger
     * @throws Exception
     */
    public function __construct($repoPath, LogInterface $logger = null) {
        $this->path = $repoPath;

        if(isset($logger)) {
            $this->logger = $logger;
        }

        if (!file_exists($this->path)) {
            if (substr($this->path, -4) != '.git') {
                $this->bare = false;
            }
            mkdir($this->path);
            if ($this->bare) {
                $this->exec('git init --bare');
            } else {
                $this->exec('git init');
            }
        } elseif (!is_dir($this->path)) {
            throw new Exception('Repo path not a directory');
        } else {
            if (is_dir($this->path.'/.git')) {
                $this->bare = false;
            }
        }

        $this->detectCurrentBranch();
    }

    /**
     * @param string $command
     *
     * @return string
     * @throws Exception
     */
    private function exec($command) {
        $cwd = getcwd();
        chdir($this->path);

        if($this->logger) {
            $this->logger->start(__METHOD__);
            $this->logger->log(':> calling[ %s]', $command);
        }

        $out = exec($command.' 2>&1', $output, $return);
        chdir($cwd);
        if ($return != 0) {
            throw new Exception('Git binary returned an error "'.$out.'"');
        }

        if($this->logger) {
            $this->logger->dump('output', $output);
            $this->logger->end(__METHOD__);
        }

        return join("\n", $output);
    }

    /**
     * @throws Exception
     */
    private function detectCurrentBranch() {
        $branchtext = $this->exec('git status -b --porcelain');
        list($line) = explode("\n", $branchtext);

        $line = trim(trim($line, '#'));
        $branches = explode('...', $line);
        $this->setBranch($branches[0]);
    }

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
        $this->detectCurrentBranch();

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
        return $this->getRefNames($this->exec('git show-ref --heads'), 'refs/heads/');
    }

    /**
     * @param string $refString
     * @param string $prefix
     *
     * @return array
     */
    private function getRefNames($refString, $prefix) {
        $refs = array();
        $trimLength = 41 + strlen($prefix);
        foreach (explode("\n", $refString) as $ref) {
            $refs[] = substr($ref, $trimLength);
        }

        return $refs;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTags() {
        return $this->getRefNames($this->exec('git show-ref --tags'), 'refs/tags/');
    }

    /**
     * @param string $name
     *
     * @throws Exception
     */
    public function createBranch($name) {
        $this->exec('git branch '.escapeshellarg($name));
    }

    /**
     * @param string $oldName
     * @param string $newName
     *
     * @throws Exception
     */
    public function renameBranch($oldName, $newName) {
        $this->exec('git branch -m '.escapeshellarg($oldName).' '.escapeshellarg($newName));
    }

    /**
     * @param string $name
     * @param bool   $mustBeMerged
     *
     * @throws Exception
     */
    public function deleteBranch($name, $mustBeMerged = true) {
        if ($mustBeMerged) {
            $this->exec('git branch -d '.escapeshellarg($name));
        } else {
            $this->exec('git branch -D '.escapeshellarg($name));
        }
    }

    /**
     * @param string $name
     *
     * @throws Exception
     */
    public function checkoutBranch($name) {
        $this->exec('git checkout ' . escapeshellarg($name));
        $this->detectCurrentBranch();
    }

    /**
     * @param string $sha
     * @param string $path
     *
     * @return array
     */
    public function loadTree($sha, $path = '') {
        $entries = array();
        $treeString = $this->catFile($sha);
        preg_match_all('/^[0-9]{6} (blob|tree) ([0-9a-f]{40})\t(.+)$/m', $treeString, $matches, PREG_SET_ORDER);
        $path = $path ? $path.'/' : '';
        foreach ($matches as $entry) {
            switch ($entry[1]) {
                case 'blob':
                    $entries[$entry[3]] = new Blob($this, $entry[2], $path.$entry[3]);
                    break;
                case 'tree':
                    $entries[$entry[3]] = new Tree($this, $entry[2], $path.$entry[3]);
                    break;
            }
        }

        return $entries;
    }

    /**
     * @param string $sha
     *
     * @return string
     * @throws Exception
     */
    public function catFile($sha) {
        return $this->exec('git cat-file -p '.$sha);
    }

    /**
     * @param string $filename
     *
     * @return array
     * @throws Exception
     */
    public function log($filename) {
        $log = $this->exec(
            'git log --format=format:"%H" refs/heads/'.escapeshellarg($this->branch).' -- '.escapeshellarg($filename)
        );

        return explode("\n", $log);
    }

    /**
     * @param string $term
     * @return string[]
     * @throws Exception
     */
    public function searchLog($term) {
        $log = $this->exec('git log --format=oneline --grep=' . $term);
        return explode(PHP_EOL, $log);
    }

    /**
     * @param string $sha
     *
     * @return array
     * @throws Exception
     */
    public function files($sha) {
        $show = $this->exec('git show --pretty="format:" --name-only '.$sha);

        return explode("\n", trim($show));
    }

    public function clean($includeUntrackedFiles = false) {
        $this->exec('git checkout .');
        if($includeUntrackedFiles) {
            $this->exec('git clean -xfd');
        }
    }

    /**
     * @param string $sha
     *
     * @return Metadata
     * @throws Exception
     */
    public function commitMetadata($sha) {
        $commitString = $this->exec('git show -U5 --format=format:'.escapeshellarg(Metadata::LOG_FORMAT).' '.$sha);
        if (!$commitString) {
            throw new Exception('Log for commit "'.$sha.'"" not found');
        }
        $parts = explode("\n", $commitString);
        $metadata = explode(',', array_shift($parts));

        if ($parts) {
            $diffString = join("\n", $parts);
        } else {
            $diffString = $this->exec('git diff -p '.$sha.'^1 '.$sha);
        }

        $diff = array();
        foreach (explode('diff --git', $diffString) as $d) {
            if ($d) {
                preg_match(
                    '#^[^\n]+\n(?:[^\n]+\n)?[^\n]+\n--- (?:/dev/null|a/([^\n]+))\n\+\+\+ (?:/dev/null|b/([^\n]+))\n(@@.+)$#s',
                    $d,
                    $matches
                );
                if (count($matches) == 4) {
                    $diff[$matches[1] ?: $matches[2]] = $matches[3];
                }
            }
        }

        return new Metadata(
            $metadata[0], // commit
            $metadata[1] ? explode(' ', $metadata[1]) : array(), // parents
            $metadata[2], // user
            $metadata[3], // email
            $metadata[4], // date
            $metadata[5], // message
            $diff //diff
        );
    }

    /**
     * @param string|null $sha
     * @param int         $number
     *
     * @return array
     */
    public function commits($sha = null, $number = 20) {
        $commits = array();
        if (!$sha) {
            $sha = 'refs/heads/'.$this->branch;
        }

        for ($foo = $number; $foo > 0; $foo--) {
            $commit = new Commit($this, $sha);
            $commits[] = $commit;

            if (!isset($commit->parents[0])) {
                break;
            }

            $sha = $commit->parents[0];
        }

        return $commits;
    }

    # read

    /**
     * @param string|null $sha
     *
     * @return Commit
     */
    public function commit($sha = null) {
        if (!$sha) {
            $sha = 'refs/heads/'.$this->branch;
        }
        $commit = new Commit($this, $sha);

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
        $sha = $this->exec('echo '.escapeshellarg($content).' | git hash-object -w --stdin');
        $this->exec('git update-index --cacheinfo 100644 '.$sha.' '.escapeshellarg($filename));
        if ($commitMessage) {
            return $this->save($commitMessage);
        }

        return true;
    }

    /**
     * @param string      $from
     * @param string      $to
     * @param string|null $commitMessage
     *
     * @return bool|string
     */
    public function move($from, $to, $commitMessage = null) {
        $this->remove($from);

        return $this->copy($from, $to, $commitMessage);
    }

    /**
     * @param string      $filename
     * @param string|null $commitMessage
     *
     * @return bool|string
     * @throws Exception
     */
    public function remove($filename, $commitMessage = null) {
        $this->exec('git rm --cached '.escapeshellarg($filename));
        if ($commitMessage) {
            return $this->save($commitMessage);
        }

        return true;
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
        $this->add($to, $this->file($from));
        if ($commitMessage) {
            return $this->save($commitMessage);
        }

        return true;
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
        $sha = $this->exec('echo '.escapeshellarg($content).' | git hash-object -w --stdin');
        $this->exec('git update-index --add --cacheinfo 100644 '.$sha.' '.escapeshellarg($filename));
        if ($commitMessage) {
            return $this->save($commitMessage);
        }

        return true;
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
        $sha = $this->exec('git write-tree');
        try {
            $parentSha = $this->dereference($this->branch);
            $sha = $this->exec('echo '.escapeshellarg($commitMessage).' | git commit-tree -p '.$parentSha.' '.$sha);
        } catch (Exception $e) {
            $sha = $this->exec('echo '.escapeshellarg($commitMessage).' | git commit-tree '.$sha);
        }
        $this->exec('git update-ref '.escapeshellarg('refs/heads/'.$this->branch).' '.escapeshellarg($sha));
        $this->resetIndex();

        return $sha;
    }

    /**
     * @param string $reference
     *
     * @return string
     * @throws Exception
     */
    public function dereference($reference) {
        if (preg_match('/^[a-f0-9]{40}$/', $reference)) {
            $sha = $reference;
        } else {
            $sha = $this->exec('git show-ref --heads -s '.escapeshellarg($reference));
            if (!$sha) {
                throw new Exception('Could not dereference '.$reference);
            }
        }

        return $sha;
    }

    /**
     * @throws Exception
     */
    public function resetIndex() {
        $this->exec('git read-tree refs/heads/'.escapeshellarg($this->branch));
    }

    /**
     * @param string $filename
     *
     * @return mixed
     * @throws Exception
     */
    public function file($filename) {
        $tree = $this->tree(dirname($filename));
        if (isset($tree[basename($filename)])) {
            return $tree[basename($filename)];
        }
        throw new Exception('File "'.$filename.'" not found');
    }

    /**
     * @param string $path
     *
     * @return mixed|null|Tree
     * @throws Exception
     */
    public function tree($path = '.') {
        if ($path == '.' || $path == '') {
            $commit = $this->exec('git cat-file -p refs/heads/'.$this->branch);
            preg_match('/^tree ([0-9a-f]{40})$/m', $commit, $match);
            if (!isset($match[1])) {
                throw new Exception('Could not find HEAD commit for '.$this->branch);
            }
            $tree = new Tree($this, $match[1]);
        } else {
            $path = explode('/', $path);
            $directory = array_pop($path);
            $parent = join('/', $path);

            $tree = $this->tree($parent)[$directory];
            if (!is_a($tree, 'Git\Tree')) {
                $tree = null;
            }
        }

        return $tree;
    }

    /**
     * @param string $branch
     *
     * @return array|null
     * @throws Exception
     */
    public function mergeConflicts($branch) {
        try {
            $this->canMerge($branch);
        } catch (Exception $e) {
            if (isset($e->filenames)) {
                $diffs = array();
                foreach ($e->filenames as $filename) {
                    $d = $this->exec(
                        'git diff '.escapeshellarg($this->branch).' '.escapeshellarg($branch).' -- '.$filename
                    );
                    preg_match(
                        '#^[^\n]+\n(?:[^\n]+\n)?[^\n]+\n--- (?:/dev/null|a/([^\n]+))\n\+\+\+ (?:/dev/null|b/([^\n]+))\n(@@.+)$#s',
                        $d,
                        $matches
                    );
                    if (count($matches) == 4) {
                        $diffs[$matches[1] ?: $matches[2]] = $matches[3];
                    }
                }
                $diff = new Diff($diffs);

                return $diff->diff;
            }
        }

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
        $sha = $this->exec('git merge-base '.escapeshellarg($this->branch).' '.escapeshellarg($branch));
        $merge = $this->exec(
            'git merge-tree '.escapeshellarg($sha).' '.escapeshellarg($this->branch).' '.escapeshellarg($branch)
        );
        if ($merge == '') {
            throw new Exception('Base branch already contains everything in head branch, nothing to merge');
        } elseif (preg_match_all('/in both\n *(?:base|our|their) +100644 [a-f0-9]+ ([^\n]+)/', $merge, $filenames)) {
            $e = new Exception('Can not merge branches without conflict, resolve conflicts first');
            if (isset($filenames[1]) && $filenames[1]) {
                $e->filenames = $filenames[1];
            }
            throw $e;
        }

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
        if ($this->index()) {
            throw new Exception('Can not merge with dirty index');
        }
        $parent1Sha = $this->dereference($this->branch);
        $parent2Sha = $this->dereference($branch);

        $this->canMerge($branch);

        if (!$commitMessage) {
            $commitMessage = 'Merge '.$branch.' into '.$this->branch;
        }

        $baseSha = $this->exec('git merge-base '.escapeshellarg($this->branch).' '.escapeshellarg($branch));
        $this->exec(
            'git read-tree -m -i '.escapeshellarg($baseSha).' '.escapeshellarg($this->branch).' '.escapeshellarg(
                $branch
            )
        );
        $sha = $this->exec('git write-tree');

        $sha = $this->exec(
            'echo '.escapeshellarg($commitMessage).' | git commit-tree -p '.$parent1Sha.' -p '.$parent2Sha.' '.$sha
        );

        $this->exec('git update-ref '.escapeshellarg('refs/heads/'.$this->branch).' '.escapeshellarg($sha));

        return $sha;
    }

    public function push() {
        $this->exec('git push origin ' . $this->branch);
    }

    public function pull($args) {
        $this->exec('git pull ' . $args);
    }

    public function fetch($args) {
        $this->exec('git fetch ' . $args);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function index() {
        $index = $this->exec('git diff-index refs/heads/'.$this->branch);
        preg_match_all(
            '/^:[0-9]{6} [0-9]{6} [0-9a-f]{40} [0-9a-f]{40} ([ACDMRTUX])[0-9]{0,3}\t(.+)$/m',
            $index,
            $matches,
            PREG_SET_ORDER
        );
        $items = array();
        foreach ($matches as $match) {
            $items[$match[2]] = $match[1];
        }

        return $items;
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
        if ($this->index()) {
            throw new Exception('Can not revert with dirty index');
        }

        try {
            $this->exec('git diff -R '.escapeshellarg($sha.'~1').' '.escapeshellarg($sha).' | git apply --index');
        } catch (Exception $e) {
            return false;
        }

        if ($commitMessage) {
            return $this->save($commitMessage);
        }

        return true;
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
        if ($this->index()) {
            throw new Exception('Can not revert with dirty index');
        }

        $this->exec('git diff -R --cached '.escapeshellarg($sha).' | git apply --index');

        if ($commitMessage) {
            return $this->save($commitMessage);
        }

        return true;
    }
}
