<?php

namespace Torzer\GitlabFlow\Helpers;

/**
 * Helper class with Git functions
 *
 * @author nunomazer
 */
class Git {

    static function push($source) {
        $this->info('Pushing ' . $source . ' to origin ... wait ...');
        exec('git push origin ' . $source . ' --progress 2>&1', $out, $status);
        foreach ($out as $line) {
            $this->line($line);
        }
        if ($status) {
            if ($this->confirm('Continue?') == false) {
                $this->warn('Command cancelled!');
                return false;
            }
        }

        return true;
    }

}
