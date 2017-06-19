# torzer GitFlow

This Laravel package aims to help developers to easier adopt and decrease the operational time when
using Feature Branch Flow with Gitlab.

## Installation

Use composer to install it:

```bash
composer require torzer/gitlab-flow
```

Register the ServiceProvider in `config/app.php`

```php
Torzer\GitlabFlow\GitlabFlowServiceProvider::class,
```

## Config

In `.env` file you must set the following variables:

```ini
GITLAB_API_TOKEN=your-gitlab-token
GITLAB_DEFAULT_PROJECT_ID=project-id-on-gitlab
GITLAB_DEFAULT_MR_TARGET_BRANCH=dev
```

* **GITLAB_API_TOKEN** is your's private API Token to access Gitlab API;
* **GITLAB_DEFAULT_PROJECT_ID** is the project id on Gitlab, you can find it in settings menu;
* **GITLAB_DEFAULT_MR_TARGET_BRANCH** is the name of the default target branch to open Merge Request.
If this variable is not set, the `dev` name is used as deafult.

## Creating Merge Requests

The most commom command is used to create Merge Request from current branch.
If you type the foloowing command:

```
php artisan gitlab:mr
```

A MR gonna be created on Gitlab project, using the name of the branch as title.
If you are using feature branch name, starting with the issue number that the branch resolves,
the command inject a `Closes #issue` in description.

The default command gonna confirm if it must execute a `git push` as the first step of the proccess
and then ask you to choose the assignee and milestone from the project.

To change the behavior use some of the following options:

* --source[=SOURCE] - the name of the source branch, default is the current branch
* --target[=TARGET] - the name of the target branch to create the MR, default is set in project gitlab config
* -D, --description[=DESCRIPTION] - a long text description for the MR
* -T, --title[=TITLE] - a short text description (title) for the MR
* --no-assignee - set this if no assignee will be made, otherwise you'll be asked to choose the assignee user
* --no-milestone - set this if no milestone will be set, otherwise you'll be asked to choose the milestone
* --wip - set this if you want to create a WIP MR
* --no-push : don\'t push current branch to remote origin before open MR
* --remove-source - used when mergin after MR, set the acceptance to remove source
* --tag-after[=TAG-AFTER] - used when mergin after MR, checkout target source, pull it and tag it after merge
* --merge - set this if you want to create the MR and then merge it

Some uses:

### MR with no default source and target

This gonna ask you for assignee and milestone, setting the title for a MR from dev to stage branches.

The default command gonna confirm if it must execute a `git push` as the first step of the proccess.

```
la5 gitlab:mr --source dev --target stage -T 'Staging milestone 3'
```

### MR pushing last changes before open

This gonna push the last commits to repository before opening the MR.

```
la5 gitlab:mr --push
```

### MR followed by merge acceptance

This gonna push the last commits to repository before opening the MR, open MR,
list changes and merge it to target branch, tag it, change local branch to target and pull
from repository.

```
la5 gitlab:mr --no-assignee --no-milestone --push --remove-source --merge --tag-after 0.0.3
```

See merging request below to understand the merging behavior of the above command.

## Merging Requests

The command is used to create accept a Merge Request from its id.
If you type the foloowing command:

```
php artisan gitlab:mr-merge 99
```

It will check the MR state, list members of the project to you choose assignee,
list milestones to you choose, list the changes if confirmed and execute the merge
of the MR !99 (change this number by MR id you want to accept).

You can change this behavior using some of the following options:

* -m, --message[=MESSAGE] - set the message to be inserted in MR acceptance
* --remove-source - remove the source branch after merge
* --no-push : don\'t push current branch to remote to insert in current branch MR before merge it
* --update-local - checkout target source and pull it after merge
* --tag-after[=TAG-AFTER] - local checkout to target source, pull it and tag it after merge
* -y, --yes - don't interact listing commits and issues or asking for confirmation
