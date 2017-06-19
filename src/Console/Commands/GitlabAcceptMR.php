<?php

namespace Torzer\GitlabFlow\Console\Commands;

use Illuminate\Console\Command;
use Torzer\GitlabClient\Gitlab;

class GitlabAcceptMR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gitlab:mr-merge  '
                            . '{id : the id (number) of the MR at the project}'
                            . '{--m|message= : a message to be inserted in MR acceptance}'
                            . '{--remove-source : remove the source branch after merge}'
                            . '{--P|push : push current branch to remote to insert in current branch MR before merge it }'
                            . '{--update-local : checkout target source and pull it after merge}'
                            . '{--tag-after= : checkout target source, pull it and tag it after merge}'
                            . '{--y|yes : don\'t interact listing commits and issues or asking for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accept a Merge Request from current project on Gitlab repository';

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
        $mr_id = $this->argument('id');
        $project_id = config('gitlab-flow.default.project.id');

        $gl = Gitlab::client(config('gitlab-flow.api.token'), config('gitlab-flow.api.url'));

        $this->info('Checking MR state ...');
        if ($this->isMergedClosed($gl, $project_id, $mr_id)) {
            return $this->error('Can\'t accept, MR !' . $mr_id . ' is Merged or Closed already !!');
        }

        if ($this->option('yes') == false) {
            $this->listIssues($gl, $project_id, $mr_id);

            $this->listCommits($gl, $project_id, $mr_id);

            if ($this->confirm('List changes?')) {
                $this->listChanges($gl, $project_id, $mr_id);
            }
        }

        $continue = $this->option('yes');
        if ($this->option('yes') == false) {
            $continue = $this->confirm('Accept and merge this MR?',true);
        }

        if ( $continue ) {
            $this->info('Wait ... this can take a while ...');

            $message = null;
            if ($this->option('message')) {
                $message = $this->option('message');
            }

            $removeSourceBranch = $this->option('remove-source');

            try {
                $mr = $gl->acceptMR($project_id, $mr_id, $message, $removeSourceBranch);

                $this->info('');
                $this->info('  MR !'.$mr->iid. ' MERGED.');
                $this->info('');

                $this->afterMerge($gl, $project_id, $mr);

                return;

            } catch (\GuzzleHttp\Exception\ClientException $ex) {
                $this->info('');
                $this->error('  Http status error: ' . $ex->getCode() . ' - ' . $ex->getResponse()->getReasonPhrase());
                $this->error('  ' . $ex->getResponseBodySummary($ex->getResponse()));
                $this->info('');
            } catch (\Exception $ex) {
                $this->error($ex->getMessage());

                if (strpos(strtolower($ex->getMessage()), 'gitlab is not responding') !== FALSE) {
                    if ($this->isMergedClosed($gl, $project_id, $mr_id)) {
                        $this->warn('Even with the error result it seems the MR was merged.');
                        $this->warn('The error may have been generated due to an excessive server response time.');

                        $this->afterMerge($gl, $project_id, $mr_id);

                        return;
                    }
                }
            }
        }

        return $this->warn('MR still opened');
    }

    protected function afterMerge(Gitlab $gl, $project_id, $mr) {
        if ($this->option('update-local') || $this->option('tag-after')) {
            if (is_numeric($mr)) {
                $mr = $gl->getMR($project_id, $mr);
            }

            $this->updateLocal($mr);

            if ($this->option('tag-after')) {
                $this->info('Tagging');
                $gl->createTag($project_id, $this->option('tag-after'), $mr->target_branch);
                $this->info('Branch ' . $mr->target_branch . ' tagged with name ' . $this->option('tag-after'));
            }
        }
    }

    protected function isMergedClosed(Gitlab $gl, $project_id, $mr_id) {
        $mr = $gl->getMR($project_id, $mr_id);

        return ($mr->state == 'merged' || $mr->state == 'closed');
    }

    public function listCommits(Gitlab $gl, $project_id, $mr_id) {
        $this->info('Loading commits for this MR ...');

        $commits = $gl->getMRCommits($project_id, $mr_id);

        $tableCommits = [];

        $this->warn('Commits in this MR');
        foreach ($commits as $key => $commit) {
            $tableCommits[] = [
                $commit->short_id,
                $commit->title,
                $commit->author_name,
                \Carbon\Carbon::parse($commit->created_at)->toFormattedDateString(),
                $commit->message,
            ];
        }

        if (empty($tableCommits)) {
            return $this->warn(' - No commits in this MR');
        }

        return $this->table(['Hash','Title','Atuhor','Created','Message'], $tableCommits);
    }

    public function listIssues(Gitlab $gl, $project_id, $mr_id) {
        $this->info('Loading issues that will be closed in this MR ...');

        $issues = $gl->getMRIssues($project_id, $mr_id);

        $tableIssues = [];

        $this->warn('ISSUES to be closed');
        foreach ($issues as $key => $issue) {
            $tableIssues[] = [
                $issue->iid,
                $issue->description,
                $issue->author->name,
                $issue->assignee->name,
                \Carbon\Carbon::parse($issue->created_at)->toFormattedDateString(),
            ];
        }

        if (empty($tableIssues)) {
            return $this->warn(' - No issues will be closed in this MR');
        }

        return $this->table(['id','Title','Auhor','Assignee','Created'], $tableIssues);
    }

    public function listChanges(Gitlab $gl, $project_id, $mr_id) {
        $this->info('Loading changes in this MR ...');

        $changes = $gl->getMRChanges($project_id, $mr_id)->changes;

        $noChanges = true;

        $this->warn('Changes in this MR');
        foreach ($changes as $key => $change) {
            $noChanges = false;

            $tableChanges[0] = [
                $change->old_path . ($change->old_path != $change->new_path) ? ' => ' . $change->new_path : '',
                $change->a_mode . ($change->a_mode != $change->b_mode) ? ' => ' . $change->b_mode : '',
                $change->new_file ? 'x' : '',
                $change->renamed_file ? 'x' : '',
                $change->deleted_file ? 'x' : '',
            ];

            $this->table(['Path','Mode','New file','Renamed file', 'Deleted file'], $tableChanges);

            $diff = explode(PHP_EOL, $change->diff);

            foreach ($diff as $key => $line) {
                if (substr($line, 0, 1) == '@') {
                    $this->line($line);
                }
                if (substr($line, 0, 1) == '-') {
                    $this->warn($line);
                }
                if (substr($line, 0, 1) == '+') {
                    $this->info($line);
                }
            }

            if ($this->confirm('Show next change?', true) == false) {
                break;
            }

        }

        if ($noChanges) {
            return $this->warn(' - No changes in this MR');
        }

        $this->info('End of changes.');

    }

    protected function updateLocal($mr) {
        $this->line('Checkout branch ' . $mr->target_branch);
        exec('git checkout '. $mr->target_branch, $out, $status);
        $this->showExecOutput($out);

        if ($status) {
            $this->error('Checkout err ' . $status);
            return false;
        }

        $this->line('Pull branch ' . $mr->target_branch);
        exec('git pull origin ' . $mr->target_branch, $out, $status);
        $this->showExecOutput($out);

        if ($status) {
            $this->error('Pull err ' . $status);
            return false;
        }

        return true;
    }

    protected function showExecOutput($out) {
        foreach ($out as $line) {
            $this->line($line);
        }
    }
}
