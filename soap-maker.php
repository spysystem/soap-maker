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
			$strOutputDir	= __DIR__.self::OutputFolder.'/'.$this->strProjectName;
			mkdir($strOutputDir);

			$oGenerator	= new \Wsdl2PhpGenerator\Generator();
			$oGenerator->generate(
				new \Wsdl2PhpGenerator\Config([
					'inputFile'			=> $this->strWSDL,
					'outputDir'			=> $strOutputDir,
					'namespaceName'		=> $this->strProjectName,
					'bracketedArrays'	=> true
				])
			);

			echo $this->strProjectName.' SOAP library created at '.$strOutputDir.".\n\n";
		}
		catch (Throwable $oThrowable)
		{
			echo 'Fatal: '.$oThrowable->getMessage()."\n";
			echo 'Trace: '.$oThrowable->getTraceAsString()."\n";
		}
	}
}