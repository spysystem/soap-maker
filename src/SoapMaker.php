<?php
namespace SpySystem\SoapMaker;

use Exception;
use Nadar\PhpComposerReader\Autoload;
use Nadar\PhpComposerReader\AutoloadSection;
use Nadar\PhpComposerReader\ComposerReader;
use RuntimeException;
use Throwable;
use Wsdl2PhpGenerator\Config;
use Wsdl2PhpGenerator\Generator;

/**
 * Class SoapMaker
 */
class SoapMaker
{
	private const OutputFolder = '/output/';

	private const Option_VendorName					= 'vendor-name';
	private const Option_ProjectName				= 'project-name';
	private const Option_Namespace					= 'namespace';
	private const Option_WSDLPath					= 'wsdl-path';
	private const Option_Username					= 'username';
	private const Option_Password					= 'password';
	private const Option_SOAPVersion				= 'soap-version';
	private const Option_OutputPath					= 'output-path';
	private const Option_UsePrivatePackagist		= 'use-private-packagist';
	private const Option_UseLocationInsideOptions	= 'use-location-inside-options';
	private const Option_WithValue					= ':';

	private string	$strVendorName;
	private string	$strProjectName;
	private string	$strWSDL;
	private string	$strUsername;
	private string	$strPassword;
	private string	$strNamespace;
	private string	$strSOAPVersion;
	private string	$strOutputPath;
	private bool	$bUsePrivatePackagist;
	private bool    $bUseLocationInsideOptions;

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
	 * @return string
	 */
	private function generateSrcOutputDir(): string
	{
		$strSrcOutputDir	= $this->strOutputPath.'/src';
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
			$strSrcOutputDir	= $this->generateSrcOutputDir();
			if(file_exists($this->strOutputPath))
			{
				$bProjectExists	= true;
				echo "Project already exists - if the given namespace already exists, this may overwrite files. Use with caution.\n";
			}
			elseif(!mkdir($strSrcOutputDir, 0777, true) && !is_dir($strSrcOutputDir))
			{
				throw new RuntimeException(sprintf('Directory "%s" was not created', $strSrcOutputDir));
			}

			$arrSoapClientOptions	= [
				'trace'			=> true,
				'exceptions'	=> true,
				'soap_version'	=> $this->strSOAPVersion,
				'encoding'		=> 'UTF-8'
			];

			if($this->strUsername !== '')
			{
				$arrSoapClientOptions['authentication']	= SOAP_AUTHENTICATION_BASIC;
				$arrSoapClientOptions['login']			= $this->strUsername;
				$arrSoapClientOptions['password']		= $this->strPassword;
			}

			if($this->bUseLocationInsideOptions)
			{
				$arrSoapClientOptions['useLocationInsideSoapClientOptions']		= true;
			}

			$oGenerator	= new Generator();
			$oGenerator->generate(
				new Config([
					'inputFile'			=> $this->strWSDL,
					'outputDir'			=> $strSrcOutputDir,
					'namespaceName'		=> $this->strNamespace,
					'bracketedArrays'	=> true,
					'soapClientOptions'	=> $arrSoapClientOptions
				])
			);


			if($bProjectExists)
			{
				$this->updateComposer($this->strOutputPath);
			}
			else
			{
				file_put_contents($this->strOutputPath.'/README.md', $this->strProjectName.' Web Services');
				file_put_contents($this->strOutputPath.'/composer.json', $this->getComposerJsonContent());
				file_put_contents($this->strOutputPath.'/.gitignore', $this->getGitIgnoreContent());
			}

			echo "\n".$this->strProjectName.' SOAP library created at '.$this->strOutputPath.".\n\n";
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
		    Mac/Linux:
		        ./soap-maker --vendor-name <VendorName> --project-name <ProjectName> --wsdl-path <WSDL> [--namespace <Namespace>] [--username <Username> --password <Password>] [--soap-version <SOAPVersion>] [--output-path <PathToOutput>] [--use-private-packagist <true|false>]
		    Windows:
		        php soap-maker --vendor-name <VendorName> --project-name <ProjectName> --wsdl-path <WSDL> [--namespace <Namespace>] [--username <Username> --password <Password>] [--soap-version <SOAPVersion>] [--output-path <PathToOutput>] [--use-private-packagist <true|false>]
		
		Where:
		    --vendor-name           = Vendor name on github, without spaces
		    --project-name          = Name for the project, without spaces
		    --wsdl-path             = file or URL for the WSDL SOAP description
		    --namespace             = Namespace for the project classes. If omitted, defaults to ProjectName
		    --soap-version          = SOAP Version: either 1 (for v1.1) or 2 (for v1.2). If omitted, defaults to 2
		    --username              = username for Basic Authentication - mandatory if --password is present
		    --password              = password for Basic Authentication - mandatory if --username is present
		    --output-path           = path for output. If omitted, project will be generated into the "output" folder
		    --use-private-packagist = if present and set to true, the composer file will point to the vendor name private packagist repository
		
		EOT;
	}

