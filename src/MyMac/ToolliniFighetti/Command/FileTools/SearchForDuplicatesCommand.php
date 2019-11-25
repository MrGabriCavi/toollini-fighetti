<?php
/**
 * Created by PhpStorm.
 * User: Gabriele Cavigiolo
 * Date: 02/10/2019
 * Time: 11:10
 */

namespace MyMac\ToolliniFighetti\Command\FileTools;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SearchForDuplicatesCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'my-mac:search-for-duplicates';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * SearchForDuplicatesCommand constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->setFilesystem($filesystem);
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument("source-dir",InputArgument::REQUIRED)
            ->addArgument("destination-target", InputArgument::REQUIRED)
            ->addOption("strict-search","s", InputOption::VALUE_NONE,"Ricerca con omissione dei caratteri jolly")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finderDestination = new Finder();
        $finderDestination->in($input->getArgument("destination-target"));
        $finder->files()->in($input->getArgument("source-dir"));

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                $sha1Source = sha1_file($absoluteFilePath);
                $fileNameWithExtension = $file->getRelativePathname();
                $output->writeln([
                    str_repeat("*",20),
                    "ANALISI FILE SORGENTE",
                    $fileNameWithExtension,
                    $absoluteFilePath
                ]);
                if ($input->getOption('strict-search') == true) {
                    $pattern = $file->getRelativePathname();
                } else {
                    $pattern = "*".$file->getFilenameWithoutExtension()."*.".$file->getExtension();
                }
                $output->writeln("PATTERN RICERCA: '".$pattern."'");
                $res = $finderDestination->files()
                    ->name($pattern);
                if ($finderDestination->hasResults()) {
                    foreach ($res as $fileFound) {
                        $sha1Dest = sha1_file($fileFound->getRealPath());
                        if ($sha1Source === $sha1Dest) {
                            $output->writeln("DUPLICATO TROVATO: ".$fileFound->getRealPath());

//                            $helper = $this->getHelper('question');
//                            $question = new ConfirmationQuestion(
//                                'Cancellare il file?',
//                                false
//                            );
//                            if (!$helper->ask($input, $output, $question)) {
//                                continue;
//                            }

                            $this->getFilesystem()->remove($absoluteFilePath);
                            $output->writeln("Cancellato");
                        }
                    }
                }
            }
        }
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * @param Filesystem $filesystem
     * @return SearchForDuplicatesCommand
     */
    public function setFilesystem(Filesystem $filesystem): SearchForDuplicatesCommand
    {
        $this->filesystem = $filesystem;
        return $this;
    }
}