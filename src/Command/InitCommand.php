<?php

namespace TomeConsole\Command;

use Drupal\Core\Site\Settings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Contains the tome:init command.
 */
class InitCommand extends InstallCommand {


  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tome:init')
      ->setDescription('Initializes tome')
      ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Assume "yes" as answer to all prompts,');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);

    $executable = $this->findExecutable($input);

    if (is_dir(config_get_config_directory(CONFIG_SYNC_DIRECTORY)) || is_dir(Settings::get('tome_content_directory', '../content'))) {
      if (!$input->getOption('yes') && !$io->confirm('Running this command will remove all exported content and configuration. Do you want to continue?', FALSE)) {
        return 0;
      }
    }

    $this->runCommand($executable . ' site:install --force');
    $this->runCommand($executable . ' module:install tome');
    $this->runCommand($executable . ' tome:export -y');
  }

}
