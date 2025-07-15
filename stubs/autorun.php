<?php
namespace pms;

facade\Interpreter::install(
    'http',
    HttpInterpreter::class
);

if(class_exists('pms\facade\TerminalCommand')){
    facade\TerminalCommand::install(
        'dev-http-server',
        source\InterpreterHttp\command\DevHttpServerCommand::class
    );
}