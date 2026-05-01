# CI仕様

この文書は、`op-unit-ci` の現行実装における CI の挙動を説明します。

## 対象範囲

これは現行の As-Is 仕様です。

対象は次です。

- `op-unit-ci` が何を CI 対象とみなすか
- いつ CI を実行するか
- いつ CI をスキップするか
- CI 結果をどう記録するか
- その記録結果が後で `git push` にどう使われるか

## [DOC-FUTURE] 意図している CI の責務境界

長期的に意図している方向は、CI 関連のロジックを `op-unit-ci` に集約することです。

理想的な To-Be は次です。

- CI 側の inspection logic は `op-unit-ci`
- CI 側の marker 管理は `op-unit-ci`
- CI 側の repository inspection flow は `op-unit-ci`

歴史的経緯で framework 周辺に CI 関連挙動が散在している箇所はありますが、目指す先はこの unit への集中です。

## 関連 framework 文書

- `asset/docs/op/invariants.ja.md`
- `asset/docs/op/responsibility-boundaries.ja.md`
- `asset/docs/op/common-recipes.ja.md`

## エントリーポイント

通常の CI エントリーポイントは次です。

- `cicd`

この repository では、`cicd` は次への symlink です。

- `asset/unit/ci/cicd3.php`

この script は application を bootstrap し、CI mode を有効にし、`OP::Unit()->CI()->Auto()` を実行し、その後 CI 成功かつ dry-run でなければ CD へ進みます。

`cicd` file 自体は実行形式なので、通常の operator の入口は単純に次です。

```sh
./cicd
```

## script 名の歴史

歴史的には、最初の CI entry script 名は次でした。

- `ci.sh`

その後の運用では次へ寄っていきました。

- `.ci.sh`

実務上の理由は、通常の directory 一覧に visible な `ci.sh` があると、framework 開発者ではない利用者を混乱させるおそれがあると考えたためです。

互換性のため、現行 hook は今でも次の順で確認します。

1. `ci.sh`
2. `.ci.sh`

## CI mode

`cicd3.php` は次を定義します。

- `_IS_CI_ = true`

これにより、その process は CI 実行として扱われます。

## 既定の実行範囲

`CI::Auto()` は既定で次を実行します。

- `CI::All()`

request parameter で明示的に対象を絞らない限り、これが使われます。

つまり通常挙動は、現在の repository だけではなく複数 repository を検査することです。

## よく使う request option

current の operator 向け request option には次があります。

- `ci=1` または `ci=0`
- `cd=1` または `cd=0`
- `unit=core`
- `unit=<unit-name>`
- `class=<class-name>`
- `method=<method-name>`

## 対象 repository

`CI::All()` は次から対象 repository を集めます。

- Git submodule 設定
- `asset/config/submodule/*/*.php` 配下の非 git managed submodule 設定
- main repository 自体
- `asset/core/` 配下の nested submodule

実装は各対象 repository に `chdir()` して、その場で single-repository CI を実行します。

## 各 repository での開始条件

各対象 repository では、`CI_Client::Init()` が CI を続行するかどうかを決定します。

### 必須の CI script

次のどちらも存在しない場合:

- `ci.sh`
- `.ci.sh`

その repository は、実行可能な CI エントリを持たないものとして扱われます。

この場合は次の挙動になります。

- 警告的な message を表示する
- その repository の CI はそこで終了する
- それだけを理由に CI 全体 failure にはしない

現行 message:

- `Does not found ci.sh or .ci.sh file.`

## [DOC-GAP] 空の `ci.sh` による bypass

現行 hook は `.ci.sh` より先に `ci.sh` を優先するため、次の As-Is 挙動があります。

- 空の `ci.sh` file が存在する
- hook はその file を先に source する
- その段階では実質的な CI enforcement が走らない
- push は後続 check へ進めてしまう

これは現行実装の結果であり、推奨ワークフローではありません。

### 確認済みの技術的意味

