<?php
require 'vendor/autoload.php';

/**
 * Class SoapMaker
 */
class SoapMaker
{
	private const OutputFolder = '/output/';

	private const Option_ProjectName	= 'project-name';
	private const Option_WSDLPath		= 'wsdl-path';
	private const Option_Username		= 'username';
	private const Option_Password		= 'password';

	private const Option_WithValue		= ':';

	private $strProjectName;
	private $strWSDL;
	private $strUsername;
	private $strPassword;

	/**
	 * SoapMaker constructor.
	 *
	 * @param array $arrOptions
	 */
	public function __construct(array $arrOptions)
	{
		try
		{
			$this->parseOptions($arrOptions);
		}
		catch(Exception $oException)
		{
			echo $oException->getMessage()."\n";
			$this->showUsage();
		}
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

			$arrSoapClientOptions	= [
				'trace'        => true,
				'exceptions'   => true,
				'soap_version' => SOAP_1_2,
				'encoding'     => 'UTF-8'
			];

			if($this->strUsername !== '')
			{
				$arrSoapClientOptions['authentication']	= SOAP_AUTHENTICATION_BASIC;
				$arrSoapClientOptions['login']			= $this->strUsername;
				$arrSoapClientOptions['password']		= $this->strPassword;
			}

			$oGenerator	= new \Wsdl2PhpGenerator\Generator();
			$oGenerator->generate(
				new \Wsdl2PhpGenerator\Config([
					'inputFile'			=> $this->strWSDL,
					'outputDir'			=> $strSrcOutputDir,
					'namespaceName'		=> $this->strProjectName,
					'bracketedArrays'	=> true,
					'soapClientOptions'	=> $arrSoapClientOptions
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


	private function showUsage(): void
	{
		echo <<<EOT
Usage:
	php soap-maker.php --project-name <ProjectName> --wsdl-path <WSDL> [--username <Username> --password <Password>]

Where:
	<ProjectName> = Name for the project, without spaces
	<WSDL> = file or URL for the WSDL SOAP description
	<Username>, <Password> = credentials for Basic Authentication, if required

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

	/**
	 * @param $arrOptions
	 * @throws Exception
	 */
	private function parseOptions($arrOptions): void
	{
		if(!array_key_exists(self::Option_ProjectName, $arrOptions) || $arrOptions[self::Option_ProjectName] === '')
		{
			throw new Exception('Missing Project Name!');
		}

		if(!array_key_exists(self::Option_WSDLPath, $arrOptions) || $arrOptions[self::Option_WSDLPath] === '')
		{
			throw new Exception('Missing wsdl url or filename!');
		}

		if(
			(
				array_key_exists(self::Option_Username, $arrOptions)
			 &&	!array_key_exists(self::Option_Password, $arrOptions)
			)
		 ||
			(
				!array_key_exists(self::Option_Username, $arrOptions)
			 &&	array_key_exists(self::Option_Password, $arrOptions)
			)
		)
		{
			throw new Exception('To use authentication, you must provide both Username and Password!');
		}
		$this->strProjectName	= $arrOptions[self::Option_ProjectName];
		$this->strWSDL			= $arrOptions[self::Option_WSDLPath];
		$this->strUsername		= $arrOptions[self::Option_Username] ?? '';
		$this->strPassword		= $arrOptions[self::Option_Password] ?? '';
	}

	public static function GetLongOptsArray(): array
	{
		return [
			self::Option_ProjectName.self::Option_WithValue,
			self::Option_WSDLPath.self::Option_WithValue,
			self::Option_Username.self::Option_WithValue,
			self::Option_Password.self::Option_WithValue
		];
	}
}

$oSoapMaker	= new SoapMaker(getopt('', SoapMaker::GetLongOptsArray()));

$oSoapMaker->generate();