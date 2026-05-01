# Push Gate

この文書は、`git push` 時に使われる CI 関連の push gate の技術的な挙動を説明します。

## 対象範囲

この文書は、push gate のうち CI に関わる部分を対象にしています。

commit message prefix の検証自体は、実行順序に関係する箇所を除き、ここでは詳述しません。

## エントリーポイント

`git push` が実行されると、Git は次を起動します。

- `asset/init/hooks/pre-push.sh`

この hook が local push gate の入り口です。

## 実行順序

現行の `pre-push` の流れは次です。

1. `hook-the-hooks.sh` を実行する
2. その hook chain が失敗したら停止する
3. `ci.sh` または `.ci.sh` を探す
4. CI script を source する
5. CI script が失敗したら停止する
6. `pre-push-prefix.php` を実行する
7. prefix チェックが失敗したら停止する

つまり、CI チェックは commit message prefix チェックより先に実行されます。

## CI script の解決

`pre-push.sh` は次を探します。

- `ci.sh`
- `.ci.sh`

どちらも存在しなければ push は拒否されます。

現在の repository 状態では `ci.sh` が存在しないため、`.ci.sh` が実際に解決される CI gate script です。

ただし、後から `ci.sh` が追加されると、解決順はそちらが優先されます。

## remote 名の判定

`.ci.sh` は親 process の command line を読み取り、push 先 remote 名を抽出します。

この remote 名によって、CI enforcement を実行するかどうかを決めます。

## `local` remote の特例

判定された remote 名が次の場合:

- `local`

`.ci.sh` は即座に成功終了します。

これは次を意味します。

- CI 通過チェックはスキップされる
- push は次の検証段階へ進む

この例外は、CI gate にだけ適用されます。

`pre-push` 全体がスキップされるわけではありません。

`.ci.sh` が成功終了した後も、`pre-push-prefix.php` は引き続き実行されます。

つまり、実際の挙動は次です。

- `local` remote では CI enforcement をしない
- ただし commit message prefix の enforcement は引き続き適用される

## `local` 例外の背景

この例外は、軽量な private repository workflow を支えるために存在します。

- local repository への push は GitHub への push より速い
- developer が offline でも履歴を保存できる
- WIP commit や一時的な test code を、後で整理して公開する前提で private に保存できる

## branch 名の判定

remote 判定の後、`.ci.sh` は branch 名を決定します。

まず次を試します。

- push command から解析した引数

それが使えない場合は次へ fallback します。

- `git symbolic-ref --short HEAD`

branch 名を決定できなければ push は拒否されます。

## PHP version の判定

`.ci.sh` は active な PHP version も決定します。

通常は次を使います。

- `PHP_MAJOR_VERSION.PHP_MINOR_VERSION`

また、branch 名自体が `phpNN` 形式に一致する場合の特別処理もあります。

## CI marker file

期待される CI marker file は次の形式で組み立てられます。

```text
.ci_commit_id_<branch>_php<version>
```

例:

```text
.ci_commit_id_2030_php83
.ci_commit_id_2030_php84
```

## commit ID の照合

その後 script は次を行います。

1. marker file が存在するか確認する
2. CI 通過済みとして保存された commit ID を読む
3. `refs/heads/<branch>` から現在の commit ID を読む
4. 両者を比較する

marker file が無ければ push は拒否されます。

commit ID が一致しなければ push は拒否されます。

## 意味

この CI gate は次の契約を enforce します。

- CI が、現在の branch の正確な commit を通過済みであること
- その通過結果が、期待される marker file に記録されていること
- その条件を満たしたときだけ push を継続できること

これが、local push の許可を CI 状態に結びつける仕組みです。

## 他の push ルールとの境界

この CI gate は、push policy 全体の一部に過ぎません。

commit message prefix 検証のような他のルールは、CI script が成功した後にも引き続き実行されます。

この分離は、`local` remote の例外を正しく理解するうえで重要です。