	/**
	 * @return string
	 */
	private function getComposerJsonContent(): string
	{
		$strAdjustedNamespace	= str_replace('\\', '\\\\', $this->strNamespace);

		$strRepositories	= '';
		if($this->bUsePrivatePackagist)
		{
			$strRepositories	= <<<EOT
			"repositories": [
				{
					"type": "composer",
					"url": "https://repo.packagist.com/{$this->strVendorName}/"
				},
				{
					"packagist.org": false
				}
			],
			EOT;
		}

		return <<< EOT
		{
			"name": "{$this->strVendorName}/{$this->strProjectName}",
			"description": "PHP library for {$this->strProjectName} Web Services",
			"license": "proprietary",
			{$strRepositories}
			"require": {
				"php": ">=7.4",
				"ext-soap": "*"
			},
			"require-dev": {
				"spysystem/soap-maker": "^2.0.0"
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
		if(!array_key_exists(self::Option_VendorName, $arrOptions) || $arrOptions[self::Option_VendorName] === '')
		{
			throw new RuntimeException('Missing Vendor Name!');
		}

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

		$this->strVendorName				= $arrOptions[self::Option_VendorName];
		$this->strProjectName				= $arrOptions[self::Option_ProjectName];
		$this->strWSDL						= $arrOptions[self::Option_WSDLPath];
		$this->strUsername					= $arrOptions[self::Option_Username] ?? '';
		$this->strPassword					= $arrOptions[self::Option_Password] ?? '';
		$this->strNamespace					= $arrOptions[self::Option_Namespace] ?? $this->strProjectName;
		$this->strSOAPVersion				= (int)($arrOptions[self::Option_SOAPVersion] ?? SOAP_1_2);
		$this->strOutputPath				= rtrim($arrOptions[self::Option_OutputPath] ?? __DIR__.self::OutputFolder.str_replace('\\', '/',$this->strProjectName), '/');
		$this->bUsePrivatePackagist			= filter_var($arrOptions[self::Option_UsePrivatePackagist] ?? '', FILTER_VALIDATE_BOOLEAN);
		$this->bUseLocationInsideOptions	= filter_var($arrOptions[self::Option_UseLocationInsideOptions] ?? '', FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * @return array
	 */
	public static function GetLongOptsArray(): array
	{
		return [
			self::Option_VendorName.self::Option_WithValue,
			self::Option_ProjectName.self::Option_WithValue,
			self::Option_WSDLPath.self::Option_WithValue,
			self::Option_Username.self::Option_WithValue,
			self::Option_Password.self::Option_WithValue,
			self::Option_Namespace.self::Option_WithValue,
			self::Option_SOAPVersion.self::Option_WithValue,
			self::Option_OutputPath.self::Option_WithValue,
			self::Option_UsePrivatePackagist.self::Option_WithValue,
			self::Option_UseLocationInsideOptions.self::Option_WithValue,
		];
	}
}
