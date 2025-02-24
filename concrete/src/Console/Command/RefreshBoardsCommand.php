<?php

namespace Concrete\Core\Console\Command;

use Concrete\Core\Application\Application;
use Concrete\Core\Board\Command\RegenerateBoardInstanceCommand;
use Concrete\Core\Console\Command;
use Concrete\Core\Entity\Board\Board;
use Concrete\Core\Entity\Board\Instance;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RefreshBoardsCommand extends Command
{
    protected function configure()
    {
        $okExitCode = static::SUCCESS;
        $errExitCode = static::FAILURE;

        $this
            ->setName('c5:boards:refresh')
            ->setDescription('Add content to boards and board instances.')
            ->addOption('--all',  'a',InputOption::VALUE_NONE,
    'Refreshes and regenerates boards.')
            ->addArgument('boardID', InputArgument::OPTIONAL)
            ->setHelp(<<<EOT
This command will add content to specified boards or board instances.

  {$okExitCode} operation completed successfully
  {$errExitCode} errors occurred
EOT
            )
        ;
    }

    protected function refreshBoard(Application $app, EntityManager $em, int $boardID)
    {
        $board = $em->find(Board::class, $boardID); // we do this because em->clear() clears out the board.
        $this->output->writeln(t('Retrieving instances from board: %s', $board->getBoardName()));
        $instances = $board->getInstances();
        foreach($instances as $instance) {
            $this->refreshInstance($app, $instance);
        }
        $em->clear();
    }

    protected function refreshInstance(Application $app, Instance $instance)
    {
        $this->output->writeln(t('** Regenerating board instance: %s', $instance->getBoardInstanceName()));
        $command = new RegenerateBoardInstanceCommand();
        $command->setInstance($instance);
        $app->executeCommand($command);
    }

    public function handle(Application $app, EntityManager $em)
    {
        if ($this->input->getOption('all')) {
            $boards = $em->getRepository(Board::class)
                ->findAll();
            foreach($boards as $board) {
                $this->refreshBoard($app, $em, $board->getBoardID());
            }
        } else {
            $boardID = $this->input->getArgument('boardID');
            if ($boardID) {
                $board = $em->find(Board::class, $boardID);
                if ($board) {
                    $this->refreshBoard($app, $em, $board->getBoardID());
                } else {
                    throw new \Exception(t('Invalid board ID.'));
                }
            } else {
                throw new \Exception(t('You must specify a board ID or use the --all option.'));
            }
        }
        return static::SUCCESS;
    }
}
