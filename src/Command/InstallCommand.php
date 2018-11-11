<?php

namespace TomeConsole\Command;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Config\FileStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Contains the tome:install command.
 */
class InstallCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tome:install')
      ->setDescription('Installs tome')
      ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Assume "yes" as answer to all prompts,');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);

    if (!$input->getOption('yes') && !$io->confirm('You are about to DROP all tables in your local database and re-install Tome. Do you want to continue?', FALSE)) {
      return 0;
    }

    $executable = $this->findExecutable($input);

    FileCacheFactory::setConfiguration(['default' => ['class' => '\Drupal\Component\FileCache\NullFileCache']]);
    $source_storage = new FileStorage(config_get_config_directory(CONFIG_SYNC_DIRECTORY));

    if (!$source_storage->exists('core.extension')) {
      $io->warning('Existing configuration to install from not found. If this is your first time using Tome try running "drupal tome:init".');
      return 1;
    }

    $config = $source_storage->read('core.extension');

    $this->runCommand($executable . ' site:install ' . escapeshellarg($config['profile']) . ' --force --no-interaction');

    if (isset($config['module']['tome_sync'])) {
      $this->runCommand($executable . ' module:install tome_sync');
    }
    else {
      $this->runCommand($executable . ' module:install tome');
    }
    $this->runCommand($executable . ' tome:import -y');
    $this->runCommand($executable . ' cache:rebuild');
  }

  /**
   * Runs a command.
   *
   * @param string $command
   *   A command to run.
   *
   * @throws \Symfony\Component\Process\Exception\ProcessFailedException
   */
  protected function runCommand($command) {
    $process = new Process($command);
    $process->setTty(TRUE);
    $process->run(function ($type, $buffer) {
      if ($type === 'out') {
        echo $buffer;
      }
    });
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }
  }

  /**
   * Finds an executable string for the current process.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   *
   * @return string
   *   An executable string, i.e. "drush @foo.bar" or "./vendor/bin/drupal".
   */
  protected function findExecutable(InputInterface $input) {
    $args = [];
    foreach ($_SERVER['argv'] as $arg) {
      if ($arg === $input->getFirstArgument()) {
        break;
      }
      $args[] = $arg;
    }
    return implode(' ', $args);
  }

}
