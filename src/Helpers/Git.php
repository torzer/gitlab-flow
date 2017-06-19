<?php

namespace Torzer\GitlabFlow\Helpers;

/**
 * Helper class with Git functions
 *
 * @author nunomazer
 */
class Git {

    static function push($source) {
        exec('git push origin ' . $source . ' --progress 2>&1', $out, $status);
        foreach ($out as $line) {
            $this->line($line);
        }
        if ($status) {
            return false;
        }

        return true;
    }

}
