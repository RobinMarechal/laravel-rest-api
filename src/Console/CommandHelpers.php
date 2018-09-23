<?php

namespace RobinMarechal\RestApi\Commands;

class CommandHelpers
{
    const CONSOLE_RED = "\e[31m";
    const CONSOLE_GREEN = "\e[32m";
    const CONSOLE_DEFAULT = "\e[0m";
    const CONSOLE_BOLD = "\e[1m";

    public static function removeLastChar(&...$param)
    {
        foreach ($param as &$p) {
            $p = substr($p, 0, -1);
        }
    }


    public static function printError($msg)
    {
        print(self::CONSOLE_BOLD . self::CONSOLE_RED . $msg . self::CONSOLE_DEFAULT);
    }


    public static function printSuccess($msg)
    {
        print(self::CONSOLE_BOLD . self::CONSOLE_GREEN . $msg . self::CONSOLE_DEFAULT);
    }


    public static function createFile($path, $content)
    {
        if (is_file($path)) {
            CommandHelpers::printError("ERROR: The file '$path' already exists!\n");

            return false;
        }

        $resource = fopen($path, 'w');
        fputs($resource, $content);
        fclose($resource);

        return true;
    }
}