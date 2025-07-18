<?php
function HttpCustomErrorHandler($errno, $errstr, $errfile, int $errline){
    throw new \pms\exception\WarningException($errno, $errstr, $errfile, $errline);
}