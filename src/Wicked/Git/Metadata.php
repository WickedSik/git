<?php

namespace Wicked\Git;

/**
 * Class Metadata
 *
 * @package Wicked\Git
 */
class Metadata
{
    /**
     * @var string
     */
    public $commit;
    /**
     * @var array
     */
    public $parents = array();
    /**
     * @var string
     */
    public $user;
    /**
     * @var string
     */
    public $email;
    /**
     * @var int
     */
    public $date;
    /**
     * @var string
     */
    public $message;
    /**
     * @var Diff
     */
    public $diff;

    /**
     *
     */
    const LOG_FORMAT = '%H,%P,%aN,%aE,%at,%s';

    /**
     * @param string $commit
     * @param string $parents
     * @param string $user
     * @param string $email
     * @param int $date
     * @param string $message
     * @param array $diff
     */
    public function __construct($commit, $parents, $user, $email, $date, $message, $diff)
    {
        $this->commit = $commit;
        $this->parents = $parents;
        $this->user = $user;
        $this->email = $email;
        $this->date = (int) $date;
        $this->message = $message;
        $this->diff = new Diff($diff);
    }
}