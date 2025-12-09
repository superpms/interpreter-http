<?php
namespace pms;

use pms\hook\InterpreterHook;
use pms\hook\TerminalCommandHook;
use pms\interpreter\http\Interpreter;

InterpreterHook::mount(
    'http',
    Interpreter::class
);

if(class_exists('pms\hook\TerminalCommandHook')){
    TerminalCommandHook::mount(
        program\http\DevHttpServerCommand::class
    );
}