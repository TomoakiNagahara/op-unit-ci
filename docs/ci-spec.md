# CI Specification

This document describes the current CI behavior of `op-unit-ci` as implemented today.

## Scope

This is the current As-Is specification.

It focuses on:

- what `op-unit-ci` considers a CI target
- when CI runs
- when CI is skipped
- how CI results are recorded
- how those recorded results are later used by `git push`

## [DOC-FUTURE] Intended CI Boundary

The intended long-term direction is that CI-related logic should be concentrated in `op-unit-ci`.

That means the ideal To-Be is:

- CI-side inspection logic belongs in `op-unit-ci`
- CI-side marker management belongs in `op-unit-ci`
- CI-side repository inspection flow belongs in `op-unit-ci`

Historically scattered CI-related behavior may still exist around the framework, but the preferred destination is concentration into this unit.

## Related Framework Documents

- `asset/docs/op/invariants.md`
- `asset/docs/op/responsibility-boundaries.md`
- `asset/docs/op/common-recipes.md`

## Entry Point

The normal CI entry point is:

- `cicd`

In this repository, `cicd` is a symbolic link to:

- `asset/unit/ci/cicd3.php`

That script bootstraps the application, sets CI mode, runs `OP::Unit()->CI()->Auto()`, and then may continue to CD when CI succeeds and dry-run is not active.

The `cicd` file itself is executable, so the normal operator entry is simply:

```sh
./cicd
```

## Historical Script Naming

Historically, the earliest CI entry script name was:

- `ci.sh`

Later workflows moved toward:

- `.ci.sh`

The practical reason was to reduce confusion for non-developer users who might list repository contents and wonder why a visible CI script was present in ordinary directories.

For compatibility, the current hook still checks:

1. `ci.sh`
2. `.ci.sh`

## CI Mode

`cicd3.php` defines:

- `_IS_CI_ = true`

This marks the process as a CI run.

## Default Execution Range

`CI::Auto()` runs:

- `CI::All()` by default

unless request parameters explicitly narrow the target.

This means the normal behavior is to inspect multiple repositories, not just the current one.

## Common Request Options

Current operator-facing request options include:

- `ci=1` or `ci=0`
- `cd=1` or `cd=0`
- `unit=core`
- `unit=<unit-name>`
- `class=<class-name>`
- `method=<method-name>`

## Target Repositories

`CI::All()` gathers target repositories from:

- Git submodule configuration
- non-git-managed submodule config files under `asset/config/submodule/*/*.php`
- the main repository itself
- nested submodules under `asset/core/`

The implementation changes directories into each target repository and runs the single-repository CI flow there.

## Per-Repository Start Conditions

Inside each target repository, `CI_Client::Init()` decides whether CI should proceed.

### Required CI Script

If neither of these files exists:

- `ci.sh`
- `.ci.sh`

then the repository is treated as not having an executable CI entry.

In that case:

- a warning-like message is displayed
- CI for that repository stops there
- the overall CI process does not fail only because of that

Current message:

- `Does not found ci.sh or .ci.sh file.`

## [DOC-GAP] Empty `ci.sh` Bypass

Because the current hook prefers `ci.sh` before `.ci.sh`, the following As-Is behavior exists:

- if an empty `ci.sh` file is present
- the hook sources that file first
- no real CI enforcement runs at that step
- push can continue to later checks

This is a current implementation consequence, not a recommended workflow.

### Confirmed Technical Meaning

The current implementation was checked directly.

The practical result is:

- an empty `ci.sh` returns success when sourced by Bash
- `pre-push.sh` accepts that success result
- `.ci.sh` is therefore not reached

So, in the current As-Is implementation, an empty `ci.sh` can bypass the CI gate.

However, this does not bypass the entire push policy.

After the CI step succeeds, `pre-push-prefix.php` still runs.

That means:

- the CI gate can be bypassed
- the commit-message prefix gate still remains active

## [DOC-FUTURE] Contract Status

The empty-`ci.sh` bypass should not be treated as a stable long-term specification.

It exists in the current implementation, but it may change in the future.

## Historical `cd.sh`

An older generation also used:

- `cd.sh`

That file is no longer part of the active workflow, but it may still remain in older repositories.

### `.ci_skip`

If `.ci_skip` exists:

- CI execution is skipped
- `SaveCommitID()` is still executed
- the repository is treated as completed without full inspection

### `.git`

If the target directory does not contain `.git`:

- CI is not executed for that directory

## Reuse of Previous CI Result

Before running a fresh inspection, `CheckCommitID()` is used.

If all of the following are true:

- the expected `.ci_commit_id_<branch>_php<version>` file exists
- its timestamp is within one hour
- its saved commit ID matches the current branch commit ID

then the repository is treated as already inspected and CI is skipped for that repository.

This means `cicd` does not always re-run every repository unconditionally.

## Actual Inspection

If CI is needed, `CI_Client::CI()`:

