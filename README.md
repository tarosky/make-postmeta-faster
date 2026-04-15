# Make Post Meta Faster

A WordPress plugin to manage wp_postmeta index.

## Description

This plugin was born related to this ticket: https://core.trac.wordpress.org/ticket/41281

これまでの私の経験から、大量の投稿を持つWordPressサイトはpost metaを含むクエリがスロークエリになる傾向があります。ひどい場合は30秒もかかることがあり、また、それは投稿が特定の数を超えた場合に発生する、つまり「ある日突然サイトが重くなる」という事象によって発生します。これはMySQLがFile Sortを戦略として選んだ時です。

このプラグインはFile Sort発生を防ぐため、post metaにインデックスを付与します。

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
