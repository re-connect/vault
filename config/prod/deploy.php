<?php

use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class() extends DefaultDeployer {
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('vault-prod')
            ->deployDir('/var/www/reconnect_fr/www')
            ->repositoryUrl('git@github.com:re-connect/vault.git')
            ->repositoryBranch('main')
            ->remoteComposerBinaryPath('/var/www/reconnect_fr/composer.phar')
            ->composerOptimizeFlags('--optimize --no-dev --classmap-authoritative')
            ->useSshAgentForwarding(false)
            ->sharedFilesAndDirs([
                'config/secrets/prod/prod.decrypt.private.php',
                'config/jwt/',
                '.env',
                'var/log',
                'var/sessions',
                'var/oauth',
                'vendor/',
                'node_modules/',
                'public/bundles/',
                'public/uploads/',
                'public/js/',
                'public/build/',
            ]);
    }

    public function beforeOptimizing()
    {
        $this->log('Remote yarn');
        $this->runRemote('~/.yarn/bin/yarn install');
        $this->runRemote('~/.yarn/bin/yarn dev');
    }

    public function beforeFinishingDeploy()
    {
        $this->runRemote('{{ console_bin }} fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json');
        $this->runRemote('{{ console_bin }} doctrine:migrations:migrate -q');
    }
};
