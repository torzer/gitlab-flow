# torzer GitFlow

This Laravel package aims to help developers to easier adopt and decrease the operational time when
using Feature Branch Flow with Gitlab.

## Installation

Use composer to install it:

```bash
composer require torzer/gitlab-flow
```

Register the ServiceProvider in `config/app.path`

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

The default command gonna ask you to choose the assignee and milestone from the project.

To change the behavior use some of the following options:

* --source[=SOURCE] - the name of the source branch, default is the current branch
* --target[=TARGET] - the name of the target branch to create the MR, default is set in project gitlab config
* -D, --description[=DESCRIPTION] - a long text description for the MR
* -T, --title[=TITLE] - a short text description (title) for the MR
* --no-assignee - set this if no assignee will be made, otherwise you'll be asked to choose the assignee user
* --no-milestone - set this if no milestone will be set, otherwise you'll be asked to choose the milestone
* --wip - set this if you want to create a WIP MR
* -P, --push - push current branch to remote origin before open MR
* --remove-source - used when mergin after MR, set the acceptance to remove source
* --tag-after[=TAG-AFTER] - used when mergin after MR, checkout target source, pull it and tag it after merge
* --merge - set this if you want to create the MR and then merge it
