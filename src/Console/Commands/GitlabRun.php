<?php

namespace Torzer\GitlabFlow\Console\Commands;

use Illuminate\Console\Command;
use Torzer\GitlabClient\Gitlab;

class GitlabRun extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gitlab:run '
                            . '{flow=default : the name of the flow to be executed, it is a section in ".gitlab-flow" file formated as a ini file.}'
                            . '{--show-config : list the settings in .gitlab-flow file.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run one of the custom flows described in ".gitlab-flow" file';

    protected $ini;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->displayLogo();

        $gl = Gitlab::client(config('gitlab-flow.api.token'), config('gitlab-flow.api.url'));
        $project_id = config('gitlab-flow.default.project.id');

        $this->loadGitlabFlowFile($this->argument('flow'));

        try {
            $this->info('Loading flow');

            $command = 'gitlab:'. $this->ini['command'];

            $opt = [];

            foreach ($this->ini as $key => $value) {
                if ($key != 'command') {

                    if ($value == 'ask') {
                        $opt[$key] = $this->askValue($key);
                    } else {
                        $opt[$key] = $value;
                    }
                }
            }

            $this->line('');
            $this->line('--------------------------');
            $this->info('  CALLING FLOW '.$this->argument('flow').'  ');
            $this->warn('  '.$command.'  ');
            $this->info('  OPTIONS  ');
            $this->warn('  '. var_export($opt, true).'  ');
            $this->line('--------------------------');
            $this->line('');

            $this->call($command, $opt);

        } catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
    }

    protected function loadGitlabFlowFile($section="default") {
        $file = getcwd() . '/.gitlab-flow';
        if (file_exists($file)) {
            $ini = parse_ini_file($file, true);

            if ($this->option('show-config')) {
                dd($ini);
            }

            if (isset($ini[$section])) {
                return $this->ini = $ini[$section];

            }

            $this->error('Cannot find section "'.$section.'" !!!');
            exit;
        }

        $this->error('Cannot find or read ".gitlab-flow" file !!');
        exit;
    }


    protected function askValue($key) {
        return $this->ask('Please enter the value for the argument/option " '.$key.' "');
    }

}
