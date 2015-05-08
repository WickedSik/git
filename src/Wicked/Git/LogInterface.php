<?php
/**
 * Created by PhpStorm.
 * User: jurrien.dokter
 * Date: 8-5-2015
 * Time: 14:35
 */

namespace Wicked\Git;


interface LogInterface {
    public function log($m);
    public function start($m, array $p = array());
    public function end($m, $r = null);
    public function ret($m, $r = null);
    public function dump($n, $v);
    public function group($t);
    public function endgroup();
}