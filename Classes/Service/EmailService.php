<?php

namespace Itx\Importer\Service;

use Exception;
use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Repository\BackendUserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\Channel;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Channel('import')]
class EmailService
{
    public function __construct(
        protected BackendUserRepository $userRepository,
        protected LoggerInterface $logger
    ) {}

    public function sendEmailForFailedImport(Import $import, string $importLabel): void
    {
        $this->sendFailureNotificationEmail(
            $importLabel,
            "Failed Import: $importLabel [{$import->getUid()}]",
            'FailedJobEmail',
            ['import' => $import, 'importUrl' => $this->getLinkToImport($import)]
        );
    }

    public function sendSourceNotAvailableEmail(string $importLabel, string $reason): void
    {
        $this->sendFailureNotificationEmail(
            $importLabel,
            "Import could not be started: $importLabel",
            'SourceNotAvailableEmail',
            ['reason' => $reason]
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RouteNotFoundException
     * @throws SiteNotFoundException
     */
    protected function sendFailureNotificationEmail(string $importLabel, string $subject, string $template, array $additionalData = []): void
    {
        $logPrefix = '[EMAIL] ';

        //Get the users that should receive the email
        $users = $this->userRepository->findByimporterFailedNotification();
        if (count($users) === 0) {
            return;
        }

        $emailSender = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '';
        if (empty($emailSender)) {
            return;
        }

        $site = $this->getSite();
        $normalizedParams = new NormalizedParams([
            'HTTP_HOST' => $site->getBase()->getHost(),
            'HTTPS' => $site->getBase()->getScheme() === 'https' ? 'on' : 'off',
        ], $GLOBALS['TYPO3_CONF_VARS']['SYS'], '', '');

        $siteName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? 'TYPO3 Site (no sitename found))';
        $emailSenderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? "$siteName Importer";

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                                        ->withAttribute('normalizedParams', $normalizedParams)
                                        ->withAttribute('site', $site);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        //Create and send the mail
        $email = GeneralUtility::makeInstance(FluidEmail::class);
        $email->setRequest($request);
        $email->from(new Address($emailSender, $emailSenderName))
              ->subject("$siteName - $subject")
              ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
              ->setTemplate($template)
              ->assignMultiple([
                  'importName' => $importLabel,
                  'siteName' => $siteName,
                  ...$additionalData,
              ]);

        $receivers = [];

        foreach ($users as $user) {
            $address = $user->getEmail() ?? '';
            if ($address === '') {
                continue;
            }

            $receiver = new Address($address, $user->getRealName() ?? '');

            $receivers[] = $receiver;
        }

        $email->to(...$receivers);

        $mailer = GeneralUtility::makeInstance(Mailer::class);

        try {
            $mailer->send($email);
        } catch (Exception $e) {
            $this->logger->error(
                $logPrefix . ' Failed to send email for failed import',
                [
                    'exception' => $e,
                ]
            );
        }
    }

    protected function getLinkToImport(Import $import): string
    {
        $site = $this->getSite();
        $baseUrl = $site->getBase()->__toString();

        GeneralUtility::setIndpEnv('TYPO3_REQUEST_DIR', $site->getBase() . '/');

        $importUid = $import->getUid();

        // We need to hardcode the URL here, because url generation is not possible in the CLI context and backend routes
        return "$baseUrl/typo3/module/web/ImporterImportManager?tx_importer_web_importerimportmanager%5Bimport%5D=$importUid&tx_importer_web_importerimportmanager%5Baction%5D=show&tx_importer_web_importerimportmanager%5Bcontroller%5D=Import";
    }

    protected function getSite(): Site
    {
        $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
        $site = $sites[array_key_first($sites)] ?? null;
        if ($site === null) {
            throw new \RuntimeException('No sites found');
        }

        return $site;
    }
}
