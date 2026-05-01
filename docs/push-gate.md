# Push Gate

This document describes the technical behavior of the CI-related push gate used during `git push`.

## Scope

This document focuses on the CI part of the push gate.

It does not describe the separate commit-message prefix validation in detail, except where the execution order matters.

## Entry Point

When `git push` is executed, Git runs:

- `asset/init/hooks/pre-push.sh`

That hook is the entry point for the local push gate.

## Execution Order

The current `pre-push` flow is:

1. run `hook-the-hooks.sh`
2. stop if that hook chain fails
3. look for `ci.sh` or `.ci.sh`
4. source the CI script
5. stop if the CI script fails
6. run `pre-push-prefix.php`
7. stop if the prefix check fails

This means the CI check runs before the commit-message prefix check.

## CI Script Resolution

`pre-push.sh` looks for:

- `ci.sh`
- `.ci.sh`

If neither exists, push is rejected.

In the current repository state, `.ci.sh` is the resolved CI gate script because no `ci.sh` is present.

However, the resolution order still prefers `ci.sh` if one is added later.

## Remote Name Detection

`.ci.sh` reads the parent process command line and extracts the push target remote name.

The detected remote name is used to decide whether CI enforcement should run.

## Special Case: `local` Remote

If the detected remote name is:

- `local`

then `.ci.sh` exits successfully immediately.

This means:

- the CI pass check is skipped
- push is allowed to continue to the next verification step

This exception applies only to the CI gate.

It does **not** mean the entire `pre-push` process is skipped.

After `.ci.sh` exits successfully, `pre-push-prefix.php` still runs.

So the actual behavior is:

- `local` remote skips CI enforcement
- commit-message prefix enforcement still applies

## Background of the `local` Exception

This exception exists to support a lightweight private repository workflow.

- pushing to a local repository is faster than pushing to GitHub
- local history can be preserved even when the developer is offline
- work-in-progress commits and temporary test code can be stored privately before later cleanup and publication

## Branch Detection

After the remote check, `.ci.sh` determines the branch name.

It first tries:

- the parsed push command argument

and falls back to:

- `git symbolic-ref --short HEAD`

If the branch name cannot be determined, push is rejected.

## PHP Version Detection

`.ci.sh` also determines the active PHP version.

Normally it uses:

- `PHP_MAJOR_VERSION.PHP_MINOR_VERSION`

There is also special handling when the branch name itself matches a `phpNN` pattern.

## CI Marker File

The expected CI marker file is built as:

```text
.ci_commit_id_<branch>_php<version>
```

Examples:

```text
.ci_commit_id_2030_php83
.ci_commit_id_2030_php84
```

## Commit ID Verification

The script then:

1. checks whether the marker file exists
2. reads the saved CI-approved commit ID
3. reads the current commit ID from `refs/heads/<branch>`
4. compares both values

If the marker file does not exist, push is rejected.

If the commit IDs do not match, push is rejected.

## Meaning

The CI gate enforces this contract:

- CI must have approved the exact current branch commit
- that approval must be recorded in the expected marker file
- only then may the push continue

This is how local push permission is tied to CI state.

## Boundary With Other Push Rules

This CI gate is only one part of the full push policy.

Other rules, such as commit-message prefix validation, still run after the CI script succeeds.

That separation matters when reading the behavior of the `local` remote exception.
