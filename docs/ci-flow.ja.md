# CIフロー

この文書は、ONEPIECE Framework の現行 CI フローを手順ベースで説明します。

## 全体フロー

通常の流れは次です。

1. `./cicd` を実行する
2. `asset/unit/ci/cicd3.php` が動く
3. application を bootstrap する
4. CI mode を有効にする
5. `OP::Unit()->CI()->Auto()` を実行する
6. 対象 repository 群を検査する
7. 通過した commit の ID を `.ci_commit_id_*` に保存する
8. 後の `git push` 時に `.ci.sh` がその file を確認する

## 関連 framework 文書

- `asset/docs/op/invariants.ja.md`
- `asset/docs/op/responsibility-boundaries.ja.md`
- `asset/docs/op/common-recipes.ja.md`

## 詳細フロー

### 1. CI 開始

操作者は次を実行します。

```sh
./cicd
```

この実行形式 file が、通常の operator 向け entry point です。

### 2. application root の解決

`cicd3.php` は current working directory から上位へ探索し、次を見つけます。

- `.public_html`
- または `app.php`

見つかった位置を application root として扱います。

### 3. bootstrap

application root に移動した後、`cicd3.php` は次を読み込みます。

- `asset/bootstrap/index.php`

これにより framework が実行可能な状態になります。

### 4. CI Auto 実行

`cicd3.php` は次を実行します。

- `OP::Unit()->CI()->Auto()`

既定ではこれは次を意味します。

- `CI::All()`

### 5. working tree の保存

`op-unit-ci` が読み込まれると、まず次を実行します。

- `GitStashSave()`

さらに shutdown 時の復元として次を登録します。

- `GitStashPop()`

その結果、標準 CI は current の dirty working tree ではなく、commit 済みの repository 状態に対して実行されます。

### 6. repository 一覧の構築

`CI::All()` は次から CI 対象を集めます。

- active な Git submodule
- 設定された非 git managed submodule repository
- `asset/core/` 配下の nested submodule
- main repository

### 7. 各 repository に入る

各対象 repository ごとに次を行います。

- その repository に `chdir()` する
- `CI::Single()` を実行する
- その中で `CI_Client::Auto()` を呼ぶ

### 8. CI が必要か判定する

`CI_Client::Init()` は実質的に次を確認します。

1. `ci.sh` または `.ci.sh` はあるか
2. `.ci_skip` はあるか
3. `.git` はあるか
4. fresh で一致する `.ci_commit_id_*` は既にあるか

#### 8a. `ci.sh` / `.ci.sh` が無い

どちらも無い場合は次です。

- `Does not found ci.sh or .ci.sh file.` を出力する
- その repository の CI は終了する
- ただし CI 全体を hard failure にはしない

#### 8b. `.ci_skip`

`.ci_skip` がある場合は次です。

- CI 実行をスキップする
- current commit ID の marker は保存する
- その repository は完了扱いにする

#### 8c. fresh で一致する marker がある

一致する `.ci_commit_id_*` が存在し、1時間以内で、current commit ID と一致する場合は次です。

- 再検査をスキップする
- その repository は既検査扱いにする

### 9. class / method inspection 実行

CI が必要な場合は次を行います。

- 対象 class を探す
- instantiate する
- `OP_CI` を要求する
- CI config を読む
- method inspection を実行する
- expected と actual を比較する

必要な inspection が1つでも失敗すれば、その repository の CI は失敗です。

### 10. commit ID marker 保存

repository の CI が成功した場合は次を書き込みます。

- `.ci_commit_id_<branch>_php<version>`

file の中身は current branch commit ID です。

dry-run が有効な場合は、`SaveCommitID()` が即 return するため、この段階はスキップされます。

### 11. stash 状態の復元

shutdown 時には次が実行されます。

- `GitStashPop()`

これにより、必要に応じて stash された repository 状態が戻されます。

dry-run mode では stash save/pop 自体がスキップされるため、未コミットの code をそのまま検査できます。

### 12. 後段の push-time enforcement

開発者が後で `git push` を実行すると、次が起こります。

1. `pre-push.sh` が動く
2. `ci.sh` または `.ci.sh` を source する
3. `.ci.sh` が期待される `.ci_commit_id_*` を探す
4. `.ci.sh` が保存済み commit ID と current branch commit ID を比較する
5. 一致しなければ push をブロックする

#### `local` remote の例外

remote 名が `local` の場合は次です。

- `.ci.sh` は即成功終了する
- CI enforcement はスキップされる
- その後の prefix チェックは引き続き実行される

#### script 解決の歴史的順序

現行 hook は、CI script を次の順で解決します。

1. `ci.sh`
2. `.ci.sh`

これは古い repository との互換経路です。

#### [DOC-GAP] 空の `ci.sh`

現行実装の副作用として、空の `ci.sh` が存在すると、その file が先に source されます。

その場合、その段階の CI gate は実質的に bypass され、push は後続 check へ進みます。

これは現行の As-Is フローの一部であり、将来も保証されるルールではありません。

より正確な確認済み挙動は次です。

1. `pre-push.sh` が `ci.sh` を選ぶ
2. 空 file の Bash `source` は終了 status `0` で成功する
3. そのため CI step は通過扱いになる
4. `.ci.sh` には到達しない
5. その後も `pre-push-prefix.php` は引き続き実行される

## 実務上の意味

現行フローでは、`git push` の許可は、CI が作成した commit ID marker に結び付けられています。

つまり次を意味します。

- CI 通過は file で表現される
- その file は branch ごと・PHP version ごとに分かれる
- branch がその承認済み commit を指しているときだけ push が許可される
