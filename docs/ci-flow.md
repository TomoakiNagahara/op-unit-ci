# CI Flow

This document describes the current step-by-step CI flow of the ONEPIECE Framework.

## High-Level Flow

The normal flow is:

1. run `./cicd`
2. execute `asset/unit/ci/cicd3.php`
3. bootstrap the application
4. set CI mode
5. run `OP::Unit()->CI()->Auto()`
6. inspect target repositories
7. save passed commit IDs to `.ci_commit_id_*` files
8. later, `git push` checks those files through `.ci.sh`

## Related Framework Documents

- `asset/docs/op/invariants.md`
- `asset/docs/op/responsibility-boundaries.md`
- `asset/docs/op/common-recipes.md`

## Detailed Flow

### 1. Start CI

The operator runs:

```sh
./cicd
```

This executable file is the normal operator-facing entry.

### 2. Resolve the Application Root

`cicd3.php` searches upward from the current working directory until it finds:

- `.public_html`
- or `app.php`

It then treats that location as the application root.

### 3. Bootstrap

After changing into the application root, `cicd3.php` loads:

- `asset/bootstrap/index.php`

This brings the framework into an executable state.

### 4. Run CI Auto

`cicd3.php` runs:

- `OP::Unit()->CI()->Auto()`

By default, this means:

- `CI::All()`

### 5. Save Working Tree State

When `op-unit-ci` is loaded, it first performs:

- `GitStashSave()`

and registers:

- `GitStashPop()`

for shutdown restoration.

As a result, standard CI runs against the committed repository state, not the current dirty working tree.

### 6. Build the Repository List

`CI::All()` collects CI targets from:

- active Git submodules
- configured non-git-managed submodule repositories
- nested submodules in `asset/core/`
- the main repository

### 7. Enter Each Repository

For each target repository:

- change directory into that repository
- run `CI::Single()`
- which calls `CI_Client::Auto()`

### 8. Decide Whether CI Is Needed

`CI_Client::Init()` checks, in effect:

1. is `ci.sh` or `.ci.sh` present?
2. is `.ci_skip` present?
3. does `.git` exist?
4. does a fresh matching `.ci_commit_id_*` already exist?

#### 8a. No `ci.sh` or `.ci.sh`

If neither file exists:

- output `Does not found ci.sh or .ci.sh file.`
- stop CI for that repository
- continue the overall CI process without treating that as a hard failure

#### 8b. `.ci_skip`

If `.ci_skip` exists:

- skip CI execution
- save the current commit ID marker
- treat the repository as finished

#### 8c. Fresh Matching Marker Exists

If a matching `.ci_commit_id_*` exists, is less than one hour old, and matches the current commit ID:

- skip re-inspection
- treat that repository as already inspected

### 9. Run Class/Method Inspection

If CI is needed:

- find target classes
- instantiate them
- require `OP_CI`
- load CI config
- run method inspections
- compare expected and actual results

If any required inspection fails, that repository CI fails.

### 10. Save Commit ID Marker

If repository CI succeeds:

- write `.ci_commit_id_<branch>_php<version>`

The file content is the current branch commit ID.

If dry-run is active, this step is skipped because `SaveCommitID()` returns immediately.

### 11. Restore Stashed State

At shutdown:

- `GitStashPop()` runs

and restores stashed repository state where applicable.

In dry-run mode, stash save/pop is skipped, so the operator can inspect uncommitted code directly.

### 12. Later Push-Time Enforcement

When the developer later runs `git push`:

1. `pre-push.sh` runs
2. it sources `ci.sh` or `.ci.sh`
3. `.ci.sh` finds the expected `.ci_commit_id_*` file
4. `.ci.sh` compares the saved commit ID with the current branch commit ID
5. if they do not match, push is blocked

#### `local` Remote Exception

If the remote name is `local`:

- `.ci.sh` exits successfully immediately
- CI enforcement is skipped
- the later prefix check still runs

#### Historical Script Resolution

The current hook resolves the CI script in this order:

1. `ci.sh`
2. `.ci.sh`

This is a compatibility path from older repositories.

#### [DOC-GAP] Empty `ci.sh`

As a current implementation side effect, if an empty `ci.sh` exists, that file is sourced first.

In that case, the CI gate may effectively be bypassed at that step, and the push proceeds to later checks.

This is part of the current As-Is flow, not a guaranteed future rule.

More precisely, the current checked behavior is:

1. `pre-push.sh` selects `ci.sh`
2. Bash `source` of an empty file succeeds with exit status `0`
3. the CI step is treated as passed
4. `.ci.sh` is never reached
5. `pre-push-prefix.php` still runs afterward

## Operational Meaning

The current flow ties `git push` permission to a commit ID marker created by CI.

That means:

- CI approval is represented by a file
- the file is branch-specific and PHP-version-specific
- push is allowed only when the branch still points to the approved commit
