<?php

use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class() extends DefaultDeployer {
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('vault-pp')
            ->deployDir('/var/www/preprod_coffre_reconnect_fr/www')
            ->repositoryUrl('git@github.com:re-connect/vault.git')
            ->repositoryBranch('master')
            ->remoteComposerBinaryPath('/var/www/preprod_coffre_reconnect_fr/composer.phar')
            ->useSshAgentForwarding(false)
            ->composerOptimizeFlags('--optimize --no-dev --classmap-authoritative')
            ->sharedFilesAndDirs([
                'config/secrets/preprod/preprod.decrypt.private.php',
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
