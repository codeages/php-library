<?php
namespace Deployer;

// Tasks
task('build', function() {

})->local();

desc('Deploy your project');
task('deploy', function() {
    invoke('deploy:info');

    $canDeploy = get('can_deploy');

    if (!$canDeploy) {
        writeln("<error>Please run 'deploy:dry_run' task first.</error>");
        return ;
    }

    if (askConfirmation("Do you real deploy ? (Press 'Enter' or 'Y' key)", true)) {
        invoke('deploy:real-run');
    }
});

set('can_deploy', function() {
    $src = get('rsync_src');
    while (is_callable($src)) {
        $src = $src();
    }

    if (!trim($src)) {
        throw new \RuntimeException('You need to specify a source path.');
    }

    return testLocally("[ -f $src/.dry_run ]");
});

task('deploy:dry_run', function() {
    $config = get('rsync');

    $src = get('rsync_src');
    while (is_callable($src)) {
        $src = $src();
    }

    if (!trim($src)) {
        throw new \RuntimeException('You need to specify a source path.');
    }

    try {
        $dst = get('rsync_dest');
    } catch (\Exception $e) {
        runLocally("touch {$src}/.dry_run");
        writeln("<bg=yellow;options=bold>Failed to get configuration `rsync_dest`, if this is the first deployment, please ignore this error, otherwise please check your configuration.</>");
        return ;
    }

    while (is_callable($dst)) {
        $dst = $dst();
    }

    if (!trim($dst)) {
        throw new \RuntimeException('You need to specify a destination path.');
    }

    if (strpos($config['flags'], 'v') === false) {
        $config['flags'] .= 'v';
    }

    $server = \Deployer\Task\Context::get()->getHost();
    if ($server instanceof \Deployer\Host\Localhost) {
        runLocally("rsync -{$config['flags']} --dry-run {{rsync_options}}{{rsync_includes}}{{rsync_excludes}}{{rsync_filter}} '$src/' '$dst/'", $config);
        return;
    }

    $host = $server->getRealHostname();
    $port = $server->getPort() ? ' -p' . $server->getPort() : '';
    $sshArguments = $server->getSshArguments();
    $user = !$server->getUser() ? '' : $server->getUser() . '@';

    runLocally("rsync -{$config['flags']} -e 'ssh$port $sshArguments' --dry-run {{rsync_options}}{{rsync_includes}}{{rsync_excludes}}{{rsync_filter}} '$src/' '$user$host:$dst/'", $config);
    runLocally("touch {$src}/.dry_run");
});

task('deploy:remove_rsync_lockfile', function() {
    $config = get('rsync');

    $src = get('rsync_src');
    while (is_callable($src)) {
        $src = $src();
    }

    if (!trim($src)) {
        throw new \RuntimeException('You need to specify a source path.');
    }

    if (testLocally("[ -f $src/.dry_run ]")) {
        runLocally("rm {$src}/.dry_run");
    } else {
        writeln("<comment>{$src}/.dry_run is not exit.</comment>");
    }
})->setPrivate();;

task('deploy:real-run', [
    'build',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'rsync:warmup',
    'rsync',
    'deploy:shared',
    'deploy:writable',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'deploy:remove_rsync_lockfile',
    'cleanup',
    'success'
])->setPrivate();

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');