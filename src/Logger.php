<?php

namespace App;

class Logger
{
    public function log(string $loginfo)
    {
        var_dump("LOGGING FICTIF : " . $loginfo);
    }
}
