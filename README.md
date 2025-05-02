# index-faster

A WordPress plugin to manage wp_postmeta index.

## Usage

インストールして有効化してください。

### CLI

`wp plugin install` コマンドでインストールして有効化できます。

```
wp plugin install https://github.com/tarosky/make-postmeta-faster/releases/latest/download/make-postmeta-faster.zip --activate
```

`index` コマンドが利用可能になります。

```
wp index display postmeta
```

利用方法はヘルプをご覧ください。

```
# コマンドのリストを表示
wp help index
# サブコマンドのヘルプ
wp help index add
# postmetaにインデックスを付与する
wp index add postmeta
```
