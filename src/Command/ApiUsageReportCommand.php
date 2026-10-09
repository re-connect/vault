<?php

namespace App\Command;

use App\EventSubscriber\Api\ApiRouteUsageSubscriber;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:api:usage-report',
    description: 'Aggregate the API usage logs per route, API version, OAuth client and user type',
)]
class ApiUsageReportCommand extends Command
{
    /** @var array<string, ?string> */
    private array $clientNames = [];

    public function __construct(
        #[Autowire('%kernel.logs_dir%/api_usage')]
        private readonly string $logDirectory,
        private readonly ClientManagerInterface $clientManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Only count calls made during the last N days', '30')
            ->addOption('api-version', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only count these API versions (legacy, v2, v3, oauth, appli, public)');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $since = new \DateTimeImmutable(sprintf('-%d days', (int) $input->getOption('days')));
        $versions = $input->getOption('api-version');

        $rows = [];
        foreach (glob(sprintf('%s/*.log', $this->logDirectory)) ?: [] as $file) {
            foreach (new \SplFileObject($file) as $line) {
                $record = json_decode((string) $line, true);
                if (!is_array($record) || ApiRouteUsageSubscriber::MESSAGE !== ($record['message'] ?? null)) {
                    continue;
                }

                $date = new \DateTimeImmutable($record['datetime']);
                $context = $record['context'];
                if ($date < $since || ($versions && !in_array($context['version'], $versions, true))) {
                    continue;
                }

                $key = implode('|', [$context['route'], $context['version'], $context['client_id'], $context['user_type']]);
                $rows[$key] ??= [
                    $context['route'] ?? '(unmatched)',
                    $context['version'],
                    $this->getClientName($context['client_id']),
                    $context['user_type'],
                    0,
                    $date,
                ];
                ++$rows[$key][4];
                $rows[$key][5] = max($rows[$key][5], $date);
            }
        }

        usort($rows, static fn (array $a, array $b) => $b[4] <=> $a[4]);
        $io->table(
            ['Route', 'Version', 'Client', 'User type', 'Calls', 'Last call'],
            array_map(static fn (array $row) => [...array_slice($row, 0, 5), $row[5]->format('Y-m-d H:i')], $rows),
        );

        return Command::SUCCESS;
    }

    private function getClientName(?string $clientId): string
    {
        if (null === $clientId) {
            return '-';
        }

        if (!array_key_exists($clientId, $this->clientNames)) {
            $this->clientNames[$clientId] = $this->clientManager->find($clientId)?->getName();
        }

        return $this->clientNames[$clientId] ?? $clientId;
    }
}
