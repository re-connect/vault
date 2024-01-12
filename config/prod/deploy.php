<?php

use App\Deployer;

return new class extends Deployer {
    protected const HOME_PAGE_URL = 'https://reconnect.fr';
    protected string $server = 'vault-prod';
    protected string $repositoryBranch = 'main';
    protected string $home = '/var/www/reconnect_fr';
    protected string $symfonyVaultDecryptKeyPath = '/prod/prod.decrypt.private.php';
};
