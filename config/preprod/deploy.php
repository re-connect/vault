<?php

use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class() extends DefaultDeployer {
    private const FLUSH_CACHE_FILE_PATH = '{{ deploy_dir }}/current/public/flush-cache.php';
    private const FLUSH_CACHE_FILE_NAME = 'flush-cache.php';
    private const HOME_PAGE_URL = 'https://preprod.reconnect.fr';

    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('vault-pp')
            ->deployDir('/var/www/preprod_coffre_reconnect_fr/www')
            ->repositoryUrl('git@github.com:re-connect/vault.git')
            ->repositoryBranch('development')
            ->remoteComposerBinaryPath('/var/www/preprod_coffre_reconnect_fr/composer.phar')
            ->useSshAgentForwarding(false)
            ->composerOptimizeFlags('--optimize --no-dev --classmap-authoritative')
            ->sharedFilesAndDirs([
                'config/secrets/preprod/preprod.decrypt.private.php',
                'config/jwt/',
                '.env',
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
    }

    public function beforeOptimizing(): void
    {
        $this->log('Remote yarn');
        $this->runRemote('~/.yarn/bin/yarn install --force');
        $this->runRemote('~/.yarn/bin/yarn dev');
    }

    public function beforeFinishingDeploy(): void
    {
        $this->flushOpCache();
        $this->runRemote('{{ console_bin }} fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json');
        $this->runRemote('{{ console_bin }} doctrine:migrations:migrate -q');
    }

    private function flushOpCache(): void
    {
        $this->runRemote(sprintf('echo "<?php opcache_reset(); ?>" >> %s', self::FLUSH_CACHE_FILE_PATH));
        $this->runLocal(sprintf('wget "%s/%s" --spider', self::HOME_PAGE_URL, self::FLUSH_CACHE_FILE_NAME));
        $this->runRemote(sprintf('rm %s', self::FLUSH_CACHE_FILE_PATH));
    }
};
