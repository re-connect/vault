<?php
namespace Deployer;

require 'recipe/symfony.php';
require 'contrib/cachetool.php';

// Config

set('repository', 'git@github.com:re-connect/vault.git');
set('branch', 'development');
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
    ->set('config_file', '~/.ssh/config')
    ->set('deploy_path', '~/www')
    ->setForwardAgent(true)
    ->set('http_user', 'preprod_coffre_reconnect_fr');

// Tasks

task('deploy:install_frontend', function () {
    run('cd {{release_path}} && ~/.yarn/bin/yarn install');
});
task('deploy:build_frontend', function () {
    run('cd {{release_path}} && ~/.yarn/bin/yarn build');
});
task('deploy:dump_frontend_routes', function () {
    run('cd {{release_path}} && {{bin/console}} fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json');
});

set('cachetool_args', '--web=SymfonyHttpClient --web-path=/var/www/preprod_coffre_reconnect_fr/www/current/public --web-url=https://preprod.reconnect.fr');

// Hooks

before('deploy:build_frontend', 'deploy:dump_frontend_routes');
before('deploy:build_frontend', 'deploy:install_frontend');
before('deploy:cache:clear', 'deploy:build_frontend');
before('deploy:symlink', 'database:migrate');
after('deploy:symlink', 'cachetool:clear:opcache');

after('deploy:failed', 'deploy:unlock');
