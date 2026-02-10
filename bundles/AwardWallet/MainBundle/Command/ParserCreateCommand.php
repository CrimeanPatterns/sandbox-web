<?php

namespace AwardWallet\MainBundle\Command;

use \AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParserCreateCommand extends Command
{
    public function configure()
    {
        $this->setName("lp:create");
        $this->setDescription("create LPs parser template");
        $this->addArgument("code", InputArgument::REQUIRED, "program code");
        $this->addOption('template', null, InputOption::VALUE_REQUIRED, 'template', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = strtolower($input->getArgument("code"));
        $path = "src/engine/" . $code;
        if (is_dir($path) && file_exists($path . DIRECTORY_SEPARATOR . 'functions.php')) {
            throw new \Exception("This program already exists!");
        } else {
            if (!is_dir($path)) {
                mkdir($path);
                $output->writeln("<comment>LP:</comment><info>directory created successfully!</info>");
            } else {
                $output->writeln("<comment>LP:</comment><info>directory already exist...</info>");
            }

            $connection = $this->getContainer()->get('database_connection');
            $provider = $connection->executeQuery("select * from Provider where Code = ?", [$code])->fetch(FetchMode::ASSOCIATIVE);
            $template = file_get_contents("data/templates/lpTemplates/" . $input->getOption('template') . ".php");
            if ($provider !== false) {
                $output->writeln("<comment>LP:</comment><info>provider found in database, ID:<comment>{$provider['ProviderID']}</comment>. Preset...</info>");
                $template = str_replace("loginURL", $provider['LoginURL'], $template);
                $template = str_replace("siteURL", $provider['Site'], $template);
            } else {
                $output->writeln("<comment>LP:</comment><info>provider not found in database. Preset default...</info>");
            };

            $template = str_replace("ProviderName", ucfirst($code), $template);
//            $template = str_replace("providerCode", $code, $template);

            if (file_put_contents($path . DIRECTORY_SEPARATOR . 'functions.php', $template))
                $output->writeln("<comment>LPs:</comment><info>parser template added successfully!</info>");
        }
    }
}