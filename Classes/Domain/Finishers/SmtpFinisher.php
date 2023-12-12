<?php

declare(strict_types=1);

namespace PeerNissen\SmtpFinisher\Domain\Finishers;

use DateTime;
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

        $mailer->CharSet = "UTF-8";
        $mailer->Encoding = 'base64';

        $mailer->setFrom($this->options["senderAddress"]);
        foreach ($this->parseOption('notifying') as $notifyingEmail => $notifyingName) {
            if ($notifyingEmail != null && $notifyingName != null) {
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

        $mailer->CharSet = "UTF-8";
        $mailer->Encoding = 'base64';

        $mailer->setFrom($this->options["senderAddress"]);
        foreach ($this->parseOption('recipients') as $recipientEmail => $recipientName) {
            $mailer->addAddress($this->parseText($recipientEmail), $this->parseText($recipientName));
        }

        $mailer->isHTML();
        $mailer->Subject = $this->parseText($this->options["subject"]);
        $mailer->Body = $this->parseHtmlMarkup((int)$this->parseOption('htmlBody'));
        $mailer->AltBody = $this->parseText($this->options["altBody"]);

        $mailer->Send();
    }

    private function getNotificationMessage(): string
    {
        $formValues = $this->finisherContext->getFormValues();
        return $this->options["htmlNotificationBody"] . "Jemand hat eine Anfrage gestellt:<br />" . $this->arrayToHtml($formValues);
    }

    private function arrayToHtml($array): string
    {
        $result = "<table>";
        $keys = array_keys($array);
        foreach ($keys as $key) {
            $result = $result . "<tr><td>" . $key . "</td><td>" . $this->arrayValueToHtml($array[$key]) . "</td></tr>";
        }
        $result = $result . "</table>";
        return $result;
    }

    private function arrayValueToHtml($arrayValue): string
    {
        if (is_null($arrayValue)) {
            return "";
        }
        if (!is_array($arrayValue)) {
            return $this->strToValue($arrayValue);
        }
        return $this->arrayToHtml($arrayValue);
    }

    private function parseText(string $s): string
    {
        $result = $s;
        $formValues = $this->finisherContext->getFormValues();
        $keys = array_keys($formValues);
        foreach ($keys as $key) {
            $result = str_replace("{" . $key . "}", $this->strToValue($this->getValueFromXdimensional($formValues, $key)), $result);
        }
        return $result;
    }

    private function strToValue($val)
    {
        if ($val instanceof DateTime) {
            return $val->format('Y-m-d H:i:s');
        }
        return strval($val);
    }

    private function getValueFromXdimensional($array, $key)
    {
        foreach ($array as $k => $value) {
            if ($key === $k) {
                return $value;
            } else if (is_array($value)) {
                $result = $this->getValueFromXdimensional($value, $key);
                if (!is_null($result)) {
                    return $result;
                }
            }
        }
        return null;
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
            $content = str_replace("{" . $key . "}", $this->strToValue($this->getValueFromXdimensional($formValues, $key)), $content);
        }

        return $content;
    }

    private function getContentByUid($uid)
    {
        // Configurieren
        $conf['tables'] = 'tt_content';
        $conf['conf.']['tt_content'] = '< tt_content';
        $conf['source'] = $uid; //"tt_content_".$uid
        $conf['dontCheckPid'] = '1';
        // content holen
        $content = $this->cObj->cObjGetSingle('RECORDS', $conf);
        return $content;
    }
}
