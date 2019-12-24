# 通过构建服务器(Build Server)部署

## 简介

Deployer 的 [Rsync recipe](https://deployer.org/recipes/rsync.html) 支持通过构建服务器(Build Server)将程序发布到应用集群服务器的模式。但 Rsync recipe 不支持 rsync 的 dry-run 模式，本仓库的 [../scripts/rsync-dry-run-recipe](scripts/rsync-dry-run-recipe.php) 对 Rsync recipe 做了增强，支持 DRY-RUN 模式，以便于在正式执行部署命令前，可以通过 DRY-RUN 模式，查看本次要部署的具体代码变更文件列表，通过核实变更文件列表，以降低可能的误操作。

## 安装

```
composer require codeages/library
composer require deployer/recipes --dev
```

在 `deploy.php` 文件头部 添加：

```php
require __DIR__ . '/vendor/deployer/recipes/recipe/rsync.php';
require __DIR__ . '/vendor/codeages/library/scripts/rsync-dry-run-recipe.php';
```

配置项，参见 [Rsync recipe 官方文档](https://deployer.org/recipes/rsync.html)。

## 配置示例

```php
<?php
namespace Deployer;

require 'recipe/common.php';
require __DIR__ . '/vendor/deployer/recipes/recipe/rsync.php';
require __DIR__ . '/vendor/codeages/library/scripts/rsync-dry-run-recipe.php';

// Project name
set('application', 'my_project');

// Project repository
set('repository', 'git@domain.com:username/repository.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

set('allow_anonymous_stats', false);
set('writable_mode', 'chmod');
set('writable_chmod_mode', '0777');

// Shared files/dirs between deploys 
set('shared_files', []);
set('shared_dirs', [
    'var',
]);

// Writable dirs by web server 
set('writable_dirs', [
    'var/log',
    'var/tmp',
    'var/run',
    'var/cache',
]);

set('ssh_multiplexing', true);

set('rsync_src', __DIR__);
set('rsync_dest','{{release_path}}');

set('rsync',[
    'exclude'       => ['.git', 'var'],
    'exclude-file'  => false,
    'include'       => [],
    'include-file'  => false,
    'filter'        => [],
    'filter-file'   => false,
    'filter-perdir' => false,
    'flags'         => 'azcE', // Recursive, with compress, check based on checksum rather than time/size, preserve Executable flag
    'options'       => ['delete', 'delete-after', 'force'], //Delete after successful transfer, delete even if deleted dir is not empty
    'timeout'       => 3600, //for those huge repos or crappy connection
]);

// Hosts
inventory('.deploy-hosts.yml');

// Tasks
task('build', function() {
    // 
    /**
    * 注意：
    * 下述命令的 -o 参数针对线上生产环境类的加载做了优化；
    * 如果你在本地开发环境运行过此命令，那么你本地开发环境新增类的的时候，会报 class not found 的错误，这个时候你可以删除 vendor，重新安装 vendor 解决。
    */
    run('composer install --prefer-dist --no-progress --no-interaction -o --no-suggest');
})->local();
```

## 使用

1. 查看本次要发布的代码变更

    ```
    dep deploy:dry_run [stage] -vvv
    ```

2. 正式发布

    ```
    dep deploy [stage] -vvv
    ```
    注意 ：必须执行过 `deploy:dry_run` 之后，才能执行 `deploy` 任务。