現行実装は直接確認済みです。

実務上の結果は次です。

- 空の `ci.sh` は、Bash で source すると成功終了になる
- `pre-push.sh` はその成功終了をそのまま通す
- そのため `.ci.sh` には到達しない

つまり、現行の As-Is 実装では、空の `ci.sh` により CI gate は bypass できます。

ただし、push policy 全体が bypass されるわけではありません。

CI step 成功後も `pre-push-prefix.php` は引き続き実行されます。

そのため、正確には次です。

- CI gate は bypass できる
- commit message prefix gate は引き続き有効

## [DOC-FUTURE] contract 上の位置付け

空の `ci.sh` による bypass は、安定した長期仕様として扱うべきではありません。

現行実装には存在しますが、将来的に変更される可能性があります。

## `cd.sh` の歴史

古い世代では次も使われていました。

- `cd.sh`

これは現行の active workflow には含まれませんが、古い repository に残存していることがあります。

### `.ci_skip`

`.ci_skip` が存在する場合は次です。

- CI 実行をスキップする
- それでも `SaveCommitID()` は実行する
- その repository は full inspection 無しで完了扱いにする

### `.git`

対象 directory に `.git` が存在しない場合:

- その directory では CI を実行しない

## 既存 CI 結果の再利用

fresh な再検査の前に `CheckCommitID()` が使われます。

次のすべてを満たす場合:

- 期待される `.ci_commit_id_<branch>_php<version>` が存在する
- その timestamp が 1 時間以内である
- 保存済み commit ID が現在の branch commit ID と一致する

その repository は既に検査済みとみなされ、CI はスキップされます。

つまり、`cicd` は毎回すべての repository を無条件に再実行するわけではありません。

## 実際の inspection

CI が必要な場合、`CI_Client::CI()` は次を行います。

- CI target collector が選んだ各 target repository directory を走査する
- namespace context を決める
- current directory の `*.class.php` を列挙する
- class が `class/` directory に分類されている場合は `class/*.class.php` も列挙する
- `_` で始まる class file はスキップする
- 対象 class を instantiate する
- `OP_CI` を use していることを要求する
- method ごとの CI config を読む
- `CI_Inspection()` を method 単位・args 単位で実行する

つまり、現行の CI target model は class-file 指向です。

現行 As-Is では次の意味になります。

- `.class.php` 拡張子を持つ class が CI 対象になる
- class が多い場合は `class/` directory も走査対象になる
- repository-level の CI flow は、まず submodule や subsystem directory を巡回し、その中で class file を検査する
- 見つかった method は、CI engine 側の skip rule に当たらない限り inspection 候補になる

関数は current の CI target ではありません。

## なぜ関数は CI target ではないのか

current の CI/CD 設計では、inspection の責任範囲を class と、その unit が共有する contract に限定しています。

理由は、関数が技術的に呼び出せないからではありません。

実際には、他 unit の namespace にある関数を無理やり使うこと自体は可能です。

しかしそれは、呼び出し側が自分でリスクを負って行う利用だと考えています。

unit 開発者は、そのような強引な cross-unit function usage まで責任を負うべきではありません。

unit 開発者が利用者に対して負うべき責任は、もっと狭く明確です。

- 公開されている共通 interface への入力
- その共通 interface を通して返される出力

そのため、current の CI 設計は、任意の関数利用ではなく class ベースの contract を中心にしています。

もし関数も strict な CI target にしたいなら、その開発者自身がそう設計するべきです。

実務上、厳密な CI を求める開発者は、standalone function を主要な shared contract surface として利用しない方がよいです。

また、関数を current の CI target にしていないこと自体が、現在の設計における緩やかな緩衝地帯としても機能しています。

## なぜ `OP_CI` が必要なのか

`OP_CI` は optional ではありません。

対象 class が `OP_CI` を use していなければ、その repository の CI は失敗します。

実務上の理由は、CI pipeline が trait 提供の次の 2 つの method に依存しているためです。

