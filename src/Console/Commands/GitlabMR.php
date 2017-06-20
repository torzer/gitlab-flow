<?php

namespace Torzer\GitlabFlow\Console\Commands;

use Illuminate\Console\Command;
use Torzer\GitlabClient\Gitlab;

class GitlabMR extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gitlab:mr '
                            . '{--source= : the name of the source branch, default is the current branch}'
                            . '{--target= : the name of the target branch to create the MR, default is set in project gitlab config}'
                            . '{--D|description= : a long text description for the MR}'
                            . '{--T|title= : a short text description (title) for the MR}'
                            . '{--no-assignee : set this if no assignee will be made, otherwise you\'ll be asked to choose the assignee user }'
                            . '{--no-milestone : set this if no milestone will be set, otherwise you\'ll be asked to choose the milestone }'
                            . '{--wip : set this if you want to create a WIP MR }'
                            . '{--no-push : don\'t push current branch to remote origin before open MR }'
                            . '{--remove-source : used when merging after MR, set the acceptance to remove source }'
                            . '{--update-local : used when merging after MR, checkout target source and pull it after merge}'
                            . '{--tag-after= : used when merging after MR, checkout target source, pull it and tag it after merge}'
                            . '{--merge : set this if you want to create the MR and then merge it }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a Merge Request from actual branch in project on Gitlab repository';

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

        $target = $this->getTarget();

        $source = $this->getSource();

        if ($this->option('no-push') == false) {
            if ($this->confirm('PUSH changes before open the MR?', true)) {
                if (\Torzer\GitlabFlow\Helpers\Git::push($source, $this) === false) return ;
            }
        }

        $title = $this->getTitle($gl, $source, $project_id);

        $description = $this->getMRDescription($source);

        $assignee_id = null;
        if ($this->option('no-assignee') == false) {
            $assignee_id = $this->askAssignee($gl, $project_id);
        }

        $milestone_id = null;
        if ($this->option('no-milestone') == false) {
            $milestone_id = $this->askMilestone($gl, $project_id);
        }

        try {
            $this->info('Creating MR ... wait ... this can take a while ...');

            $mr = $gl->createMR(
                    $project_id,
                    $source,
                    $target,
                    $title,
                    $description,
                    $assignee_id,
                    $milestone_id
                );

            $this->info('');
            $this->info('  MR !'.$mr->iid. ' created.');
            $this->info('');

            if ($this->option('merge')) {
                $this->line('');
                $this->line('--------------------------');
                $this->info('*  Calling Accept Merge  *');
                $this->line('--------------------------');
                $this->line('');
                $this->call('gitlab:mr-merge', [
                    'id' => $mr->iid,
                    '--remove-source' => $this->option('remove-source'),
                    '--update-local' => $this->option('update-local'),
                    '--tag-after' => $this->option('tag-after'),
                ]);
            }
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            $this->info('');
            $this->error('  Http status error: ' . $ex->getCode() . ' - ' . $ex->getResponse()->getReasonPhrase());
            $this->error('  ' . $ex->getResponseBodySummary($ex->getResponse()));
            $this->info('');
        } catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
    }

    protected function askAssignee(Gitlab $gl, $project_id) {
        $this->info('Loading members ...');
        $members = $gl->getProjectMembers($project_id);
        $choice = ['No assignee'];
        foreach ($members as $member) {
            $choice[] = $member->name;
        }

        $assignee_choosen = $this->choice('Assignee to:', $choice );

        $assignee_id = null;
        if ($assignee_choosen != 'No assignee') {
            foreach ($members as $member) {
                if ($member->name == $assignee_choosen) {
                    $assignee_id = $member->id;
                }
            }
        }

        return $assignee_id;
    }

    protected function askMilestone(Gitlab $gl, $project_id) {
        $this->info('Loading milestones ...');
        $milestones = $gl->getProjectMilestones($project_id, true);
        $choice = ['No milestone'];
        foreach ($milestones as $ml) {
            $choice[] = $ml->title;
        }

        $ml_choosen = $this->choice('Milestone:', $choice );

        $ml_id = null;
        if ($ml_choosen != 'No milestone') {
            foreach ($milestones as $ml) {
                if ($ml->title == $ml_choosen) {
                    $ml_id = $ml->id;
                }
            }
        }

        return $ml_id;
    }

    protected function getTarget() {
        $target = config('gitlab-flow.default.mr.target-branch');
        if ($this->option('target')) {
            $target = $this->option('target');
        }

        return $target;
    }

    protected function getSource() {
        $source = exec('git rev-parse --abbrev-ref HEAD');
        if ($this->option('source')) {
            $source = $this->option('source');
        }

        return $source;
    }

    protected function getIssue($source) {
        return $issue = intval(explode('-', $source)[0]);
    }

    protected function getTitle(Gitlab $gl, $source, $project_id) {
        $issue = $this->getIssue($source);

        if ($this->option('title')) {
            $title = $this->option('title');
        } else {
            $title = 'Resolve "' . $source . '"';

            if ($issue > 0) {
                $this->info('Loading issue title ...');
                $title = 'Resolve "'. $gl->getIssue($project_id, $issue)->title .'"';
                $this->warn('Title: ' . $title);
            }
        }

        if ($this->option('wip')) {
            $title = 'WIP: ' . $title;
        }

        return $title;

    }

    protected function getMRDescription($source) {
        $issue = $this->getIssue($source);
        $description = null;
        if ($this->option('description')) {
            $description = $this->option('description');
        } else {
            if ($issue > 0 ) {
                $description = 'Closes%20%23' . $issue;
            }
        }

        return $description;
    }
}
