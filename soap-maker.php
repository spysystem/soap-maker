<?php

use Nadar\PhpComposerReader\Autoload;
use Nadar\PhpComposerReader\AutoloadSection;
use Nadar\PhpComposerReader\ComposerReader;

require 'vendor/autoload.php';

/**
 * Class SoapMaker
 */
class SoapMaker
{
	private const OutputFolder = '/output/';

	private const Option_ProjectName	= 'project-name';
	private const Option_Namespace		= 'namespace';
	private const Option_WSDLPath		= 'wsdl-path';
	private const Option_Username		= 'username';
	private const Option_Password		= 'password';
	private const Option_SOAPVersion	= 'soap-version';

	private const Option_WithValue   = ':';

	private $strProjectName;
	private $strWSDL;
	private $strUsername;
	private $strPassword;
	private $strNamespace;
	private $strSOAPVersion;

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
			exit();
		}
	}

	/**
	 * @param string $strOutputDir
	 * @return string
	 */
	private function generateSrcOutputDir(string $strOutputDir): string
	{
		$strSrcOutputDir	= $strOutputDir.'/src';
		if(strpos($this->strNamespace, '\\') === false)
		{
			return $strSrcOutputDir;
		}

		return $strSrcOutputDir.$this->getPathComplementFromNamespace();
	}

	/**
	 * @return string
	 */
	private function getPathComplementFromNamespace(): string
	{
		if(strpos($this->strNamespace, '\\') === false)
		{
			return '';
		}

		$strTarget	= substr($this->strNamespace, strpos($this->strNamespace, '\\'));
		return str_replace('\\', '/', $strTarget);
	}

	public function generate(): void
	{
		$bProjectExists	= false;
		try
		{
			$strOutputDir		= __DIR__.self::OutputFolder.str_replace('\\', '/',$this->strProjectName);
			$strSrcOutputDir	= $this->generateSrcOutputDir($strOutputDir);
			if(file_exists($strOutputDir))
			{
				if($this->strProjectName === $this->strNamespace)
				{
					throw new Exception('Cannot create project: folder "'.$strOutputDir.'" already exists.');
				}
				$bProjectExists	= true;
				echo "Project already exists - if the given namespace already exists, this may overwrite files. Use with caution.\n";
			}
			elseif(!mkdir($strSrcOutputDir, 0777, true) && !is_dir($strSrcOutputDir))
			{
				throw new RuntimeException(sprintf('Directory "%s" was not created', $strSrcOutputDir));
			}

			$arrSoapClientOptions	= [
				'trace'        => true,
				'exceptions'   => true,
				'soap_version' => $this->strSOAPVersion,
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
					'namespaceName'		=> $this->strNamespace,
					'bracketedArrays'	=> true,
					'soapClientOptions'	=> $arrSoapClientOptions
				])
			);


			if($bProjectExists)
			{
				$this->updateComposer($strOutputDir);
			}
			else
			{
				file_put_contents($strOutputDir.'/README.md', $this->strProjectName.' Web Services');
				file_put_contents($strOutputDir.'/composer.json', $this->getComposerJsonContent());
				file_put_contents($strOutputDir.'/.gitignore', $this->getGitIgnoreContent());
			}

			echo "\n".$this->strProjectName.' SOAP library created at '.$strOutputDir.".\n\n";
		}
		catch (Throwable $oThrowable)
		{
			echo "\n";
			echo 'Fatal: '.$oThrowable->getMessage()."\n\n";
		}
	}

	/**
	 * @param string $strOutputDir
	 */
	private function updateComposer(string $strOutputDir): void
	{
		$strComposerFile	= $strOutputDir.'/composer.json';

		if(!is_file($strComposerFile))
		{
			file_put_contents($strOutputDir.'/composer.json', $this->getComposerJsonContent());
			return;
		}

		$strNamespacePath	= 'src'.$this->getPathComplementFromNamespace().'/';

		$oReader	= new ComposerReader($strComposerFile);
		$oNew		= new Autoload($oReader, $this->strNamespace.'\\', $strNamespacePath, AutoloadSection::TYPE_PSR4);

		$oSection	= new AutoloadSection($oReader);
		$oSection
			->add($oNew)
			->save()
		;


	}

	private function showUsage(): void
	{
		echo <<<EOT
Usage:
	php soap-maker.php --project-name <ProjectName> --wsdl-path <WSDL> [--namespace <Namespace>] [--username <Username> --password <Password>] [--soap-version <SOAPVersion>]

Where:
	<ProjectName> = Name for the project, without spaces
	<WSDL> = file or URL for the WSDL SOAP description
	<Namespace> = Namespace for the project classes. If omitted, defaults to ProjectName
	<SOAPVersion> = SOAP Version
	<Username>, <Password> = credentials for Basic Authentication, if required (if you need authentication, both must be present)

Project will be generated into the "output" folder

EOT;

	}

	/**
	 * @return string
	 */
	private function getComposerJsonContent(): string
	{
		$strAdjustedNamespace	= str_replace('\\', '\\\\', $this->strNamespace);
		return <<< EOT
{
	"name": "spysystem/{$this->strProjectName}",
	"description": "PHP library for {$this->strProjectName} Web Services",
	"license": "proprietary",
	"require": {
		"php": ">=7.1"
	},
	"autoload": {
		"psr-4": {
			"$strAdjustedNamespace\\\\": "src{$this->getPathComplementFromNamespace()}/"
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
			throw new RuntimeException('Missing Project Name!');
		}

		if(!array_key_exists(self::Option_WSDLPath, $arrOptions) || $arrOptions[self::Option_WSDLPath] === '')
		{
			throw new RuntimeException('Missing wsdl url or filename!');
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
			throw new RuntimeException('To use authentication, you must provide both Username and Password!');
		}
		$this->strProjectName	= $arrOptions[self::Option_ProjectName];
		$this->strWSDL			= $arrOptions[self::Option_WSDLPath];
		$this->strUsername		= $arrOptions[self::Option_Username] ?? '';
		$this->strPassword		= $arrOptions[self::Option_Password] ?? '';
		$this->strNamespace		= $arrOptions[self::Option_Namespace] ?? $this->strProjectName;
		$this->strSOAPVersion	= (int)($arrOptions[self::Option_SOAPVersion] ?? SOAP_1_2);
	}

	/**
	 * @return array
	 */
	public static function GetLongOptsArray(): array
	{
		return [
			self::Option_ProjectName.self::Option_WithValue,
			self::Option_WSDLPath.self::Option_WithValue,
			self::Option_Username.self::Option_WithValue,
			self::Option_Password.self::Option_WithValue,
			self::Option_Namespace.self::Option_WithValue,
			self::Option_SOAPVersion.self::Option_WithValue
		];
	}
}

$oSoapMaker	= new SoapMaker(getopt('', SoapMaker::GetLongOptsArray()));

$oSoapMaker->generate();