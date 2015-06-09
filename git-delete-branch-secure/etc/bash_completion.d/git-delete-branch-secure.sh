#!/bin/bash

_git_delete_branch_secure(){
  __gitcomp_nl "$(__git_refs '' 1)"
}
