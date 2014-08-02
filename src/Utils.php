<?php

namespace ajf\Hanno;

class Utils
{
    /* Runs a task in an infinite loop */
    static function run(\Iterator $task)
    {
        while ($task->valid()) {
            $task->next();
        }
    }
}