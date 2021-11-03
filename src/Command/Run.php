<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\Database;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Run extends Command
{
    protected static string $defaultName = 'app:run';

    private Database $database;

    public function __construct(Database $database, string $name = null)
    {
        parent::__construct($name);

        $this->database = $database;
    }

    protected function configure()
    {
        $this
            ->setDescription('Проверка работоспособности сервиса')
            ->setHelp(
                ''
            );
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title($this->getDescription());

        $io->text('Для запуска тестов: ./vendor/bin/phpunit');

        return Command::SUCCESS;
    }
}