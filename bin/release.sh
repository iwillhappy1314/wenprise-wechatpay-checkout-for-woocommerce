#!/bin/bash

# 检查是否提供了新版本号和发布描述
if [ -z "$1" ]; then
  echo "未提供新版本号，获取最新的 tag 并计算新版本号"
  # 获取最新的 Git tag（假设是语义化版本号格式）
  old_version=$(git describe --tags --abbrev=0)
  # 提取小版本号 + 1
  new_version=$(echo $old_version | awk -F. -v OFS=. '{$NF++;print}')
else
  new_version=$1
fi

# 发布描述
release_description=$2

# 检查并替换根目录中的 PHP 文件中的版本号
echo "替换根目录下 PHP 文件中的版本号为 $new_version"
for php_file in ./*.php; do
  if [ -f "$php_file" ]; then
    sed -i "" "s/$old_version/$new_version/g" "$php_file"
    echo "已更新文件: $php_file"
  fi
done

# 检查并替换 readme.txt 文件中的 Stable tag: 版本号
echo "更新 readme.txt 文件中的 Stable tag 版本号"
if [ -f "readme.txt" ]; then
  # 替换 `Stable tag:` 后的版本号
  sed -i "" "s/^\(Stable tag: *\)$old_version/\1$new_version/" readme.txt

  # 如果有发布描述，则更新 changelog
  if [ -n "$release_description" ]; then
    echo "添加更新日志到 changelog 部分"
    sed -i "" "/## Changelog ##/a\\
### $new_version ###\\
* $release_description
" readme.txt
  else
    echo "未提供发布描述，跳过更新 changelog 部分"
  fi
else
  echo "未找到 readme.txt 文件"
fi

# 根据是否提供发布描述来决定提交信息
if [ -n "$release_description" ]; then
  commit_message="Release $new_version: $release_description"
else
  commit_message="Release $new_version"
fi

# 提交更改
echo "提交更改"
git add .
git commit -m $commit_message

## 推送到远程仓库
echo "推送更改到远程仓库"
git push origin main

## 添加新版本 tag 并推送
echo "添加新版本 tag 并推送"
git tag $new_version
git push origin $new_version

# 使用 gh 创建 GitHub Release
echo "创建 GitHub Release"
if [ -n "$release_description" ]; then
  gh release create "$new_version" --title "Release $new_version" --notes "$release_description"
else
  gh release create "$new_version" --title "Release $new_version"
fi

echo "发布成功！"
