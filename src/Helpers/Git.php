<?php

namespace Torzer\GitlabFlow\Helpers;

/**
 * Helper class with Git functions
 *
 * @author nunomazer
 */
class Git {

    static function push($source, $console = null) {
        $console->info('Pushing ' . $source . ' to origin ... wait ...');
        exec('git push origin ' . $source . ' --progress 2>&1', $out, $status);
        foreach ($out as $line) {
            $console->line($line);
        }
        if ($status) {
            if ($console) {
                if ($console->confirm('Continue?') == false) {
                    $console->warn('Command cancelled!');
                    return false;
                }
            }
        }

        return true;
    }

}
