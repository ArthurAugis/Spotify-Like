<?php

namespace App\Command;

use App\Entity\User;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-recommendations',
    description: 'Generate recommendations for all users or a specific user',
)]
class GenerateRecommendationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecommendationService $recommendationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'Generate recommendations for specific user ID')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of recommendations per user', 10)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be generated without saving')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $userId = $input->getOption('user-id');
        $limit = (int) $input->getOption('limit');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No recommendations will be saved');
        }

        $userRepository = $this->entityManager->getRepository(User::class);

        if ($userId) {
            $user = $userRepository->find($userId);
            if (!$user) {
                $io->error("User with ID {$userId} not found");
                return Command::FAILURE;
            }
            $users = [$user];
        } else {
            $users = $userRepository->findAll();
        }

        $io->title('Generating Music Recommendations');
        $io->progressStart(count($users));

        $totalGenerated = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $recommendations = $this->recommendationService->generateRecommendationsForUser($user, $limit);
                
                if (!$dryRun && !empty($recommendations)) {
                    $this->recommendationService->saveRecommendations($recommendations);
                }
                
                $count = count($recommendations);
                $totalGenerated += $count;
                
                if ($io->isVerbose()) {
                    $io->text("Generated {$count} recommendations for user: {$user->getEmail()}");
                }
                
            } catch (\Exception $e) {
                $errors[] = "Error for user {$user->getEmail()}: " . $e->getMessage();
            }
            
            $io->progressAdvance();
        }

        $io->progressFinish();

        // Show results
        $io->success([
            "Generated {$totalGenerated} recommendations for " . count($users) . " users",
            $dryRun ? "(DRY RUN - nothing saved)" : "All recommendations saved to database"
        ]);

        if (!empty($errors)) {
            $io->warning('Some errors occurred:');
            foreach ($errors as $error) {
                $io->text("- {$error}");
            }
        }

        // Show statistics if verbose
        if ($io->isVerbose() && $totalGenerated > 0) {
            $avgPerUser = round($totalGenerated / count($users), 2);
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Total Users', count($users)],
                    ['Total Recommendations', $totalGenerated],
                    ['Average per User', $avgPerUser],
                    ['Max per User', $limit],
                ]
            );
        }

        return Command::SUCCESS;
    }
}