- `CI_AllMethods()`
- `CI_Inspection()`

これらが、inspect 対象 method の見つけ方と、その呼び出し方を定義しています。

現在の実装では、`CI_AllMethods()` を使って、class 側 trait contract 経由で method 一覧を取得します。

## private method の inspection

現在の重要な設計点のひとつは、`OP_CI` によって private method も inspection 対象にできることです。

現在の仕組みは次です。

- CI は `CI_AllMethods()` を通して object から method list を取得する
- CI は `$obj->CI_Inspection($method, ...$args)` を通して対象を実行する
- `CI_Inspection()` は trait により class context の内側で動く

そのため、inspection は public に呼び出せる application API だけに制限されません。

現行設計では、class の挙動として検証すべきであれば、class 内部 method も CI に含められるようにしています。

## inspection 定義ファイル

期待結果の rule は、各 subsystem の `ci/` directory 配下にある CI 定義 file に保存されます。

現在の設計では、`CIConfig()` が class namespace と repository type からその file を解決します。

代表的な配置は次です。

- `asset/core/ci/<ClassName>.php`
- `asset/unit/<unit-name>/ci/<ClassName>.php`
- `asset/module/<module-name>/ci/<ClassName>.php`

これらの file は、method ごとに次を定義します。

- 渡す引数
- 期待結果
- 任意の prepare / cleanup hook
- trace や message metadata

つまり、実際の inspection rule は次です。

- 設定された引数で method を呼ぶ
- 戻り値を設定済み expected value と比較する

これによって、各 inspected method の pass / fail を判定します。

class が `OP_CI` を use していなければ、その repository の CI は失敗します。

method の結果が期待値と一致しなければ、その repository の CI は失敗します。

## 結果の記録

repository の CI が成功すると、`SaveCommitID()` が次の marker file を書き込みます。

```text
.ci_commit_id_<branch>_php<version>
```

file の中身は、その branch の current commit ID です。

例:

```text
.ci_commit_id_2030_php83
```

## Git Push での利用

保存された marker file は、後の `pre-push` flow で使われます。

`git push` 時には次が行われます。

- `pre-push.sh` が実行される
- `.ci.sh` が期待される marker file を確認する
- 保存済み commit ID と current branch commit ID を比較する

file が無いか、commit ID が一致しなければ push はブロックされます。

remote 名が `local` の場合は、この CI enforcement はスキップされますが、その後の push ルールは引き続き処理されます。

## stash の挙動

`op-unit-ci` が読み込まれると:

- CI 前に `GitStashSave()` を実行する
- shutdown 時に `GitStashPop()` を実行するよう登録する

この挙動は dry-run mode ではスキップされます。

目的は、CI 実行中に複数 repository をまたいでも working tree の状態を保存・復元できるようにすることです。

実務上は、標準 CI は commit 済みの repository 状態を検査します。そのため、未コミットの変更は実行中、一時的に visible な working tree から消えます。

未コミットの current working tree をそのまま検査したい場合は、`test=1` または `dry-run=1` を使います。

## dry-run と `test=1`

current 実装では、次はすべて dry-run として扱われます。

- `dry-run=1`
- `dryrun=1`
- `test=1`

また、一部の focused execution mode では自動的に dry-run が有効になります。

current 実装では、`unit=...` を指定すると、dry-run が明示されていない場合でも自動的に dry-run が有効になります。

つまり次は:

- `unit=core`
- `unit=<unit-name>`

dry-run 判定の意味では、暗黙的に `test=1` と同等に扱われます。

focused 実行の典型例は次です。

```sh
./cicd unit=app class=App
./cicd unit=app class=App method=Title
```

dry-run mode では次のようになります。

- stash save / pop はスキップされる
- 未コミットの working tree をそのまま CI 検査できる
- `SaveCommitID()` は即 return する
- CI 通過済み commit ID marker は書き込まれない
- `cicd3.php` は CD へ進まない
