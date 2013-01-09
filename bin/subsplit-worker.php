<?php

require __DIR__.'/../vendor/autoload.php';

$configFilename = file_exists(__DIR__.'/../config.json')
    ? __DIR__.'/../config.json'
    : __DIR__.'/../config.json.dist';

$config = json_decode(file_get_contents($configFilename), true);

$start = time();

$redis = new Predis\Client(array('read_write_timeout' => 0,));
while ($body = $redis->brpoplpush('dflydev-git-subsplit:incoming', 'dflydev-git-subsplit:processing', 0)) {
    $data = json_decode($body, true);
    $name = null;
    $project = null;

    $data['dflydev_git_subsplit'] = array(
        'processed_at' => time(),
    );

    foreach ($config['projects'] as $testName => $testProject) {
        if ($testProject['url'] === $data['repository']['url']) {
            $name = $testName;
            $project = $testProject;
            break;
        }
    }

    if (null === $name) {
        print(sprintf('Skipping request for URL %s (not configured)', $data['repository']['url'])."\n");

        $redis->lrem('dflydev-git-subsplit:processing', 1, $body);
        $redis->lpush('dflydev-git-subspilt:failures', json_encode($data));
        continue;
    }

    $data['dflydev_git_subsplit']['name'] = $name;
    $data['dflydev_git_subsplit']['project'] = $project;

    $ref = $data['ref'];

    $publishCommand = array(
        'git subsplit publish',
        escapeshellarg(implode(' ', $project['splits'])),
    );

    if (preg_match('/refs\/tags\/(.+)$/', $ref, $matches)) {
        $publishCommand[] = escapeshellarg('--rebuild-tags');
        $publishCommand[] = escapeshellarg('--no-heads');
        $publishCommand[] = escapeshellarg(sprintf('--tags=%s', $matches[1]));
    } elseif (preg_match('/refs\/heads\/(.+)$/', $ref, $matches)) {
        $publishCommand[] = escapeshellarg('--no-tags');
        $publishCommand[] = escapeshellarg(sprintf('--heads=%s', $matches[1]));
    } else {
        print sprintf('Skipping request for URL %s (unexpected reference detected: %s)', $data['repository']['url'], $ref)."\n";

        $redis->lrem('dflydev-git-subsplit:processing', 1, $body);
        $redis->lpush('dflydev-git-subspilt:failures', json_encode($data));
        continue;
    }

    $repositoryUrl = isset($project['repository-url'])
        ? $project['repository-url']
        : $project['url'];

    print sprintf('Processing subsplit for %s (%s)', $name, $ref)."\n";

    $projectWorkingDirectory = $config['working-directory'].'/'.$name;
    if (!file_exists($projectWorkingDirectory)) {
        print sprintf('Creating working directory for project %s (%s)', $name, $projectWorkingDirectory)."\n";
        mkdir($projectWorkingDirectory, 0750, true);
    }

    $command = implode(' && ', array(
        sprintf('cd %s', $projectWorkingDirectory),
        sprintf('( git subsplit init %s || true )', $repositoryUrl),
        'git subsplit update',
        implode(' ', $publishCommand)
    ));

    passthru($command, $exitCode);

    if (0 !== $exitCode) {
        print sprintf('Command %s had a problem, exit code %s', $command, $exitCode)."\n";

        $redis->lrem('dflydev-git-subsplit:processing', 1, $body);
        $redis->lpush('dflydev-git-subspilt:failures', json_encode($data));
        continue;
    }

    $redis->lrem('dflydev-git-subsplit:processing', 1, $body);
    $redis->rpush('dflydev-git-subsplit:processed', json_encode($data));
}

$seconds = time() - $start;
throw new \RuntimeException(sprintf('Something strange happened after %s seconds', $seconds));
