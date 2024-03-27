<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config
const MAX_CONNECTION_RETRY = 5;
set('repository', 'git@github.com:re-connect/vault.git');
set('branch', 'development');
set('flush_cache_file_name', 'flush-cache.php');
set('flush_cache_file_path', '{{current_path}}/public/{{flush_cache_file_name}}');
set('homepage_url', 'https://preprod.reconnect.fr');

add('shared_files', [
    '.env',
    'config/secrets/preprod/preprod.decrypt.private.php',
]);
add('shared_dirs', [
    'var/log',
    'var/sessions',
    'var/oauth',
    'vendor/',
    'node_modules/',
    'public/bundles/',
    'public/js/',
    'public/uploads/',
    'public/build/',
]);
add('writable_dirs', []);

// Hosts

host('vault-pp')
    ->setForwardAgent(true)
    ->setSshMultiplexing(true)
    ->set('config_file', '~/.ssh/config')
    ->set('deploy_path', '~/www')
    ->set('http_user', 'preprod_coffre_reconnect_fr');

// Tasks

// Test connection in order to avoid "Failed to connect to secondary target" error
task('deploy:test_connection', function () {
    $i = 0;
    while (MAX_CONNECTION_RETRY > $i) {
        if (run('echo test', no_throw: true)) {
            break;
        }
        ++$i;
    }
});
task('deploy:install_frontend', function () {
    run('cd {{release_path}} && ~/.yarn/bin/yarn install');
});
task('deploy:build_frontend', function () {
    run('cd {{release_path}} && ~/.yarn/bin/yarn build');
});
task('deploy:dump_frontend_routes', function () {
    run('cd {{release_path}} && {{bin/console}} fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json');
});
task('deploy:reset-opcache', function () {
    run('sleep 5');
    run('echo "<?php opcache_reset(); ?>" >> {{flush_cache_file_path}}');
    run('sleep 5');
    run('wget "{{homepage_url}}/{{flush_cache_file_name}}" --spider --retry-connrefused -t 5');
    run('rm {{flush_cache_file_path}}');
});

// Hooks

before('deploy:setup', 'deploy:test_connection');
before('deploy:build_frontend', 'deploy:dump_frontend_routes');
before('deploy:build_frontend', 'deploy:install_frontend');
before('deploy:cache:clear', 'deploy:build_frontend');
before('deploy:symlink', 'database:migrate');
after('deploy:symlink', 'deploy:reset-opcache');
after('deploy:failed', 'deploy:unlock');
