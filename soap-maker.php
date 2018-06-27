<?php
require 'vendor/autoload.php';

/**
 * Class SoapMaker
 */
class SoapMaker
{
	const OutputFolder = '/output/';

	private $strProjectName;
	private $strWSDL;

	/**
	 * SoapMaker constructor.
	 *
	 * @param string $strProjectName
	 * @param string $strWSDL
	 */
	public function __construct(string $strProjectName, string $strWSDL)
	{
		$this->strProjectName	= $strProjectName;
		$this->strWSDL			= $strWSDL;
	}

	public function generate(): void
	{
		try
		{
			$strOutputDir		= __DIR__.self::OutputFolder.str_replace('\\', '/',$this->strProjectName);
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
					'bracketedArrays'	=> true,
					'soapClientOptions'	=> [
						'trace'        => true,
						'exceptions'   => true,
						'soap_version' => SOAP_1_2,
						'encoding'     => 'UTF-8'
					]
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
		$strAdjustedNamespace	= str_replace('\\', '\\\\', $this->strProjectName);
		$strPackageName			= str_replace('\\', '', $this->strProjectName);
		return <<< EOT
{
	"name": "spysystem/$strPackageName",
	"description": "PHP library for $strPackageName Web Services",
	"license": "proprietary",
	"require": {
		"php": ">=7.1"
	},
	"autoload": {
		"psr-4": {
			"$strAdjustedNamespace\\\\": "src/"
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
	exit();
}

$oSoapMaker	= new SoapMaker($argv[1], $argv[2]);

$oSoapMaker->generate();