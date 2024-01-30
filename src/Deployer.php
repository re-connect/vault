<?php

namespace App;

use EasyCorp\Bundle\EasyDeployBundle\Configuration\DefaultConfiguration;
use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

class Deployer extends DefaultDeployer
{
    protected const FLUSH_CACHE_FILE_NAME = 'flush-cache.php';
    protected const PUBLIC_FILE_PATH = '{{ deploy_dir }}/current/public/';
    protected const FLUSH_CACHE_FILE_PATH = self::PUBLIC_FILE_PATH.self::FLUSH_CACHE_FILE_NAME;
    protected const HOME_PAGE_URL = 'https://localhost:8000';
    protected string $server = 'vault-pp';
    protected string $repositoryBranch = 'development';
    protected string $home = '/var/www/preprod_coffre_reconnect_fr';
    protected string $symfonyVaultDecryptKeyPath = '/preprod/preprod.decrypt.private.php';

    public function configure(): DefaultConfiguration
    {
        return $this->getConfigBuilder()
            ->server($this->server)
            ->deployDir($this->getAbsolutePath('/www'))
            ->repositoryUrl('git@github.com:re-connect/vault.git')
            ->repositoryBranch($this->repositoryBranch)
            ->remoteComposerBinaryPath($this->getAbsolutePath('/composer.phar'))
            ->useSshAgentForwarding(false)
            ->composerOptimizeFlags('--optimize --no-dev --classmap-authoritative')
            ->sharedFilesAndDirs([
                sprintf('config/secrets%s', $this->symfonyVaultDecryptKeyPath),
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

    private function getAbsolutePath(string $path): string
    {
        return sprintf('%s%s', $this->home, $path);
    }

    public function beforeOptimizing(): void
    {
        $this->log('Remote yarn');
        $this->runRemote('~/.yarn/bin/yarn install --force');
        $this->runRemote('~/.yarn/bin/yarn dev');
    }

    public function beforePublishing(): void
    {
        $this->flushOpCache();
    }

    public function beforeFinishingDeploy(): void
    {
        $this->runRemote('{{ console_bin }} fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json');
        $this->runRemote('{{ console_bin }} doctrine:migrations:migrate -q');
    }

    protected function flushOpCache(): void
    {
        $this->runRemote(sprintf('echo "<?php opcache_reset(); ?>" >> %s', self::FLUSH_CACHE_FILE_PATH));
        $this->runRemote(sprintf('wget "%s/%s" --spider', static::HOME_PAGE_URL, self::FLUSH_CACHE_FILE_NAME));
        $this->runRemote(sprintf('rm %s', self::FLUSH_CACHE_FILE_PATH));
    }
}
