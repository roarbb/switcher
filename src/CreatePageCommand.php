<?php namespace Switcher;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreatePageCommand extends Command
{
    public function configure()
    {
        $this->setName('createPage');
        $this->setDescription('Creates VCMS v3 page');
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the new Page');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $message = 'Creating page ' . $input->getArgument('name');
        $output->writeln("<info>{$message}</info>");
    }
}