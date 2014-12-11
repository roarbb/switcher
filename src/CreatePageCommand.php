<?php namespace Switcher;


use Nette\Utils\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreatePageCommand extends Command
{
    private $placeholderPattern;
    private $configPlaceholder;
    private $templateFolder;
    private $outputFolder;
    private $commonConfigFile;

    public function __construct()
    {
        $ds = DIRECTORY_SEPARATOR;
        $this->templateFolder = __DIR__ . $ds . '..' . $ds . 'fileTemplates' . $ds;
        $this->outputFolder = __DIR__ . $ds . '..' . $ds . '..' . $ds;
        $this->commonConfigFile = $this->outputFolder . 'config' . $ds . 'common.inc';

        $this->placeholderPattern = '/\[SWCH_(\w+)_(\w+)\]/';
        $this->configPlaceholder = '// [SWITCHER-INCLUDING-PLACE]';

        parent::__construct();
    }

    public function configure()
    {
        $this->setName('createPage');
        $this->setDescription('Creates VCMS v3 page, component and register it to config file');
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the new Page (camelCase)');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $inputArguments = $input->getArguments();
        $files = Finder::findFiles('*.xml', '*.php')->from($this->templateFolder);

        foreach ($files as $key => $file) {
            $createdPathForNewFile = $this->createFolderForFile($file, $inputArguments);
            $this->createFile($file, $createdPathForNewFile, $inputArguments);
        }

        $this->registerPageToConfig($output, $inputArguments);
        $output->writeln('All done.');
    }

    private function createFolderForFile(\SplFileInfo $file, $arguments)
    {
        $folderStructurePath = str_replace($this->templateFolder, '', $file->getPathname());
        $folderOutputPath = $this->outputFolder;
        $pathPieces = explode(DIRECTORY_SEPARATOR, $folderStructurePath);
        array_pop($pathPieces); //because last element is file, I don't want to create it

        foreach ($pathPieces as $folder) {
            $folder = $this->translatePlaceholder($folder, $arguments);

            $folderOutputPath .= $folder . DIRECTORY_SEPARATOR;
            $this->createDirectoryIfNotExists($folderOutputPath);
        }

        return $folderOutputPath;
    }

    private function createDirectoryIfNotExists($path)
    {
        if(!file_exists($path) && !is_dir($path)) {
            mkdir($path);
        }
    }

    private function createFile(\SplFileInfo $file, $translatedOutputPath, $arguments)
    {
        $originalFileName = $file->getFilename();
        $newFileName = $this->translatePlaceholder($originalFileName, $arguments);
        $fileContent = $this->getFileContent($file->getRealPath(), $arguments);

        file_put_contents($translatedOutputPath . $newFileName, $fileContent);
    }

    private function translatePlaceholder($stringToTranslate, $inputArguments)
    {
        preg_match($this->placeholderPattern, $stringToTranslate, $match);

        if(!isset($match) || empty($match)) {
            return $stringToTranslate;
        }

        $stringToTranslateArgument = strtolower($match[1]);
        $stringToTranslateFunction = strtolower($match[2]);

        $translatedString = $this->getStringTranslatedByFunction($stringToTranslateArgument, $stringToTranslateFunction, $inputArguments);
        $endOfString = preg_replace($this->placeholderPattern, '', $stringToTranslate);

        return $translatedString . $endOfString;
    }

    private function getFileContent($file, $arguments)
    {
        $content = file_get_contents($file);

        if (empty($content)) {
            return $content;
        }

        preg_match_all($this->placeholderPattern, $content, $matches);

        foreach ($matches[0] as $match) {
            $content = str_replace($match, $this->translatePlaceholder($match, $arguments), $content);
        }

        return $content;
    }

    private function registerPageToConfig(OutputInterface $output, $arguments)
    {
        if (!file_exists($this->commonConfigFile) && !is_dir($this->commonConfigFile)) {
            $output->writeln('<error>Write to \config\common.inc unsuccessfull. File does not exists.</error>');
            return false;
        }

        $content = file_get_contents($this->commonConfigFile);
        $pageName = $this->translatePlaceholder('[SWCH_name_lower]', $arguments);
        $newLineToConfig = '"1.0/' . $pageName . '/index" => "auth",' . PHP_EOL . "\t\t" . $this->configPlaceholder;

        $content = str_replace($this->configPlaceholder, $newLineToConfig, $content);

        file_put_contents($this->commonConfigFile, $content);
    }

    private function getStringTranslatedByFunction($stringToTranslateArgument, $stringToTranslateFunction, $inputArguments)
    {
        $onlyPlaceholderRelatedArguments = array_filter($inputArguments, function($inputArgument) use($stringToTranslateArgument) {
            return $stringToTranslateArgument == $inputArgument;
        }, ARRAY_FILTER_USE_KEY);

        foreach ($onlyPlaceholderRelatedArguments as $inputArgument => $inputArgumentValue) {
            return $this->translateString($stringToTranslateFunction, $inputArgumentValue);
        }
    }

    private function translateString($function, $value)
    {
        if($function == 'lower') {
            return lcfirst($value);
        }

        if ($function == 'upper') {
            return ucfirst($value);
        }
    }
}