<?php
namespace pms;

use pms\hook\InterpreterHook;
use pms\interpreter\http\Interpreter;

InterpreterHook::mount(
    'http',
    Interpreter::class
);

if(class_exists('pms\hook\TerminalCommandHook')){
    \pms\hook\TerminalCommandHook::mount(
        'dev-http-server',
        program\http\DevHttpServerCommand::class
    );
}