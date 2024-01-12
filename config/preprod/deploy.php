<?php

use App\Deployer;

return new class extends Deployer {
    protected const HOME_PAGE_URL = 'https://preprod.reconnect.fr';
    protected string $server = 'vault-pp';
    protected string $repositoryBranch = 'development';
    protected string $home = '/var/www/preprod_coffre_reconnect_fr';
    protected string $symfonyVaultDecryptKeyPath = '/preprod/preprod.decrypt.private.php';
};
