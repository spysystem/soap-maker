<?php
require 'vendor/autoload.php';

class SoapMaker
{
	const OutputFolder = '/output/';

	private $strProjectName;
	private $strWSDL;

	public function __construct(string $strProjectName, string $strWSDL)
	{
		$this->strProjectName	= $strProjectName;
		$this->strWSDL			= $strWSDL;
	}

	public function generate(): void
	{
		try
		{
			$strOutputDir		= __DIR__.self::OutputFolder.$this->strProjectName;
			$strSrcOutputDir	= $strOutputDir.'/src';
			if(file_exists($strOutputDir))
			{
				throw new Exception('Cannot create project: folder "'.$strOutputDir.'" already exists.');
			}
			mkdir($strSrcOutputDir, 0777, true);

			$oGenerator	= new \Wsdl2PhpGenerator\Generator();
			$oGenerator->generate(
				new \Wsdl2PhpGenerator\Config([
					'inputFile'			=> $this->strWSDL,
					'outputDir'			=> $strSrcOutputDir,
					'namespaceName'		=> $this->strProjectName,
					'bracketedArrays'	=> true
				])
			);

			file_put_contents($strOutputDir.'/README.md', $this->strProjectName.' Web Services');
			file_put_contents($strOutputDir.'/composer.json', $this->getComposerJsonContent());
			file_put_contents($strOutputDir.'/.gitignore', $this->getGitIgnoreContent());

			echo "\n".$this->strProjectName.' SOAP library created at '.$strOutputDir.".\n\n";
		}
		catch (Throwable $oThrowable)
		{
			echo "\n";
			echo 'Fatal: '.$oThrowable->getMessage()."\n\n";
		}
	}


	public static function ShowUsage(): void
	{
		echo <<<EOT
Usage:
	php soap-maker.php <ProjectName> <WSDL>

Where:
	<ProjectName> = Name for the project, without spaces
	<WSDL> = file or URL for the WSDL SOAP description

Project will be generated into the "output" folder

EOT;

	}

	/**
	 * @return string
	 */
	private function getComposerJsonContent(): string
	{
		return <<< EOT
{
	"name": "spysystem/$this->strProjectName",
	"description": "PHP library for $this->strProjectName Web Services",
	"license": "proprietary",
	"require": {
		"php": ">=5.6"
	},
	"autoload": {
		"psr-4": {
			"$this->strProjectName\\\\": "src"
		}
	}
}
EOT;

	}

	/**
	 * @return string
	 */
	private function getGitIgnoreContent(): string
	{
		return <<< EOT
# IntelliJ project files
.idea
EOT;

	}
}

if($argc !== 3)
{
	SoapMaker::ShowUsage();
}

$oSoapMaker	= new SoapMaker($argv[1], $argv[2]);

$oSoapMaker->generate();