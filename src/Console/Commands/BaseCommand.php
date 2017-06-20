<?php

namespace Torzer\GitlabFlow\Console\Commands;

use Illuminate\Console\Command;

/**
 * Description of BaseCommand
 *
 * @author nunomazer
 */
abstract class BaseCommand extends Command {

    abstract public function handle();

    /**
     * Print logo
     */
    protected function displayLogo() {
        $this->comment(".___________.  ______   .______      ________   _______ .______      ");
        $this->comment("|           | /  __  \  |   _  \    |       /  |   ____||   _  \     ");
        $this->comment("`---|  |----`|  |  |  | |  |_)  |   `---/  /   |  |__   |  |_)  |    ");
        $this->comment("    |  |     |  |  |  | |      /       /  /    |   __|  |      /     ");
        $this->comment("    |  |     |  `--'  | |  |\  \----. /  /----.|  |____ |  |\  \----.");
        $this->comment("    |__|      \______/  | _| `._____|/________||_______|| _| `._____|");
        $this->comment("");
        $this->comment(" developed with ♥ by ( t | o | r | z | e | r ) team - version ." . $this->version());
        $this->line('');
    }

    protected function version() {
        return exec('cd vendor/torzer/laravel-common; git describe --tags --abbrev=0');
    }

}
