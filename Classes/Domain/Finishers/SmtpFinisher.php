<?php
declare(strict_types=1);

namespace PeerNissen\SmtpFinisher\Domain\Finishers;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;


final class SmtpFinisher extends AbstractFinisher
{
    protected PHPMailer $phpMailerLib;

    protected $shortFinisherIdentifier = 'smtp';

    protected $defaultOptions = [
        'htmlBody' => 0,
        'typoscriptObjectPath' => 'lib.tx_form.contentElementRendering'
    ];

    /**
     * @var array
     */
    protected array $typoScriptSetup = [];

    /**
     * @var ConfigurationManagerInterface
     */
    protected ConfigurationManagerInterface $configurationManager;

    /**
     * @var ContentObjectRenderer
     */
    protected ContentObjectRenderer $contentObjectRenderer;


    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->typoScriptSetup = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
    }

    /**
     * @param ContentObjectRenderer $contentObjectRenderer
     */
    public function injectContentObjectRenderer(ContentObjectRenderer $contentObjectRenderer)
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
    }

    /**
     * @throws FinisherException
     * @throws Exception
     */
    public function executeInternal(): void
    {
        $this->confirmInput();
        $this->notify();
    }

    /**
     * @throws Exception
     */
    private function notify(): void
    {
        $mailer = $this->phpMailerLib = new PHPMailer();

        $mailer->isSMTP();
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = $this->phpMailerLib::ENCRYPTION_STARTTLS;

        $mailer->Port = (int)$this->options["smtpPort"];
        $mailer->Host = $this->options["smtpServer"];

        $mailer->Username = $this->options["username"];
        $mailer->Password = $this->options["password"];

        $mailer->setFrom($this->options["senderAddress"]);
        foreach ($this->parseOption('notifying') as $notifyingEmail => $notifyingName) {
            if($notifyingEmail != null && $notifyingName != null){
                $mailer->addAddress($this->parseText($notifyingEmail), $this->parseText($notifyingName));
            } else if ($notifyingEmail != null && $notifyingName == null) {
                $mailer->addAddress($this->parseText($notifyingEmail));
            }
        }

        $mailer->isHTML();
        $mailer->Subject = $this->parseText($this->options["subject"]);
        $mailer->Body = $this->getNotificationMessage();
        $mailer->AltBody = $this->getNotificationMessage();

        $mailer->Send();
    }

    /**
     * @throws Exception
     * @throws FinisherException
     */
    private function confirmInput(): void
    {
        $mailer = $this->phpMailerLib = new PHPMailer();

        $mailer->isSMTP();
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = $this->phpMailerLib::ENCRYPTION_STARTTLS;

        $mailer->Port = (int)$this->options["smtpPort"];
        $mailer->Host = $this->options["smtpServer"];

        $mailer->Username = $this->options["username"];
        $mailer->Password = $this->options["password"];

        $mailer->setFrom($this->options["senderAddress"]);
        foreach ($this->parseOption('recipients') as $recipientEmail => $recipientName) {
            $mailer->addAddress($this->parseText($recipientEmail), $this->parseText($recipientName));
        }

        $mailer->isHTML();
        $mailer->Subject = $this->parseText($this->options["subject"]);
        $mailer->Body = $this->parseHtmlMarkup( (int)$this->parseOption('htmlBody'));
        $mailer->AltBody = $this->parseText($this->options["altBody"]);

        $mailer->Send();
    }

    private function getNotificationMessage(): string
    {
        $result = "Jemand hat eine Anfrage gestellt:<br /><table>";
        $formValues = $this->finisherContext->getFormValues();
        $keys = array_keys($formValues);
        foreach ($keys as $key) {
            $result = $result . "<tr><td>" . $key . "</td><td>" . $formValues[$key] . "</td></tr>";
        }
        return $result . "</table>";
    }

    private function parseText(string $s): string
    {
        $result = $s;
        $formValues = $this->finisherContext->getFormValues();
        $keys = array_keys($formValues);
        foreach ($keys as $key) {
            $result = str_replace("{" . $key . "}", $formValues[$key], $result);
        }
        return $result;
    }

    private function parseHtmlMarkup(int $contentElementUid): string
    {
        $typoscriptObjectPath = $this->parseOption('typoscriptObjectPath');

        if (!empty($contentElementUid)) {
            $pathSegments = GeneralUtility::trimExplode('.', $typoscriptObjectPath);
            $lastSegment = array_pop($pathSegments);
            $setup = $this->typoScriptSetup;
            foreach ($pathSegments as $segment) {
                if (!array_key_exists($segment . '.', $setup)) {
                    throw new FinisherException(
                        sprintf('TypoScript object path "%s" does not exist', $typoscriptObjectPath),
                        1489238980
                    );
                }
                $setup = $setup[$segment . '.'];
            }
            $this->contentObjectRenderer->start([$contentElementUid], '');
            $this->contentObjectRenderer->setCurrentVal((string)$contentElementUid);
            $content = $this->contentObjectRenderer->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment . '.'], $lastSegment);
        } else {
            $content = $this->parseOption('altBody');
        }

        $formValues = $this->finisherContext->getFormValues();
        $keys = array_keys($formValues);

        foreach ($keys as $key) {
            $content = str_replace("{" . $key . "}", $formValues[$key], $content);
        }

        return $content;
    }
}
