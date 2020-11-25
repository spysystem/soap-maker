# soap-maker

This is a tool for creating SOAP clients to services, based on a provided wsdl file or url. It relies on SpySystem's own branch of wsdl2phpgenerator.

## Installation and usage

### First run: API creation
1. Create a folder for your project, and run git init on it. Let's say it's called ***fancy-api***:
   ```
   # mkdir fancy-api
   # cd fancy-api
   # git init
   ```

2. Download the install script and run it:
   ```
   # wget -c https://github.com/spysystem/soap-maker/raw/master/install.sh
   # chmod 755 install.sh
   # ./install.sh
   ```

3. This will place two files on the root of your project folder:
   ```
   # ls
   build.sh     composer.json
   ```
   You must adjust *build.sh* to match your needs.
   For instructions on the available options, see the **Configuration** section.
   
   Let's say your fancy-api project will use the namespace "FancyAPI": if your vendor name in github is "johndoe", this is how your build.sh might look:
   ```
   #!/bin/bash
   
   set -e
   
   rm -rf src
   
   composer install
   
   # comment out this line if you want to manage the composer.json file by yourself
   rm -rf composer.json
   
   ./vendor/spysystem/soap-maker/soap-maker \
    --vendor-name johndoe \
    --project-name fancy-api \
    --wsdl-path https://path.to.your/fancy-api/url.wsdl \
    --namespace 'FancyAPI' \
    --output-path `pwd`
   
   git add src/*

   ```

4. Run your build script:
   ```
      ./build.sh
   ```

5. Delete the *install.sh* script:
   ```
      rm install.sh
   ```

Remember to add an origin to your git configuration and commit your changes! :)

### Next runs: API changes
If the API definition has changed, you may need to run SoapMaker again. If the url for the WSDL file has changed, just update it inside the build.sh script.

You may also comment out the line deleting composer.json, in case you did manual changes to it.

Finally, just run your build script again:
```
   ./build.sh
```
, and then `git diff` your project to review the changes.

## Configuration
The build script is a wrapper to the **soap-maker** program.
**soap-maker** has the following syntax:

>./soap-maker --vendor-name <VendorName> --project-name <ProjectName> --wsdl-path <WSDL> [--namespace <Namespace>] [--username <Username> --password <Password>] [--soap-version <SOAPVersion>] [--output-path <PathToOutput>] [--use-private-packagist <true|false>]
#####Windows:
>php soap-maker --vendor-name <VendorName> --project-name <ProjectName> --wsdl-path <WSDL> [--namespace <Namespace>] [--username <Username> --password <Password>] [--soap-version <SOAPVersion>] [--output-path <PathToOutput>] [--use-private-packagist <true|false>]

, where:
- **--vendor-name** = *Vendor name on github, without spaces.
- **--project-name** = *Name for the project, without spaces.
- **--wsdl-path** = *File or URL for the WSDL SOAP description.
- **--namespace** = Namespace for the project classes. If omitted, defaults to ProjectName.
- **--soap-version** = SOAP Version: either 1 (for v1.1) or 2 (for v1.2). If omitted, defaults to 2.
- **--username** = User name for Basic Authentication. Mandatory if --password is present.
- **--password** = Password for Basic Authentication. Mandatory if --username is present.
- **--output-path** = Path for output. If omitted, project will be generated into the "output" folder.
- **--use-private-packagist** = if present and set to true, the composer file will include the vendor private packagist repository.

(* = mandatory fields)