- scans each target repository directory selected by the CI target collector
- determines namespace context
- finds `*.class.php` files in the current directory
- also finds `class/*.class.php` files when classes are classified under a `class/` directory
- skips underscore-prefixed class files
- instantiates target classes
- requires them to use `OP_CI`
- loads CI config for methods
- runs `CI_Inspection()` method-by-method and arg-by-arg

This means the current CI target model is class-file oriented.

At the current As-Is level:

- classes with the `.class.php` extension are CI targets
- if there are many classes, a `class/` directory is also scanned
- the repository-level CI flow walks submodules and subsystem directories first, then class files inside each target repository
- all discovered methods are inspection candidates unless they are skipped by the CI engine rules

Functions are not current CI targets.

## Why Functions Are Not CI Targets

The current CI/CD design limits inspection responsibility to classes and their shared unit-facing contracts.

The reason is not that functions are technically impossible to call.

In practice, a caller can force the use of functions across namespace boundaries if they want to.

However, that is treated as a risk accepted by the caller.

The unit developer is not expected to take responsibility for every such forced cross-unit function usage.

The responsibility that the unit developer is expected to bear is narrower and clearer:

- inputs to the publicly shared common interface
- outputs produced through that common interface

That is why the current CI design focuses on class-based contracts rather than free function usage across arbitrary boundaries.

If a developer wants functions to participate in stricter CI, that developer is expected to design for it explicitly.

In practice, developers who want strict CI should avoid depending on standalone functions as their primary shared contract surface.

The fact that functions are not current CI targets also works as a deliberately loose buffer zone in the current design.

## Why `OP_CI` Is Required

`OP_CI` is not treated as optional.

If a target class does not use `OP_CI`, CI fails for that repository.

The practical reason is that the CI pipeline depends on two trait-provided methods:

- `CI_AllMethods()`
- `CI_Inspection()`

Those methods define how CI discovers inspectable methods and how it invokes them.

In the current implementation, `CI_AllMethods()` is used to obtain the full method list through the class-side trait contract.

## Private Method Inspection

One of the important current design points is that `OP_CI` allows CI to inspect private methods as well.

The current mechanism is:

- CI gets the method list from the object through `CI_AllMethods()`
- CI executes the target through `$obj->CI_Inspection($method, ...$args)`
- `CI_Inspection()` runs inside the class context provided by the trait

Because of that, inspection is not limited to only publicly callable application APIs.

The current design intentionally allows class-internal methods to be included in CI inspection when they are part of the class behavior that should be verified.

## Inspection Rule Files

The expected result rules are stored in CI definition files under each subsystem's `ci/` directory.

In the current design, `CIConfig()` resolves those files from the class namespace and repository type.

Typical locations are:

- `asset/core/ci/<ClassName>.php`
- `asset/unit/<unit-name>/ci/<ClassName>.php`
- `asset/module/<module-name>/ci/<ClassName>.php`

These files define, per method:

- arguments to pass
- expected result
- optional prepare / cleanup hooks
- trace or message metadata

So the actual inspection rule is:

- call the method with the configured arguments
- compare the returned result with the expected configured value

That is how pass/fail is decided for each inspected method.

If a class does not use `OP_CI`, that repository CI fails.

If a method result does not match the expected CI config, that repository CI fails.

## Result Recording

When repository CI succeeds, `SaveCommitID()` writes a marker file:

```text
.ci_commit_id_<branch>_php<version>
```

The file content is the current commit ID of the branch.

Example:

```text
.ci_commit_id_2030_php83
```

## Use by Git Push

The saved marker file is later used by the `pre-push` flow.

During `git push`:

- `pre-push.sh` runs
- `.ci.sh` checks the expected marker file
- the saved commit ID is compared with the current branch commit ID

If the file is missing or the commit IDs do not match, push is blocked.

If the remote name is `local`, this CI enforcement is skipped, but later push rules still continue.

## Stash Behavior

When `op-unit-ci` is loaded:

- `GitStashSave()` runs before CI
- `GitStashPop()` is registered for shutdown

This behavior is skipped in dry-run mode.

The purpose is to preserve and restore working tree state across the repository set during CI operations.

In practical terms, standard CI inspects the committed repository state. Uncommitted changes are temporarily removed from the visible working tree during the run.

If the operator wants to inspect the current uncommitted working tree instead, `test=1` or `dry-run=1` can be used.

## Dry-Run and `test=1`

Current dry-run detection treats all of the following as dry-run:

- `dry-run=1`
- `dryrun=1`
- `test=1`

Also, some focused execution modes automatically turn on dry-run.

In current implementation, specifying `unit=...` automatically turns on dry-run if dry-run was not already explicitly requested.

That means:

- `unit=core`
- `unit=<unit-name>`

implicitly behave like `test=1` for dry-run purposes.

Typical focused examples are:

```sh
./cicd unit=app class=App
./cicd unit=app class=App method=Title
```

In dry-run mode:

- stash save / pop is skipped
- CI can inspect uncommitted working tree changes
- `SaveCommitID()` returns immediately
- CI-approved commit ID markers are not written
- `cicd3.php` does not continue to CD
