# EatSmarter Scripts
A collection of scripts we are using at EatSmarter.

## git-delete-branch-secure
### Description
A git subcommand to delete branches that are merge into the current one via a
rebase.

You can supply multiple branch names which are then checked and deleted.

The script checks for numeric prefixes in branches to determine if there are
commits in the current branch of those commits.
It searches for those prefixes followed by a colon in the current branch log and
lists those commits.
Afterwards it checkes the log of the to-be-deleted branch on origin and lists
the number of commits that are found.

You can then decide if you want to delete that branch or not or if you want to
abort the script.

### Requirements
1. [Git completion](https://github.com/git/git/tree/master/contrib/completion) needs to be installed and working.

2. Branch names should be prefixed with a number followed by either and underscore
  or a hyphen.

  Examples:
  ```
  1234_something
  1333-something-very-long
  1633-something_very_long_too
  ```

  Each commit in that branch should have a commit message with the number followed
  by a colon to make them identifiable.

  Examples:
  ```
  1234_something
  -> 1234: Did something great.
  -> 1234: Did something even greater.

  1633-something_very_long_too
  -> 1633: Fixed coding standards.
  ```

### Installation

1. Clone the repository

  ```
  git clone git@github.com:EatSmarter/scripts.git
  ```

2. Either link the script `git-delete-branch-secure` in a folder that is already
  in your path (for example `/usr/local/bin`:

  ```
  ln -s [PATH_TO_REPO]/git-delete-branch-secure/git-delete-branch-secure /usr/local/bin/git-delete-branch-secure
  ```

  **or**

  Put the `git-delete-branch-secure` folder in your path (for example in your
  ~/.bash_profile)

  ```bash
  PATH=$PATH:[PATH_TO_REPO]/git-delete-branch-secure
  ```

3. (optional) To make the branch completition work you need to symlink the `/etc
  /bash_completion.d/git-delete-branch-secure` file in your
  `/etc/bash_completion.d/` (or else, check **$BASH_COMPLETION_COMPAT_DIR**
  variable) folder.

  ```
  ln -s [PATH_TO_REPO]/git-delete-branch-secure/etc/bash_completion.d/git-delete-branch-secure /etc/bash_completion.d/git-delete-branch-secure
  ```

  **or**

  source the completion file (for example in your ~/.bash_profile)

  ```
  source [PATH_TO_REPO]/git-delete-branch-secure/etc/bash_completion.d/git-delete-branch-secure
  ```

  The completion works a little different than bash completion. It is wrapped by
  git-completion which requires a function with the appropriate name to be
  defined somewhere in the bash scope.

### Usage
```
git delete-branche-secure [BRANCH_NAME] [BRANCH_NAME_2] ...
```
