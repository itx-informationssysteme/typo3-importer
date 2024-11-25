<?php

namespace Itx\Importer\Service;

use GuzzleHttp\Psr7\ServerRequest;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ClassesConfigurationFactory;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

class ConfigurationLoaderService
{
    protected ClassesConfigurationFactory $classesConfigurationFactory;
    protected ConfigurationManager $configurationManager;

    public function __construct(
        ClassesConfigurationFactory $classesConfigurationFactory,
        ConfigurationManager $configurationManager
    )
    {
        $this->classesConfigurationFactory = $classesConfigurationFactory;
        $this->configurationManager = $configurationManager;
    }
    
    /*
     * Issue:   Since TYPO3 v13, you can't directly call an extbase repository from a command
     *          anymore, so you have to manually load the ConfigurationManager with this
     *          workaround
     * Link:    https://forge.typo3.org/issues/105616  
     */ 
    public function initCliEnvironment(): void
    {
        if (PHP_SAPI === 'cli') {
            $this->classesConfigurationFactory->createClassesConfiguration();

            Bootstrap::initializeBackendAuthentication();

            /** @var SiteFinder $siteFinder */
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = array_values($siteFinder->getAllSites())[0];
            $language = $site->getLanguageById(0);
            $GLOBALS['BE_USER']->user['lang'] = $language->getLocale()->getLanguageCode();

            $serverRequest = new ServerRequest('GET', 'ThisURLDoesntDoAnything.lol');
            $serverRequest = $serverRequest->withAttribute('extbase', []);
            $serverRequest = $serverRequest->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
            $serverRequest = $serverRequest->withAttribute('language', $language);
            $this->configurationManager->setRequest($serverRequest);
        }
    }
}